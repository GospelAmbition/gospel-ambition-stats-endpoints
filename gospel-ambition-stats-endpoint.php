<?php
/**
 * Plugin Name: Gospel Ambition Stats Endpoint
 * Plugin URI: https://github.com/GospelAmbition/gospel-ambition-stats-endpoint
 * Description: Gospel Ambition Stats Endpoint
 * Text Domain: gospel-ambition-stats-endpoint
 * Domain Path: /languages
 * Version:  0.1
 * Author URI: https://github.com/GospelAmbition/gospel-ambition-stats-endpoint
 * GitHub Plugin URI: https://github.com/GospelAmbition/gospel-ambition-stats-endpoint
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 6.3
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

class GO_Context_Switcher {
    private static $instance = null;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct(){
        $site = get_bloginfo();

        require_once( 'globals/loader.php' );

        switch ( $site ) {
            case 'Prayer Global':
                require_once( 'prayer_global/loader.php' );
                break;

            default:
                return false;
        }
        return false;
    }
}

add_action( 'after_setup_theme', [ 'GO_Context_Switcher', 'instance' ], 10 );