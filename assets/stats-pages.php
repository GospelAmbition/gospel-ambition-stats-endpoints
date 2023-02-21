<?php

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_action('wp_enqueue_scripts', function (){

    //@todo if stats page, then enqueue the stats-pages.css file
    wp_enqueue_style( 'stats-pages', plugin_dir_url( __FILE__ ) . 'stats-page.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'stats-page.css' ) );
});
