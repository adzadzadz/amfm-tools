<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Shortcode_Loader {
    
    public static function init() {
        self::load_base_class();
        self::load_shortcodes();
    }
    
    private static function load_base_class() {
        require_once plugin_dir_path( __FILE__ ) . 'class-shortcode-base.php';
    }
    
    private static function load_shortcodes() {
        $shortcode_dir = plugin_dir_path( __FILE__ ) . 'shortcodes/';
        
        if ( ! is_dir( $shortcode_dir ) ) {
            return;
        }
        
        $shortcode_files = glob( $shortcode_dir . '*.php' );
        
        foreach ( $shortcode_files as $file ) {
            require_once $file;
            
            $class_name = self::get_class_name_from_file( $file );
            if ( class_exists( $class_name ) ) {
                new $class_name();
            }
        }
    }
    
    private static function get_class_name_from_file( $file ) {
        $filename = basename( $file, '.php' );
        $parts = explode( '-', $filename );
        $class_parts = array_map( 'ucfirst', $parts );
        return 'AMFM_Shortcode_' . implode( '_', $class_parts );
    }
}