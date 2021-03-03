<?php
    
    /* 
     * 
     * Plugin Name: Poker Tournament Manager
     * Plugin URI: https://github.com/komed3/poker-tournament-manager
     * Description: Poker Tournament Manager is an extension for WordPress to manage poker tournaments with player statistics, table overviews, blind structures etc.
     * Author: komed3 (Paul Köhler)
     * Author URI: https://labs.komed3.de
     * Version: 1.0.0
     * Text Domain: ptm
     * 
     */
    
    $ptm_path = plugins_url( '/resources/', __FILE__ );
    $ptm_pages = [];
    
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/shortcodes/index.php';
    
    _ptm_init();
    
?>
