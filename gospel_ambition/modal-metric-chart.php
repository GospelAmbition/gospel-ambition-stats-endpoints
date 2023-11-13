<div class="large reveal" id="ga_metrics_modal" style="display: none;" data-reveal data-reset-on-close>
    <h3 id="ga_metrics_modal_title"></h3>
    <hr>

    <div style="justify-content: center; display: flex;">
        <div class="loading-spinner"></div>
    </div>

    <div id="ga_metrics_modal_content"></div>
    <br>

    <button class="button loader" data-close aria-label="Close reveal" type="button">
        <?php echo esc_html__( 'Close' ) ?>
    </button>

    <span id="ga_metrics_modal_buttons"></span>

    <button class="close-button" data-close aria-label="<?php esc_html_e( 'Close' ); ?>"
            type="button">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
