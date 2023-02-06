<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class GO_Stats_Endpoints
{

    public function add_api_routes() {
        $namespace = 'go-stats/v1';

        register_rest_route(
            $namespace, '/endpoint', [
                'methods'  => 'POST',
                'callback' => [ $this, 'endpoint' ],
                'permission_callback' => '__return_true'
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        $stats = [
            'site_name' => get_bloginfo(),
        ];

        return apply_filters( 'go_stats_endpoint', $stats );
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }
}
GO_Stats_Endpoints::instance();
