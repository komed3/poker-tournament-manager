<?php
    
    function _ptm_init() {
        
        global $wpdb, $ptm_pages;
        
        foreach( [ 'profile', 'tournament', 'table', 'competitor', 'level', 'payout', 'live' ] as $page ) {
            
            $ptm_pages[ $page ] = $wpdb->get_row( '
                SELECT  ID
                FROM    ' . $wpdb->prefix . 'posts
                WHERE   post_content LIKE "%[ptm_' . $page . ']%"
                AND     post_status = "publish"
            ' )->ID;
            
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
