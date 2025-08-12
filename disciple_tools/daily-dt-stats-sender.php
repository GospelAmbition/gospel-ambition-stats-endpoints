<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

class DT_Daily_Stats_Sender {

    public function __construct() {
        add_action( 'wp', [ $this, 'schedule_daily_stats' ] );
        add_action( 'dt_send_daily_stats', [ $this, 'send_daily_stats' ] );
        register_deactivation_hook( __FILE__, [ $this, 'clear_scheduled_hook' ] );
    }

    public function schedule_daily_stats() {
        if ( ! wp_next_scheduled( 'dt_send_daily_stats' ) ) {
            wp_schedule_event( strtotime( 'tomorrow 2:05 AM' ), 'daily', 'dt_send_daily_stats' );
        }
    }

    public function clear_scheduled_hook() {
        $timestamp = wp_next_scheduled( 'dt_send_daily_stats' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'dt_send_daily_stats' );
        }
    }

    public function send_daily_stats() {
        $api_key = get_option( 'go_stats_key' );
        if ( empty( $api_key ) ) {
            error_log( 'DT Daily Stats: API key not found in go_stats_key option' );
            return;
        }

        $metrics = $this->calculate_metrics();
        if ( empty( $metrics ) ) {
            error_log( 'DT Daily Stats: Failed to calculate metrics' );
            return;
        }

        $payload = [
            'project_id' => 'disciple_tools' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? '_dev' : '' ),
            'stat_date' => date( 'Y-m-d' ),
            'metrics' => $metrics,
        ];

        $response = $this->send_api_request( $api_key, $payload );
        if ( is_wp_error( $response ) ) {
            error_log( 'DT Daily Stats: API request failed - ' . $response->get_error_message() );
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            if ( $response_code === 200 || $response_code === 201 ) {
                error_log( 'DT Daily Stats: Daily stats sent successfully' );
            } else {
                error_log( 'DT Daily Stats: API request failed with response code ' . $response_code );
            }
        }
    }

    private function calculate_metrics() {
        if ( ! class_exists( 'DT_Usage_Telemetry' ) || ! method_exists( 'DT_Usage_Telemetry', 'get_stats' ) ) {
            return [];
        }

        $instances_stats = DT_Usage_Telemetry::get_stats( false );

        $metrics = [
            // sites
            '30d_online_sites' => intval( $instances_stats['total']['sites'] ?? 0 ),
            '30d_sites' => intval( $instances_stats['active']['sites'] ?? 0 ),
            'all_time_sites' => intval( $instances_stats['all_time']['sites'] ?? 0 ),

            // domains
            '30d_online_domains' => intval( $instances_stats['total']['domains'] ?? 0 ),
            '30d_domains' => intval( $instances_stats['active']['domains'] ?? 0 ),
            'all_time_domains' => intval( $instances_stats['all_time']['domains'] ?? 0 ),

            // users
            '30d_online_users_total' => intval( $instances_stats['total']['total_users'] ?? 0 ),
            '30d_users_total' => intval( $instances_stats['active']['total_users'] ?? 0 ),
            'all_time_users_total' => intval( $instances_stats['all_time']['total_users'] ?? 0 ),

            '30d_online_users_active' => intval( $instances_stats['total']['active_users'] ?? 0 ),
            '30d_users_active' => intval( $instances_stats['active']['active_users'] ?? 0 ),
            'all_time_users_active' => intval( $instances_stats['all_time']['active_users'] ?? 0 ),

            // contacts
            '30d_online_contacts_total' => intval( $instances_stats['total']['total_contacts'] ?? 0 ),
            '30d_contacts_total' => intval( $instances_stats['active']['total_contacts'] ?? 0 ),
            'all_time_contacts_total' => intval( $instances_stats['all_time']['total_contacts'] ?? 0 ),

            '30d_online_contacts_active' => intval( $instances_stats['total']['active_contacts'] ?? 0 ),
            '30d_contacts_active' => intval( $instances_stats['active']['active_contacts'] ?? 0 ),
            'all_time_contacts_active' => intval( $instances_stats['all_time']['active_contacts'] ?? 0 ),

            // groups
            '30d_online_groups_total' => intval( $instances_stats['total']['total_groups'] ?? 0 ),
            '30d_groups_total' => intval( $instances_stats['active']['total_groups'] ?? 0 ),
            'all_time_groups_total' => intval( $instances_stats['all_time']['total_groups'] ?? 0 ),

            '30d_online_groups_active' => intval( $instances_stats['total']['active_groups'] ?? 0 ),
            '30d_groups_active' => intval( $instances_stats['active']['active_groups'] ?? 0 ),
            'all_time_groups_active' => intval( $instances_stats['all_time']['active_groups'] ?? 0 ),

            // churches
            '30d_online_churches_total' => intval( $instances_stats['total']['total_churches'] ?? 0 ),
            '30d_churches_total' => intval( $instances_stats['active']['total_churches'] ?? 0 ),
            'all_time_churches_total' => intval( $instances_stats['all_time']['total_churches'] ?? 0 ),

            '30d_online_churches_active' => intval( $instances_stats['total']['active_churches'] ?? 0 ),
            '30d_churches_active' => intval( $instances_stats['active']['active_churches'] ?? 0 ),
            'all_time_churches_active' => intval( $instances_stats['all_time']['active_churches'] ?? 0 ),
        ];

        return $metrics;
    }

    private function send_api_request( $api_key, $payload ) {
        $url = 'https://stats.gospelambition.org/api/metrics';
        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
            ],
            'body' => json_encode( $payload ),
            'timeout' => 30,
        ];
        return wp_remote_request( $url, $args );
    }

    public function manual_send_stats() {
        if ( current_user_can( 'manage_dt' ) ) {
            $this->send_daily_stats();
            return 'Disciple.Tools stats sent manually';
        }
        return 'Permission denied';
    }
}

new DT_Daily_Stats_Sender();

function dt_manual_send_daily_stats() {
    $stats = new DT_Daily_Stats_Sender();
    return $stats->manual_send_stats();
}


