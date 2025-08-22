<?php

namespace adz\controllers;

use AdzWP\WordPressController as Controller;

class OptimizationController extends Controller
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
        // Check if optimization component is enabled
        $enabled_components = get_option('amfm_enabled_components', array());
        if (!in_array('optimization', $enabled_components)) {
            return;
        }

        // Add optimization hooks
        add_action('wp_enqueue_scripts', array($this, 'optimizeAssets'));
        add_action('init', array($this, 'enableGzipCompression'));
    }

    public function optimizeAssets()
    {
        // Remove unnecessary WordPress assets
        wp_dequeue_script('wp-embed');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
    }

    public function enableGzipCompression()
    {
        if (!ob_get_level()) {
            ob_start('ob_gzhandler');
        }
    }
}
