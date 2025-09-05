<?php

/**
 * Fired during plugin activation
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/includes
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Bylines_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'amfm_bylines';
		$charset_collate = $wpdb->get_charset_collate();

		// create an option called 'amfm_bylines' if it doesn't exist yet.
		if (!get_option('amfm_bylines_tags')) {
			add_option('amfm_bylines_tags', []);
		}

		// Check if the table already exists
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				byline_name varchar(100) NOT NULL,
				data longtext NOT NULL,
				profile_image TEXT,
				description TEXT,
				type VARCHAR(255),
				authorTag VARCHAR(255),
				editorTag VARCHAR(255),
				reviewedByTag VARCHAR(255),
				PRIMARY KEY  (id)
			) $charset_collate;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);

			// Check for errors
			if ($wpdb->last_error) {
				// Log the error or handle it as needed
				error_log('Error creating table: ' . $wpdb->last_error);
				wp_die('There was an error creating the table. Please contact the administrator.');
			}
		}

		// Check and add new columns if they do not exist
		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'profile_image'");
		if (empty($columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD profile_image TEXT");
		}

		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'description'");
		if (empty($columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD description TEXT");
		}

		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'type'");
		if (empty($columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD type VARCHAR(255)");
		}

		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'authorTag'");
		if (empty($columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD authorTag VARCHAR(255)");
		}

		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'editorTag'");
		if (empty($columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD editorTag VARCHAR(255)");
		}

		$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'reviewedByTag'");
		if (empty($columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD reviewedByTag VARCHAR(255)");
		}

		// Check for errors after altering the table
		if ($wpdb->last_error) {
			// Log the error or handle it as needed
			error_log('Error altering table: ' . $wpdb->last_error);
			wp_die('There was an error updating the table. Please contact the administrator.');
		}
	}
	
}
