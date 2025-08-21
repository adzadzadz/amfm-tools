<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Init {
    public static function run() {
        // Get enabled components (default to all enabled for first-time users)
        $default_components = array('acf_helper', 'text_utilities', 'optimization', 'shortcodes', 'elementor_widgets', 'import_export');
        $enabled_components = get_option( 'amfm_enabled_components', $default_components );

        // ACF Helper - Core Feature (always enabled)
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-acf-helper.php';
        \ACF_Helper::init();

        // Text Utilities - Optional
        if ( in_array( 'text_utilities', $enabled_components ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'includes/class-text.php';
            \AMFM_Text::init();
        }

        // Performance Optimization - Optional
        if ( in_array( 'optimization', $enabled_components ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'includes/class-optimization.php';
            \AMFM_Optimization::init();
        }

        // Admin Panel & Import/Export - Core Feature (always enabled)
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin.php';
        \AMFM_Admin::init();

        // Shortcode System - Optional
        if ( in_array( 'shortcodes', $enabled_components ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcode-loader.php';
            \AMFM_Shortcode_Loader::init();
        }

        // Elementor Widgets - Optional
        if ( in_array( 'elementor_widgets', $enabled_components ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'includes/elementor/class-elementor-init.php';
            \AMFM_Elementor_Init::init();
        }
    }
}
