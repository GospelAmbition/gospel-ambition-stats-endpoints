<?php

if ( !defined( 'ABSPATH' ) ){
    exit; // Exit if accessed directly
}

if ( !class_exists( 'Google_Analytics_Tab' ) ){
    class Google_Analytics_Tab{
        private static $_instance = null;

        public static function instance(){
            if ( is_null( self::$_instance ) ){
                self::$_instance = new self();
            }
            return self::$_instance;
        } // End instance()

        public function __construct(){
            //add_action( 'admin_menu', [ $this, 'add_submenu' ], 99 );
            add_action( 'admin_enqueue_scripts', [ $this, 'add_scripts' ] );
            add_action( 'gospel_ambition_tab_menu', [ $this, 'add_tab' ], 10, 1 );
            add_action( 'gospel_ambition_tab_content', [ $this, 'add_content' ], 99, 1 );
        }

        public function add_submenu(){
            add_submenu_page(
                'gospel_ambition',
                'Google Analytics',
                'Google Analytics',
                'edit_posts',
                'gospel_ambition&tab=google-analytics',
                [ 'Gospel_Ambition_Menu', 'content' ]
            );
        }

        public function add_scripts(){
            if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'gospel_ambition' ) ){
                wp_enqueue_script(
                    'google_analytics_script',
                    plugin_dir_url( __FILE__ ) . 'js/google-analytics.js',
                    [],
                    1,
                    true
                );

                wp_localize_script(
                    'google_analytics_script', 'google_analytics', [
                        'site_key' => $this->get_site_key()
                    ]
                );
            }
        }

        public function add_tab( $tab ){
            ?>
            <a href="<?php echo esc_url( admin_url() ) ?>admin.php?page=gospel_ambition&tab=google-analytics"
               class="nav-tab <?php echo esc_html( $tab == 'google-analytics' ? 'nav-tab-active' : '' ) ?>">
                <?php echo esc_html__( 'Google Analytics' ) ?>
            </a>
            <?php
        }

        public function add_content( $tab ){

            // Process any update requests.
            $this->process_updates();

            // Display relevant content.
            if ( $tab == 'google-analytics' ){
                $this->display_content();
            }
        }

        private function process_updates(){

            if ( isset( $_POST['ga_form_nonce'] ) ){
                if ( !wp_verify_nonce( sanitize_key( $_POST['ga_form_nonce'] ), 'ga_form_nonce' ) ){
                    return;
                }

                if ( isset( $_POST['ga_form_payload'] ) ){
                    $site_key = $this->get_site_key();
                    $payload = json_decode( sanitize_text_field( wp_unslash( $_POST['ga_form_payload'] ) ), true );

                    if ( !empty( $site_key ) && !empty( $payload ) ){

                        // Update Credentials.
                        $service_accounts = get_option( 'ga_ga4_service_accounts', [] );
                        if ( !empty( $service_accounts ) && isset( $service_accounts[$site_key]['credentials'] ) ){
                            $service_accounts[$site_key]['credentials'] = $payload['credentials'][$site_key]['credentials'] ?? [];
                        } else{
                            $service_accounts[$site_key] = [
                                'credentials' => $payload['credentials'][$site_key]['credentials'] ?? []
                            ];
                        }
                        update_option( 'ga_ga4_service_accounts', $service_accounts );

                        // Update Properties.
                        $properties = get_option( 'ga_ga4_service_account_properties', [] );
                        if ( !empty( $properties ) && isset( $properties[$site_key] ) ){
                            $properties[$site_key] = $payload['properties'][$site_key] ?? '';
                        } else{
                            $properties[$site_key] = $payload['properties'][$site_key] ?? '';
                        }
                        update_option( 'ga_ga4_service_account_properties', $properties );

                        // Update Date Ranges.
                        $date_ranges = get_option( 'ga_ga4_service_account_date_ranges', [] );
                        if ( !empty( $date_ranges ) && isset( $date_ranges[$site_key]['start_date'], $date_ranges[$site_key]['end_date'] ) ){
                            $date_ranges[$site_key]['start_date'] = $payload['date_ranges'][$site_key]['start_date'] ?? '';
                            $date_ranges[$site_key]['end_date'] = $payload['date_ranges'][$site_key]['end_date'] ?? '';
                        } else{
                            $date_ranges[$site_key] = $payload['date_ranges'][$site_key] ?? [];
                        }
                        update_option( 'ga_ga4_service_account_date_ranges', $date_ranges );

                    }
                }
            }
        }

        private function display_content(){
            $this->add_content_credentials();
            $this->add_content_properties();
            $this->add_content_date_ranges();
            ?>
            <form id="ga_form" method="post">
                <input type="hidden" name="ga_form_nonce" id="ga_form_nonce"
                       value="<?php echo esc_attr( wp_create_nonce( 'ga_form_nonce' ) ) ?>"/>
                <input type="hidden" name="ga_form_payload" id="ga_form_payload" value="{}"/>
            </form>
            <?php
        }

        private function add_content_credentials(){

            $title = 'Google Service Account Json Credentials Payload';
            $args = wp_parse_args( [], [
                'row_container' => true,
                'col_span' => 1,
                'striped' => true,
            ] );

            ?>
            <div class="wrap">
                <div class="metabox-holder columns-1">
                    <table class="widefat <?php echo $args['striped'] ? 'striped' : '' ?>">
                        <thead>
                        <th colspan="<?php echo esc_attr( $args['col_span'] ) ?>"><?php echo esc_html( $title ) ?></th>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <textarea id="ga_textarea_credentials" rows="20"
                                          style="width: 100%;"><?php echo esc_attr( json_encode( $this->get_option_credentials(), JSON_PRETTY_PRINT ) ); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Enter private key below and encode. Once encoded, copy and paste into <b>private_key</b>
                                property, found in above json credentials payload. Failure to properly encode private key will
                                result in an error.<br><br>
                                <textarea id="ga_textarea_credentials_private_key" rows="10"
                                          style="width: 100%;"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span style="float: left;">
                                    <button id="private_key_encode_button" class="button">Encode</button>
                                    <button id="private_key_decode_button" class="button">Decode</button>
                                </span>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>
                                <span style="float: right;">
                                    <button class="button save-button">Save</button>
                                </span>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                    <br>
                </div>
            </div>
            <?php
        }

        private function add_content_properties(){

            $title = 'Google Analytics Json Properties Payload';
            $args = wp_parse_args( [], [
                'row_container' => true,
                'col_span' => 1,
                'striped' => true,
            ] );

            ?>
            <div class="wrap">
                <div class="metabox-holder columns-1">
                    <table class="widefat <?php echo $args['striped'] ? 'striped' : '' ?>">
                        <thead>
                        <th colspan="<?php echo esc_attr( $args['col_span'] ) ?>"><?php echo esc_html( $title ) ?></th>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <textarea id="ga_textarea_properties" rows="5"
                                          style="width: 100%;"><?php echo esc_attr( json_encode( $this->get_option_properties(), JSON_PRETTY_PRINT ) ); ?></textarea>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>
                                <span style="float: right;">
                                    <button class="button save-button">Save</button>
                                </span>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                    <br>
                </div>
            </div>
            <?php
        }

        private function add_content_date_ranges(){

            $title = 'Google Analytics Report Json Date Ranges Payload';
            $args = wp_parse_args( [], [
                'row_container' => true,
                'col_span' => 1,
                'striped' => true,
            ] );

            ?>
            <div class="wrap">
                <div class="metabox-holder columns-1">
                    <table class="widefat <?php echo $args['striped'] ? 'striped' : '' ?>">
                        <thead>
                        <th colspan="<?php echo esc_attr( $args['col_span'] ) ?>"><?php echo esc_html( $title ) ?></th>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <textarea id="ga_textarea_date_ranges" rows="8"
                                          style="width: 100%;"><?php echo esc_attr( json_encode( $this->get_option_date_ranges(), JSON_PRETTY_PRINT ) ); ?></textarea>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>
                                <span style="float: right;">
                                    <button class="button save-button">Save</button>
                                </span>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                    <br>
                </div>
            </div>
            <?php
        }

        private function get_site_key(){
            switch (get_bloginfo()){

                case 'Prayer Global':
                    return 'prayer_global';

                case 'Vision':
                    return 'vision';

                case 'Zúme Training':
                    return 'zume_training';

                case 'Pray4Movement':
                    return 'pray_4_movement';

                case 'Kingdom Training':
                    return 'kingdom_training';

                case 'Disciple.Tools':
                    return 'disciple_tools';

                case 'Gospel Ambition':
                    return 'gospel_ambition';

                default:
                    return null;
            }
        }

        private function get_option_credentials(): array{
            $site_key = $this->get_site_key();
            $service_accounts = get_option( 'ga_ga4_service_accounts', [] );
            if ( !empty( $site_key ) ){

                // Return loaded option, otherwise, default to placeholder template.
                if ( !empty( $service_accounts ) && !empty( $service_accounts[$site_key]['credentials'] ) ){
                    return [
                        $site_key => [
                            'credentials' => $service_accounts[$site_key]['credentials']
                        ]
                    ];
                } else{
                    return [
                        $site_key => [
                            'credentials' => [
                                'type' => 'service_account',
                                'project_id' => '',
                                'private_key_id' => '',
                                'private_key' => '',
                                'client_email' => '',
                                'client_id' => '',
                                'auth_uri' => '',
                                'token_uri' => '',
                                'auth_provider_x509_cert_url' => '',
                                'client_x509_cert_url' => ''
                            ]
                        ]
                    ];
                }
            }

            return [];
        }

        private function get_option_properties(): array{
            $site_key = $this->get_site_key();
            $properties = get_option( 'ga_ga4_service_account_properties', [] );
            if ( !empty( $site_key ) ){

                // Return loaded option, otherwise, default to placeholder template.
                if ( !empty( $properties ) && !empty( $properties[$site_key] ) ){
                    return [
                        $site_key => $properties[$site_key]
                    ];
                } else{
                    return [
                        $site_key => ''
                    ];
                }
            }

            return [];
        }

        private function get_option_date_ranges(): array{
            $site_key = $this->get_site_key();
            $date_ranges = get_option( 'ga_ga4_service_account_date_ranges', [] );
            if ( !empty( $site_key ) ){

                // Return loaded option, otherwise, default to placeholder template.
                if ( !empty( $date_ranges ) && isset( $date_ranges[$site_key]['start_date'], $date_ranges[$site_key]['end_date'] ) ){
                    return [
                        $site_key => [
                            'start_date' => $date_ranges[$site_key]['start_date'],
                            'end_date' => $date_ranges[$site_key]['end_date']
                        ]
                    ];
                } else{
                    return [
                        $site_key => [
                            'start_date' => date( 'Y-m-d', time() ),
                            'end_date' => date( 'Y-m-d', time() )
                        ]
                    ];
                }
            }

            return [];
        }
    }

    Google_Analytics_Tab::instance();
}
