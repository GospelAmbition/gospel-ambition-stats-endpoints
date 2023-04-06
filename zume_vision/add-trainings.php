<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $ga4_metrics = ga4_fetch_zume_training_metrics();

    $stats['training_groups_formed'] = [
        'label' => 'Groups Formed',
        'description' => 'Total number of registered training groups (many of which are reused by trainers) in Zume.Training.',
        'value' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key LIKE %s;", $wpdb->esc_like( 'zume_group' ) . '%' ) ),
        'public_stats' => true,
    ];

    $stats['training_sessions'] = [
        'label' => 'Training Sessions',
        'description' => 'Total number of online training sessions delivered primarily to unregistered users.',
        'value' => $ga4_metrics['total_training_sessions'] ?? '929,000 (March 2023)',
        'public_stats' => true,
    ];

    return $stats;
}, 9, 1 );

function ga4_fetch_zume_training_metrics(){
    $site_key = 'zume_training';
    $metrics = [];
    $dependency_autoload_file = get_template_directory() . '/vendor/autoload.php';
    $credentials = get_option( 'ga_ga4_service_accounts', [] )[$site_key]['credentials'] ?? [];
    $properties = get_option( 'ga_ga4_service_account_properties', [] );
    $date_ranges = get_option( 'ga_ga4_service_account_date_ranges', [] );
    if ( !empty( $credentials ) && !empty( $properties[$site_key] ) && file_exists( $dependency_autoload_file )  ){

        require_once $dependency_autoload_file;

        try{

            // Ensure to decode private_key.
            $credentials['private_key'] = base64_decode( $credentials['private_key'] );

            // Create a GA4 Analytic Data Client instance; based on loaded credentials.
            $client = new BetaAnalyticsDataClient( [
                'credentials' => $credentials
            ] );

            // Run training session report.
            $response = $client->runReport( [
                'property' => 'properties/' . $properties[$site_key],
                'dateRanges' => [
                    new DateRange( [
                        'start_date' => $date_ranges[$site_key]['start_date'] ?? date( 'Y-m-d', time() ),
                        'end_date' => $date_ranges[$site_key]['end_date'] ?? date( 'Y-m-d', time() )
                    ] ),
                ],
                'dimensions' => [ new Dimension(
                    [
                        'name' => 'pagePath'
                    ]
                ),
                ],
                'metrics' => [ new Metric(
                    [
                        'name' => 'engagedSessions'
                    ]
                )
                ]
            ] );

            // Calculate total active users count for specific date range.
            $total_training_sessions = 0;
            foreach ( $response->getRows() ?? [] as $row ){
                $total_training_sessions += (int) $row->getMetricValues()[0]->getValue();
            }

            $metrics['total_training_sessions'] = $total_training_sessions;

        } catch (Exception $e){
            error_log( print_r( $e->getMessage(), true ) );
        }

    } else{
        error_log( 'Unable to locate GA4 Service Account Credentials or Properties...!' );
    }

    return $metrics;
}
