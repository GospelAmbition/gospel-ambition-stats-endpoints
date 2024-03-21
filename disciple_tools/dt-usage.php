<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

add_filter( 'go_stats_endpoint', function( $stats ) {
    $use_cache = !isset( $_GET['nocache'] );

    $instances_stats = [];
    if ( method_exists( 'DT_Usage_Telemetry', 'get_stats' ) ) {
        $instances_stats = DT_Usage_Telemetry::get_stats( $use_cache );
    }

    $github = get_transient( 'dt_github_stats' );
    if ( empty( $github ) || !$use_cache ){
        $github = [ 'contributors' => 0, 'stars' => 0, 'forks' => 0 ];

        $github_contributors = wp_remote_get( 'https://api.github.com/repos/DiscipleTools/disciple-tools-theme/contributors?per_page=100' );
        $github_contributors = json_decode( wp_remote_retrieve_body( $github_contributors ), true );
        $github['contributors'] = count( $github_contributors ) -3;
        set_transient( 'dt_github_stats', $github, DAY_IN_SECONDS );
    }

    function get_weblate_with_pagination( $data, $url ){
        $response = wp_remote_get( $url );
        $response = json_decode( wp_remote_retrieve_body( $response ), true );
        $data = array_merge( $data, $response['results'] );
        if ( !empty( $response['next'] ) ){
            $data = get_weblate_with_pagination( $data, $response['next'] );
        }
        return $data;
    }


    $translations_count = get_transient( 'dt_translations_count' );
    if ( empty( $translations_count ) || !$use_cache ){
        $translations_url = 'https://translate.disciple.tools/api/components/disciple-tools/disciple-tools-theme/statistics/';
        $translations = get_weblate_with_pagination( [], $translations_url );
        $over_80_percent = array_filter( $translations, function( $translation ){
            return $translation['translated_percent'] >= 80;
        });
        $translations_count = count( $over_80_percent ) ?? 0;
        set_transient( 'dt_translations_count', $translations_count, DAY_IN_SECONDS );
    }

    $stats['total_users'] = [
        'label' => 'Total Users',
        'description' => 'Total number of users',
        'value' => $instances_stats['total']['total_users'] ?? 0,
        'icon' => 'mdi mdi-account',
    ];

    $stats['active_users'] = [
        'label' => 'Total Users',
        'description' => 'Active number of users',
        'value' => $instances_stats['total']['active_users'] ?? 0,
        'icon' => 'mdi mdi-account',
    ];

    $stats['all_time_instances'] = [
        'label' => 'All Time Sites',
        'description' => 'Total number of sites that have ever been created.',
        'value' => $instances_stats['all_time']['sites'] ?? 0,
        'icon' => 'mdi mdi-web',
    ];
    $stats['total_instances'] = [
        'label' => 'Online Sites',
        'description' => 'Number of online sites in the last 30 days.',
        'value' => $instances_stats['total']['sites'],
        'icon' => 'mdi mdi-web',
    ];
    $stats['active_instances'] = [
        'label' => 'Active Sites',
        'description' => 'Total known installs of Disciple.Tools with active users.',
        'value' => $instances_stats['active']['sites'],
        'public_stats' => true,
        'icon' => 'mdi mdi-web'
    ];
    $stats['total_domains'] = [
        'label' => 'Online Domains',
        'description' => 'Number of online domains in the last 30 days.',
        'value' => $instances_stats['total']['domains'],
        'icon' => 'mdi mdi-monitor-cellphone',
    ];
    $stats['active_domains'] = [
        'label' => 'Active Domains',
        'description' => 'Total known active domains hosting Disciple.Tools installs.',
        'value' => $instances_stats['active']['domains'],
        'public_stats' => true,
        'icon' => 'mdi mdi-monitor-cellphone',
    ];
    $stats['theme_contributors'] = [
        'label' => 'Contributors',
        'description' => 'Total coders who have contributed to creating the Disciple.Tools theme.',
        'value' => $github['contributors'],
        'public_stats' => true,
        'icon' => 'mdi mdi-xml',
    ];
    $stats['languages'] = [
        'label' => 'Languages',
        'description' => 'Total languages the core of Disciple.Tools is available in with translations over 80% complete.',
        'value' => $translations_count ?? 0,
        'public_stats' => true,
        'icon' => 'mdi mdi-translate',
    ];
    $stats['kingdom_savings'] = [
        'label' => 'Kingdom Savings',
        'description' => 'Cost Difference between Disciple.Tools (hosting) and two prominent alternative softwares for 1000 users.',
        'value' => '$120,000 to $780,000 annually',
        'public_stats' => true,
        'icon' => 'mdi mdi-hand-coin',
    ];



    return $stats;
}, 10, 1 );
