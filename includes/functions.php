<?php
    
    function _ptm_init() {
        
        global $wpdb, $ptm_pages;
        
        foreach( [ 'profile', 'tournament' ] as $page ) {
            
            $ptm_pages[ $page ] = $wpdb->get_row( '
                SELECT  ID
                FROM    ' . $wpdb->prefix . 'posts
                WHERE   post_content LIKE "%[ptm_' . $page . ']%"
                AND     post_status = "publish"
            ' )->ID;
            
        }
        
    }
    
    function _ptm(
        string $content = '',
        string $classes = ''
    ) {
        
        global $ptm_path;
        
        wp_enqueue_style( 'ptm.css.global', $ptm_path . 'css/style.css' );
        
        wp_enqueue_script( 'ptm.js.global', $ptm_path . 'js/functions.js', [ 'jquery' ] );
        wp_enqueue_script( 'highstock', 'https://code.highcharts.com/stock/highstock.js', [ 'jquery' ] );
        
        return '<div class="ptm_container ' . $classes . '">' . $content . '</div>';
        
    }
    
    function _ptm_opt(
        string $key,
        $default = ''
    ) {
        
        return get_option( 'ptm_' . $key, $default );
        
    }
    
    function _ptm_pow2sfx(
        int $pow10
    ) {
        
        return [
            __( '', 'ptm' ), __( 'T', 'ptm' ), __( 'M', 'ptm' ),
            __( 'B', 'ptm' ), __( 'T', 'ptm' ), __( 'Q', 'ptm' )
        ][ $pow10 ];
        
    }
    
    function _ptm_rank(
        int $rank = 1
    ) {
        
        return '<rank title="' . __( 'rank no ', 'ptm' ) . number_format_i18n( $rank ) . '">' . number_format_i18n( $rank ) . '</rank>';
        
    }
    
    function _ptm_change(
        float $number = 0
    ) {
        
        return ( $number > 0 ? 'good' : ( $number < 0 ? 'bad' : '' ) );
        
    }
    
    function _ptm_cash(
        float $cash = 0,
        int $digits = 0
    ) {
        
        $pow10 = floor( (int) ( log10( abs( $cash ) ) ) / 3 );
        
        return '<cash class="' . _ptm_change( $cash ) . '" title="' . _ptm_opt( 'currency', 'USD' ) . ' ' . number_format_i18n( $cash, $digits ) . '">' .
            _ptm_opt( 'currency', 'USD' ) . '&nbsp;<b>' .
            number_format_i18n( abs( $cash ) / pow( 10, $pow10 * 3 ), $digits ) . '&nbsp;' .
            _ptm_pow2sfx( $pow10 ) .
        '</b></cash>';
        
    }
    
    function _ptm_stack(
        float $stack = 0,
        bool $change = false
    ) {
        
        $pow10 = floor( (int) ( log10( abs( $stack ) ) ) / 3 );
        
        return '<stack class="' . ( $change ? _ptm_change( $stack ) : '' ) . '" title="' . number_format_i18n( $stack, 0 ) . '">$&nbsp;' .
            number_format_i18n( abs( $stack ) / pow( 10, $pow10 * 3 ), 1 ) . '&nbsp;' .
            _ptm_pow2sfx( $pow10 ) .
        '</stack>';
        
    }
    
    function _ptm_date(
        string $timestring,
        $format = null
    ) {
        
        return date_i18n( !is_string( $format ) ? get_option( 'date_format' ) : $format, $timestring );
        
    }
    
    function _ptm_link(
        string $page,
        string $text,
        array $args = [],
        string $classes = ''
    ) {
        
        global $ptm_pages;
        
        $query = http_build_query( $args );
        
        return !array_key_exists( $page, $ptm_pages ) ? $text :
            '<a href="' . get_page_link( $ptm_pages[ $page ] ) . ( strlen( $query ) > 0 ? '?' . $query : '' ) . '" class="' . $classes . '">' . $text . '</a>';
        
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
                    number_format_i18n( min( $max, $offset + $limit ) ) . __( ' of ', 'ptm' ) .
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
