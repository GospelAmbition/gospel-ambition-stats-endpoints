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
                'permission_callback' => '__return_true'
            ]
        );
        register_rest_route(
            $namespace . '/dt-public', '/stats', [
                'methods'  => [ 'POST', 'GET' ],
                'callback' => [ $this, 'endpoint' ],
                'permission_callback' => '__return_true'
            ]
        );
    }
    public function endpoint( WP_REST_Request $request ) {
        $stats = apply_filters( 'go_site_info', [
            'site_name' => get_bloginfo(),
            'stats_timestamp' => time(),
            'stats' => apply_filters( 'go_stats_endpoint', [], get_bloginfo() ),
        ] );

        return $stats;
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
