<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class TextController extends Controller
{
    public $actions = [
        'init' => 'initialize'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Additional initialization if needed
    }

    public function initialize()
    {
        // Add shortcode to limit text length
        \add_shortcode('limit_words', [$this, 'limitWords']);
        
        if ($this->isAdmin()) {
            // Admin-specific initialization
        }
        
        if ($this->isFrontend()) {
            // Frontend-specific initialization
        }
    }

    public function limitWords($atts, $content = null)
    {
        $atts = shortcode_atts([
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
