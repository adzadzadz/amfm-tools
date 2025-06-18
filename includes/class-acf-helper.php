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
        // run set_keywords_to_cookies method on all post and pages
        add_action( 'wp', [ __CLASS__, 'set_keywords_to_cookies' ] );
    }
    
    public static function set_keywords_to_cookies() {
        // Get the keywords from the ACF field
        $keywords = self::get_keywords();

        // Set the keywords in cookies
        if ( ! empty( $keywords['keywords'] ) ) {
            setcookie( 'amfm_keywords', json_encode( $keywords['keywords'] ), time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
        }

        if ( ! empty( $keywords['other_keywords'] ) ) {
            setcookie( 'amfm_other_keywords', json_encode( $keywords['other_keywords'] ), time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
        }
    }

    public static function get_keywords() {
        // Get the keywords from the ACF field
        $keywords = get_field( 'amfm_keywords' );
        $otherKeywords = get_field( 'amfm_other_keywords' );

        // Return the keywords as an array
        return [
            'keywords' => $keywords ? explode( ',', $keywords ) : [],
            'other_keywords' => $otherKeywords ? explode( ',', $otherKeywords ) : []
        ];
    }
}
