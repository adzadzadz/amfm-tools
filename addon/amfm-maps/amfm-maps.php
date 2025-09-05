<?php

/**
 * Plugin Name: AMFM Maps
 * Description: A custom Elementor module to display various maps and elements.
 * Version: 3.4.7
 * Author:            Adrian T. Saycon
 * Author URI:        https://adzbyte.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       amfm-maps
 * Domain Path:       /languages
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define version
define('AMFM_MAPS_VERSION', '3.4.7');
define('AMFM_MAPS_API_KEY', 'AIzaSyAZLD2M_Rnz6p6d-d57bNOWggRUEC3ZmNc');

// Check if Elementor is installed and active
function amfm_maps_check_elementor()
{
    if (! did_action('elementor/loaded')) {
        add_action('admin_notices', 'amfm_maps_admin_notice_missing_elementor');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

// Admin notice for missing Elementor
function amfm_maps_admin_notice_missing_elementor()
{
?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e('AMFM Maps requires Elementor to be installed and activated.', 'amfm-maps'); ?></p>
    </div>
<?php
}

// Initialize the plugin
function amfm_maps_init()
{
    if (! amfm_maps_check_elementor()) {
        return;
    }

    // Include the necessary files
    require_once __DIR__ . '/includes/elementor/class-map-widget.php';
    require_once __DIR__ . '/includes/elementor/class-map-filter-widget.php';
    require_once __DIR__ . '/admin/class-amfm-maps-admin.php';
    require_once __DIR__ . '/admin/class-amfm-maps-locations-manager.php';
    require_once __DIR__ . '/admin/class-amfm-maps-filtered-endpoints.php';

    // Register the widgets
    function register_amfm_map_widgets()
    {
        \Elementor\Plugin::instance()->widgets_manager->register(new \AMFM_Maps\Elementor\MapWidget());
        \Elementor\Plugin::instance()->widgets_manager->register(new \AMFM_Maps\Elementor\MapFilterWidget());
    }

    // Add custom Elementor category
    function add_amfm_elementor_category($elements_manager) {
        $elements_manager->add_category(
            'amfm-maps',
            [
                'title' => __('AMFM Maps', 'amfm-maps'),
                'icon' => 'eicon-map-pin',
            ]
        );
    }
    add_action('elementor/elements/categories_registered', 'add_amfm_elementor_category');

    // Hook to register the widgets
    add_action('elementor/widgets/widgets_registered', 'register_amfm_map_widgets');

    // Initialize admin functionality
    if (is_admin()) {
        $amfm_maps_admin = new Amfm_Maps_Admin('amfm-maps', AMFM_MAPS_VERSION);
        $GLOBALS['amfm_maps_locations_manager'] = new Amfm_Maps_Locations_Manager('amfm-maps', AMFM_MAPS_VERSION);
        $GLOBALS['amfm_maps_filtered_endpoints'] = new Amfm_Maps_Filtered_Endpoints('amfm-maps', AMFM_MAPS_VERSION);
        
        add_action('admin_menu', array($amfm_maps_admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($amfm_maps_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($amfm_maps_admin, 'enqueue_scripts'));
        
        // Initialize hooks
        $GLOBALS['amfm_maps_locations_manager']->init_hooks();
        $GLOBALS['amfm_maps_filtered_endpoints']->init_hooks();
    }
    
    // Always initialize REST API classes and hooks for both admin and frontend
    // This ensures REST API endpoints are available regardless of context
    if (!isset($GLOBALS['amfm_maps_locations_manager'])) {
        $GLOBALS['amfm_maps_locations_manager'] = new Amfm_Maps_Locations_Manager('amfm-maps', AMFM_MAPS_VERSION);
    }
    if (!isset($GLOBALS['amfm_maps_filtered_endpoints'])) {
        $GLOBALS['amfm_maps_filtered_endpoints'] = new Amfm_Maps_Filtered_Endpoints('amfm-maps', AMFM_MAPS_VERSION);
    }
    
    // Initialize REST API hooks for both classes
    $GLOBALS['amfm_maps_locations_manager']->init_hooks();
    $GLOBALS['amfm_maps_filtered_endpoints']->init_hooks();

    // Register scripts and styles for Elementor
    add_action('elementor/frontend/after_register_scripts', function() {
        // Register Google Maps API with optimized loading
        wp_register_script(
            'amfm-google-maps', 
            'https://maps.googleapis.com/maps/api/js?key=' . AMFM_MAPS_API_KEY . '&loading=async&libraries=places&callback=amfmMapsInitCallback', 
            [], 
            null, 
            true // Load in footer for better performance
        );
        
        // Register main script with dependency on Google Maps API
        wp_register_script(
            'amfm-maps-script', 
            plugins_url('assets/js/script.js', __FILE__), 
            ['jquery'], // Remove google-maps dependency to allow conditional loading
            AMFM_MAPS_VERSION, 
            true
        );
        
        wp_register_style('amfm-maps-style', plugins_url('assets/css/style.css', __FILE__), [], AMFM_MAPS_VERSION);
        wp_register_style('amfm-maps-drawer-responsive', plugins_url('assets/css/amfm-maps-drawer-responsive.css', __FILE__), ['amfm-maps-style'], AMFM_MAPS_VERSION);
    });

    // Hook to conditionally enqueue assets only when needed
    add_action('wp_enqueue_scripts', function () {
        $has_map_widget = false;
        $has_filter_widget = false;
        
        // Always enqueue in Elementor editor or preview mode
        if (defined('ELEMENTOR_VERSION') && (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode())) {
            $has_map_widget = true;
            $has_filter_widget = true;
        } else {
            // Check specific widget types on the page
            $widget_types = amfm_maps_get_page_widgets();
            $has_map_widget = in_array('amfm-map-widget', $widget_types);
            $has_filter_widget = in_array('amfm-map-filter-widget', $widget_types);
        }
        
        // Enqueue based on specific needs
        if ($has_map_widget || $has_filter_widget) {
            // Common styles always needed
            wp_enqueue_style('amfm-maps-style');
            wp_enqueue_script('amfm-maps-script');
            
            // Add global callback for Google Maps
            wp_add_inline_script('amfm-maps-script', 
                'window.amfmMapsInitCallback = function() { 
                    console.log("Google Maps API loaded successfully"); 
                    // Trigger custom event for widgets that need Google Maps
                    if (typeof jQuery !== "undefined") {
                        jQuery(document).trigger("amfmGoogleMapsReady");
                    }
                };', 'before');
        }
        
        if ($has_map_widget || $has_filter_widget) {
            // Load Google Maps API when map widgets OR filter widgets are present
            // Filter widgets need Google Maps API for cross-widget communication
            wp_enqueue_script('amfm-google-maps');
        }
        
        if ($has_filter_widget) {
            // Only load drawer styles when filter widgets are present
            wp_enqueue_style('amfm-maps-drawer-responsive');
        }
    });
    
    // Add widget detection hook for Elementor
    add_action('elementor/widget/before_render_content', function($widget) {
        if (in_array($widget->get_name(), ['amfm-map-widget', 'amfm-map-filter-widget'])) {
            // Get existing cached widgets
            $cached_widgets = get_transient('amfm_maps_page_widgets_' . get_the_ID());
            if ($cached_widgets === false) {
                $cached_widgets = [];
            }
            
            // Add this widget type if not already present
            if (!in_array($widget->get_name(), $cached_widgets)) {
                $cached_widgets[] = $widget->get_name();
                set_transient('amfm_maps_page_widgets_' . get_the_ID(), $cached_widgets, HOUR_IN_SECONDS);
            }
        }
    });
}

/**
 * Check if current page has AMFM map widgets
 *
 * @return bool
 */
