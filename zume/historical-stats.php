<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Zume_Historical_Stats {
    
    public function __construct() {
        // AJAX hooks for admin interface
        add_action( 'wp_ajax_run_zume_historical_stats', [ $this, 'ajax_run_historical_stats' ] );
    }

    /**
     * AJAX handler for running historical stats
     */
    public function ajax_run_historical_stats() {
        // Security check
        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( json_encode( [ 'error' => 'Permission denied' ] ) );
        }
        
        if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'zume_historical_stats_nonce' ) ) {
            wp_die( json_encode( [ 'error' => 'Invalid nonce' ] ) );
        }

        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );

        if ( empty( $start_date ) || empty( $end_date ) ) {
            wp_die( json_encode( [ 'error' => 'Start date and end date are required' ] ) );
        }

        // Validate date format
        $start_timestamp = strtotime( $start_date );
        $end_timestamp = strtotime( $end_date );

        if ( ! $start_timestamp || ! $end_timestamp ) {
            wp_die( json_encode( [ 'error' => 'Invalid date format' ] ) );
        }

        if ( $start_timestamp > $end_timestamp ) {
            wp_die( json_encode( [ 'error' => 'Start date must be before or equal to end date' ] ) );
        }

        // Run the historical stats processing
        $results = $this->process_historical_stats( $start_date, $end_date );
        
        wp_die( json_encode( $results ) );
    }

    /**
     * Process historical stats for a date range
     * 
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function process_historical_stats( $start_date, $end_date ) {
        $api_key = get_option( 'go_stats_key' );
        if ( empty( $api_key ) ) {
            return [ 'error' => 'API key not found. Please set go_stats_key option.' ];
        }

        $results = [];
        $processed = 0;
        $errors = 0;

        $current_date = $start_date;
        while ( strtotime( $current_date ) <= strtotime( $end_date ) ) {
            error_log( "Zume Historical Stats: Processing date {$current_date}" );
            
            $metrics = $this->calculate_historical_metrics_for_date( $current_date );

            dt_write_log( $metrics );
            
            if ( $metrics === false ) {
                $results[] = [
                    'date' => $current_date,
                    'status' => 'error',
                    'message' => 'Failed to calculate metrics'
                ];
                $errors++;
            } else {
                $payload = [
                    'project_id' => 'zume',
                    'stat_date' => $current_date,
                    'metrics' => $metrics
                ];

                $response = $this->send_api_request( $api_key, $payload );
                
                if ( is_wp_error( $response ) ) {
                    $results[] = [
                        'date' => $current_date,
                        'status' => 'error',
                        'message' => $response->get_error_message()
                    ];
                    $errors++;
                    error_log( "Zume Historical Stats: API error for {$current_date}: " . $response->get_error_message() );
                } else {
                    $response_code = wp_remote_retrieve_response_code( $response );
                    if ( $response_code === 200 || $response_code === 201 ) {
                        $results[] = [
                            'date' => $current_date,
                            'status' => 'success',
                            'metrics' => $metrics
                        ];
                        $processed++;
                        error_log( "Zume Historical Stats: Successfully sent data for {$current_date}" );
                    } else {
                        $results[] = [
                            'date' => $current_date,
                            'status' => 'error',
                            'message' => "API returned status code {$response_code}"
                        ];
                        $errors++;
                        error_log( "Zume Historical Stats: API status error for {$current_date}: {$response_code}" );
                    }
                }
            }

            // Add a small delay to avoid overwhelming the API
            // usleep( 500000 ); // 0.5 second delay

            $current_date = date( 'Y-m-d', strtotime( $current_date . ' +1 day' ) );
        }

        return [
            'total_dates' => count( $results ),
            'processed' => $processed,
            'errors' => $errors,
            'results' => $results
        ];
    }

    /**
     * Calculate historical metrics for a specific date
     * 
     * @param string $date
     * @return array|false
     */
    private function calculate_historical_metrics_for_date( $date ) {
        global $wpdb;

        // Calculate end of day timestamp for the date
        $date_end = $date;

        // Total registered users up to this date
        $users_sql = $wpdb->prepare( "
            SELECT COUNT(*) as total_users
            FROM {$wpdb->users}
            WHERE user_registered <= %s
        ", $date_end );
        $total_users = $wpdb->get_var( $users_sql );

        if ( $total_users === null ) {
            return false;
        }

        return [
            'registered_users' => (int) $total_users,
        ];
    }

    /**
     * Send API request to stats endpoint
     * 
     * @param string $api_key
     * @param array $payload
     * @return array|WP_Error
     */
    private function send_api_request( $api_key, $payload ) {
        $url = 'https://stats.gospelambition.org/api/metrics';
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key
            ],
            'body' => json_encode( $payload ),
            'timeout' => 30
        ];

        return wp_remote_request( $url, $args );
    }
}

// Initialize the class
new Zume_Historical_Stats();