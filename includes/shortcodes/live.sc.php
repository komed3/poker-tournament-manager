<?php
    
    function ptm_sc_live() {
        
        global $wpdb, $ptm_path;
        
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
        
        if( isset( $_POST['h_new'] ) ) {
            
            $wpdb->insert(
                $wpdb->prefix . 'hand',
                [
                    'h_table' => $table->t_id,
                    'h_hand' => $_POST['h_new'],
                    'h_level' => $tm->l_level,
                    'h_dealer' => $_POST['h_dealer']
                ]
            );
            
            return '<script> location.href = "' . $_SERVER['REQUEST_URI'] . '"; </script>';
            
        }
        
        $hand = $wpdb->get_row( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'hand
            WHERE       h_table = ' . $table->t_id . '
            ORDER BY    h_hand DESC
            LIMIT       0, 1
        ' );
        
        $i = 0;
        $seats = [
            '<seat></seat>', '<seat></seat>', '<seat></seat>', '<seat></seat>',
            '<seat></seat>', '<seat></seat>', '<seat></seat>', '<seat></seat>'
        ];
        
        $players = [];
        
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
            
            $players[ $seat->s_seat ] = $seat->p_name;
            
            $holecards = $wpdb->get_row( '
                SELECT  *
                FROM    ' . $wpdb->prefix . 'holecards
                WHERE   hc_table = ' . $table->t_id . '
                AND     hc_hand = ' . $hand->h_hand . '
                AND     hc_profile = ' . $seat->p_id
            );
            
            $position = _ptm_position( $stats->seats, $seat->s_seat, $hand->h_dealer );
            
            if( $position == 'SB' )
                $next_dealer = $seat->s_seat;
            
            $seats[ ++$i ] = '<seat data-s="' . $seat->s_seat . '" data-c="' . $seat->s_profile . '">
                <div class="name" data-position="' . $position . '">' . _ptm_link( 'competitor', $seat->p_name, [ 'tm' => $tm->tm_id, 'id' => $seat->p_id ] ) . '</div>
                <div class="stack">
                    <span>' . _ptm_stack( $seat->s_stack ) . '</span>
                    <span>' . number_format_i18n( $seat->s_stack / $stats->chips * 100, 1 ) . '&nbsp;%</span>
                    <span>' . _ptm_ordinal( $i ) . __( ' in chips', 'ptm' ) . '</span>
                </div>
                <div class="bet"></div>
                <div class="holecards">
                    <cc>' . _ptm_card( $holecards->hc_1 ) . '</cc>
                    <cc>' . _ptm_card( $holecards->hc_2 ) . '</cc>
                </div>
                <div class="actions">
                    <input type="number" name="bet" min="0" max="' . $seat->s_stack . '" />
                    <button data-action="bet">' . __( 'Bet', 'ptm' ) . '</button>
                    <button data-action="call">' . __( 'Call', 'ptm' ) . '</button>
                    <button data-action="check">' . __( 'Check', 'ptm' ) . '</button>
                    <button data-action="fold">' . __( 'Fold', 'ptm' ) . '</button>
                </div>
            </seat>';
            
        }
        
        $dealer_select = '';
        foreach( $players as $seat => $name )
            $dealer_select .= '<option value="' . $seat . '" ' . ( $seat == $next_dealer ? 'selected' : '' ) . '>' . $name . '</option>';
        
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
                        <li><a href="#ptm_live_tabs_pot">' . __( 'payout pot', 'ptm' ) . '</a></li>
                        <li><a href="#ptm_live_tabs_settings">' . __( 'settings', 'ptm' ) . '</a></li>
                    </ul>
                    <div id="ptm_live_tabs_hand">
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label for="tab_h_dealer">' . __( 'dealer', 'ptm' ) . '</label>
                            <select name="h_dealer" id="tab_h_dealer">' . $dealer_select . '</select>
                            <button type="submit" name="h_new" value="' . ( $hand->h_hand + 1 ) . '">' . __( 'new hand', 'ptm' ) . '</button>
                        </form>
                    </div>
                    <div id="ptm_live_tabs_pot">
                        ...
                    </div>
                    <div id="ptm_live_tabs_settings">
                        ...
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
