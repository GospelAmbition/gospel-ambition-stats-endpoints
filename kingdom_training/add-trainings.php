<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $courses_completed = $wpdb->get_var( "SELECT COUNT(*) FROM wp_learndash_user_activity WHERE `activity_completed` > 0" );

    $users_course_completed = $wpdb->get_var( "SELECT COUNT(DISTINCT(user_id)) FROM wp_learndash_user_activity WHERE `activity_completed` > 0" );


    $stats['trainees'] = [
        'label' => 'Lab M2M Trainees',
        'description' => 'Total number of trainees who have gone through the multi-day training on media-to-movements.',
        'value' => '321 (hard coded)',
        'public_stats' => true,
    ];

    $stats['sessions'] = [
        'label' => 'Training Sessions',
        'description' => 'Total number of online training sessions delivered primarily to unregistered users.',
        'value' => '',
        'public_stats' => true,
    ];

    $stats['registrations'] = [
        'label' => 'Training Sessions',
        'description' => 'Number of users who have registered.',
        'value' => $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users;" ) ?? '',
        'public_stats' => true,
    ];

    $stats['courses_complete'] = [
        'label' => 'Courses Completed',
        'description' => 'Total courses completed by users.',
        'value' => $courses_completed ?? '',
        'public_stats' => true,
    ];

    $stats['user_course_complete'] = [
        'label' => 'Users who Completed a Course',
        'description' => 'Total users who completed a course.',
        'value' => $users_course_completed ?? '',
        'public_stats' => true,
    ];


    return $stats;
}, 10, 1 );
