<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/admin
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Bylines_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Amfm_Bylines_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amfm_Bylines_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$plugin_url = defined('AMFM_BYLINES_PLUGIN_URL') ? AMFM_BYLINES_PLUGIN_URL . 'admin/' : plugin_dir_url(__FILE__);
		wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css', array(), '5.2.3', 'all');
		wp_enqueue_style($this->plugin_name, $plugin_url . 'css/amfm-bylines-admin.min.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Amfm_Bylines_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amfm_Bylines_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$plugin_url = defined('AMFM_BYLINES_PLUGIN_URL') ? AMFM_BYLINES_PLUGIN_URL . 'admin/' : plugin_dir_url(__FILE__);
		wp_enqueue_media();
		wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.2.3', true);
		wp_enqueue_script($this->plugin_name, $plugin_url . 'js/amfm-bylines-admin.min.js', array('jquery'), $this->version, false);
		wp_localize_script($this->plugin_name, 'amfmLocalize', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'pageSelectorNonce' => wp_create_nonce('amfm_page_selector_nonce'),
			'saveBylineNonce' => wp_create_nonce('amfm_save_byline_nonce'),
			'removeBylineNonce' => wp_create_nonce('amfm_remove_byline_nonce')
		));
	}

	/**
	 * Register the admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu()
	{
		add_menu_page(
			__('AMFM', 'amfm-bylines'), // Page title
			__('AMFM', 'amfm-bylines'), // Menu title
			'manage_options', // Capability
			'amfm', // Menu slug
			array($this, 'display_admin_page'), // Callback function
			'dashicons-admin-users', // Icon
			2 // Position
		);
		add_submenu_page(
			'amfm',
			__('Bylines', 'amfm-bylines'),
			__('Bylines', 'amfm-bylines'),
			'manage_options',
			'amfm', // Use the same slug as the main menu to hide it from the submenu list
			array($this, 'display_admin_page')
		);
		add_submenu_page(
			'amfm',
			__('Shortcodes', 'amfm-bylines'),
			__('Shortcodes', 'amfm-bylines'),
			'manage_options',
			'amfm-bylines-shortcodes',
			array($this, 'display_shortcode_guide')
		);

		add_submenu_page(
			'amfm',
			__('Settings', 'amfm-bylines'),
			__('Settings', 'amfm-bylines'),
			'manage_options',
			'amfm_bylines_settings',
			array($this, 'amfm_bylines_settings_page')
		);
	}

	public function amfm_bylines_register_settings() {
		add_option('amfm_bylines_github_user', 'default_user');
		add_option('amfm_bylines_github_repo', 'default_repo');
		add_option('amfm_bylines_github_token', 'default_token');
		register_setting('amfm_bylines_options_group', 'amfm_bylines_github_user');
		register_setting('amfm_bylines_options_group', 'amfm_bylines_github_repo');
		register_setting('amfm_bylines_options_group', 'amfm_bylines_github_token');
	}

	/**
	 * Display the admin page.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_page()
	{
		$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH . 'admin/' : plugin_dir_path(__FILE__);
		include $plugin_path . 'partials/amfm-bylines-admin-display.php';
	}

	public function display_shortcode_guide()
	{
		$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH . 'admin/' : plugin_dir_path(__FILE__);
		include $plugin_path . 'partials/amfm-bylines-shortcodes.php';
	}

	public function amfm_bylines_settings_page() {
		$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH . 'admin/' : plugin_dir_path(__FILE__);
		include $plugin_path . 'partials/amfm-bylines-settings-page.php';
	}

	public function toggle_function()
	{
		// Check if the request is valid
		if (!isset($_POST['is_active'])) {
			wp_send_json_error('Invalid request');
		}

		$is_active = filter_var($_POST['is_active'], FILTER_VALIDATE_BOOLEAN);

		// Activate or deactivate the function based on the value of $is_active
		if ($is_active) {
			// Save option "amfm_use_staff_cpt" with value "1". Create the option if it doesn't already exist
			update_option('amfm_use_staff_cpt', 1);
		} else {
			// Save option "amfm_use_staff_cpt" with value "0". Create the option if it doesn't already exist
			update_option('amfm_use_staff_cpt', 0);
		}

		wp_send_json_success();
	}

	public function fetch_pages()
	{
		check_ajax_referer('amfm_page_selector_nonce', 'nonce');

		$pages = get_pages(array(
			'post_type' => 'page',
			'post_status' => 'publish'
		));

		$response = array();
		foreach ($pages as $page) {
			$response[] = array(
				'ID' => $page->ID,
				'title' => $page->post_title,
				'link' => get_permalink($page->ID)
			);
		}

		wp_send_json_success($response);
	}

	public function save_amfm_bylines()
	{
		if (!isset($_POST['nonce'])) {
			wp_send_json_error('Nonce not set', 403);
			wp_die();
		}

		if (!wp_verify_nonce($_POST['nonce'], 'amfm_save_byline_nonce')) {
			wp_send_json_error('Nonce verification failed', 403);
			wp_die();
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'amfm_bylines';

		parse_str($_POST['form_data'], $form_data);

		// Sanitize and collect the inputs
		$byline_id = isset($form_data['byline_id']) ? intval($form_data['byline_id']) : 0;
		$byline_name = sanitize_text_field($form_data['name']);
		$profile_image = esc_url_raw($form_data['image_url']);
		$description = sanitize_textarea_field($form_data['description']);
		$type = 'Default';
		$authorTag = sanitize_text_field($form_data['authorTag']);
		$editorTag = sanitize_text_field($form_data['editorTag']);
		$reviewedByTag = sanitize_text_field($form_data['reviewedByTag']);

		// Collect other inputs
		$other_data = array(
			'page_url' => esc_url_raw($form_data['page_url']),
			'honorificSuffix' => sanitize_text_field($form_data['honorificSuffix']),
			'jobTitle' => sanitize_text_field($form_data['jobTitle']),
			'hasCredential' => [
				'@type' => sanitize_text_field($form_data['credentialType']),
				'name' => sanitize_text_field($form_data['credentialName']),
			],
			'worksFor' => [
				'@type' => sanitize_text_field($form_data['worksForType']),
				'name' => sanitize_text_field($form_data['worksForName']),
			],
			'authorTag' => sanitize_text_field($form_data['authorTag']),
			'editorTag' => sanitize_text_field($form_data['editorTag']),
			'reviewedByTag' => sanitize_text_field($form_data['reviewedByTag']),
		);

		// Convert other inputs to JSON
		$other_data_json = wp_json_encode($other_data);

		if ($byline_id > 0) {
			// Update existing byline
			$wpdb->update(
				$table_name,
				array(
					'byline_name' => $byline_name,
					'profile_image' => $profile_image,
					'description' => $description,
					'data' => $other_data_json,
					'type' => $type,
					'authorTag' => $authorTag,
					'editorTag' => $editorTag,
					'reviewedByTag' => $reviewedByTag,
				),
				array('id' => $byline_id),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array('%d')
			);
		} else {
			// Insert new byline
			$wpdb->insert(
				$table_name,
				array(
					'byline_name' => $byline_name,
					'profile_image' => $profile_image,
					'description' => $description,
					'data' => $other_data_json,
					'type' => $type,
					'authorTag' => $authorTag,
					'editorTag' => $editorTag,
					'reviewedByTag' => $reviewedByTag,
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);
		}

		// Check for errors
		if ($wpdb->last_error) {
			wp_send_json_error('Error saving data: ' . $wpdb->last_error, 403);
		} else {
			// Store all tags in their respective categories
			$tags = array(
				'author_tags' => array($other_data['authorTag']),
				'editor_tags' => array($other_data['editorTag']),
				'reviewed_by_tags' => array($other_data['reviewedByTag'])
			);

			// Filter out empty tags
			$tags['author_tags'] = array_filter($tags['author_tags']);
			$tags['editor_tags'] = array_filter($tags['editor_tags']);
			$tags['reviewed_by_tags'] = array_filter($tags['reviewed_by_tags']);

			// Get existing tags from the option
			$existing_tags = get_option('amfm_bylines_tags', array(
				'author_tags' => array(),
				'editor_tags' => array(),
				'reviewed_by_tags' => array()
			));

			if (!is_array($existing_tags) || !isset($existing_tags['author_tags']) || !isset($existing_tags['editor_tags']) || !isset($existing_tags['reviewed_by_tags'])) {
				$existing_tags = array(
					'author_tags' => array(),
					'editor_tags' => array(),
					'reviewed_by_tags' => array()
				);
			}

			// Merge new tags with existing tags
			$existing_tags['author_tags'] = array_unique(array_merge($existing_tags['author_tags'], $tags['author_tags']));
			$existing_tags['editor_tags'] = array_unique(array_merge($existing_tags['editor_tags'], $tags['editor_tags']));
			$existing_tags['reviewed_by_tags'] = array_unique(array_merge($existing_tags['reviewed_by_tags'], $tags['reviewed_by_tags']));

			// Update the option with the merged tags
			update_option('amfm_bylines_tags', $existing_tags);

			// Return success message
			wp_send_json_success('Data saved successfully!');
		}
	}

	public function remove_amfm_byline()
	{
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amfm_remove_byline_nonce')) {
			wp_send_json_error('Invalid nonce', 400);
			wp_die();
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions', 403);
			wp_die();
		}

		global $wpdb;
		$id = intval($_POST['id']);

		if ($id <= 0) {
			wp_send_json_error('Invalid byline ID', 400);
			wp_die();
		}

		$deleted = $wpdb->delete($wpdb->prefix . 'amfm_bylines', array('id' => $id), array('%d'));

		if ($deleted !== false) {
			wp_send_json_success('Byline deleted successfully');
		} else {
			wp_send_json_error('Error deleting byline.', 500);
		}
	}

	/**
	 * Adds categories and tags support to pages.
	 *
	 * This function registers the 'category' and 'post_tag' taxonomies for the 'page' post type,
	 * allowing pages to be categorized and tagged similarly to posts.
	 *
	 * @return void
	 */

	public function add_categories_and_tags_to_pages()
	{
		register_taxonomy_for_object_type('category', 'page');
		register_taxonomy_for_object_type('post_tag', 'page');
	}


	public function update_staff_order_callback() {
		check_ajax_referer('update_staff_order_nonce', 'nonce'); // Verify nonce
	
		if (isset($_POST['ids']) && is_array($_POST['ids'])) {
			$ids = $_POST['ids'];
			
			// Start with 1 for the first item
			$i = 1; 
			
			foreach ($ids as $index => $id) {
				// If this is the first item in the sorted list, set its amfm_sort to 1
				if ($index === 0) {
					update_field('amfm_sort', 1, $id); // First item gets 1
				} else {
					update_field('amfm_sort', $i, $id); // Other items get incremented
				}
				$i++; // Increment for the next item
			}
	
			wp_send_json_success('success');
		} else {
			wp_send_json_error('Invalid data');
		}
		wp_die(); // Important to prevent extra output
	}
}
