<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://adzjo.online/adz
 * @since             1.0.0
 * @package           Amfm_Bylines
 *
 * @wordpress-plugin
 * Plugin Name:       AMFM Bylines
 * Plugin URI: https://github.com/adzadzadz/woocommerce-box
 * Description:       Byline Management
 * Version:           3.1.1
 * Author:            Adrian T. Saycon
 * Author URI:        https://adzjo.online/adz/
 * License:           GPL2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       amfm-bylines
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if ( ! defined( 'AMFM_BYLINES_VERSION' ) ) {
	define( 'AMFM_BYLINES_VERSION', '3.1.1' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-amfm-bylines-activator.php
 */
function activate_amfm_bylines() {
	$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH : plugin_dir_path( __FILE__ );
	require_once $plugin_path . 'includes/class-amfm-bylines-activator.php';
	Amfm_Bylines_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-amfm-bylines-deactivator.php
 */
function deactivate_amfm_bylines() {
	$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH : plugin_dir_path( __FILE__ );
	require_once $plugin_path . 'includes/class-amfm-bylines-deactivator.php';
	Amfm_Bylines_Deactivator::deactivate();
}

// Only register activation/deactivation hooks if running as standalone plugin
if ( ! defined('AMFM_BYLINES_PLUGIN_PATH') ) {
	register_activation_hook( __FILE__, 'activate_amfm_bylines' );
	register_deactivation_hook( __FILE__, 'deactivate_amfm_bylines' );
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH : plugin_dir_path( __FILE__ );
require $plugin_path . 'includes/class-amfm-bylines.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_amfm_bylines() {

	$plugin = new Amfm_Bylines();
	$plugin->run();

}
run_amfm_bylines();