function amfm_maps_page_has_widgets() {
    $widgets = amfm_maps_get_page_widgets();
    return !empty($widgets);
}

/**
 * Get specific AMFM widget types on current page
 *
 * @return array Array of widget types found on page
 */
function amfm_maps_get_page_widgets() {
    // Check transient first (set during widget rendering)
    $cached_widgets = get_transient('amfm_maps_page_widgets_' . get_the_ID());
    if ($cached_widgets !== false) {
        return $cached_widgets;
    }
    
    global $post;
    if (!$post) {
        return [];
    }
    
    $found_widgets = [];
    
    // Check post content for widget usage
    if (strpos($post->post_content, 'amfm-map-widget') !== false) {
        $found_widgets[] = 'amfm-map-widget';
    }
    if (strpos($post->post_content, 'amfm-map-filter-widget') !== false) {
        $found_widgets[] = 'amfm-map-filter-widget';
    }
    
    // Check if page uses Elementor and has our widgets
    if (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->db->is_built_with_elementor($post->ID)) {
        $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
        if (!empty($elementor_data)) {
            $elementor_data = json_decode($elementor_data, true);
            $elementor_widgets = amfm_maps_search_elementor_data($elementor_data);
            $found_widgets = array_merge($found_widgets, $elementor_widgets);
        }
    }
    
    // Remove duplicates and cache result
    $found_widgets = array_unique($found_widgets);
    set_transient('amfm_maps_page_widgets_' . get_the_ID(), $found_widgets, HOUR_IN_SECONDS);
    
    return $found_widgets;
}

/**
 * Recursively search Elementor data for AMFM widgets
 *
 * @param array $data
 * @return array Array of widget types found
 */
function amfm_maps_search_elementor_data($data) {
    if (!is_array($data)) {
        return [];
    }
    
    $found_widgets = [];
    
    foreach ($data as $element) {
        if (isset($element['widgetType']) && 
            in_array($element['widgetType'], ['amfm-map-widget', 'amfm-map-filter-widget'])) {
            $found_widgets[] = $element['widgetType'];
        }
        
        // Check nested elements
        if (isset($element['elements'])) {
            $nested_widgets = amfm_maps_search_elementor_data($element['elements']);
            $found_widgets = array_merge($found_widgets, $nested_widgets);
        }
    }
    
    return $found_widgets;
}

// Add custom cron intervals
add_filter('cron_schedules', 'amfm_maps_cron_schedules');
function amfm_maps_cron_schedules($schedules) {
    if (!isset($schedules['weekly'])) {
        $schedules['weekly'] = array(
            'interval' => 604800, // 1 week in seconds
            'display' => __('Once Weekly', 'amfm-maps')
        );
    }
    if (!isset($schedules['monthly'])) {
        $schedules['monthly'] = array(
            'interval' => 2635200, // 30.5 days in seconds
            'display' => __('Once Monthly', 'amfm-maps')
        );
    }
    return $schedules;
}

// Hook to initialize the plugin
add_action('elementor/init', 'amfm_maps_init');

// Remove all admin notices
add_action('admin_init', function () {
    remove_all_actions('admin_notices');
    remove_all_actions('all_admin_notices');
});