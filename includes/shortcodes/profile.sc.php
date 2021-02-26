<?php
    
    function ptm_sc_profile() {
        
        global $wpdb;
        
        if( !isset( $_GET['id'] ) )
            return ptm_cs_profile_list();
        
        $profile = $wpdb->get_row('
            SELECT  *
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
                        <h3>' . __( 'buyin cash', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $profile->p_buyin ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'all time payout', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $profile->p_payout ) . '</span>
                    </div>
                    <div>
                        <h3>' . __( 'cash balance', 'ptm' ) . '</h3>
                        <span>' . _ptm_cash( $profile->p_payout - $profile->p_buyin ) . '</span>
                    </div>
                </div>
            </div>
        ', 'ptm_profile_grid' );
        
    }
    
    function ptm_cs_profile_list() {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 50 : $_GET['limit'];
        
        $list = [];
        
        foreach( $wpdb->get_results('
            SELECT  p_id, p_name
            FROM    ' . $wpdb->prefix . 'profile
            LIMIT   ' . $offset . ', ' . $limit
        ) as $profile ) {
            
            $list[] = '<a href="?id=' . $profile->p_id . '">' . $profile->p_name . '</a>';
            
        }
        
        return _ptm( '
            <div class="ptm_profile_list_header ptm_header">
                <h1>' . __( 'profiles', 'ptm' ) . '</h1>
            </div>
            <div class="ptm_profile_list">
                <ul><li>' . implode( '</li><li>', $list ) . '</li></ul>
            </div>
        ', 'ptm_profile_list_grid' );
        
    }
    
    add_shortcode( 'ptm_profile', 'ptm_sc_profile' );
    
?>
