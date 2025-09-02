<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Zume_Historical_Stats_Admin {
    
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 100 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_save_zume_stats_key', [ $this, 'ajax_save_stats_key' ] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'dt_utilities',
            'Zume Historical Stats',
            'Zume Stats',
            'manage_dt',
            'zume-historical-stats',
            [ $this, 'admin_page' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        // Enqueue when viewing our page regardless of exact prefix variations across environments
        if ( false === strpos( (string) $hook, 'zume-historical-stats' ) ) {
            return;
        }
        
        wp_enqueue_script( 
            'zume-historical-stats-admin', 
            plugin_dir_url( __FILE__ ) . 'historical-stats-admin.js', 
            [ 'jquery' ], 
            '1.0.0', 
            true 
        );
        
        wp_localize_script( 'zume-historical-stats-admin', 'zumeHistoricalStats', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'zume_historical_stats_nonce' )
        ]);
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Zume Historical Stats</h1>
            <p>This tool calculates and sends historical daily statistics for Zume to the stats API.</p>
            
            <div class="tab-content" style="display: flex; gap: 20px;">
                <div class="left-column" style="max-width: 60%;">
                <div class="card">
                    <h2>Run Historical Zume Stats</h2>
                    <form id="zume-historical-stats-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="zume_start_date">Start Date</label>
                                </th>
                                <td>
                                    <input type="date" id="zume_start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required />
                                    <p class="description">The earliest date to calculate stats for.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="zume_end_date">End Date</label>
                                </th>
                                <td>
                                    <input type="date" id="zume_end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required />
                                    <p class="description">The latest date to calculate stats for (usually yesterday).</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" id="run-zume-stats" class="button-primary" value="Run Zume Historical Stats" />
                            <span id="zume-loading" style="display: none;">
                                <img src="<?php echo admin_url('images/spinner.gif'); ?>" alt="Loading..." />
                                Processing... This may take several minutes.
                            </span>
                        </p>
                    </form>
                    
                    <div id="zume-results" style="display: none; margin-top: 20px;">
                        <h3>Results</h3>
                        <div id="zume-results-content"></div>
                    </div>
                    
                    <div id="zume-error" style="display: none; margin-top: 20px;" class="notice notice-error">
                        <p id="zume-error-content"></p>
                    </div>
                </div>

                <div class="card">
                    <h2>Zume Metrics</h2>
                    <ul>
                        <li><strong>Registered Users:</strong> Total number of registered WordPress users</li>
                        <li><strong>Active Users:</strong> Users with activity in dt_reports within the specified timeframes:
                            <ul>
                                <li><strong>7_day_active:</strong> Users active in the last 7 days</li>
                                <li><strong>30_day_active:</strong> Users active in the last 30 days</li>
                                <li><strong>90_day_active:</strong> Users active in the last 90 days</li>
                            </ul>
                        </li>
                        <li><strong>Zume Languages Enabled:</strong> Number of languages currently enabled (version 5 ready)</li>
                        <li><strong>Training Participation:</strong> For each of the 33 training items (1-33):
                            <ul>
                                <li><strong>users_heard_X:</strong> Users who have heard training item X</li>
                                <li><strong>users_obeyed_X:</strong> Users who have obeyed training item X</li>
                                <li><strong>users_shared_X:</strong> Users who have shared training item X</li>
                                <li><strong>users_trained_X:</strong> Users who have trained others in item X</li>
                            </ul>
                        </li>
                    </ul>
                    <p><em>Note: Activity and training participation are tracked in dt_reports with post_type='zume'. Activity includes any record, while training uses subtypes like '1_heard', '1_obeyed', etc.</em></p>
                </div>

                <div class="card">
                    <h2>Configuration</h2>
                    <p>Make sure the API key is configured in WordPress options:</p>
                    <ul>
                        <li><strong>API Key Option:</strong> <code>go_stats_key</code></li>
                        <li><strong>API Endpoint:</strong> <code>https://stats.gospelambition.org/api/metrics</code></li>
                        <li><strong>Project ID:</strong> <code>zume</code> (or <code>zume_dev</code> if WP_DEBUG is enabled)</li>
                    </ul>
                    
                    <h3>Current API Key Status</h3>
                    <?php
                    $api_key = get_option( 'go_stats_key' );
                    if ( empty( $api_key ) ) {
                        echo '<p id="zume-api-key-status" style="color: red;"><strong>❌ No API key configured!</strong> Please set the <code>go_stats_key</code> option.</p>';
                    } else {
                        $key_preview = substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 );
                        echo '<p id="zume-api-key-status" style="color: green;"><strong>✅ API key configured:</strong> ' . esc_html( $key_preview ) . '</p>';
                    }
                    ?>

                    <form id="zume-api-key-form" method="post" style="margin-top: 10px; display: flex; gap: 10px; align-items: center;">
                        <label for="zume_api_key" class="screen-reader-text">API Key</label>
                        <input type="password" id="zume_api_key" name="api_key" placeholder="Enter API key" style="max-width: 360px; width: 100%;" autocomplete="off" />
                        <button type="submit" id="zume-save-api-key" class="button button-primary">Save API Key</button>
                        <span id="zume-api-key-loading" style="display:none;">
                            <img src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="Loading" />
                        </span>
                    </form>
                    <div id="zume-api-key-message" style="margin-top: 8px;"></div>
                </div>

                <div class="card">
                    <h2>Important Notes</h2>
                    <ul>
                        <li><strong>Processing Time:</strong> This process can take several minutes depending on the date range.</li>
                        <li><strong>API Rate Limiting:</strong> The script includes delays between API calls to avoid overwhelming the server.</li>
                        <li><strong>Error Logging:</strong> All results and errors are logged to the WordPress error log.</li>
                        <li><strong>Permissions:</strong> Only users with 'manage_dt' capability can run this tool.</li>
                        <li><strong>Daily Automation:</strong> Daily stats are automatically sent at 2:00 AM via WordPress cron.</li>
                    </ul>
                </div>
                </div>

                <div class="right-column" style="flex: 1; max-width: 40%;">
                <div class="card">
                    <h2>Current Stats (Today)</h2>
                    <?php
                    // Get current metrics from the historical stats processor
                    $historical_stats = new Zume_Historical_Stats();
                    $reflection = new ReflectionClass($historical_stats);
                    $calculate_metrics = $reflection->getMethod('calculate_historical_metrics_for_date');
                    $calculate_metrics->setAccessible(true);
                    $metrics = $calculate_metrics->invoke($historical_stats, date('Y-m-d'));
                    
                    $total_users = $metrics['registered_users'];
                    
                    // Get current language count separately (this is current data, not historical)
                    $enabled_languages = zume_languages();
                    $languages_enabled = count( $enabled_languages );
                    
                    // Calculate totals across all training items
                    $total_heard = $total_obeyed = $total_shared = $total_trained = 0;
                    for ( $i = 1; $i <= 33; $i++ ) {
                        $total_heard += (int) $metrics["users_heard_{$i}"];
                        $total_obeyed += (int) $metrics["users_obeyed_{$i}"];
                        $total_shared += (int) $metrics["users_shared_{$i}"];
                        $total_trained += (int) $metrics["users_trained_{$i}"];
                    }
                    ?>
                    <div class="current-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 15px 0;">
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $total_users ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Registered Users</p>
                        </div>
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $metrics['7_day_active'] ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">7-Day Active</p>
                        </div>
                        <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $metrics['30_day_active'] ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">30-Day Active</p>
                        </div>
                        <div style="background: #fff5ee; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $metrics['90_day_active'] ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">90-Day Active</p>
                        </div>
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $languages_enabled ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Languages Enabled</p>
                        </div>
                        <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( $total_heard ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Total Heard</p>
                        </div>
                        <div style="background: #fff2e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( $total_obeyed ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Total Obeyed</p>
                        </div>
                        <div style="background: #f0f8e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( $total_shared ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Total Shared</p>
                        </div>
                        <div style="background: #ffeaa7; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( $total_trained ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Total Trained</p>
                        </div>
                    </div>
                    
                    <h3>All Training Items (1-33)</h3>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; background: white; margin-top: 10px;">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Heard</th>
                                    <th>Obeyed</th>
                                    <th>Shared</th>
                                    <th>Trained</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ( $i = 1; $i <= 33; $i++ ) : ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo number_format( (int) $metrics["users_heard_{$i}"] ); ?></td>
                                    <td><?php echo number_format( (int) $metrics["users_obeyed_{$i}"] ); ?></td>
                                    <td><?php echo number_format( (int) $metrics["users_shared_{$i}"] ); ?></td>
                                    <td><?php echo number_format( (int) $metrics["users_trained_{$i}"] ); ?></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <p><small><em>Last updated: <?php echo date( 'Y-m-d H:i:s T' ); ?></em></small></p>
                </div>

                
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler to save the API key (stores in option 'go_stats_key')
     */
    public function ajax_save_stats_key() {
        if ( ! current_user_can( 'manage_dt' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }

        $nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'zume_historical_stats_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API key is required' ] );
        }

        update_option( 'go_stats_key', $api_key, false );

        $key_preview = substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 );
        wp_send_json_success( [
            'message' => 'API key saved',
            'preview' => $key_preview,
        ] );
    }
}

new Zume_Historical_Stats_Admin();