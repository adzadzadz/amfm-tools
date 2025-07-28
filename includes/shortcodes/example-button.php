<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Shortcode_Example_Button extends AMFM_Shortcode_Base {
    
    protected $tag = 'amfm_button';
    
    public function render( $atts = array(), $content = null ) {
        $defaults = array(
            'text' => 'Click Me',
            'url' => '#',
            'style' => 'primary',
            'target' => '_self'
        );
        
        $atts = $this->parse_attributes( $atts, $defaults );
        $atts = $this->sanitize_attributes( $atts );
        
        $classes = 'amfm-button amfm-button--' . esc_attr( $atts['style'] );
        
        return sprintf(
            '<a href="%s" class="%s" target="%s">%s</a>',
            esc_url( $atts['url'] ),
            esc_attr( $classes ),
            esc_attr( $atts['target'] ),
            esc_html( $atts['text'] )
        );
    }
}