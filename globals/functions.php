<?php


if ( !function_exists( 'dt_cached_api_call' ) ){
    function dt_cached_api_call( $url, $type = 'GET', $args = [], $duration = HOUR_IN_SECONDS, $use_cache = true ){
        $data = get_transient( 'dt_cached_' . esc_url( $url ) );
        if ( !$use_cache || empty( $data ) ){
            if ( $type === 'GET' ){
                $response = wp_remote_get( $url, $args );
            } else {
                $response = wp_remote_post( $url, $args );
            }
            if ( is_wp_error( $response ) || isset( $response['response']['code'] ) && $response['response']['code'] !== 200 ){
                return false;
            }
            $data = wp_remote_retrieve_body( $response );

            set_transient( 'dt_cached_' . esc_url( $url ), $data, $duration );
        }
        return $data;
    }
}
function go_format_stat_value( $value, $type = null ){
    if ( empty( $value ) ){
        return 'Coming Soon';
    }
    if ( $type === 'minutes' ){
        return go_display_minutes( $value );
    }
    if ( is_numeric( $value ) ){
        return number_format( $value );
    }
    return $value;
}

function go_display_stats_icon( $stat ){
    if ( !empty( $stat['icon'] ) ){
        echo '<span class="go-stats-icon ' . esc_html( $stat['icon'] ) . '"></span>';
    }
    return '';
}

function go_display_cards( $project_id, $stats, $display_all = false, $ignored_display_chart_stats = [] ){
    ?>
    <div class='go-cards'>
        <?php foreach ( $stats ?? [] as $stat_key => $stat ) :
            if ( !empty( $stat['public_stats'] ) || $display_all ) :?>
                <div class="go-card">
                    <div class="go-card-container">
                        <h4 class="go-card-title">
                            <?php go_display_stats_icon( $stat ) ?>
                            <?php echo esc_html( $stat['label'] ) ?>
                            <?php
                            if ( !in_array( $stat_key, $ignored_display_chart_stats ) ) {
                                ?>
                                <span class="mdi mdi-chart-box-outline display-metric-chart"
                                      style="float: right; cursor: pointer;"
                                      data-project_id="<?php echo esc_html( $project_id )?>"
                                      data-metric="<?php echo esc_html( $stat_key )?>"
                                      data-metric_title="<?php echo esc_html( $stat['label'] )?>"
                                      data-metric_type="<?php echo esc_html( $stat['type'] ?? '' )?>"
                                ></span>
                                <?php
                            }
                            ?>
                        </h4>
                        <?php if ( is_array( $stat['value'] ) ) : ?>
                            <div class="go-card-array">
                                <?php foreach ( $stat['value'] as $value ) : ?>
                                    <p><?php echo esc_html( $value['label'] ?? '' ) ?> (<?php echo esc_html( $value['value'] ?? '' ); ?>)</p>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p class="go-card-value"><?php echo esc_html( go_format_stat_value( $stat['value'], $stat['type'] ?? null ) ); ?>
                            <?php if ( !empty( $stat['note'] ) ) : ?>
                                <span class="go-card-note"><?php echo esc_html( $stat['note'] ); ?></span>
                            <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <p class="go-stat-desc"><?php echo esc_html( $stat['description'] ?? '' ) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

function go_display_site( $site ){
    ?>
    <h2>
        <img class='go-logo-icon' src="<?php echo esc_html( $site['icon'] ) ?>"/><?php echo esc_html( $site['site_name'] ?? '' ); ?> Stats
    </h2>
    <?php if ( !empty( $site['site_description'] ) ) : ?>
        <p class="site-description">
        <?php
            $url = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
            $site['site_description'] = preg_replace( $url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $site['site_description'] );
            echo nl2br( wp_kses_post( $site['site_description'] ) );
        ?>
        </p>
    <?php endif;
}
