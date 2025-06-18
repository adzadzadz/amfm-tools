<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ACF_Helper {

    public static function init() {
        // Check if ACF is active, run method if it is
        if ( class_exists( 'ACF' ) ) {
            self::run();
        } else {
            // Optionally, you can log an error or notify the user that ACF is not active
            error_log( 'ACF_Helper: Advanced Custom Fields is not active.' );
        }
    }

    // run method
    public static function run() {
        // Code to run the ACF helper
    }
}
