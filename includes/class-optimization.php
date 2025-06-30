<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Optimization {

    public static function init() {
       self::run_snippets();
    }

    public static function run_snippets() {
        add_filter( 'gform_noconflict_scripts', '__return_true' );
        add_filter( 'gform_noconflict_styles', '__return_true' );

        add_action( 'wp_enqueue_scripts', 'conditionally_load_gf_assets', 11 );
        function conditionally_load_gf_assets() {
            // Only load on pages with Gravity Forms shortcode or block
            global $post;
            if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'gravityform' ) ) {
                wp_dequeue_style( 'gforms_css' );
                wp_dequeue_script( 'gforms_conditional_logic' );
                wp_dequeue_script( 'gform_gravityforms' );
                wp_dequeue_script( 'gform_json' );
                wp_dequeue_script( 'gform_placeholder' );
                wp_dequeue_script( 'gform_masked_input' );
                wp_dequeue_script( 'gform_datepicker_init' );
            }
        }
    }
}