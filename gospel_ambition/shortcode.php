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


function go_stats( $atts ){

    $use_cache = true;

    $dt_stats = dt_cached_api_call( 'https://disciple.tools/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $dt_stats = json_decode( $dt_stats, true );

    $p4m_stats = dt_cached_api_call( 'https://pray4movement.org/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $p4m_stats = json_decode( $p4m_stats, true );
    $p4m_stats['stats']['minutes_of_prayer']['value'] = go_display_minutes( $p4m_stats['stats']['minutes_of_prayer']['value'] );

    $pg_stats = dt_cached_api_call( 'https://prayer.global/wp-json/go/v1/stats?', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $pg_stats = json_decode( $pg_stats, true );

    $zume_stats = dt_cached_api_call( 'https://zume.vision/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $zume_stats = json_decode( $zume_stats, true );


    ob_start();
    ?>


        <div id="go-stats" style="width:100%; max-width: 100% !important;">

            <h2>
                <img class="go-logo-icon" src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/dt-circle-logo.png' ) ) ?>"/>Disciple.Tools Stats
            </h2>

            <div class='go-cards'>
                <? foreach ( $dt_stats['stats'] as $stat ) :
                    if ( !empty( $stat['public_stats'] ) ) :?>
                        <div class="go-card">
                            <div class="go-card-container">
                                <h4 class="go-card-title"><? echo esc_html( $stat['label'] ) ?></h4>
                                <p class="go-card-value"><strong><? echo esc_html( $stat['value'] ) ?></strong></p>
                                <p class="go-stat-desc"><? echo esc_html( $stat['description'] ?? '' ) ?></p>
                            </div>
                        </div>
                    <? endif; ?>
                <? endforeach; ?>
            </div>

            <br>
            <br>

            <h2><img class='go-logo-icon'
                src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/p4m-circle-logo.png' ) ) ?>"/>Pray4movement Stats</h2>

            <div class='go-cards'>
                <? foreach ( $p4m_stats['stats'] as $stat ) : ?>
                    <div class="go-card">
                        <div class="go-card-container">
                            <h4 class="go-card-title"><? echo esc_html( $stat['label'] ) ?></h4>
                            <p class='go-card-value'><strong><? echo esc_html( $stat['value'] ) ?></strong></p>
                            <p class="go-stat-desc"><? echo esc_html( $stat['description'] ?? '' ) ?></p>
                        </div>
                    </div>
                <? endforeach; ?>
            </div>


            <br>
            <br>
            <h2>
                <img class='go-logo-icon'
                     src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/pray-circle-logo.png' ) ) ?>"/>Prayer.Global Stats</h2>


            <div class='go-cards'>
                <? foreach ( $pg_stats['stats'] as $stat ) :
                    if ( !empty( $stat['public_stats'] ) ) :?>
                        <div class="go-card">
                            <div class="go-card-container">
                                <h4 class="go-card-title"><? echo esc_html( $stat['label'] ) ?></h4>
                                <p class='go-card-value'><strong><? echo esc_html( $stat['value'] ) ?></strong></p>
                                <p class="go-stat-desc"><? echo esc_html( $stat['description'] ?? '' ) ?></p>
                            </div>
                        </div>
                    <? endif; ?>
                <? endforeach; ?>
            </div>

            <br>
            <br>

            <h2><img class='go-logo-icon'
                     src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/zume-circle-logo.png' ) ) ?>"/>Zúme Stats</h2>

            <div class='go-cards'>
                <? foreach ( $zume_stats['stats'] as $stat ) : ?>
                    <div class="go-card">
                        <div class="go-card-container">
                            <h4 class="go-card-title"><? echo esc_html( $stat['label'] ) ?></h4>
                            <p class='go-card-value'><strong><? echo esc_html( $stat['value'] ) ?></strong></p>
                            <p class="go-stat-desc"><? echo esc_html( $stat['description'] ?? '' ) ?></p>
                        </div>
                    </div>
                <? endforeach; ?>
            </div>


            <br>
            <br>
        </div>





    <?php

    return ob_get_clean();
}
add_shortcode( 'go_stats', 'go_stats' );
