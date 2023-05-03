<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $languages = zume_language_file();

    $stats['languages'] = [
        'label' => 'Languages',
        'description' => 'Total number of Zume.Training languages, spoken by billions.',
        'value' => count( $languages ) ?? 0,
        'public_stats' => true,
    ];

    return $stats;
}, 15, 1 );
