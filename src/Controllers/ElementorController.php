<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ElementorController extends Controller
{
    public $actions = [
        'elementor/loaded' => 'initializeElementor',
        'elementor/elements/categories_registered' => 'registerWidgetCategory',
        'elementor/widgets/register' => 'registerWidgets',
        'init' => 'debugElementorStatus'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Check if Elementor is active before setting up hooks
        if (!did_action('elementor/loaded')) {
            // If Elementor hasn't loaded yet, wait for it
            add_action('elementor/loaded', [$this, 'ensureElementorHooks']);
        } else {
            // Elementor is already loaded
            $this->ensureElementorHooks();
        }
    }
    
    /**
     * Ensure Elementor hooks are properly set up
     */
    public function ensureElementorHooks()
    {
        // Force re-registration if needed
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance && \Elementor\Plugin::$instance->elements_manager) {
            // Re-trigger category registration if needed
            $categories = \Elementor\Plugin::$instance->elements_manager->get_categories();
            if (!isset($categories['amfm-tools'])) {
                $this->registerWidgetCategory(\Elementor\Plugin::$instance->elements_manager);
            }
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
     * Debug Elementor status - helps troubleshoot registration issues
     */
    public function debugElementorStatus()
    {
        // Only run debug for admin users
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if this is an admin page request
        if (!is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        error_log("AMFM DEBUG: Elementor status check");
        error_log("AMFM DEBUG: Elementor Plugin class exists: " . (class_exists('\Elementor\Plugin') ? 'YES' : 'NO'));
        error_log("AMFM DEBUG: RelatedPostsWidget class exists: " . (class_exists('App\\Widgets\\Elementor\\RelatedPostsWidget') ? 'YES' : 'NO'));
        // elementor_widgets component is now always enabled - widgets controlled individually
        error_log("AMFM DEBUG: amfm_related_posts widget enabled: " . (in_array('amfm_related_posts', get_option('amfm_elementor_enabled_widgets', ['amfm_related_posts'])) ? 'YES' : 'NO'));
    }

    /**
     * Register AMFM widget category
     */
    public function registerWidgetCategory($elements_manager)
    {
        // Check if category already exists
        $existing_categories = $elements_manager->get_categories();
        if (isset($existing_categories['amfm-tools'])) {
            return; // Category already registered
        }
        
        try {
            $elements_manager->add_category(
                'amfm-tools',
                [
                    'title' => __('AMFM Tools', 'amfm-tools'),
                    'icon' => 'fa fa-plug',
                ]
            );
            error_log("AMFM: Successfully registered widget category 'amfm-tools'");
        } catch (Exception $e) {
            error_log("AMFM: Error registering widget category: " . $e->getMessage());
        }
    }

    /**
     * Register Elementor widgets
     */
    public function registerWidgets($widgets_manager)
    {
        // Check if Elementor is properly loaded
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        // Get enabled widgets from settings
        $enabled_widgets = \get_option('amfm_elementor_enabled_widgets', ['amfm_related_posts']);
        
        // Widget registry
        $available_widgets = [
            'amfm_related_posts' => [
                'file' => 'Widgets/Elementor/RelatedPostsWidget.php',
                'class' => 'App\\Widgets\\Elementor\\RelatedPostsWidget'
            ],
            'amfm_show' => [
                'file' => 'Widgets/Elementor/Bylines/ShowWidget.php',
                'class' => 'App\\Widgets\\Elementor\\Bylines\\ShowWidget'
            ],
            'amfm_bylines_posts' => [
                'file' => 'Widgets/Elementor/Bylines/PostsWidget.php',
                'class' => 'App\\Widgets\\Elementor\\Bylines\\PostsWidget'
            ],
            'amfm_bylines_featured_images' => [
                'file' => 'Widgets/Elementor/Bylines/FeaturedImagesWidget.php',
                'class' => 'App\\Widgets\\Elementor\\Bylines\\FeaturedImagesWidget'
            ],
            'amfm_bylines_display' => [
                'file' => 'Widgets/Elementor/Bylines/BylinesWidget.php',
                'class' => 'App\\Widgets\\Elementor\\Bylines\\BylinesWidget'
            ],
            'amfm_staff_grid' => [
                'file' => 'Widgets/Elementor/Bylines/StaffGridWidget.php',
                'class' => 'App\\Widgets\\Elementor\\Bylines\\StaffGridWidget'
            ]
        ];
        
        // Register only enabled widgets
        foreach ($available_widgets as $widget_key => $widget_info) {
            if (in_array($widget_key, $enabled_widgets)) {
                $class_name = $widget_info['class'];
                
                // Try to instantiate the class
                if (class_exists($class_name)) {
                    try {
                        $widget_instance = new $class_name();
                        $widgets_manager->register($widget_instance);
                        
                        // Debug log (can be removed in production)
                        error_log("AMFM: Successfully registered widget: {$widget_key}");
                    } catch (Exception $e) {
                        error_log("AMFM: Error instantiating widget {$widget_key}: " . $e->getMessage());
                    }
                } else {
                    // Fallback: try to load the file manually if class doesn't exist
                    $widget_file = AMFM_TOOLS_PATH . 'src/' . $widget_info['file'];
                    if (file_exists($widget_file)) {
                        require_once $widget_file;
                        if (class_exists($class_name)) {
                            try {
                                $widget_instance = new $class_name();
                                $widgets_manager->register($widget_instance);
                                error_log("AMFM: Successfully registered widget after manual load: {$widget_key}");
                            } catch (Exception $e) {
                                error_log("AMFM: Error after manual load for widget {$widget_key}: " . $e->getMessage());
                            }
                        } else {
                            error_log("AMFM: Widget class not found after manual load: {$class_name}");
                        }
                    } else {
                        error_log("AMFM: Widget file not found: {$widget_file}");
                    }
                }
            }
        }
    }
}