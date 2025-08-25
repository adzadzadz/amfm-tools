<?php

namespace App\Shortcodes;

class LimitWordsShortcode
{
    /**
     * Render limit_words shortcode
     */
    public function render($atts, $content = null)
    {
        $atts = \shortcode_atts([
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words');

        // Get the ACF text value
        if (!empty($atts['text'])) {
            $content = \get_field($atts['text']);
        } else {
            $content = $content;
        }

        // Limit the number of words
        if (!empty($content)) {
            $words = explode(' ', $content);
            if (count($words) > $atts['words']) {
                $content = implode(' ', array_slice($words, 0, $atts['words'])) . '...';
            }
        } else {
            $content = '';
        }

        return $content;
    }
}