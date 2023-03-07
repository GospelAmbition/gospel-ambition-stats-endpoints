<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $stats['registrations_total'] = [
        'label' => 'Total Registrations',
        'description' => 'The total number of registrations to the Zume.Training system.',
        'value' => $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users;" ),
    ];

    $twelve_months_ago = gmdate( 'Y-m-d', strtotime( '-12 months' ) );
    $stats['registrations_last_12_months'] = [
        'label' => 'Registrations in the last 12 months',
        'description' => 'The number of registrations who have registered in the last 12 months to the Zume.Training system.',
        'value' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE user_registered > %s;", $twelve_months_ago ) ),
    ];

    $thirty_days_ago = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
    $stats['registrations_last_30_days'] = [
        'label' => 'Registrations in the last 30 days',
        'description' => 'The number of registrations who have registered in the last 30 days to the Zume.Training system.',
        'value' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE user_registered > %s;", $thirty_days_ago ) ),
    ];

    $stats['registrations_by_month'] = [
        'label' => 'Registrations by Month',
        'description' => 'The total number of registrations by month.',
        'value' => $wpdb->get_results( "SELECT DATE_FORMAT(user_registered, '%Y-%m') as month, DATE_FORMAT(user_registered, '%M %Y') as month_formatted, COUNT(ID) as value FROM $wpdb->users GROUP BY DATE_FORMAT(user_registered, '%Y-%m'), DATE_FORMAT(user_registered, '%M %Y') ORDER BY DATE_FORMAT(user_registered, '%Y-%m') DESC;", ARRAY_A ),
    ];

    //    $stats['registrations_by_language'] = [
    //        'label' => 'Registrations by Language',
    //        'description' => 'The total number of registrations by language.',
    //        'value' => $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users;" ),
    //    ];

    return $stats;
}, 10, 1 );
