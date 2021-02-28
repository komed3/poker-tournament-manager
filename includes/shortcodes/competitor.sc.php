<?php
    
    function ptm_sc_competitor() {
        
        global $wpdb;
        
        if( !isset( $_GET['tm'] ) )
            return ptm_cs_competitor_tournaments();
        
        else if( !isset( $_GET['id'] ) )
            return ptm_cs_competitor_list();
        
        return '';
        
    }
    
    function ptm_cs_competitor_list() {
        
        global $wpdb;
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['tm']
        );
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( cp_profile ) AS cnt,
                    SUM( cp_stack ) AS stack
            FROM    ' . $wpdb->prefix . 'competitor
        ' );
        
        $pager = _ptm_pager( $offset, $limit, $max->cnt, '&tm=' . $tm->tm_id );
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'competitor,
                        ' . $wpdb->prefix . 'profile
            WHERE       cp_tournament = ' . $tm->tm_id . '
            AND         p_id = cp_profile
            LIMIT       ' . $offset . ', ' . $limit
        ) as $profile ) {
            
            $list[] = '<tr>
                <td>' . _ptm_rank( $profile->cp_rank ) . '</td>
                <td>' . _ptm_link( 'competitor', $profile->p_name, [ 'tm' => $tm->tm_id, 'id' => $profile->p_id ] ) . '</td>
                ' . ( $profile->cp_stack == 0
                        ? '<td colspan="2">' . _ptm_msg( 'e' ) . '</td>'
                        : '<td>' . _ptm_stack( $profile->cp_stack ) . '</td>
                           <td>' . number_format_i18n( $profile->cp_stack / $max->stack * 100, 1 ) . '&nbsp;%</td>' ) . '
                <td>' . _ptm_cash( $tm->tm_buyin + ( $profile->cp_buyins - 1 ) * $tm->tm_rebuy ) . '</td>
                <td>' . ( $profile->cp_payout == null ? '–' : _ptm_cash( $profile->cp_payout ) ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_competitor_list_header ptm_header">
                ' . _ptm_link( 'competitor', __( 'add', 'ptm' ), [ 'tm' => $tm->tm_id, 'id' => 'new' ], 'ptm_button ptm_hlink' ) . '
                <h1>' . ucfirst( __( 'competitors of ', 'ptm' ) ) . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_profile_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'rank', 'ptm' ) . '</th>
                            <th>' . __( 'competitor', 'ptm' ) . '</th>
                            <th>' . __( 'stack', 'ptm' ) . '</th>
                            <th>' . __( 'stack pct', 'ptm' ) . '</th>
                            <th>' . __( 'buy-in', 'ptm' ) . '</th>
                            <th>' . __( 'payout', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_competitor_list_grid ptm_page' );
        
    }
    
    function ptm_cs_competitor_tournaments() {
        
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
            
            $cleft = $wpdb->get_row( '
                SELECT  COUNT( cp_profile ) AS cnt
                FROM    ' . $wpdb->prefix . 'competitor
                WHERE   cp_tournament = ' . $tm->tm_id . '
                AND     cp_stack > 0
            ' )->cnt;
            
            $total_payout = ( $competitors->cnt * $tm->tm_buyin + ( $competitors->buyins - $competitors->cnt ) * $tm->tm_rebuy ) * $tm->tm_payout_pct;
            
            $list[] = '<tr>
                <td>' . _ptm_link( 'competitor', $tm->tm_name, [ 'tm' => $tm->tm_id ] ) . '</td>
                <td>' . _ptm_date( $tm->tm_date ) . '</td>
                <td>' . number_format_i18n( $cleft ) . __( ' of ', 'ptm' ) . number_format_i18n( $competitors->cnt ) . '</td>
                <td>' . _ptm_cash( $tm->tm_buyin ) . '</td>
                <td>' . _ptm_cash( $total_payout ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_tournament_list_header ptm_header">
                <h1>' . ucfirst( __( 'tournament competitors', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_tournament_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'tournament', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                            <th>' . __( 'field', 'ptm' ) . '</th>
                            <th>' . __( 'buy-in', 'ptm' ) . '</th>
                            <th>' . __( 'price pool', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_tournament_list_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_competitor', 'ptm_sc_competitor' );
    
?>
