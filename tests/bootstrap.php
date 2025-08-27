<?php
/**
 * PHPUnit Bootstrap File for ADZ WordPress Plugin Framework
 * 
 * This file sets up the testing environment for the framework.
 * It loads necessary dependencies and sets up WordPress testing environment.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Define testing constants
define('ADZ_FRAMEWORK_TESTS', true);
define('ADZ_PLUGIN_PATH', dirname(__DIR__) . '/');
define('ADZ_PLUGIN_URL', 'http://example.org/wp-content/plugins/adz-framework/');
define('ADZ_PLUGIN_VERSION', '1.0.0-test');
define('WP_CONTENT_DIR', '/tmp/wordpress/wp-content/');
define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins/');

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load Brain Monkey for WordPress function mocking
if (class_exists('\Brain\Monkey')) {
    \Brain\Monkey\setUp();
    
    // Mock essential WordPress functions for framework initialization
    \Brain\Monkey\Functions\when('wp_mkdir_p')->justReturn(true);
    \Brain\Monkey\Functions\when('wp_upload_dir')->justReturn(['basedir' => '/tmp']);
}

// Set up error reporting
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

// Load WordPress test environment if available
if (getenv('WP_TESTS_DIR')) {
    $wp_tests_dir = getenv('WP_TESTS_DIR');
    if (file_exists($wp_tests_dir . '/includes/functions.php')) {
        require_once $wp_tests_dir . '/includes/functions.php';
        
        // Load the plugin
        function _manually_load_plugin() {
            require_once dirname(__DIR__) . '/main-plugin-file.php';
        }
        tests_add_filter('muplugins_loaded', '_manually_load_plugin');
        
        require_once $wp_tests_dir . '/includes/bootstrap.php';
    }
}

// Load test helpers and utilities
require_once __DIR__ . '/Helpers/TestCase.php';
require_once __DIR__ . '/Helpers/WordPressTestCase.php';
require_once __DIR__ . '/Helpers/FrameworkTestCase.php';

// Skip framework initialization in test environment to avoid dependency issues