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
function go_format_stat_value( $value ){
    if ( empty( $value ) ){
        return 'Coming Soon';
    }
    if ( is_numeric( $value ) ){
        return number_format( $value );
    }
    return $value;
}

function go_display_cards( $stats ){
    ?>
    <div class='go-cards'>
        <? foreach ( $stats ?? [] as $stat ) :
            if ( !empty( $stat['public_stats'] ) ) :?>
                <div class="go-card">
                    <div class="go-card-container">
                        <h4 class="go-card-title"><? echo esc_html( $stat['label'] ) ?></h4>
                        <p class="go-card-value"><?php echo esc_html( go_format_stat_value( $stat['value'] ) ); ?></p>
                        <p class="go-stat-desc"><? echo esc_html( $stat['description'] ?? '' ) ?></p>
                    </div>
                </div>
            <? endif; ?>
        <? endforeach; ?>
    </div>
    <?php
}


function go_display_site( $site ){
    ?>
    <h2>
        <img class='go-logo-icon' src="<?php echo esc_html( $site['icon'] ) ?>"/><?php echo esc_html( $site['site_name'] ); ?> Stats
    </h2>
    <?php
}

