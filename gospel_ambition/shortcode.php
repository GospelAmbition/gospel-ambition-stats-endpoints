<?php



function go_stats( $atts ){

    $use_cache = !isset( $_GET['nocache'] );

    $display_all_stats = isset( $_GET['all'] );

    $projects = GO_Sats::get_all_projects( $use_cache );

    ob_start();
    ?>


        <div id="go-stats" class="go-page" style="width:100%; max-width: 1340px">

            <div style='margin-bottom: 40px'>
                <a href="?all=true" >Show all stats</a>
            </div>


            <?php go_display_site( $projects['disciple_tools'] ) ?>

            <?php go_display_cards( $projects['disciple_tools']['stats'] ?? [], $display_all_stats ) ?>

            <?php go_display_site( $projects['pray4movement'] ) ?>

            <?php go_display_cards( $projects['pray4movement']['stats'], $display_all_stats ) ?>

            <?php if ( $display_all_stats ) : ?>
                <h2>
                <img class='go-logo-icon'
                     src="<?php echo esc_html( GO_Context_Switcher::plugin_url( '/assets/icons/pray-circle-logo.png' ) ) ?>"/>Prayer.Global Stats</h2>


                <?php go_display_cards( $projects['prayer_global']['stats'], $display_all_stats ) ?>
            <?php endif; ?>

            <?php go_display_site( $projects['zume_training'] ) ?>

            <?php go_display_cards( $projects['zume_training']['stats'], $display_all_stats ) ?>

            <?php go_display_site( $projects['kingdom_training'] ) ?>

            <?php go_display_cards( $projects['kingdom_training']['stats'], $display_all_stats ) ?>
        </div>

    <?php

    return ob_get_clean();
}
add_shortcode( 'go_stats', 'go_stats' );
