<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $stats['training_groups_formed'] = [
        'label' => 'Training Groups Formed with Zume.Training System',
        'description' => 'Total number of trainings formed with the Zume.Training system. This does not include groups that reused the same training group, or groups that were formed but did not use the Zume.Training system.',
        'value' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key LIKE %s;", $wpdb->esc_like( 'zume_group' ) . '%' ) ),
    ];

    return $stats;
}, 10, 1 );
