<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class PG_Historical_Stats_Admin {
    
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 100 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'dt_utilities',
            'Prayer Global Historical Stats',
            'Historical Stats',
            'manage_dt',
            'pg-historical-stats',
            [ $this, 'admin_page' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'utilities-d-t_page_pg-historical-stats' && $hook !== 'disciple-tools_page_pg-historical-stats' ) {
            return;
        }
        
        wp_enqueue_script( 
            'pg-historical-stats-admin', 
            plugin_dir_url( __FILE__ ) . 'historical-stats-admin.js', 
            [ 'jquery' ], 
            '1.0.0', 
            true 
        );
        
        wp_localize_script( 'pg-historical-stats-admin', 'pgHistoricalStats', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'pg_historical_stats_nonce' )
        ]);
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Prayer Global Historical Stats</h1>
            <p>This tool calculates and sends historical daily statistics for Prayer Global to the stats API.</p>
            
            <div class="tab-content">
                <div class="card">
                    <h2>Run Historical Prayer Global Stats</h2>
                    <form id="pg-historical-stats-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="pg_start_date">Start Date</label>
                                </th>
                                <td>
                                    <input type="date" id="pg_start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required />
                                    <p class="description">The earliest date to calculate stats for.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="pg_end_date">End Date</label>
                                </th>
                                <td>
                                    <input type="date" id="pg_end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required />
                                    <p class="description">The latest date to calculate stats for (usually yesterday).</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" id="run-pg-stats" class="button-primary" value="Run Prayer Global Historical Stats" />
                            <span id="pg-loading" style="display: none;">
                                <img src="<?php echo admin_url('images/spinner.gif'); ?>" alt="Loading..." />
                                Processing... This may take several minutes.
                            </span>
                        </p>
                    </form>
                    
                    <div id="pg-results" style="display: none; margin-top: 20px;">
                        <h3>Results</h3>
                        <div id="pg-results-content"></div>
                    </div>
                    
                    <div id="pg-error" style="display: none; margin-top: 20px;" class="notice notice-error">
                        <p id="pg-error-content"></p>
                    </div>
                </div>

                <div class="card">
                    <h2>Prayer Global Metrics</h2>
                    <ul>
                        <li><strong>Prayer Warriors:</strong> Unique users who have prayed (distinct hashes from dt_reports)</li>
                        <li><strong>Minutes of Prayer:</strong> Total time spent in prayer across all sessions</li>
                        <li><strong>Total Prayers:</strong> Number of prayer sessions over specific locations</li>
                        <li><strong>Laps Completed:</strong> Number of complete prayer laps around the world</li>
                        <li><strong>Locations Covered:</strong> Total locations covered by completed laps (laps × 4770)</li>
                        <li><strong>Daily Recurring Active Users:</strong> Users active in the last 24h who were also active before</li>
                        <li><strong>Daily New Active Users:</strong> Users active in the last 24h for the first time</li>
                        <li><strong>Weekly Recurring Active Users:</strong> Users active in the last 7 days who were also active before</li>
                        <li><strong>Weekly New Active Users:</strong> Users active in the last 7 days for the first time</li>
                        <li><strong>Monthly Recurring Active Users:</strong> Users active in the last 30 days who were also active before</li>
                        <li><strong>Monthly New Active Users:</strong> Users active in the last 30 days for the first time</li>
                    </ul>
                </div>

                <div class="card">
                    <h2>Current Stats (Today)</h2>
                    <?php
                    // Get current metrics from the daily stats sender
                    $daily_stats = new PG_Daily_Stats_Sender();
                    $reflection = new ReflectionClass($daily_stats);
                    $calculate_metrics = $reflection->getMethod('calculate_metrics');
                    $calculate_metrics->setAccessible(true);
                    $metrics = $calculate_metrics->invoke($daily_stats);
                    
                    $prayer_warriors = $metrics['prayer_warriors'];
                    $minutes_of_prayer = $metrics['minutes_of_prayer'];
                    $total_prayers = $metrics['total_prayers'];
                    $global_laps_completed = $metrics['global_laps_completed'];
                    $locations_covered = $metrics['locations_covered_by_laps'];
                    $total_users = $metrics['registered_users'];
                    $custom_laps_completed = $metrics['custom_laps_completed'];
                    $daily_recurring_active = $metrics['daily_recurring_active_users'];
                    $daily_new_active = $metrics['daily_new_active_users'];
                    $weekly_recurring_active = $metrics['weekly_recurring_active_users'];
                    $weekly_new_active = $metrics['weekly_new_active_users'];
                    $monthly_recurring_active = $metrics['monthly_recurring_active_users'];
                    $monthly_new_active = $metrics['monthly_new_active_users'];
                    ?>
                    <div class="current-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;">
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $prayer_warriors ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Prayer Warriors</p>
                        </div>
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $minutes_of_prayer ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Minutes of Prayer</p>
                        </div>
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $total_prayers ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Total Prayers</p>
                        </div>
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $global_laps_completed ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Global Laps</p>
                        </div>
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $locations_covered ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Locations Covered</p>
                        </div>
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $total_users ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Registered Users</p>
                        </div>
                        <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $custom_laps_completed ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Custom Laps</p>
                        </div>
                        <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $daily_recurring_active ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Daily Recurring Active</p>
                        </div>
                        <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $daily_new_active ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Daily New Active</p>
                        </div>
                        <div style="background: #fff2e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $weekly_recurring_active ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Weekly Recurring Active</p>
                        </div>
                        <div style="background: #fff2e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $weekly_new_active ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Weekly New Active</p>
                        </div>
                        <div style="background: #f0f8e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $monthly_recurring_active ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Monthly Recurring Active</p>
                        </div>
                        <div style="background: #f0f8e8; padding: 15px; border-radius: 5px; text-align: center;">
                            <h3 style="margin: 0; color: #0073aa;"><?php echo number_format( (int) $monthly_new_active ); ?></h3>
                            <p style="margin: 5px 0 0 0; font-weight: bold;">Monthly New Active</p>
                        </div>
                    </div>
                    <p><small><em>Last updated: <?php echo date( 'Y-m-d H:i:s T' ); ?></em></small></p>
                </div>

                <div class="card">
                    <h2>Test Daily Stats Sender</h2>
                    <p>To manually trigger the daily stats sender:</p>
                    <code>pg_manual_send_daily_stats();</code>
                </div>

                <div class="card">
                    <h2>Configuration</h2>
                    <p>Make sure the API key is configured in WordPress options:</p>
                    <ul>
                        <li><strong>API Key Option:</strong> <code>go_stats_key</code></li>
                        <li><strong>API Endpoint:</strong> <code>https://stats.gospelambition.org/api/metrics</code></li>
                        <li><strong>Project ID:</strong> <code>prayer_global</code> (or <code>prayer_global_dev</code> if WP_DEBUG is enabled)</li>
                    </ul>
                    
                    <h3>Current API Key Status</h3>
                    <?php
                    $api_key = get_option( 'go_stats_key' );
                    if ( empty( $api_key ) ) {
                        echo '<p style="color: red;"><strong>❌ No API key configured!</strong> Please set the <code>go_stats_key</code> option.</p>';
                    } else {
                        $key_preview = substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 );
                        echo '<p style="color: green;"><strong>✅ API key configured:</strong> ' . esc_html( $key_preview ) . '</p>';
                    }
                    ?>
                </div>

                <div class="card">
                    <h2>Important Notes</h2>
                    <ul>
                        <li><strong>Processing Time:</strong> This process can take several minutes depending on the date range.</li>
                        <li><strong>API Rate Limiting:</strong> The script includes delays between API calls to avoid overwhelming the server.</li>
                        <li><strong>Error Logging:</strong> All results and errors are logged to the WordPress error log.</li>
                        <li><strong>Permissions:</strong> Only users with 'manage_dt' capability can run this tool.</li>
                        <li><strong>Historical Accuracy:</strong> Lap completion data uses approximations for historical dates. For precise historical data, consider implementing a lap history tracking system.</li>
                        <li><strong>Daily Automation:</strong> Daily stats are automatically sent at 2:00 AM via WordPress cron.</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}

new PG_Historical_Stats_Admin();