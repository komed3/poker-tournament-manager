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
        
        return _ptm('
            <div class="ptm_profile_header ptm_header">
                <h1>' . $profile->p_name . '</h1>
            </div>
            <div class="ptm_profile_overview ptm_box">
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
        ');
        
    }
    
    function ptm_cs_profile_list() {
        
        return _ptm( '...' );
        
    }
    
    add_shortcode( 'ptm_profile', 'ptm_sc_profile' );
    
?>
