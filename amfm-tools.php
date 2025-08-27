<?php
/**
 * Plugin Name: AMFM Tools
 * Plugin URI: https://adzbyte.com/
 * Description: A plugin for AMFM custom functionalities.
 * Version: 3.1.1
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
define('AMFM_TOOLS_VERSION', '3.1.1');
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
    
    // Set up default configuration for features (only if not already set)
    // Use WordPress options for persistence
    if (get_option('amfm_shortcodes_dkv') === false) add_option('amfm_shortcodes_dkv', true);
    if (get_option('amfm_shortcodes_limit_words') === false) add_option('amfm_shortcodes_limit_words', true);
    if (get_option('amfm_shortcodes_text_util') === false) add_option('amfm_shortcodes_text_util', true);
    if (get_option('amfm_elementor_widgets_dkv_widget') === false) add_option('amfm_elementor_widgets_dkv_widget', true);
    if (get_option('amfm_components_acf_helper') === false) add_option('amfm_components_acf_helper', true);
    if (get_option('amfm_components_import_export') === false) add_option('amfm_components_import_export', true);
    if (get_option('amfm_components_optimization') === false) add_option('amfm_components_optimization', true);
    
    // Load persisted values into framework config (convert to boolean)
    $framework->set('shortcodes.dkv', (bool) get_option('amfm_shortcodes_dkv', true));
    $framework->set('shortcodes.limit_words', (bool) get_option('amfm_shortcodes_limit_words', true));
    $framework->set('shortcodes.text_util', (bool) get_option('amfm_shortcodes_text_util', true));
    $framework->set('elementor.widgets.dkv_widget', (bool) get_option('amfm_elementor_widgets_dkv_widget', true));
    $framework->set('components.acf_helper', (bool) get_option('amfm_components_acf_helper', true));
    $framework->set('components.import_export', (bool) get_option('amfm_components_import_export', true));
    $framework->set('components.optimization', (bool) get_option('amfm_components_optimization', true));
    
    // Set up view template paths
    \AdzWP\Core\View::addTemplatePath(AMFM_TOOLS_PATH . 'src/Views/');

    // Initialize AssetManager with Bootstrap 5 for admin/plugin pages
    add_action('init', function() {
        \Adz::init();
        \AdzWP\Core\AssetManager::setBootstrap(true, ['admin', 'plugin']);
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

    // Initialize Controllers
    // Admin Controllers
    new \App\Controllers\Admin\DashboardController();
    new \App\Controllers\Admin\ElementorController();
    new \App\Controllers\Admin\ShortcodesController();
    new \App\Controllers\Admin\UtilitiesController();
    new \App\Controllers\Admin\ACFController();
    new \App\Controllers\Admin\ImportExportController();
    new \App\Controllers\Admin\AjaxController();

    // Feature Controllers (check their own config for enabling/disabling features)
    new \App\Controllers\ACFFieldsController();
    new \App\Controllers\ACFController();
    new \App\Controllers\OptimizationController();
    new \App\Controllers\ShortcodeController();
    new \App\Controllers\ElementorController();
});