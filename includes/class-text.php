<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Text {

    public static function init() {
        // add shortcode to limit text length
        add_shortcode( 'limit_words', array( __CLASS__, 'limit_words' ) );
    }

    // add shortcode to limit text length, sample usage: [limit_words text="description" words="20"]
    public static function limit_words( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'text' => '',
            'words' => 20,
        ), $atts, 'limit_words' );

        // get the acf text value
        if ( ! empty( $atts['text'] ) ) {
            $content = get_field( $atts['text'] );
        } else {
            $content = $content;
        }

        // limit the number of words
        if ( ! empty( $content ) ) {
            $words = explode( ' ', $content );
            if ( count( $words ) > $atts['words'] ) {
                $content = implode( ' ', array_slice( $words, 0, $atts  ['words'] ) ) . '...';
            }
        } else {
            $content = '';
        }

        return $content;
    }


}