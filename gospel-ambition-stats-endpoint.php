<?php
/**
 * Plugin Name: Gospel Ambition - Stats Endpoint
 * Plugin URI: https://github.com/GospelAmbition/gospel-ambition-stats-endpoint
 * Description: Gospel Ambition Stats Endpoint
 * Text Domain: gospel-ambition-stats-endpoint
 * Domain Path: /languages
 * Version:  2023.03.07
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
    public static function plugin_url( $path = '' ) {
        return plugins_url( $path, __FILE__ );
    }
    public function __construct(){
        $site = get_bloginfo();

        require_once( 'globals/loader.php' );

        require_once( 'assets/stats-pages.php' );

        self::ga4_handle_credentials();
        self::ga4_handle_properties();

        switch ( $site ) {

            case 'Prayer Global':
                require_once( 'prayer_global/loader.php' );
                break;

            case 'Vision':
            case 'Zúme Training':
                require_once( 'zume_vision/loader.php' );
                break;

            case 'Pray4Movement':
                require_once( 'pray4movement/loader.php' );
                break;

            case 'Kingdom Training':
                require_once( 'kingdom_training/loader.php' );
                break;

            case 'Disciple.Tools':
                require_once( 'disciple_tools/loader.php' );
                break;

            case 'Gospel Ambition':
                require_once( 'gospel_ambition/loader.php' );
                break;

            default:
                return false;
        }
        return false;
    }

    private function ga4_handle_credentials(): void{
        $credentials = get_option( 'ga_ga4_service_account_credentials', [] );
        if ( empty( $credentials ) ){

            // Build and store GA4 service account credentials to be used.
            // TODO: Source from environmental variable or manually insert into db option table!
            //  - As not too keen on hard-coding private key within code!
            $credentials = [
                'type' => 'service_account',
                'project_id' => 'zume-project',
                'private_key_id' => '8588b84474ae20c5b371a6fbba74a311dceac2df',
                'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCsSG3Sqgv0hafh
+d5SuvFtUkW3FGSClWBaALEKk7KrYm02bCfcTdifnOvDClrb9XXdB835QUyVzXid
taUX+KaUhL/shmDf9kgCM1xyZMQNsX8L7cgxf3m27dyzgqf50W8qAr9cLMOJ1yzx
IQsS16ldppuLjBDoGuZDyDRfWooMJ1+WhytYOOP3dHUqywC3BSJe6pcOnYwtojgn
/qmB/smhmSANH+Tx7YnO04fg2C+nny5t6gXrega3cOGjSFRj6hDCaVjfyc42RStE
SodaJScR58UdunAVjT5CzXwHbwwKi8ynqKhJq6VrKKmJtsZtcJS2nXwbS5MEWx0c
2Oyip4WTAgMBAAECggEAFwuz79OMuITjKY4BzhHxFwKZDh3Njs+UjUrnuSvQtq76
lPvFc+l7P2lU+vN4+/t1whS32iPWBCPxgoc4h7Wx8s60gwXmvOQg9cpmZMGonXxP
gQpUa+qgHsxAEvM4rROgSdUG7A7riUDIuOixUS/wGUr0JlKc4FouJdsIxnNCDCXv
yzsfTxtLM90s7rpD6/E6Tf5lU3Uyz/17qyMvH1pQcDt5qCOOaCbHUCdlCc4g692J
ur4Sa2knGeMiLHvr1HK/ciGAp32YgtgHR5YxZKKf5FEbFNOjuplkfqNaa62M2BWS
k02BvqxZAL2OAhRZdjgEO5V7ItIxI/Bl5PAwoxmvwQKBgQDkxZgvRIkpZmhGPAbB
QU7QuOnGnhUutanZHjNK1JgJDAlhyzgM/see26hwmZ30COBr1uChH5TZHLN/W6Cy
JXPsXUUoC0PWHpyoI7mvG2H/Q9BB2Ft3XS80NjtEOwescYGaSiBtpVxEQfNaSLus
Nv6/DoSUHo4DAAWNMFnY6NZKOQKBgQDAya8sXpmVwiJQdXepnGtXePkz53v+yDUU
2SihxErXe1JTbhnNGGngKDJ0LpEYjxlk3UgpnEA2ql2P7F47P8mzVw7Zx9exwycb
YC6fAWZRdTZUBl86VrlAmL4vGu/xhMz9lRcrS8mvWa+aaOYNQAS+dnuHJW2XKGHj
3k1Pgyl+KwKBgQCasCk5PORqA/7aDtiacCh05bPdQyMblGamks8n+BxdcbAeWiUq
VFRyTCDXEmhFjIMDKCZ3jD0/mTKeGTzNeJmr511NuGBENirDXnS9vIxE6Hu3Ki6e
xXmXmlv0xN1pcs6pnxnSSg/bb6S3FZsg1YbndU+cQBTSXn9ieqEmFDphsQKBgQCc
AVjwlajBCDTOWRA5P2uZgDpgpxyuwwI5WbVImlhZ4OBwxPK6Bdx/WOfjHl+puPq6
pVok2d0Yn2pQ8dwbI/YrWvKYht/jaGF4BLAVWYObvTb2baWXxt/oBvI3mhu6nFVp
isER9yVA8VlpoSMwa7KrEaPKbB4vqKz2QUjcY+4quwKBgHwh0znUN+3bDw+wd3Tk
KXkMvySL5Pw5wRXJL6bBxJqrh1aSDRUsFtdYonl1rNrpd1Hsavi1HN6ZTZ0OcMrQ
/gI0Zite3UsRF254pT82UhqhoWsByondjKAli+xIpOdMbgZ+L/7PRKltu43xvlIP
jSnSP3e7qHcW0uU4loVT5dWR
-----END PRIVATE KEY-----
',
                'client_email' => 'impactometers@zume-project.iam.gserviceaccount.com',
                'client_id' => '102953997316344064913',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/impactometers%40zume-project.iam.gserviceaccount.com'
            ];
            add_option( 'ga_ga4_service_account_credentials', $credentials );
        }
    }

    private function ga4_handle_properties(): void{
        $properties = get_option( 'ga_ga4_service_account_properties', [] );
        if ( empty( $properties ) ){
            $properties = [
                'prayer_global' => '',
                'vision' => '',
                'zume_training' => '354575475',
                'pray_4_movement' => '',
                'kingdom_training' => '315303779',
                'disciple_tools' => '',
                'gospel_ambition' => ''
            ];
            add_option( 'ga_ga4_service_account_properties', $properties );
        }
    }
}

add_action( 'after_setup_theme', [ 'GO_Context_Switcher', 'instance' ], 10 );


function go_display_minutes( $time_committed ){
    $days_committed = round( $time_committed / 60 / 24, 2 ) % 365;
    $years_committed = floor( $time_committed / 60 / 24 / 365 );
    $string = '';
    if ( !empty( $years_committed ) ){
        $string .= $years_committed . ' year' . ( $years_committed > 1 ? 's' : '' );
    }
    $string .= ' ' . $days_committed . ' day' . ( $days_committed > 1 ? 's' : '' );
    return $string;
}
