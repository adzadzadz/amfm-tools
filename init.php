<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Init {
    public static function run() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-acf-helper.php';
        \ACF_Helper::init();

        require_once plugin_dir_path( __FILE__ ) . 'includes/class-text.php';
        \AMFM_Text::init();

        require_once plugin_dir_path( __FILE__ ) . 'includes/class-optimization.php';
        \AMFM_Optimization::init();
    }
}
