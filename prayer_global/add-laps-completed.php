<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    /**
     * Get the current finished lap number from the options table
     */
    $option = get_option( 'pg_current_global_lap' );
    $current_lap_number = (int) $option['lap_number'];

    $stats['laps_completed'] = $current_lap_number - 1;

    return $stats;
}, 10, 1 );
