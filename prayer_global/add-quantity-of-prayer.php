<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;
    /**
     * Counting number of minutes of prayer as indicated by the value column
     */
    $stats['minutes_of_prayer'] = $wpdb->get_var( "SELECT SUM(value) FROM $wpdb->dt_reports WHERE type = 'prayer_app';" );

    return $stats;
}, 10, 1 );
