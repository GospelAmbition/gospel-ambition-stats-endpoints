<?php



function go_display_stats( $atts ){

    $use_cache = !isset( $_GET['nocache'] );

    $stats_data = get_transient( 'dt-stats' );
    if ( empty( $stats_data ) || !$use_cache ) {
        $stats_data = [
            'stats' => apply_filters( 'go_stats_endpoint', [] ),
            'time' => time(),
        ];

        set_transient( 'dt-stats', $stats_data, DAY_IN_SECONDS );
    }



    ob_start();
    ?>


        <div id="go-stats">

            <h2>
                <img class="go-logo-icon" src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/dt-circle-logo.png' ) ) ?>"/>Disciple.Tools Stats
            </h2>

            <?php go_display_cards( $stats_data["stats"] ) ?>


        </div>

    <?php

    return ob_get_clean();
}
add_shortcode( 'go_display_stats', 'go_display_stats' );
