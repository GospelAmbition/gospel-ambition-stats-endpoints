<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    if ( !function_exists( 'p4m_get_all_campaigns' ) ){
        return $stats;
    }

    $pg_request = dt_cached_api_call( 'https://prayer.global/wp-json/go/v1/stats?', 'GET', [], HOUR_IN_SECONDS, true );
    $pg_data = json_decode( $pg_request, true );
    $pg_stats = $pg_data['stats'] ?? [];


    /**
     * Counting number of minutes of prayer as indicated by the value column
     */
    $stats['p4m_pg_prayer_time'] = [
        'label' => 'Prayer Time',
        'description' => 'Total time committed to pray for all past and upcoming prayer campaigns + Prayer.Global prayer time.',
        'value' => go_display_minutes( $stats['minutes_of_prayer']['value'] + $pg_stats['minutes_of_prayer']['value'] ?? 0 ),
        'public_stats' => true,
    ];

    $stats['pg_laps_completed'] = [
        'label' => 'Prayer.Global Laps Completed',
        'description' => 'Total number of Prayer.Global laps completed for all 4770 of the world’s states.',
        'value' => $pg_stats['laps_completed']['value'] ?? 0,
        'public_stats' => true,
    ];

    $stats['p4m_pg_prayer_warriors'] = [
        'label' => 'Intercessors',
        'description' => 'Total number of intercessors in the prayer campaigns plus Prayer.Global laps.',
        'value' => $stats['prayer_warriors']['value'] + $pg_stats['prayer_warriors']['value'] ?? 0,
        'public_stats' => true,
    ];


    return $stats;
}, 20, 1 );
