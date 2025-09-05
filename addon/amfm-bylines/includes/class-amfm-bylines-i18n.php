<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/includes
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Bylines_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'amfm-bylines',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
