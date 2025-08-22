<?php

namespace adz\controllers;

use AdzWP\WordPressController as Controller;

class ShortcodeController extends Controller
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
        // Check if shortcodes component is enabled
        $enabled_components = get_option('amfm_enabled_components', array());
        if (!in_array('shortcodes', $enabled_components)) {
            return;
        }

        // Register DKV shortcode
        add_shortcode('dkv', array($this, 'dkvShortcode'));
    }

    public function dkvShortcode($atts)
    {
        $atts = shortcode_atts(array(
            'key' => '',
            'default' => ''
        ), $atts, 'dkv');

        if (empty($atts['key'])) {
            return $atts['default'];
        }

        // Get value from options or meta
        $value = get_option($atts['key'], $atts['default']);
        
        return esc_html($value);
    }
}
