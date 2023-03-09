<?php



function go_stats( $atts ){

    $use_cache = !isset( $_GET['nocache'] );

    $all_stats = isset( $_GET['all'] );

    $dt_stats = dt_cached_api_call( 'https://disciple.tools/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $dt_stats = json_decode( $dt_stats, true );

    $p4m_stats = dt_cached_api_call( 'https://pray4movement.org/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $p4m_stats = json_decode( $p4m_stats, true );
    $p4m_stats['stats']['minutes_of_prayer']['value'] = go_display_minutes( $p4m_stats['stats']['minutes_of_prayer']['value'] );

    $pg_stats = dt_cached_api_call( 'https://prayer.global/wp-json/go/v1/stats?', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $pg_stats = json_decode( $pg_stats, true );

    $zume_stats = dt_cached_api_call( 'https://zume.vision/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $zume_stats = json_decode( $zume_stats, true );

    $kt_stats = dt_cached_api_call( 'https://kingdom.training/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
    $kt_stats = json_decode( $kt_stats, true );


    ob_start();
    ?>


        <div id="go-stats" style="width:100%; max-width: 1340px">

            <div style='margin-bottom: 40px'>
                <a href="?all=true" >Show all stats</a>
            </div>


            <h2>
                <img class="go-logo-icon" src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/dt-circle-logo.png' ) ) ?>"/>Disciple.Tools Stats
            </h2>

            <?php go_display_cards( $dt_stats['stats'] ?? [], $all_stats ) ?>


            <h2><img class='go-logo-icon'
                src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/p4m-circle-logo.png' ) ) ?>"/>Pray4movement Stats</h2>

            <?php go_display_cards( $p4m_stats['stats'], $all_stats ) ?>

            <?php if ( $all_stats ) : ?>
                <h2>
                <img class='go-logo-icon'
                     src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/pray-circle-logo.png' ) ) ?>"/>Prayer.Global Stats</h2>


                <?php go_display_cards( $pg_stats['stats'], $all_stats ) ?>
            <?php endif; ?>


            <h2><img class='go-logo-icon'
                     src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/zume-circle-logo.png' ) ) ?>"/>Zúme Stats</h2>

            <?php go_display_cards( $zume_stats['stats'], $all_stats ) ?>

            <?php go_display_site( $kt_stats ) ?>

            <?php go_display_cards( $kt_stats['stats'], $all_stats ) ?>
        </div>

    <?php

    return ob_get_clean();
}
add_shortcode( 'go_stats', 'go_stats' );
