<?php
    
    function ptm_sc_competitor() {
        
        global $wpdb, $ptm_path;
        
        if( !isset( $_GET['tm'] ) )
            return ptm_cs_competitor_tournaments();
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['tm']
        );
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( cp_profile ) AS cnt,
                    SUM( cp_stack ) AS stack
            FROM    ' . $wpdb->prefix . 'competitor
            WHERE   cp_tournament = ' . $tm->tm_id
        );
        
        if( !isset( $_GET['id'] ) )
            return ptm_cs_competitor_list( $tm, $max );
        
        $profile = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'competitor,
                    ' . $wpdb->prefix . 'profile
            WHERE   cp_tournament = ' . $tm->tm_id . '
            AND     cp_profile = ' . $_GET['id'] . '
            AND     p_id = cp_profile
        ' );
        
        $stack_rank = $wpdb->get_row( '
            SELECT  COUNT( cp_profile ) AS cnt
            FROM    ' . $wpdb->prefix . 'competitor
            WHERE   cp_tournament = ' . $tm->tm_id . '
            AND     cp_stack > ' . $profile->cp_stack
        )->cnt + 1;
        
        $buyin = $tm->tm_buyin + ( $profile->cp_buyins - 1 ) * $tm->tm_rebuy;
        
        $range = [ 'win' => 0, 'loss' => 0, 'unplayed' => 0 ];
        $stack = [];
        $hands = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'stack
            WHERE       st_profile = ' . $profile->p_id . '
            AND         st_tournament = ' . $tm->tm_id . '
            ORDER BY    st_touched ASC
        ' ) as $st ) {
            
            if( $st->st_hand != null ) {
                
                $range[ $st->st_flag == null ? 'unplayed' : $st->st_flag ]++;
                $hands++;
                
            }
            
            $stack['stack'][] = [
                strtotime( $st->st_touched ) * 1000,
                $st->st_value
            ];
            
            $stack['change'][] = [
                strtotime( $st->st_touched ) * 1000,
                $st->st_change
            ];
            
        }
        
        if( $hands > 0 )
            $range = array_map( function( $value ) use ( $hands ) { return $value / $hands * 100; }, $range );
        
        wp_enqueue_script( 'ptm.js.competitor', $ptm_path . 'js/competitor.js', [ 'jquery', 'highstock', 'ptm.js.global' ] );
        
        return _ptm( '
            <div class="ptm_competitor_header ptm_header">
                ' . _ptm_link( 'competitor', __( 'back', 'ptm' ), [ 'tm' => $tm->tm_id ], 'ptm_button ptm_hlink' ) . '
                <h1>' . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . ': ' .
                        _ptm_link( 'profile', $profile->p_name, [ 'id' => $profile->p_id ] ) . '</h1>
            </div>
            <div class="ptm_competitor_overview">
                <div class="ptm_biglist">
                    <div>
                        <h3>' . __( 'rank', 'ptm' ) . '</h3>
                        <span>' . _ptm_rank( $profile->cp_rank ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'buy-in', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $buyin ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'payout', 'ptm' ) . '</h3>
                        <span>' . ( $profile->cp_payout == null ? '–' : _ptm_cash( $profile->cp_payout ) ) . '</span>
                    </div>
                    ' . ( $profile->cp_stack == 0
                            ? '<div>
                                   <h3>' . __( 'stack', 'ptm' ) . '</h3>
                                   <span>' .  _ptm_msg( 'e' ) . '</span>
                               </div>'
                            : '<div>
                                   <h3>' . __( 'stack', 'ptm' ) . '</h3>
                                   <span>' . _ptm_stack( $profile->cp_stack ) . '</span>
                               </div>
                               <div>
                                   <h3>' . __( 'stack pct', 'ptm' ) . '</h3>
                                   <span>' . number_format_i18n( $profile->cp_stack / $max->stack * 100, 1 ) . '&nbsp;%</span>
                               </div>
                               <div>
                                   <h3>' . __( 'stack rank', 'ptm' ) . '</h3>
                                   <span>' . _ptm_ordinal( $stack_rank ) . __( ' in chips', 'ptm' ) . '</span>
                               </div>' ) . '
                </div>
            </div>
            ' . ( $hands > 0 ? '
                    <div class="ptm_competitor_range">
                        <div class="ptm_range">
                            <div class="bar good" style="width: ' . $range['win'] . '%;" title="' . __( 'win', 'ptm' ) . '">
                                <span>' . number_format_i18n( $range['win'], 1 ) . '&nbsp;%</span>
                            </div>
                            <div class="bar bad" style="width: ' . $range['loss'] . '%;" title="' . __( 'loss', 'ptm' ) . '">
                                <span>' . number_format_i18n( $range['loss'], 1 ) . '&nbsp;%</span>
                            </div>
                            <div class="bar" style="width: ' . $range['unplayed'] . '%;" title="' . __( 'unplayed', 'ptm' ) . '">
                                <span>' . number_format_i18n( $range['unplayed'], 1 ) . '&nbsp;%</span>
                            </div>
                        </div>
                    </div>' : '' ) . '
            <div class="ptm_competitor_stack">
                <h3>' . __( 'realtime stack size', 'ptm' ) . '</h3>
                <div id="ptm_chart_stack" data-stack="' . json_encode( $stack['stack'], JSON_NUMERIC_CHECK ) . '" data-change="' .
                                                          json_encode( $stack['change'], JSON_NUMERIC_CHECK ) . '"></div>
            </div>
        ', 'ptm_competitor_grid ptm_page' );
        
    }
    
    function ptm_cs_competitor_list( $tm, $max ) {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
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
            <div class="ptm_competitor_list_header ptm_header">
                <h1>' . ucfirst( __( 'tournament competitors', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_competitor_list">
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
        ', 'ptm_competitor_list_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_competitor', 'ptm_sc_competitor' );
    
?>
