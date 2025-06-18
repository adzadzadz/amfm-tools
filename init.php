<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Init {
    public static function run() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-acf-helper.php';
        \ACF_Helper::init();
    }
}
