<?php
/*
Plugin Name: AMFM Tools
Plugin URI: https://adzbyte.com/
Description: A plugin for AMFM custom functionalities.
Version: 1.8.0
Author: Adrian T. Saycon
Author URI: https://adzbyte.com/adz
License: GPL2
*/

// Silence is golden.

// Load the plugin initializer
require_once plugin_dir_path( __FILE__ ) . 'init.php';

// Run the Init class
if ( class_exists( 'Init' ) ) {
    Init::run();
}
