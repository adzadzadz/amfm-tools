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
        
        // Custom sanitization to preserve spaces in pre/post
        $sanitized_atts = array(
            'pre' => wp_kses_post( $atts['pre'] ), // Preserve spaces
            'post' => wp_kses_post( $atts['post'] ), // Preserve spaces
            'fallback' => sanitize_text_field( $atts['fallback'] ),
            'other_keywords' => sanitize_text_field( $atts['other_keywords'] )
        );
        
        $use_other_keywords = filter_var( $sanitized_atts['other_keywords'], FILTER_VALIDATE_BOOLEAN );
        $keyword = $this->get_random_keyword( $use_other_keywords );
        
        if ( empty( $keyword ) ) {
            return $sanitized_atts['fallback'];
        }
        
        return $sanitized_atts['pre'] . $keyword . $sanitized_atts['post'];
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
        
        // Apply global keyword filters
        $keywords = $this->filter_excluded_keywords( $keywords );
        
        if ( empty( $keywords ) ) {
            return '';
        }
        
        // Return a random keyword
        return $keywords[ array_rand( $keywords ) ];
    }
    
    private function filter_excluded_keywords( $keywords ) {
        // Get excluded keywords from option (includes defaults + custom)
        $excluded_keywords = $this->get_excluded_keywords();
        
        // Convert to lowercase for case-insensitive matching
        $excluded_keywords = array_map( 'strtolower', $excluded_keywords );
        
        // Filter out excluded keywords
        $filtered_keywords = array();
        foreach ( $keywords as $keyword ) {
            $keyword_lower = strtolower( trim( $keyword ) );
            if ( ! in_array( $keyword_lower, $excluded_keywords ) ) {
                $filtered_keywords[] = $keyword;
            }
        }
        
        return $filtered_keywords;
    }
    
    private function get_excluded_keywords() {
        // Get excluded keywords from option
        $excluded_keywords = get_option( 'amfm_excluded_keywords', null );
        
        // If option doesn't exist, initialize with defaults
        if ( $excluded_keywords === null ) {
            $excluded_keywords = $this->get_default_excluded_keywords();
            update_option( 'amfm_excluded_keywords', $excluded_keywords );
        }
        
        if ( ! is_array( $excluded_keywords ) ) {
            $excluded_keywords = array();
        }
        
        return $excluded_keywords;
    }
    
    private function get_default_excluded_keywords() {
        return array(
            'co-occurring',
            'life adjustment transition',
            'comorbidity',
            'comorbid',
            'co-morbidity',
            'co-morbid'
        );
    }
}