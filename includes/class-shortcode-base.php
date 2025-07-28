<?php

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class AMFM_Shortcode_Base {
    
    protected $tag;
    
    public function __construct() {
        $this->register_shortcode();
    }
    
    protected function register_shortcode() {
        if ( $this->tag ) {
            add_shortcode( $this->tag, array( $this, 'render' ) );
        }
    }
    
    abstract public function render( $atts = array(), $content = null );
    
    protected function parse_attributes( $atts, $defaults = array() ) {
        return shortcode_atts( $defaults, $atts );
    }
    
    protected function sanitize_attributes( $atts ) {
        $sanitized = array();
        foreach ( $atts as $key => $value ) {
            $sanitized[ $key ] = sanitize_text_field( $value );
        }
        return $sanitized;
    }
}