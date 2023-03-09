<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_site_info', function( $stats ) {
    $stats['site_name'] = 'Zúme';
    $stats['icon'] = GO_Context_Switcher::plugin_url( '/assets/icons/zume-circle-logo.png' );

    return $stats;
}, 10, 1 );
