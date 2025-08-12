<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class DT_Historical_Stats_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 100 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_save_dt_stats_key', [ $this, 'ajax_save_stats_key' ] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'dt_usage',
            'Disciple.Tools Historical Stats',
            'DT Historical Stats',
            'manage_dt',
            'dt-historical-stats',
            [ $this, 'admin_page' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( false === strpos( (string) $hook, 'dt-historical-stats' ) ) {
            return;
        }
        wp_enqueue_script(
            'dt-historical-stats-admin',
            plugin_dir_url( __FILE__ ) . 'historical-stats-admin.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
        wp_localize_script( 'dt-historical-stats-admin', 'dtHistoricalStats', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'dt_historical_stats_nonce' ),
        ] );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Disciple.Tools Historical Stats</h1>
            <p>Calculate and send historical Disciple.Tools metrics to the stats API.</p>

            <div class="tab-content" style="display: flex; gap: 20px;">
                <div class="left-column" style="max-width: 60%;">
                    <div class="card">
                        <h2>Run Historical DT Stats</h2>
                        <form id="dt-historical-stats-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="dt_start_date">Start Date</label>
                                    </th>
                                    <td>
                                        <input type="date" id="dt_start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required />
                                        <p class="description">The earliest date to calculate stats for.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="dt_end_date">End Date</label>
                                    </th>
                                    <td>
                                        <input type="date" id="dt_end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required />
                                        <p class="description">The latest date to calculate stats for (usually yesterday).</p>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <input type="submit" id="run-dt-stats" class="button-primary" value="Run DT Historical Stats" />
                                <span id="dt-loading" style="display: none;">
                                    <img src="<?php echo admin_url('images/spinner.gif'); ?>" alt="Loading..." />
                                    Processing... This may take several minutes.
                                </span>
                            </p>
                        </form>

                        <div id="dt-results" style="display: none; margin-top: 20px;">
                            <h3>Results</h3>
                            <div id="dt-results-content"></div>
                        </div>

                        <div id="dt-error" style="display: none; margin-top: 20px;" class="notice notice-error">
                            <p id="dt-error-content"></p>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Configuration</h2>
                        <p>Make sure the API key is configured in WordPress options:</p>
                        <ul>
                            <li><strong>API Key Option:</strong> <code>go_stats_key</code></li>
                            <li><strong>API Endpoint:</strong> <code>https://stats.gospelambition.org/api/metrics</code></li>
                            <li><strong>Project ID:</strong> <code>disciple_tools</code></li>
                        </ul>
                        <h3>Current API Key Status</h3>
                        <?php
                        $api_key = get_option( 'go_stats_key' );
                        if ( empty( $api_key ) ) {
                            echo '<p id="dt-api-key-status" style="color: red;"><strong>❌ No API key configured!</strong> Please set the <code>go_stats_key</code> option.</p>';
                        } else {
                            $key_preview = substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 );
                            echo '<p id="dt-api-key-status" style="color: green;"><strong>✅ API key configured:</strong> ' . esc_html( $key_preview ) . '</p>';
                        }
                        ?>

                        <form id="dt-api-key-form" method="post" style="margin-top: 10px; display: flex; gap: 10px; align-items: center;">
                            <label for="dt_api_key" class="screen-reader-text">API Key</label>
                            <input type="password" id="dt_api_key" name="api_key" placeholder="Enter API key" style="max-width: 360px; width: 100%;" autocomplete="off" />
                            <button type="submit" id="dt-save-api-key" class="button button-primary">Save API Key</button>
                            <span id="dt-api-key-loading" style="display:none;">
                                <img src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="Loading" />
                            </span>
                        </form>
                        <div id="dt-api-key-message" style="margin-top: 8px;"></div>
                    </div>
                </div>

                <div class="right-column" style="flex: 1; max-width: 40%;">
                    <div class="card">
                        <h2>Today (Preview)</h2>
                        <?php
                        $sender = new DT_Daily_Stats_Sender();
                        $reflection = new ReflectionClass( $sender );
                        $calculate = $reflection->getMethod( 'calculate_metrics' );
                        $calculate->setAccessible( true );
                        $metrics = $calculate->invoke( $sender );
                        ?>
                        <pre style="max-height: 400px; overflow: auto; background: #f8f8f8; padding: 10px; border: 1px solid #ddd;">
<?php echo esc_html( print_r( $metrics, true ) ); ?>
                        </pre>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_save_stats_key() {
        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }
        $nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'dt_historical_stats_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
        }
        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API key is required' ] );
        }
        update_option( 'go_stats_key', $api_key, false );
        $key_preview = substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 );
        wp_send_json_success( [ 'message' => 'API key saved', 'preview' => $key_preview ] );
    }
}

new DT_Historical_Stats_Admin();


