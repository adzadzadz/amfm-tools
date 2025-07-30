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
        // Get enabled widgets from settings
        $enabled_widgets = get_option( 'amfm_elementor_enabled_widgets', array( 'amfm_related_posts' ) );
        
        // Widget registry
        $available_widgets = array(
            'amfm_related_posts' => array(
                'file' => 'widgets/class-related-posts-widget.php',
                'class' => 'AMFM_Related_Posts_Widget'
            )
        );
        
        // Register only enabled widgets
        foreach ( $available_widgets as $widget_key => $widget_info ) {
            if ( in_array( $widget_key, $enabled_widgets ) ) {
                // Load widget file
                require_once plugin_dir_path( __FILE__ ) . $widget_info['file'];
                
                // Register widget
                $class_name = '\\' . $widget_info['class'];
                if ( class_exists( $class_name ) ) {
                    $widgets_manager->register( new $class_name() );
                }
            }
        }
    }
}