<?php

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_action('wp_enqueue_scripts', function (){

    if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'stats' ) !== false ){
        wp_enqueue_style( 'stats-pages', plugin_dir_url( __FILE__ ) . 'stats-page.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'stats-page.css' ) );
        wp_enqueue_style( 'material-font-icons', 'https://cdn.jsdelivr.net/npm/@mdi/font@6.6.96/css/materialdesignicons.min.css' );
    }
});
