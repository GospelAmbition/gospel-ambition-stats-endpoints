<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;
    /**
     * Counting number of minutes of prayer as indicated by the value column
     */
    $minutes = $wpdb->get_var( "SELECT SUM(value) FROM $wpdb->dt_reports WHERE type = 'prayer_app';" );
    $stats['minutes_of_prayer'] = [
        'label' => 'Total Minutes of Prayer',
        'description' => 'The total number of minutes of prayer regardless of location.',
        'value' => $minutes,
    ];
    $stats['prayer_time'] = [
        'label' => 'Total Prayer Time',
        'description' => 'The total time of prayer regardless of location.',
        'value' => go_display_minutes( $minutes ),
        'public_stats' => true,
    ];

    $stats['prayers_over_specific_locations'] = [
        'label' => 'Total Number of Prayers Covering Specific Locations',
        'description' => 'This total represents the number of times a prayer has been placed over a location.',
        'value' => $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->dt_reports WHERE type = 'prayer_app';" ),
    ];

    return $stats;
}, 10, 1 );
