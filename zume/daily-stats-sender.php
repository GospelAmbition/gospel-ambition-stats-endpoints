<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class Zume_Daily_Stats_Sender {

    public function __construct() {
        // Hook to schedule the cron job when the plugin is activated
        add_action( 'wp', [ $this, 'schedule_daily_stats' ] );
        
        // Hook for the actual cron job execution
        add_action( 'zume_send_daily_stats', [ $this, 'send_daily_stats' ] );
        
        // Clean up scheduled event on deactivation
        register_deactivation_hook( __FILE__, [ $this, 'clear_scheduled_hook' ] );
    }

    /**
     * Schedule the daily stats cron job if it's not already scheduled
     */
    public function schedule_daily_stats() {
        if ( ! wp_next_scheduled( 'zume_send_daily_stats' ) ) {
            // Schedule to run daily at 2:00 AM
            wp_schedule_event( strtotime( 'tomorrow 2:00 AM' ), 'daily', 'zume_send_daily_stats' );
        }
    }

    /**
     * Clear the scheduled hook
     */
    public function clear_scheduled_hook() {
        $timestamp = wp_next_scheduled( 'zume_send_daily_stats' );
        wp_unschedule_event( $timestamp, 'zume_send_daily_stats' );
    }

    /**
     * Send daily stats to the API
     */
    public function send_daily_stats() {
        // Get the API key from wp_options
        $api_key = get_option( 'go_stats_key' );
        if ( empty( $api_key ) ) {
            error_log( 'Zume Daily Stats: API key not found in go_stats_key option' );
            return;
        }

        // Calculate metrics
        $metrics = $this->calculate_metrics();
        if ( empty( $metrics ) ) {
            error_log( 'Zume Daily Stats: Failed to calculate metrics' );
            return;
        }

        // Prepare the payload
        $payload = [
            'project_id' => 'zume' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? '_dev' : '' ),
            'stat_date' => date( 'Y-m-d' ), // YYYY-MM-DD format
            'metrics' => $metrics
        ];

        // Send the API request
        $response = $this->send_api_request( $api_key, $payload );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'Zume Daily Stats: API request failed - ' . $response->get_error_message() );
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            if ( $response_code === 200 || $response_code === 201 ) {
                error_log( 'Zume Daily Stats: Daily stats sent successfully' );
            } else {
                error_log( 'Zume Daily Stats: API request failed with response code ' . $response_code );
            }
        }
    }

    /**
     * Calculate the required metrics using direct SQL queries
     * 
     * @return array
     */
    private function calculate_metrics() {
        global $wpdb;

        // Total registered users
        $users_sql = "
            SELECT COUNT(*) as total_users
            FROM {$wpdb->users}
        ";
        $total_users = $wpdb->get_var( $users_sql );

        // Count enabled languages (version 5 ready)
        $enabled_languages = zume_languages();
        $languages_enabled_count = count( $enabled_languages );

        // Calculate active users based on dt_reports activity
        $seven_days_ago = time() - (7 * 24 * 60 * 60);
        $thirty_days_ago = time() - (30 * 24 * 60 * 60);
        $ninety_days_ago = time() - (90 * 24 * 60 * 60);

        // 7 day active users
        $active_7_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT user_id) as active_users
            FROM {$wpdb->dt_reports}
            WHERE post_type = 'zume'
            AND timestamp >= %d
        ", $seven_days_ago );
        $active_7_days = $wpdb->get_var( $active_7_sql );

        // 30 day active users
        $active_30_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT user_id) as active_users
            FROM {$wpdb->dt_reports}
            WHERE post_type = 'zume'
            AND timestamp >= %d
        ", $thirty_days_ago );
        $active_30_days = $wpdb->get_var( $active_30_sql );

        // 90 day active users
        $active_90_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT user_id) as active_users
            FROM {$wpdb->dt_reports}
            WHERE post_type = 'zume'
            AND timestamp >= %d
        ", $ninety_days_ago );
        $active_90_days = $wpdb->get_var( $active_90_sql );

        // Initialize metrics array
        $metrics = [
            'registered_users' => (int) $total_users,
            'zume_languages_enabled' => (int) $languages_enabled_count,
            '7_day_active' => (int) $active_7_days,
            '30_day_active' => (int) $active_30_days,
            '90_day_active' => (int) $active_90_days,
        ];

        // Calculate participation stats for each of the 33 training items
        for ( $i = 1; $i <= 33; $i++ ) {
            // Users who heard item X
            $heard_sql = $wpdb->prepare( "
                SELECT COUNT(DISTINCT user_id) as users_heard
                FROM {$wpdb->dt_reports}
                WHERE post_type = 'zume'
                AND type = 'training'
                AND subtype = %s
            ", $i . '_heard' );
            $users_heard = $wpdb->get_var( $heard_sql );

            // Users who obeyed item X
            $obeyed_sql = $wpdb->prepare( "
                SELECT COUNT(DISTINCT user_id) as users_obeyed
                FROM {$wpdb->dt_reports}
                WHERE post_type = 'zume'
                AND type = 'training'
                AND subtype = %s
            ", $i . '_obeyed' );
            $users_obeyed = $wpdb->get_var( $obeyed_sql );

            // Users who shared item X
            $shared_sql = $wpdb->prepare( "
                SELECT COUNT(DISTINCT user_id) as users_shared
                FROM {$wpdb->dt_reports}
                WHERE post_type = 'zume'
                AND type = 'training'
                AND subtype = %s
            ", $i . '_shared' );
            $users_shared = $wpdb->get_var( $shared_sql );

            // Users who trained item X
            $trained_sql = $wpdb->prepare( "
                SELECT COUNT(DISTINCT user_id) as users_trained
                FROM {$wpdb->dt_reports}
                WHERE post_type = 'zume'
                AND type = 'training'
                AND subtype = %s
            ", $i . '_trained' );
            $users_trained = $wpdb->get_var( $trained_sql );

            // Add to metrics array
            $metrics["users_heard_{$i}"] = (int) $users_heard;
            $metrics["users_obeyed_{$i}"] = (int) $users_obeyed;
            $metrics["users_shared_{$i}"] = (int) $users_shared;
            $metrics["users_trained_{$i}"] = (int) $users_trained;
        }

        return $metrics;
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

    /**
     * Manual trigger for testing (can be called via admin or debug)
     */
    public function manual_send_stats() {
        if ( current_user_can( 'manage_dt' ) ) {
            $this->send_daily_stats();
            return 'Zume stats sent manually';
        }
        return 'Permission denied';
    }
}

// Initialize the class
new Zume_Daily_Stats_Sender();

/**
 * Helper function to manually trigger stats sending (for testing)
 * Usage: zume_manual_send_daily_stats() in wp-admin or via WP-CLI
 */
function zume_manual_send_daily_stats() {
    $stats = new Zume_Daily_Stats_Sender();
    return $stats->manual_send_stats();
}