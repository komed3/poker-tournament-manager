<?php
    
    function ptm_sc_tournament() {
        
        global $wpdb;
        
        if( !isset( $_GET['id'] ) )
            return ptm_cs_tournament_list();
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['id']
        );
        
        $competitors = $wpdb->get_row( '
            SELECT  COUNT( cp_profile ) AS cnt,
                    COUNT( cp_buyins ) AS buyins
            FROM    ' . $wpdb->prefix . 'competitor
            WHERE   cp_tournament = ' . $tm->tm_id
        );
        
        $total_buyin = $competitors->cnt * $tm->tm_buyin + ( $competitors->buyins - $competitors->cnt ) * $tm->tm_rebuy;
        $total_payout = $total_buyin * $tm->tm_payout_pct;
        
        return _ptm( '
            <div class="ptm_tournament_header ptm_header">
                ' . _ptm_link( 'tournament', __( 'back', 'ptm' ), [], 'ptm_backlink' ) . '
                <h1>' . $tm->tm_name . '</h1>
            </div>
            <div class="ptm_profile_overview">
                <div class="ptm_biglist">
                    <div>
                        <h3>' . __( 'date', 'ptm' ) . '</h3>
                        <span>' . _ptm_date( $tm->tm_date ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'status', 'ptm' ) . '</h3>
                        <span>' . $tm->tm_status . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'buy-in', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $tm->tm_buyin ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'rebuy', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $tm->tm_rebuy ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'competitors', 'ptm' ) . '</h3>
                        <span>' . number_format_i18n( $competitors->cnt ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'total buy-in', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $total_buyin ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'payout pct', 'ptm' ) . '</h3>
                        <span>' . number_format_i18n( $tm->tm_payout_pct, 2 ) . '%</span>
                    </div>
                    <div>
                        <h3>' . __( 'total payout', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $total_payout ) . '</span>
                    </div>
                </div>
            </div>
        ', 'ptm_tournament_grid ptm_page' );
        
    }
    
    function ptm_cs_tournament_list() {
        
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
            
            $competitors = $wpdb->get_row( '
                SELECT  COUNT( cp_profile ) AS cnt,
                        COUNT( cp_buyins ) AS buyins
                FROM    ' . $wpdb->prefix . 'competitor
                WHERE   cp_tournament = ' . $tm->tm_id
            );
            
            $total_payout = ( $competitors->cnt * $tm->tm_buyin + ( $competitors->buyins - $competitors->cnt ) * $tm->tm_rebuy ) * $tm->tm_payout_pct;
            
            $list[] = '<tr>
                <td>' . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . '</td>
                <td>' . _ptm_date( $tm->tm_date ) . '</td>
                <td>' . _ptm_cash( $tm->tm_buyin ) . '</td>
                <td>' . number_format_i18n( $competitors->cnt ) . '</td>
                <td>' . _ptm_cash( $total_payout ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_tournament_list_header ptm_header">
                <h1>' . ucfirst( __( 'tournaments', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_tournament_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'tournament', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                            <th>' . __( 'buy-in', 'ptm' ) . '</th>
                            <th>' . __( 'competitors', 'ptm' ) . '</th>
                            <th>' . __( 'total payout', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_tournament_list_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_tournament', 'ptm_sc_tournament' );
    
?>
