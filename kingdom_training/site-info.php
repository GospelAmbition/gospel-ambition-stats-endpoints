<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_site_info', function( $stats ) {
    $stats['site_name'] = get_bloginfo();
    $stats['icon'] = GO_Context_Switcher::plugin_url( '/assets/icons/kt-circle-logo.png' );
    $stats['site_description'] = 'https//kingdom.training equips disciple makers to accelerate movements with an end-to-end media strategy.';

    return $stats;
}, 10, 1 );
