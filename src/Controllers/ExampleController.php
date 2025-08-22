<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ExampleController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'wp_enqueue_scripts' => 'enqueueScripts'
    ];

    public $filters = [
        'the_content' => 'modifyContent'
    ];


    protected function bootstrap()
    {
        // Additional initialization if needed
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
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueueScripts()
    {
        // Enqueue your scripts and styles here
        wp_enqueue_script('example-script', plugin_dir_url(__FILE__) . '../assets/js/main.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('example-style', plugin_dir_url(__FILE__) . '../assets/css/main.css', [], '1.0.0');
    }

    /**
     * Modify post content
     */
    public function modifyContent($content)
    {
        // Example: Add custom content to posts
        return $content . '<p><!-- Powered by ADZ Framework --></p>';
    }
    
    
}