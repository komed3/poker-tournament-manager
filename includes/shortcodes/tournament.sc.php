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
        
        $cleft = $wpdb->get_row( '
            SELECT  COUNT( cp_profile ) AS cnt
            FROM    ' . $wpdb->prefix . 'competitor
            WHERE   cp_tournament = ' . $tm->tm_id . '
            AND     cp_stack > 0
        ' )->cnt;
        
        $total_buyin = $competitors->cnt * $tm->tm_buyin + ( $competitors->buyins - $competitors->cnt ) * $tm->tm_rebuy;
        $total_payout = $total_buyin * $tm->tm_payout_pct;
        
        return _ptm( '
            <div class="ptm_tournament_header ptm_header">
                ' . _ptm_link( 'tournament', __( 'back', 'ptm' ), [], 'ptm_button ptm_hlink' ) . '
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
                        <h3>' . __( 'field', 'ptm' ) . '</h3>
                        <span>' . $cleft . __( ' of ', 'ptm' ) . number_format_i18n( $competitors->cnt ) . '</span>
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
                        <h3>' . __( 'total buy-in', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $total_buyin ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'price pool', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $total_payout ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'payout pct', 'ptm' ) . '</h3>
                        <span>' . number_format_i18n( $tm->tm_payout_pct, 2 ) . '%</span>
                    </div>
                </div>
            </div>
            <div class="ptm_tournament_linklist">
                <h3>' . __( 'manage tournament', 'ptm' ) . '</h3>
                <ul>
                    <li>' . _ptm_link( 'table', __( 'Tournament tables', 'ptm' ), [ 'tm' => $tm->tm_id ] ) . '</li>
                    <li>' . _ptm_link( 'level', __( 'Levels and Blind structure', 'ptm' ), [ 'tm' => $tm->tm_id ] ) . '</li>
                    <li>' . _ptm_link( 'competitor', __( 'Competitors', 'ptm' ), [ 'tm' => $tm->tm_id ] ) . '</li>
                    <li>' . _ptm_link( 'payout', __( 'Payout spread', 'ptm' ), [ 'tm' => $tm->tm_id ] ) . '</li>
                </ul>
            </div>
            ' . ptm_cs_tournament_payout( $tm ) . '
            ' . ptm_cs_tournament_chiplead( $tm ) . '
        ', 'ptm_tournament_grid ptm_page' );
        
    }
    
    function ptm_cs_tournament_payout( $tm ) {
        
        global $wpdb;
        
        $list = [];
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'competitor,
                        ' . $wpdb->prefix . 'profile
            WHERE       cp_tournament = ' . $tm->tm_id . '
            AND         p_id = cp_profile
            AND         cp_payout IS NOT NULL
            ORDER BY    cp_rank ASC,
                        cp_payout DESC
        ' ) as $profile ) {
            
            $buyin = $tm->tm_buyin + ( $profile->cp_buyins - 1 ) * $tm->tm_rebuy;
            
            $list[] = '<tr>
                <td>' . _ptm_rank( $profile->cp_rank ) . '</td>
                <td>' . _ptm_link( 'profile', $profile->p_name, [ 'id' => $profile->p_id ] ) . '</td>
                <td>' . _ptm_cash( $buyin ) . '</td>
                <td>' . _ptm_cash( $profile->cp_payout ) . '</td>
                <td>1:' . number_format_i18n( $profile->cp_payout / $buyin ) . '</td>
            </tr>';
            
        }
        
        if( count( $list ) == 0 )
            return '';
        
        return '<div class="ptm_tournament_payout">
            <h3>' . __( 'payouts', 'ptm' ) . '</h3>
            <table class="ptm_list ranking">
                <thead>
                    <tr>
                        <th>' . __( 'rank', 'ptm' ) . '</th>
                        <th>' . __( 'profile', 'ptm' ) . '</th>
                        <th>' . __( 'buy-in', 'ptm' ) . '</th>
                        <th>' . __( 'payout', 'ptm' ) . '</th>
                        <th>' . __( 'profit rate', 'ptm' ) . '</th>
                    </tr>
                </thead>
                <tbody>' . implode( '', $list ) . '</tbody>
            </table>
        </div>';
        
    }
    
    function ptm_cs_tournament_chiplead( $tm ) {
        
        global $wpdb;
        
        $total_stack = $wpdb->get_row( '
            SELECT  SUM( cp_stack ) AS stack
            FROM    ' . $wpdb->prefix . 'competitor
            WHERE   cp_tournament = ' . $tm->tm_id . '
        ' )->stack;
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'competitor,
                        ' . $wpdb->prefix . 'profile
            WHERE       cp_tournament = ' . $tm->tm_id . '
            AND         p_id = cp_profile
            AND         cp_payout IS NULL
            AND         cp_stack > 0
            ORDER BY    cp_stack DESC
            LIMIT       0, 10
        ' ) as $profile ) {
            
            $list[] = '<tr>
                <td>' . _ptm_rank( ++$i ) . '</td>
                <td>' . _ptm_link( 'profile', $profile->p_name, [ 'id' => $profile->p_id ] ) . '</td>
                <td>' . _ptm_stack( $profile->cp_stack ) . '</td>
                <td>' . _ptm_stack( $profile->cp_stack - $tm->tm_stack, true ) . '</td>
                <td>' . number_format_i18n( $profile->cp_stack / $total_stack * 100, 1 ) . '%</td>
            </tr>';
            
        }
        
        if( count( $list ) == 0 )
            return '';
        
        return '<div class="ptm_tournament_chiplead">
            <h3>' . __( 'chiplead', 'ptm' ) . '</h3>
            <table class="ptm_list ranking">
                <thead>
                    <tr>
                        <th>' . __( 'rank', 'ptm' ) . '</th>
                        <th>' . __( 'profile', 'ptm' ) . '</th>
                        <th>' . __( 'stack', 'ptm' ) . '</th>
                        <th>' . __( 'change', 'ptm' ) . '</th>
                        <th>' . __( 'pct', 'ptm' ) . '</th>
                    </tr>
                </thead>
                <tbody>' . implode( '', $list ) . '</tbody>
            </table>
        </div>';
        
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
                <td>' . number_format_i18n( $competitors->cnt ) . '</td>
                <td>' . _ptm_cash( $tm->tm_buyin ) . '</td>
                <td>' . _ptm_cash( $total_payout ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_tournament_list_header ptm_header">
                ' . _ptm_link( 'tournament', __( 'new', 'ptm' ), [ 'id' => 'new' ], 'ptm_button ptm_hlink' ) . '
                <h1>' . ucfirst( __( 'tournaments', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_tournament_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'tournament', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                            <th>' . __( 'competitors', 'ptm' ) . '</th>
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
    
    add_shortcode( 'ptm_tournament', 'ptm_sc_tournament' );
    
?>
