<?php 
/**
 * Plugin Name: AMFM Tools
 * Plugin URI: https://adzbyte.com/wp-plugins/amfm-tools
 * Description: A plugin for AMFM custom functionalities.
 * Version: 2.2.1
 * Author: Adrian T. Saycon
 * Text Domain: amfm-tools
 * Author URI: https://adzbyte.com/adz
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Do not open this file directly.' );
}

require_once 'vendor/autoload.php';

( \ADZ::pluginize( __FILE__, $env = 'default' ) )->load([
    'Admin',
    'ACF',
    'Shortcode',
    'Elementor',
    'Optimization',
    'Frontend'
]);