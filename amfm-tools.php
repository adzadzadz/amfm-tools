<?php
/**
 * Plugin Name: AMFM Tools
 * Plugin URI: https://adzbyte.com/
 * Description: A plugin for AMFM custom functionalities.
 * Version: 2.2.1
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
define('AMFM_TOOLS_VERSION', '2.2.1');
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
    
    // Set up view template paths
    \AdzWP\Core\View::addTemplatePath(AMFM_TOOLS_PATH . 'src/Views/');

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
        // Install hook - runs when plugin is first installed
        ->onInstall(function() {
            // Create custom database tables
            // Set up default options
            // Any one-time setup tasks
        })
        
        // Activate hook - runs when plugin is activated
        ->onActivate(function() {
            // Check system requirements
            // Create/update database schema
            // Set up cron jobs
            // Clear caches
        })
        
        // Deactivate hook - runs when plugin is deactivated
        ->onDeactivate(function() {
            // Clear cron jobs
            // Clear caches
            // Temporary cleanup (don't delete data)
        })
        
        // Uninstall hook - runs when plugin is deleted
        ->onUninstall(function() {
            // Remove database tables
            // Remove options
            // Complete cleanup
        })
        
        // Helper methods for common tasks
        ->setupOptions([
            'amfm_enabled_components' => ['acf_helper', 'text_utilities', 'optimization', 'shortcodes', 'elementor_widgets', 'import_export'],
            'amfm_keywords' => '',
            'amfm_other_keywords' => ''
        ])
        ->setupCapabilities([
            'manage_amfm_tools',
            'edit_amfm_data'
        ]);

    // Initialize controllers based on enabled components
    $enabled_components = get_option('amfm_enabled_components', [
        'acf_helper', 
        'text_utilities', 
        'optimization', 
        'shortcodes', 
        'elementor_widgets', 
        'import_export'
    ]);

    // Admin Controller - Always enabled
    new \App\Controllers\AdminController();

    // ACF Helper - Core component (always enabled)
    if (in_array('acf_helper', $enabled_components)) {
        new \App\Controllers\ACFController();
    }

    // Text Utilities - Optional
    if (in_array('text_utilities', $enabled_components)) {
        new \App\Controllers\TextController();
    }

    // Performance Optimization - Optional
    if (in_array('optimization', $enabled_components)) {
        new \App\Controllers\OptimizationController();
    }

    // Shortcode System - Optional
    if (in_array('shortcodes', $enabled_components)) {
        new \App\Controllers\ShortcodeController();
    }

    // Elementor Widgets - Optional
    if (in_array('elementor_widgets', $enabled_components)) {
        new \App\Controllers\ElementorController();
    }
});