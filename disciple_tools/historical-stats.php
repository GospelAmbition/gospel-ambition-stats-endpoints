<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class DT_Historical_Stats {

    public function __construct() {
        add_action( 'wp_ajax_run_dt_historical_stats', [ $this, 'ajax_run_historical_stats' ] );
    }

    public function ajax_run_historical_stats() {
        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_die( json_encode( [ 'error' => 'Permission denied' ] ) );
        }

        if ( ! wp_verify_nonce( $_POST['_ajax_nonce'] ?? '', 'dt_historical_stats_nonce' ) ) {
            wp_die( json_encode( [ 'error' => 'Invalid nonce' ] ) );
        }

        $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
        $end_date = sanitize_text_field( $_POST['end_date'] ?? '' );

        if ( empty( $start_date ) || empty( $end_date ) ) {
            wp_die( json_encode( [ 'error' => 'Start date and end date are required' ] ) );
        }

        $start_timestamp = strtotime( $start_date );
        $end_timestamp = strtotime( $end_date );
        if ( ! $start_timestamp || ! $end_timestamp ) {
            wp_die( json_encode( [ 'error' => 'Invalid date format' ] ) );
        }
        if ( $start_timestamp > $end_timestamp ) {
            wp_die( json_encode( [ 'error' => 'Start date must be before or equal to end date' ] ) );
        }

        $results = $this->process_historical_stats( $start_date, $end_date );
        wp_die( json_encode( $results ) );
    }

    public function process_historical_stats( string $start_date, string $end_date ): array {
        $api_key = get_option( 'go_stats_key' );
        if ( empty( $api_key ) ) {
            return [ 'error' => 'API key not found. Please set go_stats_key option.' ];
        }

        $results = [];
        $processed = 0;
        $errors = 0;

        $current_date = $start_date;
        while ( strtotime( $current_date ) <= strtotime( $end_date ) ) {
            $metrics = $this->calculate_historical_metrics_for_date( $current_date );

            if ( $metrics === false ) {
                $results[] = [
                    'date' => $current_date,
                    'status' => 'error',
                    'message' => 'Failed to calculate metrics',
                ];
                $errors++;
            } else {
                $payload = [
                    'project_id' => 'disciple_tools',
                    'stat_date' => $current_date,
                    'metrics' => $metrics,
                ];
                $response = $this->send_api_request( $api_key, $payload );
                if ( is_wp_error( $response ) ) {
                    $results[] = [
                        'date' => $current_date,
                        'status' => 'error',
                        'message' => $response->get_error_message(),
                    ];
                    $errors++;
                } else {
                    $code = wp_remote_retrieve_response_code( $response );
                    if ( $code === 200 || $code === 201 ) {
                        $results[] = [
                            'date' => $current_date,
                            'status' => 'success',
                            'metrics' => $metrics,
                        ];
                        $processed++;
                    } else {
                        $results[] = [
                            'date' => $current_date,
                            'status' => 'error',
                            'message' => "API returned status code {$code}",
                        ];
                        $errors++;
                    }
                }
            }

            usleep( 100000 ); // 0.1 seconds
            $current_date = date( 'Y-m-d', strtotime( $current_date . ' +1 day' ) );
        }

        return [
            'total_dates' => count( $results ),
            'processed' => $processed,
            'errors' => $errors,
            'results' => $results,
        ];
    }

    private function calculate_historical_metrics_for_date( string $date ) {
        global $wpdb;

        $end_ts = strtotime( $date );
        if ( ! $end_ts ) {
            return false;
        }
        $month_ago_ts = $end_ts - 30 * DAY_IN_SECONDS;
        $month_ago = gmdate( 'Y-m-d H:i:s', $month_ago_ts );
        $end_str = gmdate( 'Y-m-d H:i:s', $end_ts );

        // 30d online (all reported sites in last 30 days as of date)
        $total_recent = $wpdb->get_row( $wpdb->prepare( "
            SELECT COUNT(u1.id) as sites,
                   SUM(u1.active_contacts) as active_contacts,
                   SUM(u1.total_contacts) as total_contacts,
                   SUM(u1.active_groups) as active_groups,
                   SUM(u1.total_groups) as total_groups,
                   SUM(u1.active_churches) as active_churches,
                   SUM(u1.total_churches) as total_churches,
                   SUM(u1.active_users) as active_users,
                   SUM(u1.total_users) as total_users,
                   COUNT(DISTINCT(u1.domain)) as domains
            FROM {$wpdb->dt_usage} u1
            WHERE u1.id IN (
                SELECT MAX(id) FROM {$wpdb->dt_usage}
                WHERE timestamp <= %s
                GROUP BY site_url
            )
            AND u1.timestamp > %s
            AND u1.excluded NOT LIKE 1
        ", $end_str, $month_ago ), ARRAY_A );

        // 30d (sites with active users, excluding demo)
        $active_recent = $wpdb->get_row( $wpdb->prepare( "
            SELECT COUNT(u1.id) as sites,
                   SUM(u1.active_contacts) as active_contacts,
                   SUM(u1.total_contacts) as total_contacts,
                   SUM(u1.active_groups) as active_groups,
                   SUM(u1.total_groups) as total_groups,
                   SUM(u1.active_churches) as active_churches,
                   SUM(u1.total_churches) as total_churches,
                   SUM(u1.active_users) as active_users,
                   SUM(u1.total_users) as total_users,
                   COUNT(DISTINCT(u1.domain)) as domains
            FROM {$wpdb->dt_usage} u1
            WHERE u1.id IN (
                SELECT MAX(id) FROM {$wpdb->dt_usage}
                WHERE timestamp <= %s
                GROUP BY site_url
            )
            AND u1.timestamp > %s
            AND u1.excluded NOT LIKE 1
            AND u1.has_demo_data NOT LIKE 1
            AND u1.active_users > 0
        ", $end_str, $month_ago ), ARRAY_A );

        // all reported sites ever as of date
        $all_time = $wpdb->get_row( $wpdb->prepare( "
            SELECT COUNT(u1.id) as sites,
                   SUM(u1.active_contacts) as active_contacts,
                   SUM(u1.total_contacts) as total_contacts,
                   SUM(u1.active_groups) as active_groups,
                   SUM(u1.total_groups) as total_groups,
                   SUM(u1.active_churches) as active_churches,
                   SUM(u1.total_churches) as total_churches,
                   SUM(u1.active_users) as active_users,
                   SUM(u1.total_users) as total_users,
                   COUNT(DISTINCT(u1.domain)) as domains
            FROM {$wpdb->dt_usage} u1
            WHERE u1.id IN (
                SELECT MAX(id) FROM {$wpdb->dt_usage}
                WHERE timestamp <= %s
                GROUP BY site_url
            )
            AND u1.excluded NOT LIKE 1
        ", $end_str ), ARRAY_A );

        if ( ! is_array( $total_recent ) || ! is_array( $active_recent ) || ! is_array( $all_time ) ) {
            return false;
        }

        $metrics = [
            // sites
            '30d_online_sites' => intval( $total_recent['sites'] ?? 0 ),
            '30d_sites' => intval( $active_recent['sites'] ?? 0 ),
            'all_time_sites' => intval( $all_time['sites'] ?? 0 ),

            // domains
            '30d_online_domains' => intval( $total_recent['domains'] ?? 0 ),
            '30d_domains' => intval( $active_recent['domains'] ?? 0 ),
            'all_time_domains' => intval( $all_time['domains'] ?? 0 ),

            // users
            '30d_online_users_total' => intval( $total_recent['total_users'] ?? 0 ),
            '30d_users_total' => intval( $active_recent['total_users'] ?? 0 ),
            'all_time_users_total' => intval( $all_time['total_users'] ?? 0 ),

            '30d_online_users_active' => intval( $total_recent['active_users'] ?? 0 ),
            '30d_users_active' => intval( $active_recent['active_users'] ?? 0 ),
            'all_time_users_active' => intval( $all_time['active_users'] ?? 0 ),

            // contacts
            '30d_online_contacts_total' => intval( $total_recent['total_contacts'] ?? 0 ),
            '30d_contacts_total' => intval( $active_recent['total_contacts'] ?? 0 ),
            'all_time_contacts_total' => intval( $all_time['total_contacts'] ?? 0 ),

            '30d_online_contacts_active' => intval( $total_recent['active_contacts'] ?? 0 ),
            '30d_contacts_active' => intval( $active_recent['active_contacts'] ?? 0 ),
            'all_time_contacts_active' => intval( $all_time['active_contacts'] ?? 0 ),

            // groups
            '30d_online_groups_total' => intval( $total_recent['total_groups'] ?? 0 ),
            '30d_groups_total' => intval( $active_recent['total_groups'] ?? 0 ),
            'all_time_groups_total' => intval( $all_time['total_groups'] ?? 0 ),

            '30d_online_groups_active' => intval( $total_recent['active_groups'] ?? 0 ),
            '30d_groups_active' => intval( $active_recent['active_groups'] ?? 0 ),
            'all_time_groups_active' => intval( $all_time['active_groups'] ?? 0 ),

            // churches
            '30d_online_churches_total' => intval( $total_recent['total_churches'] ?? 0 ),
            '30d_churches_total' => intval( $active_recent['total_churches'] ?? 0 ),
            'all_time_churches_total' => intval( $all_time['total_churches'] ?? 0 ),

            '30d_online_churches_active' => intval( $total_recent['active_churches'] ?? 0 ),
            '30d_churches_active' => intval( $active_recent['active_churches'] ?? 0 ),
            'all_time_churches_active' => intval( $all_time['active_churches'] ?? 0 ),
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
}

new DT_Historical_Stats();
