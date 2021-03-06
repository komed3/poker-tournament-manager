<?php
    
    function ptm_sc_live() {
        
        global $wpdb, $ptm_path, $_ptm_cards;
        
        if( !isset( $_GET['table'] ) )
            return ptm_sc_live_tables();
        
        $table = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'table
            WHERE   t_id = ' . $_GET['table']
        );
        
        $stats = $wpdb->get_row( '
            SELECT  COUNT( s_seat ) AS seats,
                    SUM( s_stack ) AS chips
            FROM    ' . $wpdb->prefix . 'seat
            WHERE   s_table = ' . $table->t_id
        );
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament,
                    ' . $wpdb->prefix . 'level
            WHERE   tm_id = ' . $table->t_tournament . '
            AND     l_tournament = tm_id
            AND     l_level = tm_level
        ' );
        
        $hand = $wpdb->get_row( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'hand
            WHERE       h_table = ' . $table->t_id . '
            ORDER BY    h_hand DESC
            LIMIT       0, 1
        ' );
        
        $cards = array_diff( $_ptm_cards, [ $hand->h_flop_1, $hand->h_flop_2, $hand->h_flop_3, $hand->h_turn, $hand->h_river ] );
        
        # ACTION ----------------------------------------------------- #
        
        if( count( $_POST ) > 0 ) {
            
            if( isset( $_POST['h_new'] ) ) {
                
                $wpdb->insert(
                    $wpdb->prefix . 'hand',
                    [
                        'h_table' => $table->t_id,
                        'h_hand' => $_POST['h_new'],
                        'h_level' => $tm->l_level,
                        'h_dealer' => $_POST['h_dealer'],
                        'h_sb' => $_POST['h_sb'],
                        'h_bb' => $_POST['h_bb']
                    ]
                );
                
                _ptm_bet( $tm, $table, $_POST['h_new'], $_POST['h_sb'], $tm->l_sb );
                _ptm_bet( $tm, $table, $_POST['h_new'], $_POST['h_bb'], $tm->l_bb );
                
                if( $tm->tm_ante == 'button' )
                    _ptm_bet( $tm, $table, $_POST['h_new'], $_POST['h_bb'], $tm->l_ante );
                
            }
            
            else if( isset( $_POST['hc_set'] ) ) {
                
                _ptm_get_hc(
                    $table->t_id, $hand->h_hand, $_POST['hc_profile'],
                    $_POST['hc_1'], $_POST['hc_2']
                );
                
            }
            
            else if( isset( $_POST['flop_set'] ) ) {
                
                _ptm_flop(
                    $table->t_id, $hand->h_hand,
                    $_POST['flop_1'], $_POST['flop_2'], $_POST['flop_3']
                );
                
            }
            
            else if( isset( $_POST['turn_set'] ) ) {
                
                _ptm_turn(
                    $table->t_id, $hand->h_hand,
                    $_POST['turn']
                );
                
            }
            
            else if( isset( $_POST['river_set'] ) ) {
                
                _ptm_river(
                    $table->t_id, $hand->h_hand,
                    $_POST['river']
                );
                
            }
            
            else if( isset( $_POST['h_term'] ) ) {
                
                _ptm_termination(
                    $table->t_id, $hand->h_hand,
                    $_POST['h_termination']
                );
                
                for( $i = 1; $i <= 8; $i++ ) {
                    
                    if( !isset( $_POST['pot_' . $i ] ) )
                        continue;
                    
                    _ptm_bet( $tm, $table, $hand->h_hand, $i, $_POST['pot_' . $i ] * (-1), $_POST['pot_' . $i ] > 0 ? 'win' : 'loss' );
                    
                }
                
            }
            
            else if( isset( $_POST['l_change'] ) ) {
                
                _ptm_level( $tm, $_POST['tm_level'] );
                
            }
            
            else if( isset( $_POST['bet'] ) ) {
                
                _ptm_bet( $tm, $table, $hand->h_hand, $_POST['seat'], $_POST['value'] );
                
                _ptm_action( $table->t_id, $hand->h_hand, $_POST['profile'], 'bet', $_POST['value'] );
                
            }
            
            else if( isset( $_POST['call'] ) ) {
                
                _ptm_bet( $tm, $table, $hand->h_hand, $_POST['seat'], $_POST['cvalue'] );
                
                _ptm_action( $table->t_id, $hand->h_hand, $_POST['profile'], 'call', $_POST['cvalue'] );
                
            }
            
            else if( isset( $_POST['check'] ) ) {
                
                _ptm_action( $table->t_id, $hand->h_hand, $_POST['profile'], 'check' );
                
            }
            
            else if( isset( $_POST['fold'] ) ) {
                
                _ptm_fold( $table->t_id, $hand->h_hand, $_POST['profile'] );
                
            }
            
            return '<script> location.href = "' . $_SERVER['REQUEST_URI'] . '"; </script>';
            
        }
        
        # ACTION END ------------------------------------------------- #
        
        $i = 0;
        $seats = [
            /*'<seat></seat>', '<seat></seat>', '<seat></seat>', '<seat></seat>',
            '<seat></seat>', '<seat></seat>', '<seat></seat>', '<seat></seat>'*/
        ];
        
        $player_select = [];
        $pot_select = [];
        
        $next_dealer = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'seat,
                        ' . $wpdb->prefix . 'profile
            WHERE       s_table = ' . $table->t_id . '
            AND         p_id = s_profile
            AND         s_stack > 0
            ORDER BY    s_stack DESC
        ' ) as $seat ) {
            
            $player_select[ $seat->s_seat ] = '<option value="' . $seat->s_seat . '">' . $seat->p_name . '</option>';
            $pot_select[ $seat->s_seat ] = '<label for="h_pot_' . $seat->s_seat . '">' . $seat->p_name . '</label>
            <input type="number" id="h_pot_' . $seat->s_seat . '" name="pot_' . $seat->s_seat . '" min="0" max="' . $hand->h_pot . '" value="0" />';
            
            $action = $wpdb->get_row( '
                SELECT      *
                FROM        ' . $wpdb->prefix . 'action
                WHERE       a_table = ' . $table->t_id . '
                AND         a_hand = ' . $hand->h_hand . '
                AND         a_profile = ' . $seat->p_id . '
                AND         a_active = true
                ORDER BY    a_id DESC
                LIMIT       0, 1
            ' );
            
            $bet = $wpdb->get_row( '
                SELECT  SUM( a_bet ) AS bet
                FROM    ' . $wpdb->prefix . 'action
                WHERE   a_table = ' . $table->t_id . '
                AND     a_hand = ' . $hand->h_hand . '
                AND     a_profile = ' . $seat->p_id . '
                AND     a_active = true
            ' )->bet;
            
            $holecards = $wpdb->get_row( '
                SELECT  *
                FROM    ' . $wpdb->prefix . 'holecards
                WHERE   hc_table = ' . $table->t_id . '
                AND     hc_hand = ' . $hand->h_hand . '
                AND     hc_profile = ' . $seat->p_id
            );
            
            $cards = array_diff( $cards, [ $holecards->hc_1, $holecards->hc_2 ] );
            
            $position = ( $seat->s_seat == $hand->h_dealer ? 'D' :
                ( $seat->s_seat == $hand->h_sb ? 'SB' :
                    ( $seat->s_seat == $hand->h_bb ? 'BB' : null ) ) );
            
            $seats[ ++$i ] = '<seat data-s="' . $seat->s_seat . '" data-c="' . $seat->s_profile . '" class="' . ( $holecards->hc_fold ? 'fold' : '' ) . '">
                <div class="name" data-position="' . $position . '">' . _ptm_link( 'competitor', $seat->p_name, [ 'tm' => $tm->tm_id, 'id' => $seat->p_id ] ) . '</div>
                <div class="stack">
                    <span>' . _ptm_stack( $seat->s_stack ) . '</span>
                    <span>' . number_format_i18n( $seat->s_stack / $stats->chips * 100, 1 ) . '&nbsp;%</span>
                    <span>' . _ptm_ordinal( $i ) . __( ' in chips', 'ptm' ) . '</span>
                </div>
                <div class="bet">
                    <span class="bet_action">' . __( $action->a_action, 'ptm' ) . '</span>
                    <span class="bet_bet">' . _ptm_stack( $action->a_bet ) . '</span>
                    <span class="bet_call">' . ( $hand->h_rbet - $bet > 0 && $action->a_action != 'fold' ?
                        _ptm_stack( $hand->h_rbet - $bet ) . __( ' to call', 'ptm' ) : '' ) . '</span>
                </div>
                <div class="holecards">
                    <cc>' . _ptm_card( $holecards->hc_1 ) . '</cc>
                    <cc>' . _ptm_card( $holecards->hc_2 ) . '</cc>
                </div>
                <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" class="actions">
                    <input type="hidden" name="seat" value="' . $seat->s_seat . '" />
                    <input type="hidden" name="profile" value="' . $seat->s_profile . '" />
                    <input type="hidden" name="cvalue" value="' . ( $hand->h_rbet - $bet ) . '" />
                    <input type="number" name="value" min="' . ( $hand->h_rbet - $bet ) . '" max="' . $seat->s_stack . '" value="" />
                    <button type="submit" name="bet" value="1">' . __( 'Bet', 'ptm' ) . '</button>
                    ' . ( $hand->h_rbet - $bet > 0 ? '' : '<button type="submit" name="check" value="1">' . __( 'Check', 'ptm' ) . '</button>' ) . '
                    <button type="submit" name="call" value="1">' . __( 'Call', 'ptm' ) . '</button>
                    <button type="submit" name="allin" value="1">' . __( 'All-In', 'ptm' ) . '</button>
                    <button type="submit" name="fold" value="1">' . __( 'Fold', 'ptm' ) . '</button>
                </form>
            </seat>';
            
        }
        
        ksort( $player_select );
        ksort( $pot_select );
        
        $level_select = [];
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'level
            WHERE       l_tournament = ' . $tm->tm_id . '
            ORDER BY    l_level ASC
        ' ) as $l ) {
            
            $level_select[] = '<option value="' . $l->l_level . '" ' . ( $l->l_level == $tm->l_level ? 'selected' : '' ) . '>
                ' . $l->l_level . ' (' . $l->l_sb . '/' . $l->l_bb . '/' . $l->l_ante . ')
            </option>';
            
        }
        
        $cards_select = [];
        
        foreach( $cards as $card ) {
            
            $cards_select[] = '<option value="' . $card . '">' . str_replace( str_split( 'CDHST' ), [
                'Clubs ', 'Diamonds ', 'Hearts ', 'Spades ', '10'
            ], $card ) . '</option>';
            
        }
        
        $term_select = [];
        
        foreach( [ 'preflop', 'flop', 'turn', 'river', 'showdown' ] as $term ) {
            
            $term_select[] = '<option value="' . $term . '">' . __( $term, 'ptm' ) . '</option>';
            
        }
        
        wp_enqueue_script( 'ptm.js.live', $ptm_path . 'js/live.js', [ 'jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'ptm.js.global' ] );
        
        return _ptm( '
            <div class="ptm_live_header">
                <h1>' . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . ' – ' .
                        _ptm_link( 'table', $table->t_name, [ 'tm' => $tm->tm_id, 'id' => $table->t_id ] ) . '</h1>
            </div>
            <div class="ptm_live_table">
                ' . implode( '', $seats ) . '
                <board>
                    <div>
                        <cc>' . _ptm_card( $hand->h_flop_1 ) . '</cc>
                        <cc>' . _ptm_card( $hand->h_flop_2 ) . '</cc>
                        <cc>' . _ptm_card( $hand->h_flop_3 ) . '</cc>
                        <cc>' . _ptm_card( $hand->h_turn ) . '</cc>
                        <cc>' . _ptm_card( $hand->h_river ) . '</cc>
                    </div>
                </board>
                <pot>
                    <div class="blinds">
                        <level><span>' . number_format_i18n( $tm->l_level ) . '</span></level>
                        <sb><span>' . number_format_i18n( $tm->l_sb ) . '</span></sb>
                        <bb><span>' . number_format_i18n( $tm->l_bb ) . '</span></bb>
                        <ante><span>' . number_format_i18n( $tm->l_ante ) . '</span></ante>
                        <hand><span>' . number_format_i18n( $hand->h_hand ) . '</span></hand>
                    </div>
                    <div class="pot">
                        <div class="pot_rpot"><span>' . number_format_i18n( $hand->h_rpot ) . '</span></div>
                        <div class="pot_pot"><span>' . number_format_i18n( $hand->h_pot ) . '</span></div>
                    </div>
                </pot>
            </div>
            <div class="ptm_live_tabs">
                <div id="ptm_live_tabs_container">
                    <ul>
                        <li><a href="#ptm_live_tabs_hand">' . __( 'new hand', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_holecards">' . __( 'hole cards', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_flop">' . __( 'flop', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_turn">' . __( 'turn', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_river">' . __( 'river', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_termination">' . __( 'termination', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_level">' . __( 'level', 'ptm' ) . '</a></li>
                    </ul>
                    <div id="ptm_live_tabs_hand">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_h_dealer">' . __( 'dealer', 'ptm' ) . '</label>
                            <select name="h_dealer" id="tab_h_dealer">' . implode( '', $player_select ) . '</select>
                            <label for="tab_h_sb">' . __( 'SB', 'ptm' ) . '</label>
                            <select name="h_sb" id="tab_h_sb">' . implode( '', $player_select ) . '</select>
                            <label for="tab_h_bb">' . __( 'BB', 'ptm' ) . '</label>
                            <select name="h_bb" id="tab_h_bb">' . implode( '', $player_select ) . '</select>
                            <button type="submit" name="h_new" value="' . ( $hand->h_hand + 1 ) . '">' . __( 'new hand', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_holecards">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_hc_profile">' . __( 'player', 'ptm' ) . '</label>
                            <select name="hc_profile" id="tab_hc_profile">' . implode( '', $player_select ) . '</select>
                            <label for="tab_hc_1">' . __( 'card 1', 'ptm' ) . '</label>
                            <select name="hc_1" id="tab_hc_1">' . implode( '', $cards_select ) . '</select>
                            <label for="tab_hc_2">' . __( 'card 2', 'ptm' ) . '</label>
                            <select name="hc_2" id="tab_hc_2">' . implode( '', $cards_select ) . '</select>
                            <button type="submit" name="hc_set" value="1">' . __( 'set hole cards', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_flop">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_flop_1">' . __( 'card 1', 'ptm' ) . '</label>
                            <select name="flop_1" id="tab_flop_1">' . implode( '', $cards_select ) . '</select>
                            <label for="tab_flop_2">' . __( 'card 2', 'ptm' ) . '</label>
                            <select name="flop_2" id="tab_flop_2">' . implode( '', $cards_select ) . '</select>
                            <label for="tab_flop_3">' . __( 'card 3', 'ptm' ) . '</label>
                            <select name="flop_3" id="tab_flop_3">' . implode( '', $cards_select ) . '</select>
                            <button type="submit" name="flop_set" value="1">' . __( 'set flop cards', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_turn">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_turn">' . __( 'turn', 'ptm' ) . '</label>
                            <select name="turn" id="tab_turn">' . implode( '', $cards_select ) . '</select>
                            <button type="submit" name="turn_set" value="1">' . __( 'set turn card', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_river">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_river">' . __( 'river', 'ptm' ) . '</label>
                            <select name="river" id="tab_river">' . implode( '', $cards_select ) . '</select>
                            <button type="submit" name="river_set" value="1">' . __( 'set river card', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_termination">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_h_termination">' . __( 'termination', 'ptm' ) . '</label>
                            <select name="h_termination" id="tab_h_termination">' . implode( '', $term_select ) . '</select>
                            ' . implode( '', $pot_select ) . '
                            <button type="submit" name="h_term" value="1">' . __( 'terminate hand', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_level">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_tm_level">' . __( 'level', 'ptm' ) . '</label>
                            <select name="tm_level" id="tab_tm_level">' . implode( '', $level_select ) . '</select>
                            <button type="submit" name="l_change" value="1">' . __( 'change blind level', 'ptm' ) . '</button>
                        </form>
                    </div>
                </div>
            </div>
        ', 'ptm_live_grid' );
        
    }
    
    function ptm_sc_live_tables() {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( t_id ) AS cnt
            FROM    ' . $wpdb->prefix . 'table
            WHERE   t_status = "open"
        ' )->cnt;
        
        $pager = _ptm_pager( $offset, $limit, $max );
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'table,
                    ' . $wpdb->prefix . 'tournament
            WHERE   t_status = "open"
            AND     tm_id = t_tournament
        ' ) as $table ) {
            
            $list[] = '<tr>
                <td>' . _ptm_link( 'live', $table->t_name, [ 'table' => $table->t_id ] ) . '</td>
                <td>' . _ptm_link( 'tournament', $table->tm_name, [ 'id' => $table->tm_id ] ) . '</td>
                <td>' . _ptm_date( $table->t_touched ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_live_list_header ptm_header">
                <h1>' . ucfirst( __( 'live tables', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_live_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'table', 'ptm' ) . '</th>
                            <th>' . __( 'tournament', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_live_list_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_live', 'ptm_sc_live' );
    
?>
