<?php
    
    function ptm_sc_table() {
        
        global $wpdb;
        
        if( !isset( $_GET['tm'] ) )
            return ptm_sc_table_tournaments();
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['tm']
        );
        
        if( !isset( $_GET['id'] ) )
            return ptm_sc_table_list( $tm );
        
        $table = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'table
            WHERE   t_id = ' . $_GET['id']
        );
        
        return _ptm( '
            <div class="ptm_table_header ptm_header">
                ' . _ptm_link( 'table', __( 'back', 'ptm' ), [ 'tm' => $tm->tm_id ], 'ptm_button ptm_hlink' ) . '
                <h1>' . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . ': ' . $table->t_name . '</h1>
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
                        SUM( s_stack ) AS stack
                FROM    ' . $wpdb->prefix . 'seat
                WHERE   s_table = ' . $table->t_id
            );
            
            $chiplead = $wpdb->get_row( '
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
                <td>' . _ptm_stack( $stats->stack ) . '</td>
                <td>' . _ptm_link( 'competitor', $chiplead->p_name, [ 'tm' => $tm->tm_id, 'id' => $chiplead->p_id ] ) . '</td>
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
                            <th>' . __( 'stack', 'ptm' ) . '</th>
                            <th>' . __( 'chiplead', 'ptm' ) . '</th>
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
    
    add_shortcode( 'ptm_table', 'ptm_sc_table' );
    
?>
