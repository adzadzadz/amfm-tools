<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class OptimizationController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'conditionallyLoadGFAssets'
    ];

    public $filters = [
        'gform_noconflict_scripts' => 'enableGFNoConflictScripts',
        'gform_noconflict_styles' => 'enableGFNoConflictStyles'
    ];

    protected function bootstrap()
    {
        // Only initialize if optimization is enabled in config
        $config = \Adz::config();
        if (!$config->get('components.optimization', true)) {
            // Disable all hooks if optimization is disabled
            $this->actions = [];
            $this->filters = [];
            return;
        }
    }

    public function initialize()
    {
        // WordPress initialization logic
        if ($this->isAdmin()) {
            // Admin-specific initialization
        }
        
        if ($this->isFrontend()) {
            // Frontend-specific initialization
        }
    }

    public function enableGFNoConflictScripts()
    {
        return true;
    }

    public function enableGFNoConflictStyles()
    {
        return true;
    }

    public function conditionallyLoadGFAssets()
    {
        // Only load on pages with Gravity Forms shortcode or block
        global $post;
        if (!\is_a($post, 'WP_Post') || !\has_shortcode($post->post_content, 'gravityform')) {
            wp_dequeue_style('gforms_css');
            wp_dequeue_script('gforms_conditional_logic');
            wp_dequeue_script('gform_gravityforms');
            wp_dequeue_script('gform_json');
            wp_dequeue_script('gform_placeholder');
            wp_dequeue_script('gform_masked_input');
            wp_dequeue_script('gform_datepicker_init');
        }
    }
}
