<?php

if ( !defined( 'ABSPATH' ) ){
    exit; // Exit if accessed directly
}

if ( !class_exists( 'Gospel_Ambition_Menu' ) ){
    class Gospel_Ambition_Menu{
        private static $_instance = null;

        public static function instance(){
            if ( is_null( self::$_instance ) ){
                self::$_instance = new self();
            }
            return self::$_instance;
        } // End instance()

        public function __construct(){
            add_action( 'admin_menu', array( $this, 'menu' ) );
        }

        public function menu(){
            add_menu_page(
                'Gospel Ambition',
                'Gospel Ambition',
                'edit_posts',
                'gospel_ambition',
                [ $this, 'content' ],
                'dashicons-admin-site-alt',
                50 );
        }

        /**
         * @return void
         */
        public function content(){
            if ( !current_user_can( 'edit_posts' ) ){
                wp_die( 'You do not have sufficient permissions to access this page.' );
            }

            $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'google-analytics';

            ?>
            <div class="wrap">
                <h2><?php esc_html_e( 'GOSPEL AMBITION' ); ?></h2>

                <h2 class="nav-tab-wrapper">
                    <?php do_action( 'gospel_ambition_tab_menu', $tab ); ?>
                </h2>

                <?php do_action( 'gospel_ambition_tab_content', $tab ); ?>

            </div>
            <?php
        }
    }

    Gospel_Ambition_Menu::instance();
}
