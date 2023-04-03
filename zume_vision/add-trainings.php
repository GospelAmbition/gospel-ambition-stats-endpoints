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
    $metrics = [];
    $dependency_autoload_file = get_template_directory() . '/vendor/autoload.php';
    $credentials = get_option( 'ga_ga4_service_account_credentials', [] );
    $properties = get_option( 'ga_ga4_service_account_properties', [] );
    if ( !empty( $credentials ) && !empty( $properties['zume_training'] ) && file_exists( $dependency_autoload_file )  ){

        require_once $dependency_autoload_file;

        try{

            // Create a GA4 Analytic Data Client instance; based on loaded credentials.
            $client = new BetaAnalyticsDataClient( [
                'credentials' => $credentials
            ] );

            // Run training session report.
            $response = $client->runReport( [
                'property' => 'properties/' . $properties['zume_training'],
                'dateRanges' => [
                    new DateRange( [ // TODO: TBC - Date range to be made configurable or hardcoded?
                        'start_date' => '2023-03-01',
                        'end_date' => '2023-03-31',
                    ] ),
                ],
                'dimensions' => [ new Dimension(
                    [
                        'name' => 'unifiedScreenClass', // TODO: TBC - Correct Dimension?
                    ]
                ),
                ],
                'metrics' => [ new Metric(
                    [
                        'name' => 'activeUsers', // TODO: TBC - Correct Metric?
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
            error_log( print_r( $e, true ) );
        }

    } else{
        error_log( 'Unable to locate GA4 Service Account Credentials or Properties...!' );
    }

    return $metrics;
}
