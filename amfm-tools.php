<?php
/**
 * Plugin Name: AMFM Tools
 * Plugin URI: https://adzbyte.com/
 * Description: A plugin for AMFM custom functionalities.
 * Version: 3.9.13
 * Author: Adrian T. Saycon
 * Author URI: https://adzbyte.com/adz
 * License: GPL2
 * Text Domain: amfm-tools-v2
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AMFM_TOOLS_VERSION', '3.9.13');
define('AMFM_TOOLS_PATH', plugin_dir_path(__FILE__));
define('AMFM_TOOLS_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader if available
if (file_exists(AMFM_TOOLS_PATH . 'vendor/autoload.php')) {
    require_once AMFM_TOOLS_PATH . 'vendor/autoload.php';
}

// Initialize the plugin after WordPress is loaded
add_action('plugins_loaded', function() {
    // Initialize the framework
    $framework = \Adz::config();
    $framework->set('plugin.path', AMFM_TOOLS_PATH);
    $framework->set('plugin.url', AMFM_TOOLS_URL);
    $framework->set('plugin.version', AMFM_TOOLS_VERSION);
    $framework->set('plugin.slug', 'amfm-tools');
    
    // Set up default configuration for features (only if not already set)
    // Use WordPress options for persistence
    if (get_option('amfm_shortcodes_dkv') === false) add_option('amfm_shortcodes_dkv', true);
    if (get_option('amfm_shortcodes_limit_words') === false) add_option('amfm_shortcodes_limit_words', true);
    if (get_option('amfm_shortcodes_text_util') === false) add_option('amfm_shortcodes_text_util', true);
    if (get_option('amfm_shortcodes_amfm_info') === false) add_option('amfm_shortcodes_amfm_info', true);
    if (get_option('amfm_shortcodes_amfm_author_url') === false) add_option('amfm_shortcodes_amfm_author_url', true);
    if (get_option('amfm_shortcodes_amfm_editor_url') === false) add_option('amfm_shortcodes_amfm_editor_url', true);
    if (get_option('amfm_shortcodes_amfm_reviewer_url') === false) add_option('amfm_shortcodes_amfm_reviewer_url', true);
    if (get_option('amfm_shortcodes_amfm_bylines_grid') === false) add_option('amfm_shortcodes_amfm_bylines_grid', true);
    if (get_option('amfm_shortcodes_amfm_acf') === false) add_option('amfm_shortcodes_amfm_acf', true);
    if (get_option('amfm_shortcodes_amfm_acf_object') === false) add_option('amfm_shortcodes_amfm_acf_object', true);
    if (get_option('amfm_elementor_widgets_dkv_widget') === false) add_option('amfm_elementor_widgets_dkv_widget', true);
    if (get_option('amfm_elementor_widgets_amfm_show') === false) add_option('amfm_elementor_widgets_amfm_show', true);
    if (get_option('amfm_elementor_widgets_amfm_bylines_posts') === false) add_option('amfm_elementor_widgets_amfm_bylines_posts', true);
    if (get_option('amfm_elementor_widgets_amfm_bylines_featured_images') === false) add_option('amfm_elementor_widgets_amfm_bylines_featured_images', true);
    if (get_option('amfm_elementor_widgets_amfm_bylines_display') === false) add_option('amfm_elementor_widgets_amfm_bylines_display', true);
    if (get_option('amfm_elementor_widgets_amfm_staff_grid') === false) add_option('amfm_elementor_widgets_amfm_staff_grid', true);
    if (get_option('amfm_components_acf_helper') === false) add_option('amfm_components_acf_helper', true);
    if (get_option('amfm_components_import_export') === false) add_option('amfm_components_import_export', true);
    if (get_option('amfm_components_optimization') === false) add_option('amfm_components_optimization', true);
    
    // Load persisted values into framework config (convert to boolean)
    $framework->set('shortcodes.dkv', (bool) get_option('amfm_shortcodes_dkv', true));
    $framework->set('shortcodes.limit_words', (bool) get_option('amfm_shortcodes_limit_words', true));
    $framework->set('shortcodes.text_util', (bool) get_option('amfm_shortcodes_text_util', true));
    $framework->set('shortcodes.amfm_info', (bool) get_option('amfm_shortcodes_amfm_info', true));
    $framework->set('shortcodes.amfm_author_url', (bool) get_option('amfm_shortcodes_amfm_author_url', true));
    $framework->set('shortcodes.amfm_editor_url', (bool) get_option('amfm_shortcodes_amfm_editor_url', true));
    $framework->set('shortcodes.amfm_reviewer_url', (bool) get_option('amfm_shortcodes_amfm_reviewer_url', true));
    $framework->set('shortcodes.amfm_bylines_grid', (bool) get_option('amfm_shortcodes_amfm_bylines_grid', true));
    $framework->set('shortcodes.amfm_acf', (bool) get_option('amfm_shortcodes_amfm_acf', true));
    $framework->set('shortcodes.amfm_acf_object', (bool) get_option('amfm_shortcodes_amfm_acf_object', true));
    $framework->set('elementor.widgets.dkv_widget', (bool) get_option('amfm_elementor_widgets_dkv_widget', true));
    $framework->set('elementor.widgets.amfm_show', (bool) get_option('amfm_elementor_widgets_amfm_show', true));
    $framework->set('elementor.widgets.amfm_bylines_posts', (bool) get_option('amfm_elementor_widgets_amfm_bylines_posts', true));
    $framework->set('elementor.widgets.amfm_bylines_featured_images', (bool) get_option('amfm_elementor_widgets_amfm_bylines_featured_images', true));
    $framework->set('elementor.widgets.amfm_bylines_display', (bool) get_option('amfm_elementor_widgets_amfm_bylines_display', true));
    $framework->set('elementor.widgets.amfm_staff_grid', (bool) get_option('amfm_elementor_widgets_amfm_staff_grid', true));
    $framework->set('components.acf_helper', (bool) get_option('amfm_components_acf_helper', true));
    $framework->set('components.import_export', (bool) get_option('amfm_components_import_export', true));
    $framework->set('components.optimization', (bool) get_option('amfm_components_optimization', true));
    
    // Set up view template paths
    \AdzWP\Core\View::addTemplatePath(AMFM_TOOLS_PATH . 'src/Views/');

    // Initialize AssetManager with local Bootstrap 5 for admin/plugin pages
    add_action('init', function() {
        \Adz::init();
        \AdzWP\Core\AssetManager::init();
        
        // Override Bootstrap with local files
        \AdzWP\Core\AssetManager::registerStyle('bootstrap-css', [
            'url' => AMFM_TOOLS_URL . 'assets/css/bootstrap.min.css',
            'contexts' => ['admin', 'plugin'],
            'version' => '5.3.3',
            'priority' => 5
        ]);
        
        \AdzWP\Core\AssetManager::registerScript('bootstrap-js', [
            'url' => AMFM_TOOLS_URL . 'assets/js/bootstrap.bundle.min.js',
            'contexts' => ['admin', 'plugin'],
            'version' => '5.3.3',
            'priority' => 5,
            'in_footer' => true
        ]);
    });

    // Initialize plugin manager with lifecycle hooks
    $pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

    // Set up plugin dependencies (optional)
    $pluginManager->setDependencies([
        [
            'slug' => 'advanced-custom-fields/acf.php',
            'name' => 'Advanced Custom Fields',
            'source' => 'repo'
        ]
    ]);

    // Set up plugin lifecycle hooks
    $pluginManager
        ->setupCapabilities([
            'manage_amfm_tools',
            'edit_amfm_data'
        ]);

    // Initialize Services
    new \App\Services\CsvExportService();
    new \App\Services\CsvImportService();
    new \App\Services\SettingsService();
    new \App\Services\PluginUpdaterService();
    new \App\Services\UploadLimitService();

    // Initialize Controllers
    // Admin Controllers
    new \App\Controllers\Admin\DashboardController();
    new \App\Controllers\Admin\BylinesController();
    new \App\Controllers\Admin\ElementorController();
    new \App\Controllers\Admin\ShortcodesController();
    new \App\Controllers\Admin\UtilitiesController();
    new \App\Controllers\Admin\ACFController();
    new \App\Controllers\Admin\ImportExportController();
    new \App\Controllers\Admin\RedirectionCleanupController();
    new \App\Controllers\Admin\AjaxController();

    // Feature Controllers (check their own config for enabling/disabling features)
    new \App\Controllers\ACFFieldsController();
    new \App\Controllers\ACFController();
    new \App\Controllers\OptimizationController();
    new \App\Controllers\ShortcodeController();
    new \App\Controllers\ElementorController();
    // new \App\Controllers\PublicBylinesController();


    // Initialize AMFM Bylines addon if it exists
    $bylines_addon_path = AMFM_TOOLS_PATH . 'addon/amfm-bylines/amfm-bylines.php';
    if (file_exists($bylines_addon_path)) {
        // Define the plugin directory path and URL for amfm-bylines
        if (!defined('AMFM_BYLINES_PLUGIN_PATH')) {
            define('AMFM_BYLINES_PLUGIN_PATH', AMFM_TOOLS_PATH . 'addon/amfm-bylines/');
        }
        if (!defined('AMFM_BYLINES_PLUGIN_URL')) {
            define('AMFM_BYLINES_PLUGIN_URL', AMFM_TOOLS_URL . 'addon/amfm-bylines/');
        }
        
        // Load the amfm-bylines plugin
        require_once $bylines_addon_path;
    }
    
    // Initialize AMFM Maps addon if it exists
    $maps_addon_path = AMFM_TOOLS_PATH . 'addon/amfm-maps/amfm-maps.php';
    if (file_exists($maps_addon_path)) {
        // Define the plugin directory path and URL for amfm-maps
        if (!defined('AMFM_MAPS_PLUGIN_PATH')) {
            define('AMFM_MAPS_PLUGIN_PATH', AMFM_TOOLS_PATH . 'addon/amfm-maps/');
        }
        if (!defined('AMFM_MAPS_PLUGIN_URL')) {
            define('AMFM_MAPS_PLUGIN_URL', AMFM_TOOLS_URL . 'addon/amfm-maps/');
        }
        
        // Load the amfm-maps plugin
        require_once $maps_addon_path;
    }
});