<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    /**
     * Get the current finished lap number from the options table
     */
    $option = get_option( 'pg_current_global_lap' );
    $current_lap_number = (int) $option['lap_number'];
    $laps_completed = $current_lap_number - 1;

    $stats['laps_completed'] = [
        'label' => 'Laps Completed',
        'description' => 'The total number of laps completed.',
        'value' => $laps_completed,
        'public_stats' => true,
    ];

    $stats['locations_completed_by_laps'] = [
        'label' => 'Locations Covered by Laps',
        'description' => 'The total number of locations covered in prayer from the previous laps.',
        'value' => $laps_completed * 4770,
    ];

    return $stats;
}, 10, 1 );
