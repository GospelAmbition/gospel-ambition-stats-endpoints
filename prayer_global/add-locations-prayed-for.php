<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;
    /**
     * Counting distinct hashes to get the number of unique prayer warriors
     */
    $stats['locations_prayed_over'] = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->dt_reports WHERE type = 'prayer_app';" );

    return $stats;
}, 10, 1 );
