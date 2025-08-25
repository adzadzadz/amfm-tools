<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ElementorController extends Controller
{
    public $actions = [
        'elementor/loaded' => 'initializeElementor',
        'elementor/elements/categories_registered' => 'registerWidgetCategory',
        'elementor/widgets/register' => 'registerWidgets'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
    }

    /**
     * Initialize Elementor integration
     */
    public function initializeElementor()
    {
        // Additional Elementor initialization if needed
    }

    /**
     * Register AMFM widget category
     */
    public function registerWidgetCategory($elements_manager)
    {
        $elements_manager->add_category(
            'amfm-widgets',
            [
                'title' => __('AMFM Widgets', 'amfm-tools'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Register Elementor widgets
     */
    public function registerWidgets($widgets_manager)
    {
        // Get enabled widgets from settings
        $enabled_widgets = \get_option('amfm_elementor_enabled_widgets', ['amfm_related_posts']);
        
        // Widget registry
        $available_widgets = [
            'amfm_related_posts' => [
                'file' => 'Widgets/Elementor/RelatedPostsWidget.php',
                'class' => 'App\\Widgets\\Elementor\\RelatedPostsWidget'
            ]
        ];
        
        // Register only enabled widgets
        foreach ($available_widgets as $widget_key => $widget_info) {
            if (in_array($widget_key, $enabled_widgets)) {
                // Load widget file if it exists
                $widget_file = AMFM_TOOLS_PATH . 'src/' . $widget_info['file'];
                
                if (file_exists($widget_file)) {
                    require_once $widget_file;
                    
                    // Register widget
                    $class_name = $widget_info['class'];
                    if (class_exists($class_name)) {
                        $widgets_manager->register(new $class_name());
                    }
                }
            }
        }
    }
}