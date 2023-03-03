<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $instances_stats = [];
    if ( method_exists( 'DT_Usage_Telemetry', 'get_stats' ) ) {
        $instances_stats = DT_Usage_Telemetry::get_stats( false );
    }

    $github = get_transient( 'dt_github_stats' );
    if ( empty( $github ) ){
        $github = [ 'contributors' => 0, 'stars' => 0, 'forks' => 0 ];

        $github_contributors = wp_remote_get( 'https://api.github.com/repos/DiscipleTools/disciple-tools-theme/contributors?per_page=100' );
        $github_contributors = json_decode( wp_remote_retrieve_body( $github_contributors ), true );
        $github['contributors'] = count( $github_contributors ) -3;
        set_transient( 'dt_github_stats', $github, DAY_IN_SECONDS );
    }


    $translations_count = get_transient( 'dt_translations_count' );
    if ( empty( $translations_count ) ){
        $translations_response = wp_remote_get( 'https://translate.disciple.tools/api/components/disciple-tools/disciple-tools-theme/statistics/' );
        $translations = json_decode( wp_remote_retrieve_body( $translations_response ), true );
        $translations_count = $translations['count'] ?? 0;
        set_transient( 'dt_translations_count', $translations_count, DAY_IN_SECONDS );
    }


    $stats['all_time_instances'] = [
        'label' => 'All Time Sites',
        'description' => 'Total number of sites that have ever been created',
        'value' => $instances_stats['all_time']['sites'] ?? 0,
    ];
    $stats['total_instances'] = [
        'label' => 'Online Sites',
        'description' => 'Number of online sites in the last 30 days',
        'value' => $instances_stats['total']['sites'],
    ];
    $stats['active_instances'] = [
        'label' => 'Active Sites',
        'description' => 'Total known installs of Disciple.Tools with active users',
        'value' => $instances_stats['active']['sites'],
    ];
    $stats['total_domains'] = [
        'label' => 'Online Domains',
        'description' => 'Number of online domains in the last 30 days',
        'value' => $instances_stats['total']['domains'],
    ];
    $stats['active_domains'] = [
        'label' => 'Active Domains',
        'description' => 'Total known active domains hosting Disciple.Tools installs',
        'value' => $instances_stats['active']['domains'],
    ];
    $stats['theme_contributors'] = [
        'label' => 'Contributors',
        'description' => 'Total coders who have contributed to creating the Disciple.Tools theme',
        'value' => $github['contributors'],
    ];
    $stats['languages'] = [
        'label' => 'Languages',
        'description' => 'Total languages the core of Disciple.Tools is translated into',
        'value' => $translations_count ?? 0,
    ];



    return $stats;
}, 10, 1 );
