<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_site_info', function( $stats ) {
    $stats['site_name'] = get_bloginfo();
    $stats['icon'] = GO_Context_Switcher::plugin_url( '/assets/icons/dt-circle-logo.png' );
    $stats['site_description'] = 'https://disciple.tools is software boosts collaboration, clarity, and accountability for disciple and church multiplication movements.';

    return $stats;
}, 10, 1 );
