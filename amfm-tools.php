<?php
/*
Plugin Name: AMFM Tools
Plugin URI: https://adzbyte.com/
Description: A plugin for AMFM custom functionalities.
Version: 2.1.0
Author: Adrian T. Saycon
Author URI: https://adzbyte.com/adz
License: GPL2
*/

// Define version constant for consistent use across the plugin
if ( ! defined( 'AMFM_TOOLS_VERSION' ) ) {
    define( 'AMFM_TOOLS_VERSION', '2.1.0' );
}

// Load the plugin initializer
require_once plugin_dir_path( __FILE__ ) . 'init.php';

// Run the Init class
if ( class_exists( 'Init' ) ) {
    Init::run();
}
