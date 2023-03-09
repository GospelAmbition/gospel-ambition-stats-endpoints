<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $stats['trainee_group_leaders_total'] = [
        'label' => 'Group Leaders',
        'description' => 'Total number of user accounts that have recorded a completed Zume.Training course.',
        'value' => $wpdb->get_var( "SELECT COUNT( DISTINCT(user_id) ) FROM wp_usermeta WHERE meta_key = 'zume_training_complete';" ),
        'public_stats' => true,
    ];

    return $stats;
}, 10, 1 );
