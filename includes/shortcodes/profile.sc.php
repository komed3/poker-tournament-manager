<?php
    
    function ptm_sc_profile() {
        
        global $wpdb;
        
        if( !isset( $_GET['id'] ) )
            return ptm_cs_profile_list();
        
        $profile = $wpdb->get_row( '
            SELECT  *, ( p_payout - p_buyin ) AS balance
            FROM    ' . $wpdb->prefix . 'profile
            WHERE   p_id = ' . $_GET['id']
        );
        
        return _ptm( '
            <div class="ptm_profile_header ptm_header">
                <h1>' . $profile->p_name . '</h1>
            </div>
            <div class="ptm_profile_overview">
                <div class="ptm_biglist">
                    <div>
                        <h3>' . __( 'tournaments', 'ptm' ) . '</h3>
                        <span>' . number_format_i18n( $profile->p_tournaments ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'buy in cash', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $profile->p_buyin ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'total payout', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $profile->p_payout ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'cash balance', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $profile->balance ) . '</span>
                    </div>
                </div>
            </div>
        ', 'ptm_profile_grid' );
        
    }
    
    function ptm_cs_profile_list() {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( p_id ) AS cnt
            FROM    ' . $wpdb->prefix . 'profile
        ' )->cnt;
        
        $pager = _ptm_pager( $offset, $limit, $max );
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *, ( p_payout - p_buyin ) AS balance
            FROM        ' . $wpdb->prefix . 'profile
            ORDER BY    balance DESC
            LIMIT       ' . $offset . ', ' . $limit
        ) as $profile ) {
            
            $list[] = '<tr>
                <td>#' . ( $offset + ++$i ) . '</td>
                <td><a href="?id=' . $profile->p_id . '">' . $profile->p_name . '</a></td>
                <td>' . _ptm_cash( $profile->p_payout ) . '</td>
                <td>' . _ptm_cash( $profile->balance ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_profile_list_header ptm_header">
                <h1>' . ucfirst( __( 'profiles', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_profile_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'rank', 'ptm' ) . '</th>
                            <th>' . __( 'profile', 'ptm' ) . '</th>
                            <th>' . __( 'total payout', 'ptm' ) . '</th>
                            <th>' . __( 'cash balance', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_profile_list_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_profile', 'ptm_sc_profile' );
    
?>
