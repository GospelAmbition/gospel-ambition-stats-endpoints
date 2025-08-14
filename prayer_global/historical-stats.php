<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class PG_Historical_Stats {
    
    public function __construct() {
        // AJAX hooks for admin interface
        add_action( 'wp_ajax_run_pg_historical_stats', [ $this, 'ajax_run_historical_stats' ] );
    }

    /**
     * AJAX handler for running historical stats
     */
    public function ajax_run_historical_stats() {
        // Security check
        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( json_encode( [ 'error' => 'Permission denied' ] ) );
        }
        
        if ( ! wp_verify_nonce( $_POST['_ajax_nonce'], 'pg_historical_stats_nonce' ) ) {
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
            error_log( "PG Historical Stats: Processing date {$current_date}" );
            
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
                    'project_id' => 'prayer_global',
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
                    error_log( "PG Historical Stats: API error for {$current_date}: " . $response->get_error_message() );
                } else {
                    $response_code = wp_remote_retrieve_response_code( $response );
                    if ( $response_code === 200 || $response_code === 201 ) {
                        $results[] = [
                            'date' => $current_date,
                            'status' => 'success',
                            'metrics' => $metrics
                        ];
                        $processed++;
                        error_log( "PG Historical Stats: Successfully sent data for {$current_date}" );
                    } else {
                        $results[] = [
                            'date' => $current_date,
                            'status' => 'error',
                            'message' => "API returned status code {$response_code}"
                        ];
                        $errors++;
                        error_log( "PG Historical Stats: API status error for {$current_date}: {$response_code}" );
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
        $timestamp_end = strtotime( $date_end );
        $timestamp_start = $timestamp_end - 1 * DAY_IN_SECONDS - 1;

        // 1. Prayer Warriors - distinct hashes up to this date
        $prayer_warriors_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as prayer_warriors
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp <= %d
        ", $timestamp_end );
        $prayer_warriors = $wpdb->get_var( $prayer_warriors_sql );

        // 2. Minutes of Prayer - sum of value up to this date
        $minutes_sql = $wpdb->prepare( "
            SELECT SUM(value) as minutes
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp <= %d
        ", $timestamp_end );
        $minutes_of_prayer = $wpdb->get_var( $minutes_sql );

        // 3. Total Prayers (over specific locations) up to this date
        $prayers_sql = $wpdb->prepare( "
            SELECT COUNT(*) as prayers
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp <= %d
        ", $timestamp_end );
        $total_prayers = $wpdb->get_var( $prayers_sql );

        // 4. Laps completed - use lap_completed logs up to this date for global relay
        $global_post_id = 2128;
        $laps_completed = 0;
        $laps_completed = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT count(*)
            FROM {$wpdb->dt_reports}
            WHERE type = 'lap_completed'
            AND post_type = 'pg_relays'
            AND post_id = %d
            AND timestamp <= %d",
            $global_post_id, $timestamp_end
        ) );


        // 6. Total registered users up to this date
        $users_sql = $wpdb->prepare( "
            SELECT COUNT(*) as total_users
            FROM {$wpdb->users}
            WHERE user_registered <= %s
        ", $date_end );
        $total_users = $wpdb->get_var( $users_sql );

        // 7. Custom laps completed - count lap_completed logs up to this date for non-global relays
        $custom_laps_completed = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(value)
            FROM {$wpdb->dt_reports}
            WHERE type = 'lap_completed'
            AND post_type = 'pg_relays'
            AND post_id != %d
            AND timestamp <= %d",
            $global_post_id, $timestamp_end
        ) );

        // 8. Historical Daily active users metrics (for this specific date)        
        // Daily recurring active users - users active on this date who were also active before - SUPER OPTIMIZED
        $daily_recurring_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT recent.hash) as daily_recurring_active_users
            FROM {$wpdb->dt_reports} recent
            WHERE recent.type = 'prayer_app'
            AND recent.timestamp >= %d
            AND recent.timestamp <= %d
            AND EXISTS (
                SELECT 1 FROM {$wpdb->dt_reports} previous
                WHERE previous.hash = recent.hash
                AND previous.type = 'prayer_app'
                AND previous.timestamp < %d
                LIMIT 1
            )
        ", $timestamp_start, $timestamp_end, $timestamp_start );
        $daily_recurring_active_users = (int) $wpdb->get_var( $daily_recurring_sql );

        // Daily new active users - users active on this date for the first time - SUPER OPTIMIZED
        $daily_new_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as daily_new_active_users
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
            AND hash IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->dt_reports} previous
                WHERE previous.hash = {$wpdb->dt_reports}.hash
                AND previous.type = 'prayer_app'
                AND previous.timestamp < %d
                LIMIT 1
            )
        ", $timestamp_start, $timestamp_end, $timestamp_start );
        $daily_new_active_users = (int) $wpdb->get_var( $daily_new_sql );

        // 9. Historical Weekly active users metrics (7 days ending on this date)
        $week_before = $timestamp_start - (6 * 24 * 60 * 60);
        
        // Weekly recurring active users
        $weekly_recurring_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT recent.hash) as weekly_recurring_active_users
            FROM {$wpdb->dt_reports} recent
            WHERE recent.type = 'prayer_app'
            AND recent.timestamp >= %d
            AND recent.timestamp <= %d
            AND EXISTS (
                SELECT 1 FROM {$wpdb->dt_reports} previous
                WHERE previous.hash = recent.hash
                AND previous.type = 'prayer_app'
                AND previous.timestamp < %d
            )
        ", $week_before, $timestamp_end, $week_before );
        $weekly_recurring_active_users = (int) $wpdb->get_var( $weekly_recurring_sql );

        // Weekly new active users - SUPER OPTIMIZED with EXISTS and LIMIT
        $weekly_new_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as weekly_new_active_users
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
            AND hash IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->dt_reports} previous
                WHERE previous.hash = {$wpdb->dt_reports}.hash
                AND previous.type = 'prayer_app'
                AND previous.timestamp < %d
                LIMIT 1
            )
        ", $week_before, $timestamp_end, $week_before );
        $weekly_new_active_users = (int) $wpdb->get_var( $weekly_new_sql );

        // 10. Historical Monthly active users metrics (30 days ending on this date)
        $month_before = $timestamp_start - (29 * 24 * 60 * 60);
        
        // Monthly recurring active users
        $monthly_recurring_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT recent.hash) as monthly_recurring_active_users
            FROM {$wpdb->dt_reports} recent
            WHERE recent.type = 'prayer_app'
            AND recent.timestamp >= %d
            AND recent.timestamp <= %d
            AND EXISTS (
                SELECT 1 FROM {$wpdb->dt_reports} previous
                WHERE previous.hash = recent.hash
                AND previous.type = 'prayer_app'
                AND previous.timestamp < %d
            )
        ", $month_before, $timestamp_end, $month_before );
        $monthly_recurring_active_users = (int) $wpdb->get_var( $monthly_recurring_sql );

        // Monthly new active users - SUPER OPTIMIZED with EXISTS and LIMIT
        $monthly_new_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as monthly_new_active_users
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
            AND hash IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->dt_reports} previous
                WHERE previous.hash = {$wpdb->dt_reports}.hash
                AND previous.type = 'prayer_app'
                AND previous.timestamp < %d
                LIMIT 1
            )
        ", $month_before, $timestamp_end, $month_before );
        $monthly_new_active_users = (int) $wpdb->get_var( $monthly_new_sql );

        // 11. Historical Average prayers per session (prayers on this date / users on this date)
        $avg_prayers_per_session_sql = $wpdb->prepare( "
            SELECT 
                CASE 
                    WHEN COUNT(DISTINCT hash) = 0 THEN 0
                    ELSE ROUND(COUNT(*) / COUNT(DISTINCT hash), 2)
                END as avg_prayers_per_session
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $timestamp_start, $timestamp_end );
        $avg_prayers_per_session = (float) $wpdb->get_var( $avg_prayers_per_session_sql );

        // 12. Historical Users who prayed on this date
        $users_24h_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as users_24h
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $timestamp_start, $timestamp_end );
        $users_24h = (int) $wpdb->get_var( $users_24h_sql );

        // 13. Historical Number of prayers on this date
        $prayers_24h_sql = $wpdb->prepare( "
            SELECT COUNT(*) as prayers_24h
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $timestamp_start, $timestamp_end );
        $prayers_24h = (int) $wpdb->get_var( $prayers_24h_sql );

        // 14. Historical Minutes of prayer on this date
        $minutes_24h_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(value), 0) as minutes_24h
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $timestamp_start, $timestamp_end );
        $minutes_24h = (int) $wpdb->get_var( $minutes_24h_sql );


        if ( $prayer_warriors === null || $minutes_of_prayer === null || $total_prayers === null ) {
            return false;
        }


        return [
            'prayer_warriors' => (int) $prayer_warriors,
            'minutes_of_prayer' => (int) $minutes_of_prayer,
            'total_prayers' => (int) $total_prayers,
            'global_laps_completed' => (int) $laps_completed,
            'registered_users' => (int) $total_users,
            'custom_laps_completed' => (int) $custom_laps_completed,
            'day_returning_users' => $daily_recurring_active_users,
            'day_new_users' => $daily_new_active_users,
            'week_returning_users' => $weekly_recurring_active_users,
            'week_new_users' => $weekly_new_active_users,
            'month_returning_users' => $monthly_recurring_active_users,
            'month_new_users' => $monthly_new_active_users,
            'avg_prayers_per_session' => $avg_prayers_per_session,
            'day_users' => $users_24h,
            'day_prayers' => $prayers_24h,
            'day_minutes_prayer' => $minutes_24h,
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
new PG_Historical_Stats();

