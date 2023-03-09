<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $stats['training_groups_formed'] = [
        'label' => 'Groups Formed',
        'description' => 'Total number of registered training groups (many of which are reused by trainers) in Zume.Training.',
        'value' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key LIKE %s;", $wpdb->esc_like( 'zume_group' ) . '%' ) ),
        'public_stats' => true,
    ];

    $stats['training_sessions'] = [
        'label' => 'Training Sessions',
        'description' => 'Total number of online training sessions delivered primarily to unregistered users.',
        'value' => '',
        'public_stats' => true,
    ];

    return $stats;
}, 9, 1 );
