<?php

namespace App\Shortcodes;

class TextUtilShortcode
{
    /**
     * Render text_util shortcode
     */
    public function render($atts)
    {
        $atts = \shortcode_atts([
            'action' => 'uppercase',
            'content' => '',
        ], $atts, 'text_util');

        $content = $atts['content'];
        $action = strtolower($atts['action']);

        switch ($action) {
            case 'uppercase':
                return strtoupper($content);
            
            case 'lowercase':
                return strtolower($content);
            
            case 'capitalize':
                return ucwords(strtolower($content));
            
            case 'word_count':
                return (string) str_word_count($content);
            
            case 'char_count':
                return (string) strlen($content);
            
            case 'sentence_case':
                return ucfirst(strtolower($content));
            
            case 'reverse':
                return strrev($content);
            
            case 'strip_spaces':
                return str_replace(' ', '', $content);
            
            case 'trim':
                return trim($content);
            
            default:
                return $content;
        }
    }
}