<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Elementor_Init {

    public static function init() {
        // Check if Elementor is active
        if ( did_action( 'elementor/loaded' ) ) {
            self::run();
        } else {
            add_action( 'elementor/loaded', [ __CLASS__, 'run' ] );
        }
    }

    public static function run() {
        // Register widget category
        add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_widget_category' ] );
        
        // Register widgets
        add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
    }

    public static function register_widget_category( $elements_manager ) {
        $elements_manager->add_category(
            'amfm-widgets',
            [
                'title' => __( 'AMFM Widgets', 'amfm-tools' ),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public static function register_widgets( $widgets_manager ) {
        // Load widget files
        require_once plugin_dir_path( __FILE__ ) . 'widgets/class-related-posts-widget.php';
        
        // Register widgets
        $widgets_manager->register( new \AMFM_Related_Posts_Widget() );
    }
}