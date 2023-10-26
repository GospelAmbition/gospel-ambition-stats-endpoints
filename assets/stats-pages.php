<?php

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_action( 'wp_head', function () {
    ?>
    <!-- Foundation Framework -->
    <!-- Compressed CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/foundation-sites@6.8.1/dist/css/foundation.min.css"
          crossorigin="anonymous">

    <!-- Compressed JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/foundation-sites@6.8.1/dist/js/foundation.min.js"
            crossorigin="anonymous"></script>

    <!-- foundation-float.min.css: Compressed CSS with legacy Float Grid -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/foundation-sites@6.8.1/dist/css/foundation-float.min.css"
          crossorigin="anonymous">

    <!-- foundation-prototype.min.css: Compressed CSS with prototyping classes -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/foundation-sites@6.8.1/dist/css/foundation-prototype.min.css"
          crossorigin="anonymous">

    <!-- foundation-rtl.min.css: Compressed CSS with right-to-left reading direction -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/foundation-sites@6.8.1/dist/css/foundation-rtl.min.css"
          crossorigin="anonymous">
    <!-- Foundation Framework -->
    <?php
} );

add_action('wp_enqueue_scripts', function (){

    if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'stats' ) !== false ){
        wp_enqueue_style( 'stats-pages', plugin_dir_url( __FILE__ ) . 'stats-page.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'stats-page.css' ) );
        wp_enqueue_style( 'material-font-icons', 'https://cdn.jsdelivr.net/npm/@mdi/font@6.6.96/css/materialdesignicons.min.css', [], '6.6.96' );

        switch ( get_bloginfo() ) {
            case 'Prayer Global':
            case 'Vision':
            case 'Zúme Training':
            case 'Pray4Movement':
            case 'Kingdom Training':
            case 'Disciple.Tools':
                break;

            case 'Gospel Ambition':
                load_scripts_gospel_ambition();
                break;
        }
    }
});

function load_scripts_gospel_ambition(): void {
    $plugin_dir_url = plugin_dir_url( __DIR__ );
    $plugin_dir_path = plugin_dir_path( __DIR__ );
    $gospel_ambition_js = 'gospel_ambition/gospel-ambition.js';

    wp_register_script( 'amcharts-index', 'https://cdn.amcharts.com/lib/5/index.js', false, '5' );
    wp_register_script( 'amcharts-xy', 'https://cdn.amcharts.com/lib/5/xy.js', false, '5' );
    wp_register_script( 'amcharts-animated', 'https://cdn.amcharts.com/lib/5/themes/Animated.js', false, '5' );
    wp_register_script( 'lodash', 'https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js', false, '4.17.21' );
    wp_register_script( 'moment', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js', false, '2.29.4' );

    $gospel_ambition_handle = 'gospel_ambition_script_handle';
    wp_enqueue_script( $gospel_ambition_handle, trailingslashit( $plugin_dir_url ) . $gospel_ambition_js, [
        'jquery',
        'jquery-ui-core',
        'amcharts-index',
        'amcharts-xy',
        'amcharts-animated',
        'lodash',
        'moment'
    ], filemtime( trailingslashit( $plugin_dir_path ) . $gospel_ambition_js ), true );

    wp_localize_script( $gospel_ambition_handle, 'gospel_ambition_script_obj', [
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' )
    ] );
}
