<?php
    
    $_ptm_cards = [];
    
    function _ptm_init() {
        
        global $wpdb, $ptm_pages, $_ptm_cards;
        
        foreach( [ 'profile', 'tournament', 'table', 'competitor', 'level', 'payout', 'live' ] as $page ) {
            
            $ptm_pages[ $page ] = $wpdb->get_row( '
                SELECT  ID
                FROM    ' . $wpdb->prefix . 'posts
                WHERE   post_content LIKE "%[ptm_' . $page . ']%"
                AND     post_status = "publish"
            ' )->ID;
            
        }
        
        foreach( str_split( 'CDHS' ) as $color ) {
            
            foreach( str_split( '23456789TJQKA' ) as $value ) {
                
                $_ptm_cards[] = $color . $value;
                
            }
            
        }
        
    }
    
    function _ptm(
        string $content = '',
        string $classes = ''
    ) {
        
        global $ptm_path;
        
        wp_enqueue_style( 'ptm.css.global', $ptm_path . 'css/style.css' );
        
        wp_enqueue_script( 'ptm.js.global', $ptm_path . 'js/functions.js', [ 'jquery' ] );
        wp_enqueue_script( 'highstock', 'https://code.highcharts.com/stock/highstock.js', [ 'jquery' ] );
        
        return '<div class="ptm_container ' . $classes . '">' . $content . '</div>';
        
    }
    
    function _ptm_opt(
        string $key,
        $default = ''
    ) {
        
        return get_option( 'ptm_' . $key, $default );
        
    }
    
    function _ptm_pow2sfx(
        int $pow10
    ) {
        
        return [
            __( '', 'ptm' ), __( 'T', 'ptm' ), __( 'M', 'ptm' ),
            __( 'B', 'ptm' ), __( 'T', 'ptm' ), __( 'Q', 'ptm' )
        ][ $pow10 ];
        
    }
    
    function _ptm_rank( $rank ) {
        
        return !is_numeric( $rank ) ? '–' : '<rank title="' . __( 'rank no ', 'ptm' ) . number_format_i18n( $rank ) . '">' . number_format_i18n( $rank ) . '</rank>';
        
    }
    
    function _ptm_msg(
        string $code = ''
    ) {
        
        switch( $code ) {
            
            default:
                return '';
            
            case 'e':
                return '<span class="eliminated">eliminated</span>';
            
        }
        
    }
    
    function _ptm_change(
        $number = 0
    ) {
        
        return ( $number > 0 ? 'good' : ( $number < 0 ? 'bad' : '' ) );
        
    }
    
    function _ptm_cash(
        $cash = 0,
        int $digits = 0,
        bool $change = false
    ) {
        
        if( !is_numeric( $cash ) )
            return '';
        
        $pow10 = floor( (int) ( log10( abs( $cash ) ) ) / 3 );
        
        return '<cash class="' . ( $change ? _ptm_change( $cash ) : '' ) . '" title="' . _ptm_opt( 'currency', 'USD' ) . ' ' . number_format_i18n( $cash, $digits ) . '">' .
            _ptm_opt( 'currency', 'USD' ) . '&nbsp;<b>' .
            number_format_i18n( abs( $cash ) / pow( 10, $pow10 * 3 ), $digits ) . '&nbsp;' .
            _ptm_pow2sfx( $pow10 ) .
        '</b></cash>';
        
    }
    
    function _ptm_stack(
        $stack = 0,
        bool $change = false
    ) {
        
        if( !is_numeric( $stack ) )
            return '';
        
        $pow10 = floor( (int) ( log10( abs( $stack ) ) ) / 3 );
        
        return '<stack class="' . ( $change ? _ptm_change( $stack ) : '' ) . '" title="' . number_format_i18n( $stack, 0 ) . '">$&nbsp;' .
            number_format_i18n( abs( $stack ) / pow( 10, $pow10 * 3 ), 1 ) . '&nbsp;' .
            _ptm_pow2sfx( $pow10 ) .
        '</stack>';
        
    }
    
    function _ptm_ordinal(
        $number = 0
    ) {
        
        return !is_numeric( $number ) || $number == 0 ? '0' : '<ordinal data-sfx="' . ( $number % 100 > 9 && $number % 100 < 20 ? 'th' : [
            0 => 'th', 1 => 'st', 2 => 'nd', 3 => 'rd', 4 => 'th',
            5 => 'th', 6 => 'th', 7 => 'th', 8 => 'th', 9 => 'th'
        ][ $number % 10 ] ) . '">' . $number . '</ordinal>';
        
    }
    
    function _ptm_card(
        $card = null,
        bool $empty = false
    ) {
        
        return strlen( $card ) != 2 ? ( $empty ? '<card></card>' : '' ) : '<card class="' . [
            'c' => 'clubs', 'd' => 'diamonds', 'h' => 'hearts', 's' => 'spades'
        ][ strtolower( substr( $card, 0, 1 ) ) ] . '" title="' . __( [
            '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6',
            '7' => '7', '8' => '8', '9' => '9', 't' => '10', 'j' => 'Jack',
            'q' => 'Queen', 'k' => 'King', 'a' => 'Ace'
        ][ strtolower( substr( $card, 1, 1 ) ) ], 'ptm' ) . __( ' of ', 'ptm' ) . __( [
            'c' => 'Clubs', 'd' => 'Diamonds', 'h' => 'Hearts', 's' => 'Spades'
        ][ strtolower( substr( $card, 0, 1 ) ) ], 'ptm' ) . '">' . [
            '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6',
            '7' => '7', '8' => '8', '9' => '9', 't' => '10', 'j' => 'J',
            'q' => 'Q', 'k' => 'K', 'a' => 'A'
        ][ strtolower( substr( $card, 1, 1 ) ) ] . '</card>';
        
    }
    
    function _ptm_date(
        string $timestring,
        $format = null
    ) {
        
        return date_i18n(
            !is_string( $format ) ? get_option( 'date_format' ) : $format,
            is_int( $timestring ) ? $timestring : strtotime( $timestring )
        );
        
    }
    
    function _ptm_link(
        string $page,
        string $text,
        array $args = [],
        string $classes = ''
    ) {
        
        global $ptm_pages;
        
        $query = http_build_query( $args );
        
        return !array_key_exists( $page, $ptm_pages ) ? $text :
            '<a href="' . get_page_link( $ptm_pages[ $page ] ) . ( strlen( $query ) > 0 ? '?' . $query : '' ) . '" class="' . $classes . '">' . $text . '</a>';
        
    }
    
    function _ptm_pager(
        int $offset,
        int $limit,
        int $max,
        string $append = ''
    ) {
        
        return '<div class="ptm_pager" data-limit="' . $limit . '">
            <a href="?offset=0&limit=' . $limit . $append . '" class="ptm_button ' .
                ( $offset == 0 ? 'disabled' : '' ) . '">
                ' . __( 'first', 'ptm' ) . '</a>
            <a href="?offset=' . ( $offset - $limit ) . '&limit=' . $limit . $append . '" class="ptm_button ' .
                ( $offset == 0 ? 'disabled' : '' ) . '">
                ' . __( 'prev', 'ptm' ) . '</a>
            <span>
                ' . number_format_i18n( $offset + 1 ) . __( '–', 'ptm' ) .
                    number_format_i18n( min( $max, $offset + $limit ) ) . __( ' of ', 'ptm' ) .
                    number_format_i18n( $max ) . '
            </span>
            <a href="?offset=' . ( $offset + $limit ) . '&limit=' . $limit . $append . '" class="ptm_button ' .
                ( $offset + $limit >= $max ? 'disabled' : '' ) . '">
                ' . __( 'next', 'ptm' ) . '</a>
            <a href="?offset=' . ( floor( ( $max - 1 ) / $limit ) * $limit ) . '&limit=' . $limit . $append . '" class="ptm_button ' .
                ( $offset + $limit >= $max ? 'disabled' : '' ) . '">
                ' . __( 'last', 'ptm' ) . '</a>
        </div>';
        
    }
    
    function _ptm_action(
        $table = null,
        $hand = null,
        $profile = null,
        $action = null,
        $bet = null
    ) {
        
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'action',
            [
                'a_table' => $table,
                'a_hand' => $hand,
                'a_profile' => $profile,
                'a_action' => $action,
                'a_bet' => $bet
            ]
        );
        
    }
    
    function _ptm_reset_action(
        $table = null,
        $hand = null
    ) {
        
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'action',
            [ 'a_active' => false ],
            [
                'a_table' => $table,
                'a_hand' => $hand
            ]
        );
        
    }
    
    function _ptm_bet(
        $tm,
        $table = null,
        $hand = null,
        $seat = null,
        $bet = 0,
        $flag = null
    ) {
        
        global $wpdb;
        
        $profile = $wpdb->get_row( '
            SELECT  s_profile
            FROM    ' . $wpdb->prefix . 'seat
            WHERE   s_table = ' . $table->t_id . '
            AND     s_seat = ' . $seat
        )->s_profile;
        
        $stack = $wpdb->get_row( '
            SELECT      cp_stack
            FROM        ' . $wpdb->prefix . 'competitor
            WHERE       cp_tournament = ' . $tm->tm_id . '
            AND         cp_profile = ' . $profile
        )->cp_stack;
        
        $wpdb->query( '
            INSERT INTO ' . $wpdb->prefix . 'stack (
                st_profile, st_tournament, st_table, st_hand, st_flag, st_value, st_change
            ) VALUES (
                ' . $profile . ', ' . $tm->tm_id . ', ' . $table->t_id . ', ' . $hand . ',
                ' . ( $flag == null ? 'NULL' : '"' . $flag . '"' ) . ',
                ' . ( $stack + $bet * (-1) ) . ', ' . ( $bet * (-1) ) . '
            ) ON DUPLICATE KEY UPDATE
                st_flag	= ' . ( $flag == null ? 'NULL' : '"' . $flag . '"' ) . ',
                st_value = ' . ( $stack + $bet * (-1) ) . ',
                st_change = st_change + ' . ( $bet * (-1) ) . '
        ' );
        
        $wpdb->update(
            $wpdb->prefix . 'seat',
            [
                's_stack' => $stack + $bet * (-1)
            ],
            [
                's_table' => $table->t_id,
                's_seat' => $seat
            ]
        );
        
        $wpdb->update(
            $wpdb->prefix . 'competitor',
            [
                'cp_stack' => $stack + $bet * (-1)
            ],
            [
                'cp_tournament' => $tm->tm_id,
                'cp_profile' => $profile
            ]
        );
        
        $wpdb->query( '
            UPDATE  ' . $wpdb->prefix . 'hand
            SET     h_rpot = h_rpot + ' . $bet . ',
                    h_pot = h_pot + ' . $bet . '
            WHERE   h_table = ' . $table->t_id . '
            AND     h_hand = ' . $hand
        );
        
        $wpdb->query( '
            UPDATE  ' . $wpdb->prefix . 'hand
            SET     h_rbet = ' . $bet . ',
            WHERE   h_table = ' . $table->t_id . '
            AND     h_hand = ' . $hand . '
            AND     h_rbet < ' . $bet
        );
        
        return true;
        
    }
    
    function _ptm_get_hc(
        $table = null,
        $hand = null,
        $profile = null,
        $hc1 = null,
        $hc2 = null
    ) {
        
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'holecards',
            [
                'hc_table' => $table,
                'hc_hand' => $hand,
                'hc_profile' => $profile,
                'hc_1' => $hc1,
                'hc_2' => $hc2
            ]
        );
        
    }
    
    function _ptm_fold(
        $table = null,
        $hand = null,
        $profile = null
    ) {
        
        global $wpdb;
        
        _ptm_action( $table, $hand, $profile, 'fold' );
        
        return $wpdb->update(
            $wpdb->prefix . 'holecards',
            [ 'hc_fold' => true ],
            [
                'hc_table' => $table,
                'hc_hand' => $hand,
                'hc_profile' => $profile
            ]
        );
        
    }
    
    function _ptm_flop(
        $table = null,
        $hand = null,
        $card1 = null,
        $card2 = null,
        $card3 = null
    ) {
        
        global $wpdb;
        
        _ptm_reset_action( $table, $hand );
        
        return $wpdb->update(
            $wpdb->prefix . 'hand',
            [
                'h_flop_1' => $card1,
                'h_flop_2' => $card2,
                'h_flop_3' => $card3,
                'h_rpot' => 0,
                'h_rbet' => 0
            ],
            [
                'h_table' => $table,
                'h_hand' => $hand,
            ]
        );
        
    }
    
    function _ptm_turn(
        $table = null,
        $hand = null,
        $card = null
    ) {
        
        global $wpdb;
        
        _ptm_reset_action( $table, $hand );
        
        return $wpdb->update(
            $wpdb->prefix . 'hand',
            [
                'h_turn' => $card,
                'h_rpot' => 0,
                'h_rbet' => 0
            ],
            [
                'h_table' => $table,
                'h_hand' => $hand,
            ]
        );
        
    }
    
    function _ptm_river(
        $table = null,
        $hand = null,
        $card = null
    ) {
        
        global $wpdb;
        
        _ptm_reset_action( $table, $hand );
        
        return $wpdb->update(
            $wpdb->prefix . 'hand',
            [
                'h_river' => $card,
                'h_rpot' => 0,
                'h_rbet' => 0
            ],
            [
                'h_table' => $table,
                'h_hand' => $hand,
            ]
        );
        
    }
    
    function _ptm_termination(
        $table = null,
        $hand = null,
        $termination = null
    ) {
        
        global $wpdb;
        
        _ptm_reset_action( $table, $hand );
        
        return $wpdb->update(
            $wpdb->prefix . 'hand',
            [
                'h_termination' => $termination,
                'h_rpot' => 0
            ],
            [
                'h_table' => $table,
                'h_hand' => $hand,
            ]
        );
        
    }
    
    function _ptm_level(
        $tm,
        $level = 1
    ) {
        
        global $wpdb;
        
        return $wpdb->update(
            $wpdb->prefix . 'tournament',
            [ 'tm_level' => $level ],
            [ 'tm_id' => $tm->tm_id ]
        );
        
    }
    
    function _ptm_position(
        $seats = null,
        $seat = null,
        $dealer = null
    ) {
        
        if( $seats == null || $seat == null || $dealer == null )
            return '';
        
        for( $i = -1; $i <= 7; $i++ ) {
            
            if( $seat == ( $dealer + $i > $seats ? $dealer + $i - $seats : $dealer + $i ) )
                return [
                    -1 => 'CO', 0 => 'D',   1 => 'SB',
                     2 => 'BB', 3 => 'UTG', 4 => '+1',
                     5 => '+2', 6=>  '+3',  7 => '+4'
                ][ $i ];
            
        }
        
    }
    
?>
