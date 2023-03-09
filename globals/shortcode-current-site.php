<?php



function go_display_stats( $atts ){

    $use_cache = !isset( $_GET['nocache'] );
    $all_stats = isset( $_GET['all'] );

    $stats_data = get_transient( 'dt-stats' );
    if ( empty( $stats_data ) || !$use_cache ) {
        $stats_data = [
            'stats' => apply_filters( 'go_stats_endpoint', [] ),
            'time' => time(),
        ];
        $stats_data = apply_filters( 'go_site_info', $stats_data );

        set_transient( 'dt-stats', $stats_data, DAY_IN_SECONDS );
    }



    ob_start();
    ?>


        <div id="go-stats">

            <?php go_display_site( $stats_data ) ?>

            <?php go_display_cards( $stats_data['stats'], $all_stats ) ?>

            <p>
                Stats as of <?php echo esc_html( round( ( time() - $stats_data['time'] ) / 60 / 60, 1 ) ); ?> hour(s) ago.
                <a href='?all=true''>Show all stats</a>
            </p>

        </div>

    <?php

    return ob_get_clean();
}
add_shortcode( 'go_display_stats', 'go_display_stats' );
