<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;

add_filter( 'go_stats_endpoint', function( $stats ) {
    global $wpdb;

    $courses_completed = $wpdb->get_var( 'SELECT COUNT(*) FROM wp_learndash_user_activity WHERE `activity_completed` > 0' );

    $users_course_completed = $wpdb->get_var( 'SELECT COUNT(DISTINCT(user_id)) FROM wp_learndash_user_activity WHERE `activity_completed` > 0' );

    $ga4_metrics = ga4_fetch_kingdom_training_metrics();

    $stats['introductions'] = [
        'label' => 'Introductions',
        'description' => 'Total number of people we’ve introduced to media-to-movements as a concept through various conferences, meetings, and events.',
        'value' => 'Thousands',
        'public_stats' => true,
    ];

    $stats['sessions'] = [
        'label' => 'Training Sessions',
        'description' => 'Total number of online training sessions delivered primarily to unregistered users.',
        'value' => $ga4_metrics['total_training_sessions'] ?? '7961 (March 2023)',
        'public_stats' => true,
    ];

    $stats['trainees'] = [
        'label' => 'Lab M2M Trainees',
        'description' => 'Total number of trainees who have gone through the multi-day training on media-to-movements.',
        'value' => '321 (March 2023)',
        'public_stats' => true,
    ];

    $stats['registrations'] = [
        'label' => 'Registrations',
        'description' => 'Number of users who have registered.',
        'value' => $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users;" ) ?? '',
        'public_stats' => true,
    ];

    $stats['courses_complete'] = [
        'label' => 'Course Completed',
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

function ga4_fetch_kingdom_training_metrics(){
    $metrics = [];
    $dependency_autoload_file = get_template_directory() . '/vendor/autoload.php';
    $credentials = get_option( 'ga_ga4_service_account_credentials', [] );
    $properties = get_option( 'ga_ga4_service_account_properties', [] );
    if ( !empty( $credentials ) && !empty( $properties['kingdom_training'] ) && file_exists( $dependency_autoload_file ) ){

        require_once $dependency_autoload_file;

        try{

            // Create a GA4 Analytic Data Client instance; based on loaded credentials.
            $client = new BetaAnalyticsDataClient( [
                'credentials' => $credentials
            ] );

            // Run training session report.
            $response = $client->runReport( [
                'property' => 'properties/' . $properties['kingdom_training'],
                'dateRanges' => [
                    new DateRange( [ // TODO: TBC - Date range to be made configurable or hardcoded?
                        'start_date' => '2023-03-01',
                        'end_date' => '2023-03-31',
                    ] ),
                ],
                'dimensions' => [ new Dimension(
                    [
                        'name' => 'pagePath',
                    ]
                ),
                ],
                'metrics' => [ new Metric(
                    [
                        'name' => 'engagedSessions',
                    ]
                )
                ]
            ] );

            // Calculate total training sessions within specified date range.
            $total_training_sessions = 0;
            foreach ( $response->getRows() ?? [] as $row ){
                $dimension = $row->getDimensionValues()[0]->getValue();
                $metric = $row->getMetricValues()[0]->getValue();

                // Only apply to course based sessions.
                if ( strpos( $dimension, 'course/' ) !== false ){
                    $total_training_sessions += (int)$metric;
                }
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
