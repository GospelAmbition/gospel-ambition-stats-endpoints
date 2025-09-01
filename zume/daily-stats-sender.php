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