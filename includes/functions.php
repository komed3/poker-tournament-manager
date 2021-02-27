<?php
    
    function _ptm(
        string $content = '',
        string $classes = ''
    ) {
        
        wp_enqueue_style( 'ptm_css', __ptm_path . 'css/style.css' );
        wp_enqueue_script( 'ptm_js', __ptm_path . 'js/functions.js', [ 'jquery' ] );
        
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
    
    function _ptm_pager(
        int $offset,
        int $limit,
        int $max
    ) {
        
        return '<div class="ptm_pager" data-limit="' . $limit . '">
            <button data-nav="first" ' . ( $offset == 0 ? 'disabled' : 'data-offset="0"' ) . '>
                ' . __( 'first', 'ptm' ) . '
            </button>
            <button data-nav="prev" ' . ( $offset == 0 ? 'disabled' : 'data-offset="' . ( $offset - $limit ) . '"' ) . '>
                ' . __( 'prev', 'ptm' ) . '
            </button>
            <span>
                ' . number_format_i18n( $offset + 1 ) . __( '–', 'ptm' ) .
                    number_format_i18n( $offset + $limit ) . __( ' of ', 'ptm' ) .
                    number_format_i18n( $max ) . '
            </span>
            <button data-nav="next" ' . ( $offset + $limit >= $max ? 'disabled' : 'data-offset="' . ( $offset + $limit ) . '"' ) . '>
                ' . __( 'next', 'ptm' ) . '
            </button>
            <button data-nav="last" ' . ( $offset + $limit >= $max ? 'disabled' : 'data-offset="' . ( floor( ( $max - 1 ) / $limit ) * $limit ) . '"' ) . '>
                ' . __( 'last', 'ptm' ) . '
            </button>
        </div>';
        
    }
    
?>
