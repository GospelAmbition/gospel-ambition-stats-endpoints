<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class GO_Stats_Endpoints
{
    public $namespace = 'go/v1';
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct() {
        if ( $this->dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
            add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        }
    }
    public function add_api_routes() {
        $namespace = $this->namespace;

        register_rest_route(
            $namespace, '/stats', [
                'methods'  => [ 'POST', 'GET' ],
                'callback' => [ $this, 'endpoint' ],
                'permission_callback' => [ $this, 'permission_callback' ]
            ]
        );
        register_rest_route(
            $namespace . '/dt-public', '/stats', [
                'methods'  => [ 'POST', 'GET' ],
                'callback' => [ $this, 'endpoint' ],
                'permission_callback' => [ $this, 'permission_callback' ]
            ]
        );
        register_rest_route(
            $namespace, '/metrics', [
                'methods'  => [ 'POST' ],
                'callback' => [ $this, 'metrics' ],
                'permission_callback' => [ $this, 'permission_callback' ]
            ]
        );
    }

    public function permission_callback(): bool {
        return true;
    }

    public function metrics( WP_REST_Request $request ) {
        $params = $request->get_json_params() ?? $request->get_body_params();

        $metrics = [];
        switch ( $params['site_id'] ?? '' ) {
            case 'gospel_ambition':
                $metrics = $this->gospel_ambition_metrics( $params['project_id'], $params['metric'], $params['ts_start'], $params['ts_end'] );
                break;
            case 'prayer_global':
            case 'vision':
            case 'zume_training':
            case 'pray4movement':
            case 'kingdom_training':
            case 'disciple_tools':
                break;
        }

        return [
            'metrics' => $metrics
        ];
    }

    public function endpoint( WP_REST_Request $request ) {
        $stats = apply_filters( 'go_site_info', [
            'site_name' => get_bloginfo(),
            'stats_timestamp' => time(),
            'stats' => apply_filters( 'go_stats_endpoint', [], get_bloginfo() ),
        ] );

        return $stats;
    }

    public function gospel_ambition_metrics( $project_id, $metric, $ts_start, $ts_end ): array {
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare(
            '
                SELECT
		            YEAR( FROM_UNIXTIME( stat_timestamp ) ) AS year,
		            MONTH( FROM_UNIXTIME( stat_timestamp ) ) AS month,
                    DAYOFMONTH( FROM_UNIXTIME( stat_timestamp ) ) AS day,
		            SUM( stat_value ) AS total
                FROM wp_go_reports
                WHERE project = %s
                    AND stat_key = %s
                    AND stat_timestamp BETWEEN %d AND %d
	            GROUP BY YEAR( FROM_UNIXTIME( stat_timestamp ) ), MONTH( FROM_UNIXTIME( stat_timestamp ) ), DAYOFMONTH( FROM_UNIXTIME( stat_timestamp ) )
	            ORDER BY YEAR( FROM_UNIXTIME( stat_timestamp ) ), MONTH( FROM_UNIXTIME( stat_timestamp ) ), DAYOFMONTH( FROM_UNIXTIME( stat_timestamp ) )
            ', $project_id, $metric, $ts_start, $ts_end
        ), ARRAY_A );
    }

    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->namespace . '/stats' ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }
    public function dt_is_rest( $namespace = null ) {
        // https://github.com/DiscipleTools/disciple-tools-theme/blob/a6024383e954cec2ac4e7a1a31fb4601c940f485/dt-core/global-functions.php#L60
        // Added here so that in non-dt sites there is no dependency.
        $prefix = rest_get_url_prefix();
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST
            || isset( $_GET['rest_route'] )
            && strpos( trim( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '\\/' ), $prefix, 0 ) === 0 ) {
            return true;
        }
        $rest_url    = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array() ) );
        $is_rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
        if ( $namespace ){
            return $is_rest && strpos( $current_url['path'], $namespace ) != false;
        } else {
            return $is_rest;
        }
    }
}
GO_Stats_Endpoints::instance();
