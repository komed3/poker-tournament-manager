<?php
    
    function ptm_sc_payout() {
        
        global $wpdb;
        
        if( !isset( $_GET['tm'] ) )
            return ptm_sc_payout_tournaments();
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['tm']
        );
        
        $competitors = $wpdb->get_row( '
            SELECT  COUNT( cp_profile ) AS cnt,
                    COUNT( cp_buyins ) AS buyins
            FROM    ' . $wpdb->prefix . 'competitor
            WHERE   cp_tournament = ' . $tm->tm_id
        );
        
        $total_buyin = $competitors->cnt * $tm->tm_buyin + ( $competitors->buyins - $competitors->cnt ) * $tm->tm_rebuy;
        $total_payout = $total_buyin * $tm->tm_payout_pct;
        
        if( isset( $_POST['p_new'] ) ) {
            
            if( $wpdb->insert(
                $wpdb->prefix . 'payout',
                [
                    'p_tournament' => $tm->tm_id,
                    'p_place' => $_POST['p_place'],
                    'p_payout' => $total_payout * $_POST['p_pct'],
                    'p_pct' => $_POST['p_pct']
                ]
            ) ) return _ptm( '
                <p>' . __( 'New payout level was added successfully.', 'ptm' ) . '</p>
                <p>' . _ptm_link( 'payout', __( 'refresh page', 'ptm' ), [ 'tm' => $tm->tm_id ] ) . '</p>
            ', 'ptm_page' );
            
        }
        
        $structure = [];
        $i = $lplace = $pct = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'payout
            WHERE       p_tournament = ' . $tm->tm_id . '
            ORDER BY    p_place ASC
        ' ) as $payout ) {
            
            $structure[] = '<tr>
                <td>' . _ptm_ordinal( ++$i ) . '</td>
                <td>' . ( $lplace + 1 != $payout->p_place ? ( $lplace + 1 ) . '–' : '' ) . $payout->p_place . '</td>
                <td>' . _ptm_cash( $payout->p_payout, 1 ) . '</td>
                <td>' . number_format_i18n( $payout->p_pct * 100, 1 ) . '&nbsp;%</td>
                <td>1:' . number_format_i18n( $payout->p_payout / $tm->tm_buyin, 2 ) . '</td>
            </tr>';
            
            $lplace = $payout->p_place;
            $pct += $payout->p_pct;
            
        }
        
        return _ptm( '
            <div class="ptm_payout_header ptm_header">
                ' . _ptm_link( 'payout', __( 'back', 'ptm' ), [], 'ptm_button ptm_hlink' ) . '
                <h1>' . ucfirst( __( 'payout structure for ', 'ptm' ) ) .
                        _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . '</h1>
            </div>
            <h3>' . __( 'payout structure', 'ptm' ) . '</h3>
            <table class="ptm_list ranking">
                <thead>
                    <tr>
                        <th>' . __( 'rank', 'ptm' ) . '</th>
                        <th>' . __( 'places', 'ptm' ) . '</th>
                        <th>' . __( 'payout', 'ptm' ) . '</th>
                        <th>' . __( 'pct', 'ptm' ) . '</th>
                        <th>' . __( 'profit rate', 'ptm' ) . '</th>
                    </tr>
                </thead>
                <tbody>' . implode( '', $structure ) . '</tbody>
            </table>
            <h3>' . __( 'add payout level', 'ptm' ) . '</h3>
            <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                <div class="form-line">
                    <label for="p_place">' . __( 'maximum place', 'ptm' ) . '</label>
                    <input type="number" id="p_place" name="p_place" min="' . ( $lplace + 1 ) . '" max="' .
                        $competitors->cnt . '" value="' . ( $lplace + 1 ) . '" step="1" required />
                </div>
                <div class="form-line">
                    <label for="p_pct">' . __( 'percent of price pool per place', 'ptm' ) . '</label>
                    <input type="number" id="p_pct" name="p_pct" min="0" max="' . ( 1 - $pct ) . '" step="0.01" required />
                </div>
                <div class="form-line">
                    <button type="submit" name="p_new" value="1">' . __( 'add payout level', 'ptm' ) . '</button>
                </div>
            </form>
        ', 'ptm_payout_grid ptm_page' );
        
    }
    
    function ptm_sc_payout_tournaments() {
        
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
                <td>' . _ptm_link( 'payout', $tm->tm_name, [ 'tm' => $tm->tm_id ] ) . '</td>
                <td>' . _ptm_date( $tm->tm_date ) . '</td>
                <td>' . number_format_i18n( $competitors->cnt ) . '</td>
                <td>' . _ptm_cash( $tm->tm_buyin ) . '</td>
                <td>' . _ptm_cash( $total_payout ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_payout_tournaments_header ptm_header">
                <h1>' . ucfirst( __( 'tournament payout structure', 'ptm' ) ) . '</h1>
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
        ', 'ptm_payout_tournaments_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_payout', 'ptm_sc_payout' );
    
?>
