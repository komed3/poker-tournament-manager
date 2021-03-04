<?php
    
    function ptm_sc_live() {
        
        global $wpdb;
        
        if( !isset( $_GET['table'] ) )
            return ptm_sc_live_tables();
        
        return _ptm( '
            
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
