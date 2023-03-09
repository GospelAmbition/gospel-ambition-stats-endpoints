<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    if ( !function_exists( 'p4m_get_all_campaigns' ) ){
        return $stats;
    }

    $campaigns = p4m_get_all_campaigns( true );

    /**
     * Counting number of minutes of prayer as indicated by the value column
     */
    $stats['minutes_of_prayer'] = [
        'label' => 'Total Prayer Time',
        'description' => 'Total time committed to pray for all past and upcoming campaigns.',
        'value' => 0,
        'public_stats' => true,
    ];

    $stats['campaigns'] = [
        'label' => 'Campaigns',
        'description' => 'Total number of campaigns.',
        'value' => count( $campaigns ),
        'public_stats' => true,
    ];

    $stats['prayer_warriors'] = [
        'label' => 'Campaign Intercessors',
        'description' => 'This number may be too high because some people may pray for multiple campaigns, or it may be too low because sometimes groups are praying together and we’re only counting them as individuals.',
        'value' => 0,
        'public_stats' => true,
    ];

    $stats['locations'] = [
        'label' => 'Campaign Countries',
        'description' => 'Countries with a campaign.',
        'value' => 0,
        'public_stats' => alse,
    ];

    $locations = [];
    foreach ( $campaigns as $campaign ){
        $stats['minutes_of_prayer']['value'] += $campaign['minutes_committed'];
        $stats['prayer_warriors']['value'] += $campaign['prayers_count'];
        foreach ( $campaign['location_grid'] ?? [] as $location ){
            if ( !in_array( $location['country_id'], $locations ) ){
                $locations[] = $location['country_id'];
            }
        }
    }
    $stats['locations']['value'] = count( $locations );

    return $stats;
}, 10, 1 );
