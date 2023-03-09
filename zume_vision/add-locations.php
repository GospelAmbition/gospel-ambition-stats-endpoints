<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;


    $stats['countries_online'] = [
        'label' => 'Countries & Territories',
        'description' => 'Total number of countries and sovereign territories that have accessed Zume.Training online.',
        'value' => '',
        'public_stats' => true,
    ];

    $stats['countries_with_groups'] = [
        'label' => 'Countries & Territories with Groups',
        'description' => 'Total number of countries and sovereign territories that have had groups go through Zume.Training',
        'value' => '',
        'public_stats' => true,
    ];


    return $stats;
}, 10, 1 );
