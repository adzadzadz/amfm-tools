<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Shortcode_Dkv extends AMFM_Shortcode_Base {
    
    protected $tag = 'dkv';
    
    public function render( $atts = array(), $content = null ) {
        $defaults = array(
            'pre' => '',
            'post' => '',
            'fallback' => '',
            'other_keywords' => 'false'
        );
        
        $atts = $this->parse_attributes( $atts, $defaults );
        $atts = $this->sanitize_attributes( $atts );
        
        $use_other_keywords = filter_var( $atts['other_keywords'], FILTER_VALIDATE_BOOLEAN );
        $keyword = $this->get_random_keyword( $use_other_keywords );
        
        if ( empty( $keyword ) ) {
            return $atts['fallback'];
        }
        
        return $atts['pre'] . $keyword . $atts['post'];
    }
    
    private function get_random_keyword( $use_other_keywords = false ) {
        $keywords = array();
        $cookie_name = $use_other_keywords ? 'amfm_other_keywords' : 'amfm_keywords';
        
        // Get keywords from the specified cookie
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            $cookie_keywords = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );
            if ( is_array( $cookie_keywords ) ) {
                $keywords = $cookie_keywords;
            }
        }
        
        // Clean up keywords (remove empty values and trim whitespace)
        $keywords = array_filter( array_map( 'trim', $keywords ) );
        
        if ( empty( $keywords ) ) {
            return '';
        }
        
        // Return a random keyword
        return $keywords[ array_rand( $keywords ) ];
    }
}