<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    /**
     * wp_3_postmeta is the table where the group_type is stored.
     */

    $stats['churches_total'] = [
        'label' => 'Total Churches',
        'description' => 'The total number of churches reported in the Zume.Training/coaching system.',
        'value' => $wpdb->get_var( "SELECT COUNT(*) FROM wp_3_postmeta as pm WHERE meta_key = 'group_type' AND meta_value = 'church'" ),
    ];

    return $stats;
}, 10, 1 );
