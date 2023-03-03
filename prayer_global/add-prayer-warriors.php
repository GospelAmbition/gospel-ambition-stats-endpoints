<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;
    /**
     * Counting distinct hashes to get the number of unique prayer warriors
     */
    $stats['prayer_warriors'] = [
        'label' => 'Total Prayer Warriors',
        'description' => 'The total number of unique prayer warriors.',
        'value' => $wpdb->get_var( "SELECT COUNT( DISTINCT hash) FROM $wpdb->dt_reports WHERE type = 'prayer_app';" ),
        'public_stats' => true,
    ];

    return $stats;
}, 10, 1 );
