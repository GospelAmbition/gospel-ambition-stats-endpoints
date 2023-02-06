<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    $stats = [
        'quantity_of_prayer' => 0, // @todo make real
    ];
    return $stats;
}, 10, 1 );
