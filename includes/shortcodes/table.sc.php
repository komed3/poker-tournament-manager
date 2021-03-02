<?php
    
    function ptm_sc_table() {
        
        global $wpdb, $ptm_path;
        
        if( !isset( $_GET['tm'] ) )
            return ptm_sc_table_tournaments();
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['tm']
        );
        
        if( !isset( $_GET['id'] ) )
            return ptm_sc_table_list( $tm );
        
        if( strtolower( $_GET['id'] ) == 'new' )
            return ptm_sc_table_new( $tm );
        
        $table = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'table
            WHERE   t_id = ' . $_GET['id']
        );
        
        $stats = $wpdb->get_row( '
            SELECT  COUNT( s_seat ) AS seats,
                    SUM( s_stack ) AS chips
            FROM    ' . $wpdb->prefix . 'seat
            WHERE   s_table = ' . $table->t_id
        );
        
        $chipleader = $wpdb->get_row( '
            SELECT		*
            FROM		' . $wpdb->prefix . 'seat,
                        ' . $wpdb->prefix . 'profile
            WHERE	    s_table = ' . $table->t_id . '
            AND		    p_id = s_profile
            ORDER BY	s_stack DESC
            LIMIT		0, 1
        ' );
        
        $seats = [];
        $hstack = $chipleader->s_stack;
        $i = 1;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'seat,
                        ' . $wpdb->prefix . 'profile
            WHERE       s_table = ' . $table->t_id . '
            AND         p_id = s_profile
            ORDER BY    s_stack DESC,
                        s_seat ASC
        ' ) as $seat ) {
            
            $seats[] = '<tr>
                <td>' . number_format_i18n( $seat->s_seat ) . '</td>
                <td>' . _ptm_link( 'competitor', $seat->p_name, [ 'tm' => $tm->tm_id, 'id' => $seat->p_id ] ) . '</td>
                ' . ( $seat->s_stack == 0
                        ? '<td colspan="4">' . _ptm_msg( 'e' ) . '</td>'
                        : '<td>' . _ptm_stack( $seat->s_stack ) . '</td>
                           <td>' . _ptm_stack( $seat->s_stack - $seat->s_entry, true ) . '</td>
                           <td>' . number_format_i18n( $seat->s_stack / $stats->chips * 100, 1 ) . '&nbsp;%</td>
                           <td>' . _ptm_ordinal( $i ) . __( ' in chips' ) . '</td>' ) . '
            </tr>';
            
            if( $seat->s_stack < $hstack ) {
                
                $hstack = $seat->s_stack;
                $i++;
                
            }
            
        }
        
        $stacks = [];
        
        foreach( $wpdb->get_results( '
            SELECT  p_name,
                    st_value,
                    st_touched
            FROM    ' . $wpdb->prefix . 'stack,
                    ' . $wpdb->prefix . 'profile
            WHERE (
                st_table = ' . $table->t_id . ' OR
                st_table IS NULL
            )
            AND     p_id = st_profile
        ' ) as $s ) {
            
            $stacks[ $s->p_name ][] = [
                strtotime( $s->st_touched ) * 1000,
                $s->st_value
            ];
            
        }
        
        $hands = [];
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'hand,
                        ' . $wpdb->prefix . 'level
            WHERE       h_table = ' . $table->t_id . '
            AND         l_level = h_level
            AND         l_tournament = ' . $tm->tm_id . '
            ORDER BY    h_hand DESC
            LIMIT       0, 250
        ' ) as $hand ) {
            
            $hands[] = '<tr>
                <td>' . number_format_i18n( $hand->h_hand ) . '</td>
                <td>' . $hand->l_sb . '/' . $hand->l_bb . '/' . $hand->l_ante . '</td>
                <td>' . _ptm_card( $hand->h_flop_1 ) . _ptm_card( $hand->h_flop_2 ) . _ptm_card( $hand->h_flop_3 ) . '</td>
                <td>' . _ptm_card( $hand->h_turn ) . '</td>
                <td>' . _ptm_card( $hand->h_river ) . '</td>
                <td>' . _ptm_stack( $hand->h_pot ) . '</td>
                <td>' . __( $hand->h_termination, 'ptm' ) . '</td>
                <td>' . _ptm_date( $hand->h_touched, 'H:i' ) . '</td>
            </tr>';
            
        }
        
        wp_enqueue_script( 'ptm.js.table', $ptm_path . 'js/table.js', [ 'jquery', 'highstock', 'ptm.js.global' ] );
        
        wp_add_inline_script( 'ptm.js.table', '
            var ptm_chart_stacks_seats = ' . json_encode( array_keys( $stacks ) ) . ',
                ptm_chart_stacks_data = ' . json_encode( array_values( $stacks ), JSON_NUMERIC_CHECK ) . ';
        ', 'before' );
        
        return _ptm( '
            <div class="ptm_table_header ptm_header">
                ' . _ptm_link( 'table', __( 'back', 'ptm' ), [ 'tm' => $tm->tm_id ], 'ptm_button ptm_hlink' ) . '
                ' . _ptm_link( 'live', __( 'live', 'ptm' ), [ 'table' => $table->t_id ], 'ptm_button ptm_hlink' ) . '
                <h1>' . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . ': ' . $table->t_name . '</h1>
            </div>
            <div class="ptm_table_overview">
                <div class="ptm_biglist">
                    <div>
                        <h3>' . __( 'status', 'ptm' ) . '</h3>
                        <span>' . __( $table->t_status, 'ptm' ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'seats', 'ptm' ) . '</h3>
                        <span>' . number_format_i18n( $stats->seats ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'total chips', 'ptm' ) . '</h3>
                        <span>' . _ptm_stack( $stats->chips ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'chipleader', 'ptm' ) . '</h3>
                        <span>' . ( $chipleader == null ? '–' :
                                        _ptm_link( 'competitor', $chipleader->p_name, [ 'tm' => $tm->tm_id, 'id' => $chipleader->p_id ] ) . ' (' .
                                        _ptm_stack( $chipleader->s_stack ) . ', ' .
                                        number_format_i18n( $chipleader->s_stack / $stats->chips * 100, 1 ) . '&nbsp;%)' ) . '</span>
                    </div>
                </div>
            </div>
            <div class="ptm_table_seats">
                <h3>' . __( 'seats and stacks', 'ptm' ) . '</h3>
                <table class="ptm_list ranking">
                    <thead>
                        <tr>
                            <th>' . __( 'seat', 'ptm' ) . '</th>
                            <th>' . __( 'competitor', 'ptm' ) . '</th>
                            <th>' . __( 'stack', 'ptm' ) . '</th>
                            <th>' . __( 'change', 'ptm' ) . '</th>
                            <th>' . __( 'pct', 'ptm' ) . '</th>
                            <th>' . __( 'rank', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $seats ) . '</tbody>
                </table>
            </div>
            <div class="ptm_table_stacks">
                <h3>' . __( 'realtime stack sizes', 'ptm' ) . '</h3>
                <div id="ptm_chart_stacks"></div>
            </div>
            <div class="ptm_table_hands">
                <h3>' . __( 'played hands', 'ptm' ) . '</h3>
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'hand', 'ptm' ) . '</th>
                            <th>' . __( 'blinds', 'ptm' ) . '</th>
                            <th>' . __( 'flop', 'ptm' ) . '</th>
                            <th>' . __( 'turn', 'ptm' ) . '</th>
                            <th>' . __( 'river', 'ptm' ) . '</th>
                            <th>' . __( 'pot', 'ptm' ) . '</th>
                            <th>' . __( 'termination', 'ptm' ) . '</th>
                            <th>' . __( 'time', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $hands ) . '</tbody>
                </table>
            </div>
        ', 'ptm_table_grid ptm_page' );
        
    }
    
    function ptm_sc_table_list( $tm ) {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( t_id ) AS cnt
            FROM    ' . $wpdb->prefix . 'table
            WHERE   t_tournament = ' . $tm->tm_id
        )->cnt;
        
        $pager = _ptm_pager( $offset, $limit, $max, '&tm=' . $tm->tm_id );
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'table
            WHERE       t_tournament = ' . $tm->tm_id . '
            ORDER BY    t_id DESC
            LIMIT       ' . $offset . ', ' . $limit
        ) as $table ) {
            
            $stats = $wpdb->get_row( '
                SELECT  COUNT( s_seat ) AS seats,
                        SUM( s_stack ) AS chips
                FROM    ' . $wpdb->prefix . 'seat
                WHERE   s_table = ' . $table->t_id
            );
            
            $chipleader = $wpdb->get_row( '
                SELECT		*
                FROM		' . $wpdb->prefix . 'seat,
                            ' . $wpdb->prefix . 'profile
                WHERE		s_table = ' . $table->t_id . '
                AND			p_id = s_profile
                ORDER BY	s_stack DESC
                LIMIT		0, 1
            ' );
            
            $list[] = '<tr>
                <td>' . _ptm_link( 'table', $table->t_name, [ 'tm' => $tm->tm_id, 'id' => $table->t_id ] ) . '</td>
                <td>' . _ptm_date( $table->t_touched ) . '</td>
                <td>' . $table->t_status . '</td>
                <td>' . number_format_i18n( $stats->seats ) . '</td>
                <td>' . _ptm_stack( $stats->chips ) . '</td>
                <td>' . ( $chipleader == null ? '–' : _ptm_link( 'competitor', $chipleader->p_name, [ 'tm' => $tm->tm_id, 'id' => $chipleader->p_id ] ) ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_table_list_header ptm_header">
                ' . _ptm_link( 'table', __( 'add', 'ptm' ), [ 'tm' => $tm->tm_id, 'id' => 'new' ], 'ptm_button ptm_hlink' ) . '
                <h1>' . ucfirst( __( 'tables of ', 'ptm' ) ) . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_table_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'table', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                            <th>' . __( 'status', 'ptm' ) . '</th>
                            <th>' . __( 'seats', 'ptm' ) . '</th>
                            <th>' . __( 'total chips', 'ptm' ) . '</th>
                            <th>' . __( 'chipleader', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_table_list_grid ptm_page' );
        
    }
    
    function ptm_sc_table_tournaments() {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( tm_id ) AS cnt
            FROM    ' . $wpdb->prefix . 'tournament
        ' )->cnt;
        
        $pager = _ptm_pager( $offset, $limit, $max );
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'tournament
            ORDER BY    tm_date DESC
            LIMIT       ' . $offset . ', ' . $limit
        ) as $tm ) {
            
            $tables = $wpdb->get_row( '
                SELECT  COUNT( t_id ) AS cnt
                FROM    ' . $wpdb->prefix . 'table
                WHERE   t_tournament = ' . $tm->tm_id
            )->cnt;
            
            $list[] = '<tr>
                <td>' . _ptm_link( 'table', $tm->tm_name, [ 'tm' => $tm->tm_id ] ) . '</td>
                <td>' . _ptm_date( $tm->tm_date ) . '</td>
                <td>' . number_format_i18n( $tables ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_table_list_header ptm_header">
                <h1>' . ucfirst( __( 'tournament tables', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_table_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'tournament', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                            <th>' . __( 'tables', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_table_list_grid ptm_page' );
        
    }
    
    function ptm_sc_table_new( $tm ) {
        
        global $wpdb;
        
        if( isset( $_POST['t_new'] ) ) {
            
            if( $wpdb->insert(
                $wpdb->prefix . 'table',
                [
                    't_tournament' => $tm->tm_id,
                    't_name' => $_POST['t_name'],
                    't_status' => 'open'
                ]
            ) ) {
                
                $table = $wpdb->insert_id;
                
                for( $i = 1; $i <= 12; $i++ ) {
                    
                    if( !isset( $_POST['seat_' . $i ] ) || empty( $_POST['seat_' . $i ] ) )
                        continue;
                    
                    $c = $wpdb->get_row( '
                        SELECT  p_id, cp_stack
                        FROM    ' . $wpdb->prefix . 'profile,
                                ' . $wpdb->prefix . 'competitor
                        WHERE   p_id = ' . $_POST['seat_' . $i ] . '
                        AND     cp_profile = p_id
                    ' );
                    
                    $wpdb->insert(
                        $wpdb->prefix . 'seat',
                        [
                            's_table' => $table,
                            's_seat' => $i,
                            's_profile' => $c->p_id,
                            's_stack' => $c->cp_stack,
                            's_entry' => $c->cp_stack
                        ]
                    );
                    
                }
                
                return _ptm( '
                    <p>' . __( 'New tournament table was added successfully: ', 'ptm' ) . '<b>' . $_POST['t_name'] . '</b></p>
                    <p>' . _ptm_link( 'table', __( '&rarr; go to table', 'ptm' ), [ 'tm' => $tm->tm_id, 'id' => $table ] ) . '</p>
                ', 'ptm_page' );
                
            }
            
        }
        
        $competitors = [];
        
        foreach( $wpdb->get_results( '
            SELECT  p_id, p_name, cp_stack
            FROM    ' . $wpdb->prefix . 'competitor,
                    ' . $wpdb->prefix . 'profile
            WHERE   cp_tournament = ' . $tm->tm_id . '
            AND     p_id = cp_profile
        ' ) as $c ) {
            
            $competitors[] = '<option value="' . $c->p_id . '">
                ' . $c->p_name . ' (' . _ptm_stack( $c->cp_stack ) . ')
            </option>';
            
        }
        
        $seats = [];
        
        for( $i = 1; $i <= 12; $i++ ) {
            
            $seats[] = '<div class="form-line">
                <label for="seat_' . $i . '">' . __( 'seat ', 'ptm' ) . $i . '</label>
                <select id="seat_' . $i . '" name="seat_' . $i . '">
                    <option value="">' . __( 'unused', 'ptm' ) . '</option>
                    ' . implode( '', $competitors ) . '
                </select>
            </div>';
            
        }
        
        return _ptm( '
            <div class="ptm_table_new_header ptm_header">
                ' . _ptm_link( 'table', __( 'back', 'ptm' ), [], 'ptm_button ptm_hlink' ) . '
                <h1>' . ucfirst( __( 'create table for ', 'ptm' ) ) .
                        _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . '</h1>
            </div>
            <p>' . __( 'Use the following form to create a new tournament table.', 'ptm' ) . '</p>
            <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                <div class="form-line">
                    <label for="t_name">' . __( 'table name', 'ptm' ) . '</label>
                    <input type="text" id="t_name" name="t_name" required />
                </div>
                ' . implode( '', $seats ) . '
                <div class="form-line">
                    <button type="submit" name="t_new" value="1">' . __( 'create table', 'ptm' ) . '</button>
                </div>
            </form>
        ', 'ptm_table_new_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_table', 'ptm_sc_table' );
    
?>
