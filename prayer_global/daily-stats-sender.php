<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class PG_Daily_Stats_Sender {

    public function __construct() {
        // Hook to schedule the cron job when the plugin is activated
        add_action( 'wp', [ $this, 'schedule_daily_stats' ] );
        
        // Hook for the actual cron job execution
        add_action( 'pg_send_daily_stats', [ $this, 'send_daily_stats' ] );
        
        // Clean up scheduled event on deactivation
        register_deactivation_hook( __FILE__, [ $this, 'clear_scheduled_hook' ] );
    }

    /**
     * Schedule the daily stats cron job if it's not already scheduled
     */
    public function schedule_daily_stats() {
        if ( ! wp_next_scheduled( 'pg_send_daily_stats' ) ) {
            // Schedule to run daily at 2:00 AM
            wp_schedule_event( strtotime( 'tomorrow 2:00 AM' ), 'daily', 'pg_send_daily_stats' );
        }
    }

    /**
     * Clear the scheduled hook
     */
    public function clear_scheduled_hook() {
        $timestamp = wp_next_scheduled( 'pg_send_daily_stats' );
        wp_unschedule_event( $timestamp, 'pg_send_daily_stats' );
    }

    /**
     * Send daily stats to the API
     */
    public function send_daily_stats() {
        // Get the API key from wp_options
        $api_key = get_option( 'go_stats_key' );
        if ( empty( $api_key ) ) {
            error_log( 'PG Daily Stats: API key not found in go_stats_key option' );
            return;
        }

        // Calculate metrics
        $metrics = $this->calculate_metrics();
        if ( empty( $metrics ) ) {
            error_log( 'PG Daily Stats: Failed to calculate metrics' );
            return;
        }

        // Prepare the payload
        $payload = [
            'project_id' => 'prayer_global' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? '_dev' : '' ),
            'stat_date' => date( 'Y-m-d' ), // YYYY-MM-DD format
            'metrics' => $metrics
        ];

        // Send the API request
        $response = $this->send_api_request( $api_key, $payload );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'PG Daily Stats: API request failed - ' . $response->get_error_message() );
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            if ( $response_code === 200 || $response_code === 201 ) {
                error_log( 'PG Daily Stats: Daily stats sent successfully' );
            } else {
                error_log( 'PG Daily Stats: API request failed with response code ' . $response_code );
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

        // 1. Prayer Warriors - distinct hashes from dt_reports
        $prayer_warriors_sql = "
            SELECT COUNT(DISTINCT hash) as prayer_warriors
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
        ";
        $prayer_warriors = $wpdb->get_var( $prayer_warriors_sql );

        // 2. Minutes of Prayer - sum of value column
        $minutes_sql = "
            SELECT SUM(value) as minutes
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
        ";
        $minutes_of_prayer = $wpdb->get_var( $minutes_sql );

        // 3. Total Prayers (over specific locations)
        $prayers_sql = "
            SELECT COUNT(*) as prayers
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
        ";
        $total_prayers = $wpdb->get_var( $prayers_sql );

        // 4. Laps Completed - get current lap number from dt_relays table
        $laps_sql = "
            SELECT COALESCE(MIN(total), 0) as laps_completed
            FROM {$wpdb->dt_relays}
            WHERE relay_key = '49ba4c'
        ";
        $laps_completed = (int) $wpdb->get_var( $laps_sql );


        // 6. Total registered users
        $users_sql = "
            SELECT COUNT(*) as total_users
            FROM {$wpdb->users}
        ";
        $total_users = $wpdb->get_var( $users_sql );

        // 7. Custom laps completed - sum the min total for each relay_key (excluding 49ba4c) where count = 4700
        $custom_laps_sql = "
            SELECT COALESCE(SUM(min_total), 0) as custom_laps_completed
            FROM (
                SELECT relay_key, MIN(total) as min_total, COUNT(*) as relay_count
                FROM {$wpdb->dt_relays}
                WHERE relay_key != '49ba4c'
                GROUP BY relay_key
                HAVING relay_count = 4770
            ) grouped_relays
        ";
        $custom_laps_completed = (int) $wpdb->get_var( $custom_laps_sql );

        // 8. Daily active users metrics (last 24 hours)
        $now = time();
        $day_ago = $now - (24 * 60 * 60);
        
        // Daily recurring active users - users active in last 24h who were also active before
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
            )
        ", $day_ago, $now, $day_ago );
        $daily_recurring_active_users = (int) $wpdb->get_var( $daily_recurring_sql );

        // Daily new active users - users active in last 24h for the first time
        $daily_new_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as daily_new_active_users
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
            AND hash NOT IN (
                SELECT DISTINCT hash FROM {$wpdb->dt_reports}
                WHERE type = 'prayer_app'
                AND timestamp < %d
            )
        ", $day_ago, $now, $day_ago );
        $daily_new_active_users = (int) $wpdb->get_var( $daily_new_sql );

        // 9. Weekly active users metrics (last 7 days)
        $week_ago = $now - (7 * 24 * 60 * 60);
        
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
        ", $week_ago, $now, $week_ago );
        $weekly_recurring_active_users = (int) $wpdb->get_var( $weekly_recurring_sql );

        // Weekly new active users
        $weekly_new_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as weekly_new_active_users
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
            AND hash NOT IN (
                SELECT DISTINCT hash FROM {$wpdb->dt_reports}
                WHERE type = 'prayer_app'
                AND timestamp < %d
            )
        ", $week_ago, $now, $week_ago );
        $weekly_new_active_users = (int) $wpdb->get_var( $weekly_new_sql );

        // 10. Monthly active users metrics (last 30 days)
        $month_ago = $now - (30 * 24 * 60 * 60);
        
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
        ", $month_ago, $now, $month_ago );
        $monthly_recurring_active_users = (int) $wpdb->get_var( $monthly_recurring_sql );

        // Monthly new active users
        $monthly_new_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as monthly_new_active_users
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
            AND hash NOT IN (
                SELECT DISTINCT hash FROM {$wpdb->dt_reports}
                WHERE type = 'prayer_app'
                AND timestamp < %d
            )
        ", $month_ago, $now, $month_ago );
        $monthly_new_active_users = (int) $wpdb->get_var( $monthly_new_sql );

        // 11. Average prayers per session (prayers in last 24h / users in last 24h)
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
        ", $day_ago, $now );
        $avg_prayers_per_session = (float) $wpdb->get_var( $avg_prayers_per_session_sql );

        // 12. Users who prayed in last 24h
        $users_24h_sql = $wpdb->prepare( "
            SELECT COUNT(DISTINCT hash) as users_24h
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $day_ago, $now );
        $users_24h = (int) $wpdb->get_var( $users_24h_sql );

        // 13. Number of prayers in last 24h
        $prayers_24h_sql = $wpdb->prepare( "
            SELECT COUNT(*) as prayers_24h
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $day_ago, $now );
        $prayers_24h = (int) $wpdb->get_var( $prayers_24h_sql );

        // 14. Minutes of prayer in last 24h
        $minutes_24h_sql = $wpdb->prepare( "
            SELECT COALESCE(SUM(value), 0) as minutes_24h
            FROM {$wpdb->dt_reports}
            WHERE type = 'prayer_app'
            AND timestamp >= %d
            AND timestamp <= %d
        ", $day_ago, $now );
        $minutes_24h = (int) $wpdb->get_var( $minutes_24h_sql );


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

    /**
     * Manual trigger for testing (can be called via admin or debug)
     */
    public function manual_send_stats() {
        if ( current_user_can( 'manage_dt' ) ) {
            $this->send_daily_stats();
            return 'Prayer Global stats sent manually';
        }
        return 'Permission denied';
    }
}

// Initialize the class
new PG_Daily_Stats_Sender();

/**
 * Helper function to manually trigger stats sending (for testing)
 * Usage: pg_manual_send_daily_stats() in wp-admin or via WP-CLI
 */
function pg_manual_send_daily_stats() {
    $stats = new PG_Daily_Stats_Sender();
    return $stats->manual_send_stats();
}