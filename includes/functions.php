<?php
    
    function _ptm(
        string $content = '',
        string $classes = ''
    ) {
        
        wp_enqueue_style( 'ptm_style', __ptm_path . 'css/style.css' );
        
        return '<div class="ptm_container ' . $classes . '">' . $content . '</div>';
        
    }
    
    function _ptm_opt(
        string $key,
        $default = ''
    ) {
        
        return get_option( 'ptm_' . $key, $default );
        
    }
    
    function _ptm_cash(
        float $cash = 0,
        int $digits = 1
    ) {
        
        $pow10 = floor( (int) ( log10( abs( $cash ) ) ) / 3 );
        
        return '<cash class="' . ( $cash < 0 ? 'bad' : 'good' ) . '" title="' . _ptm_opt( 'currency', 'USD' ) . ' ' . number_format_i18n( $cash, $digits ) . '">' .
            _ptm_opt( 'currency', 'USD' ) . '&nbsp;<b>' .
            number_format_i18n( abs( $cash ) / pow( 10, $pow10 * 3 ), $digits ) . '&nbsp;' . [
                __( '', 'ptm' ), __( 'T', 'ptm' ), __( 'M', 'ptm' ),
                __( 'B', 'ptm' ), __( 'T', 'ptm' ), __( 'Q', 'ptm' )
            ][ $pow10 ] .
        '</b></cash>';
        
    }
    
?>
