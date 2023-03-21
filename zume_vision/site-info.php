<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_site_info', function( $stats ) {
    $stats['site_name'] = 'Zúme';
    $stats['icon'] = GO_Context_Switcher::plugin_url( '/assets/icons/zume-circle-logo.png' );
    $stats['site_description'] = 'https://zume.training is an online and in-life learning experience designed for small groups that follow Jesus to learn how to obey His Great Commission and make disciples who multiply.';

    return $stats;
}, 10, 1 );
