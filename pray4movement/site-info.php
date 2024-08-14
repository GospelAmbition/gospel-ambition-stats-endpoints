<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_site_info', function( $stats ) {
    $stats['site_name'] = get_bloginfo();
    $stats['icon'] = GO_Context_Switcher::plugin_url( '/assets/icons/p4m-circle-logo.png' );
    $stats['site_description'] = 'https://prayer.tools empowers strategic prayer for church multiplication movements.';

    return $stats;
}, 10, 1 );
