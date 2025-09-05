<?php

/**
 * The admin-specific functionality of the AMFM Maps plugin.
 *
 * @link       https://adzbyte.com/
 * @since      1.0.0
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin
 */

/**
 * The admin-specific functionality of the AMFM Maps plugin.
 *
 * Defines the plugin name, version, and admin page functionality.
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Maps_Admin
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
		
		// Initialize cron functionality
		add_action('init', array($this, 'init_cron_hooks'));
		add_action('amfm_maps_sync_cron', array($this, 'cron_sync_data'));
		
		// Add AJAX handlers
		add_action('wp_ajax_amfm_maps_get_sync_status', array($this, 'ajax_get_sync_status'));
		add_action('wp_ajax_amfm_maps_manual_sync', array($this, 'ajax_manual_sync'));
		add_action('wp_ajax_amfm_maps_save_filter_config', array($this, 'ajax_save_filter_config'));
		add_action('wp_ajax_amfm_maps_get_available_filters', array($this, 'ajax_get_available_filters'));
	}

	/**
	 * Initialize cron hooks and schedules
	 */
	public function init_cron_hooks()
	{
		// Schedule cron job based on sync interval setting
		$sync_interval = get_option('amfm_maps_sync_interval', 'none');
		
		// Clear existing cron job
		wp_clear_scheduled_hook('amfm_maps_sync_cron');
		
		// Schedule new cron job if needed
		if ($sync_interval !== 'none') {
			if (!wp_next_scheduled('amfm_maps_sync_cron')) {
				wp_schedule_event(time(), $sync_interval, 'amfm_maps_sync_cron');
			}
		}
	}

	/**
	 * Cron job to sync data
	 */
	public function cron_sync_data()
	{
		$this->sync_json_data();
	}

	/**
	 * AJAX handler to get sync status
	 */
	public function ajax_get_sync_status()
	{
		$last_sync = get_option('amfm_maps_last_sync', '');
		$sync_status = get_option('amfm_maps_sync_status', '');
		
		$status_class = 'muted';
		$status_text = __('Not synced', 'amfm-maps');
		
		switch ($sync_status) {
			case 'success':
				$status_class = 'success';
				$status_text = __('Success', 'amfm-maps');
				break;
			case 'error':
				$status_class = 'error';
				$status_text = __('Error', 'amfm-maps');
				break;
		}
		
		wp_send_json_success(array(
			'last_sync' => $last_sync ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync)) : '',
			'status' => array(
				'class' => $status_class,
				'text' => $status_text
			)
		));
	}

	/**
	 * AJAX handler for manual sync
	 */
	public function ajax_manual_sync()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'amfm_maps_ajax_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		$result = $this->manual_sync_data();
		
		if ($result['success']) {
			wp_send_json_success($result);
		} else {
			wp_send_json_error($result);
		}
	}

	/**
	 * Sync data from JSON URL
	 */
	private function sync_json_data()
	{
		$json_url = get_option('amfm_maps_json_url', '');
		
		if (empty($json_url)) {
			$this->log_sync_error('No URL configured for data sync');
			update_option('amfm_maps_sync_status', 'error');
			update_option('amfm_maps_sync_error_message', __('No URL configured', 'amfm-maps'));
			return false;
		}
		
		// Validate URL format
		if (!filter_var($json_url, FILTER_VALIDATE_URL)) {
			$this->log_sync_error('Invalid URL format: ' . $json_url);
			update_option('amfm_maps_sync_status', 'error');
			update_option('amfm_maps_sync_error_message', __('Invalid URL format', 'amfm-maps'));
			return false;
		}
		
		// Attempt sync with retry logic
		$max_retries = 3;
		$retry_delay = 5; // seconds
		
		for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
			$result = $this->attempt_data_sync($json_url);
			
			if ($result['success']) {
				// Clear any previous error message
				delete_option('amfm_maps_sync_error_message');
				update_option('amfm_maps_sync_status', 'success');
				update_option('amfm_maps_last_sync', current_time('mysql'));
				return true;
			}
			
			// Log attempt failure
			$this->log_sync_error("Attempt {$attempt}/{$max_retries} failed: " . $result['error']);
			
			// Wait before retry (except on last attempt)
			if ($attempt < $max_retries) {
				sleep($retry_delay);
			}
		}
		
		// All attempts failed
		update_option('amfm_maps_sync_status', 'error');
		update_option('amfm_maps_sync_error_message', $result['error']);
		return false;
	}
	
	/**
	 * Attempt a single data sync operation
	 *
	 * @param string $json_url
	 * @return array
	 */
	private function attempt_data_sync($json_url)
	{
		try {
			$response = wp_remote_get($json_url, array(
				'timeout' => 30,
				'redirection' => 3,
				'headers' => array(
					'Accept' => 'application/json',
					'User-Agent' => 'AMFM Maps WordPress Plugin/' . AMFM_MAPS_VERSION
				),
				'sslverify' => true
			));
			
			if (is_wp_error($response)) {
				return array(
					'success' => false,
					'error' => 'HTTP Request failed: ' . $response->get_error_message()
				);
			}
			
			$response_code = wp_remote_retrieve_response_code($response);
			if ($response_code !== 200) {
				$response_message = wp_remote_retrieve_response_message($response);
				return array(
					'success' => false,
					'error' => sprintf('HTTP %d: %s', $response_code, $response_message)
				);
			}
			
			$body = wp_remote_retrieve_body($response);
			
			if (empty($body)) {
				return array(
					'success' => false,
					'error' => 'Empty response body from server'
				);
			}
			
			// Validate content type
			$content_type = wp_remote_retrieve_header($response, 'content-type');
			if ($content_type && strpos($content_type, 'application/json') === false) {
				return array(
					'success' => false,
					'error' => 'Invalid content type: ' . $content_type . '. Expected JSON.'
				);
			}
			
			$data = json_decode($body, true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				return array(
					'success' => false,
					'error' => 'Invalid JSON response: ' . json_last_error_msg()
				);
			}
			
			// Validate data structure
			if (!is_array($data)) {
				return array(
					'success' => false,
					'error' => 'Expected JSON array, got ' . gettype($data)
				);
			}
			
			if (empty($data)) {
				return array(
					'success' => false,
					'error' => 'JSON data is empty'
				);
			}
			
			// Save the data
			update_option('amfm_maps_json_data', $data);
			
			// Create fallback backup for future use
			$this->create_fallback_backup($data);
			
			// Trigger action for other components that depend on data updates
			do_action('amfm_maps_data_updated', $data);
			
			return array(
				'success' => true,
				'data' => $data
			);
			
		} catch (Exception $e) {
			return array(
				'success' => false,
				'error' => 'Exception during sync: ' . $e->getMessage()
			);
		}
	}
	
	/**
	 * Log sync errors with context
	 *
	 * @param string $message
	 */
	private function log_sync_error($message)
	{
		error_log(sprintf(
			'[AMFM Maps] Sync Error [%s]: %s',
			current_time('Y-m-d H:i:s'),
			$message
		));
	}

	/**
	 * Register the Maps admin menu under the AMFM menu.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu()
	{
		// Add Maps submenu under the existing AMFM menu
		add_submenu_page(
			'amfm-tools', // Parent menu slug (from amfm-tools plugin)
			__('Maps', 'amfm-maps'), // Page title
			__('Maps', 'amfm-maps'), // Menu title
			'manage_options', // Capability
			'amfm-maps', // Menu slug
			array($this, 'display_admin_page') // Callback function
		);
	}

	/**
	 * Display the Maps admin page.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_page()
	{
		include plugin_dir_path(__FILE__) . 'partials/amfm-maps-admin-display.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/amfm-maps-admin.min.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
   public function enqueue_scripts()
   {
	   wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/amfm-maps-admin.min.js', array('jquery'), $this->version, false);
	   wp_enqueue_script('jquery-ui-sortable');
	   wp_enqueue_script('amfm-maps-admin-sortable', plugin_dir_url(__FILE__) . 'js/amfm-maps-admin-sortable.min.js', array('jquery', 'jquery-ui-sortable'), $this->version, false);
	   // Localize script for AJAX
	   wp_localize_script($this->plugin_name, 'amfmMapsAdmin', array(
		   'ajax_url' => admin_url('admin-ajax.php'),
		   'nonce' => wp_create_nonce('amfm_maps_ajax_nonce')
	   ));
   }

	/**
	 * Get stored JSON data with fallback handling
	 *
	 * @return array|null The stored JSON data or null if not available
	 */
	public static function get_json_data()
	{
		$data = get_option('amfm_maps_json_data', null);
		
		// If primary data exists and is valid, return it
		if (is_array($data) && !empty($data)) {
			return $data;
		}
		
		// Try fallback data if primary data is unavailable
		$fallback_data = get_option('amfm_maps_fallback_data', null);
		if (is_array($fallback_data) && !empty($fallback_data)) {
			// Log that we're using fallback data
			error_log('[AMFM Maps] Using fallback data due to primary data unavailability');
			return $fallback_data;
		}
		
		// Try loading from sample data file as last resort
		return self::load_sample_data();
	}
	
	/**
	 * Load sample data as ultimate fallback
	 *
	 * @return array|null
	 */
	public static function load_sample_data()
	{
		$sample_file = plugin_dir_path(__FILE__) . '../sample_data.json';
		
		if (file_exists($sample_file)) {
			$sample_content = file_get_contents($sample_file);
			if ($sample_content !== false) {
				$sample_data = json_decode($sample_content, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($sample_data)) {
					error_log('[AMFM Maps] Using sample data as ultimate fallback');
					return $sample_data;
				}
			}
		}
		
		error_log('[AMFM Maps] No data available - all fallback methods failed');
		return null;
	}
	
	/**
	 * Create fallback data backup when sync is successful
	 *
	 * @param array $data
	 */
	private function create_fallback_backup($data)
	{
		if (is_array($data) && !empty($data)) {
			update_option('amfm_maps_fallback_data', $data);
			update_option('amfm_maps_fallback_created', current_time('mysql'));
		}
	}

	/**
	 * Get filtered JSON data based on global shortname filter
	 *
	 * @return array|null
	 */
	public static function get_filtered_json_data()
	{
		$data = self::get_json_data();
		if (!$data) {
			return null;
		}

		$global_filter = get_option('amfm_maps_global_shortname_filter', 'all');
		
		// If filter is 'all', return unfiltered data
		if ($global_filter === 'all') {
			return $data;
		}

		// Filter data by shortname
		$filtered_data = array_filter($data, function($location) use ($global_filter) {
			$shortname = isset($location['(Internal) Shortname']) ? $location['(Internal) Shortname'] : '';
			return $shortname === $global_filter;
		});

		// Re-index the array to maintain sequential indices
		return array_values($filtered_data);
	}

	/**
	 * Check if JSON data is available and recent
	 *
	 * @param int $max_age_hours Maximum age in hours (default 24)
	 * @return boolean
	 */
	public static function is_data_fresh($max_age_hours = 24)
	{
		$last_sync = get_option('amfm_maps_last_sync', '');
		$sync_status = get_option('amfm_maps_sync_status', '');
		
		if (empty($last_sync) || $sync_status !== 'success') {
			return false;
		}
		
		$last_sync_time = strtotime($last_sync);
		$max_age_seconds = $max_age_hours * 3600;
		
		return (time() - $last_sync_time) <= $max_age_seconds;
	}

	/**
	 * Get sync status information
	 *
	 * @return array
	 */
	public static function get_sync_info()
	{
		return array(
			'last_sync' => get_option('amfm_maps_last_sync', ''),
			'status' => get_option('amfm_maps_sync_status', ''),
			'url' => get_option('amfm_maps_json_url', ''),
			'interval' => get_option('amfm_maps_sync_interval', 'none'),
			'has_data' => !empty(get_option('amfm_maps_json_data', null))
		);
	}

	/**
	 * Handle manual sync request
	 *
	 * @return array Result of sync operation
	 */
	public function handle_manual_sync()
	{
		return $this->sync_json_data();
	}

	/**
	 * Public method to sync data (for manual sync)
	 *
	 * @return array
	 */
	public function manual_sync_data()
	{
		$json_url = get_option('amfm_maps_json_url', '');
		
		if (empty($json_url)) {
			return array('success' => false, 'message' => __('No URL configured', 'amfm-maps'));
		}
		
		// Validate URL format
		if (!filter_var($json_url, FILTER_VALIDATE_URL)) {
			return array('success' => false, 'message' => __('Invalid URL format', 'amfm-maps'));
		}
		
		try {
			$response = wp_remote_get($json_url, array(
				'timeout' => 30,
				'headers' => array(
					'Accept' => 'application/json',
					'User-Agent' => 'AMFM Maps WordPress Plugin'
				)
			));
			
			if (is_wp_error($response)) {
				update_option('amfm_maps_sync_status', 'error');
				return array('success' => false, 'message' => $response->get_error_message());
			}
			
			$response_code = wp_remote_retrieve_response_code($response);
			if ($response_code !== 200) {
				update_option('amfm_maps_sync_status', 'error');
				return array('success' => false, 'message' => sprintf(__('HTTP Error: %d', 'amfm-maps'), $response_code));
			}
			
			$body = wp_remote_retrieve_body($response);
			
			if (empty($body)) {
				update_option('amfm_maps_sync_status', 'error');
				return array('success' => false, 'message' => __('Empty response from server', 'amfm-maps'));
			}
			
			$data = json_decode($body, true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				update_option('amfm_maps_sync_status', 'error');
				return array('success' => false, 'message' => __('Invalid JSON response: ', 'amfm-maps') . json_last_error_msg());
			}
			
			// Save the data
			update_option('amfm_maps_json_data', $data);
			update_option('amfm_maps_last_sync', current_time('mysql'));
			update_option('amfm_maps_sync_status', 'success');
			
			// Trigger action for other components that depend on data updates
			do_action('amfm_maps_data_updated', $data);
			
			return array('success' => true, 'message' => __('Data synced successfully', 'amfm-maps'));
			
		} catch (Exception $e) {
			update_option('amfm_maps_sync_status', 'error');
			return array('success' => false, 'message' => __('Sync error: ', 'amfm-maps') . $e->getMessage());
		}
	}
	
	/**
	 * Get available filter types from JSON data
	 *
	 * @return array Array of available filter types with their options
	 */
	public static function get_available_filters()
	{
		$json_data = self::get_filtered_json_data();
		
		if (empty($json_data)) {
			return array();
		}
		
	   // Dynamically detect all filter groups and options from JSON data
	   $filters = array();
	   $group_patterns = array(
		   'location' => array('label' => __('Location', 'amfm-maps'), 'key' => 'State'),
		   'region' => array('label' => __('Region', 'amfm-maps'), 'key' => 'Region'),
		   'gender' => array('label' => __('Gender', 'amfm-maps'), 'key' => 'Details: Gender'),
		   'level_of_care' => array('label' => __('Level of Care', 'amfm-maps')),
	   );
	   $dynamic_groups = array();
	   // Scan all keys for dynamic groups (e.g., Conditions: X, Programs: Y, Accommodations: Z)
	   foreach ($json_data as $row) {
		   foreach ($row as $key => $value) {
			   if (preg_match('/^([A-Za-z]+): (.+)$/', $key, $matches)) {
				   $group = strtolower($matches[1]);
				   $option = $matches[2];
				   if (!isset($dynamic_groups[$group])) {
					   $dynamic_groups[$group] = array();
				   }
				   if ($value == 1 && !in_array($option, $dynamic_groups[$group])) {
					   $dynamic_groups[$group][] = $option;
				   }
			   }
		   }
	   }
	   // Add static groups (location, gender, level_of_care)
	   foreach ($group_patterns as $type => $info) {
		   $options = array();
		   
		   if ($type === 'level_of_care') {
			   // Handle level of care with "Level of Care: " prefixed fields
			   foreach ($json_data as $row) {
				   foreach ($row as $key => $value) {
					   if (strpos($key, 'Level of Care: ') === 0 && $value == 1) {
						   $care_type = str_replace('Level of Care: ', '', $key);
						   if (!in_array($care_type, $options)) {
							   $options[] = $care_type;
						   }
					   }
				   }
			   }
		   } else {
			   // Handle other static groups (location, gender)
			   foreach ($json_data as $row) {
				   if (!empty($row[$info['key']])) {
					   $val = $type === 'location' ? self::get_full_state_name($row[$info['key']]) : $row[$info['key']];
					   if (!in_array($val, $options)) {
						   $options[] = $val;
					   }
				   }
			   }
		   }
		   
		   sort($options);
		   $filters[$type] = array(
			   'label' => $info['label'],
			   'options' => array_map(function($opt) { return array('label' => $opt, 'value' => $opt); }, $options),
			   'enabled' => false, // Disabled by default
			   'order' => 0
		   );
	   }
	   // Add dynamic groups
	   $order = 1;
	   foreach ($dynamic_groups as $group => $options) {
		   // Normalize group name (e.g., accommodations -> accommodations)
		   $group_key = $group;
		   if ($group_key === 'accomodations') $group_key = 'accommodations';
		   $filters[$group_key] = array(
			   'label' => ucfirst($group_key),
			   'options' => array_map(function($opt) { return array('label' => $opt, 'value' => $opt); }, $options),
			   'enabled' => false, // Disabled by default
			   'order' => $order++
		   );
	   }
	   // Sort groups by order
	   uasort($filters, function($a, $b) {
		   return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
	   });
	   return $filters;
	}
	
	/**
	 * Convert state abbreviation to full name
	 *
	 * @param string $abbreviation State abbreviation
	 * @return string Full state name
	 */
	private static function get_full_state_name($abbreviation)
	{
		$states = array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming'
		);
		
		return isset($states[$abbreviation]) ? $states[$abbreviation] : $abbreviation;
	}
	
	/**
	 * Get filter configuration settings
	 *
	 * @return array Current filter configuration
	 */
   public static function get_filter_config()
   {
	   $available_filters = self::get_available_filters();
	   $saved_config = get_option('amfm_maps_filter_config', array());
	   $merged_config = array();
	   $new_filter_detected = false;
	   $order = 0;

	   foreach ($available_filters as $type => $filter) {
		   if (isset($saved_config[$type]) && is_array($saved_config[$type])) {
			   // Merge saved config with detected options
			   $merged = array_merge(
				   $filter,
				   $saved_config[$type]
			   );
			   // Merge options (preserve custom labels/order)
			   if (isset($saved_config[$type]['options']) && is_array($saved_config[$type]['options'])) {
				   // Map detected options to saved config (by value)
				   $detected_options = array_column($filter['options'], 'value');
				   $saved_options = $saved_config[$type]['options'];
				   $merged_options = array();
				   foreach ($saved_options as $opt) {
					   if (in_array($opt['value'], $detected_options)) {
						   $merged_options[] = $opt;
					   }
				   }
				   // Add any new detected options (not in saved config)
				   foreach ($filter['options'] as $opt) {
					   if (!in_array($opt['value'], array_column($merged_options, 'value'))) {
						   $merged_options[] = $opt;
						   $new_filter_detected = true;
					   }
				   }
				   $merged['options'] = $merged_options;
			   }
			   $merged_config[$type] = $merged;
		   } else {
			   $merged_config[$type] = $filter;
			   $new_filter_detected = true;
		   }
		   $merged_config[$type]['order'] = $order++;
	   }
	   // Notify admin if new filter detected
	   if ($new_filter_detected && is_admin() && !get_transient('amfm_maps_new_filter_notice')) {
		   set_transient('amfm_maps_new_filter_notice', 1, 60*60); // 1 hour
	   }

	   return $merged_config;
   }
	
	/**
	 * Save filter configuration
	 *
	 * @param array $config Filter configuration
	 * @return bool Success status
	 */
	public static function save_filter_config($config)
	{
		// Get current config for comparison
		$current_config = get_option('amfm_maps_filter_config', array());
		
		// Use update_option which handles both insert and update
		$result = update_option('amfm_maps_filter_config', $config);
		
		// If update_option returns false, it might mean the value didn't change
		// In that case, check if the option exists and has our data
		if (!$result) {
			$saved_config = get_option('amfm_maps_filter_config');
			if ($saved_config === $config) {
				return true;
			}
		}
		
		// Verify the option was saved
		$saved_config = get_option('amfm_maps_filter_config');
		
		return $result;
	}
	
	/**
	 * Get enabled filter types
	 *
	 * @return array Array of enabled filter types
	 */
	public static function get_enabled_filters()
	{
		$config = self::get_filter_config();
		$enabled = array();
		
		foreach ($config as $type => $settings) {
			if (!empty($settings['enabled'])) {
				$enabled[] = $type;
			}
		}
		
		return $enabled;
	}
	
	/**
	 * Get filter options for a specific type
	 *
	 * @param string $filter_type Filter type (location, gender, conditions, programs, accommodations)
	 * @return array Array of filter options
	 */
	public static function get_filter_options($filter_type)
	{
		$available_filters = self::get_available_filters();
		$config = self::get_filter_config();
		
		if (!isset($available_filters[$filter_type]) || empty($config[$filter_type]['enabled'])) {
			return array();
		}
		
		$options = $available_filters[$filter_type]['options'];
		$filter_config = $config[$filter_type];
		
		// Apply sorting
		if ($filter_config['sort_order'] === 'desc') {
			rsort($options);
		} else {
			sort($options);
		}
		
		// Apply limit
		if ($filter_config['limit'] > 0) {
			$options = array_slice($options, 0, $filter_config['limit']);
		}
		
		return $options;
	}
	
	/**
	 * AJAX handler for saving filter configuration
	 */
	public function ajax_save_filter_config()
	{
		// Log that the function was called
		error_log('AMFM Maps: ajax_save_filter_config called at ' . date('Y-m-d H:i:s'));
		
		// Check if this is a POST request
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			error_log('AMFM Maps: Not a POST request');
			wp_send_json_error(array('message' => 'Invalid request method'));
			return;
		}
		
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amfm_maps_ajax_nonce')) {
			error_log('AMFM Maps: Security check failed');
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			error_log('AMFM Maps: Insufficient permissions');
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		if (!isset($_POST['config'])) {
			error_log('AMFM Maps: No config data received');
			wp_send_json_error(array('message' => __('Invalid configuration data', 'amfm-maps')));
			return;
		}
		
		// Decode JSON configuration data
		$config = json_decode(stripslashes($_POST['config']), true);
		
		if (!is_array($config)) {
			error_log('AMFM Maps: Config is not an array after JSON decode');
			error_log('AMFM Maps: Raw config data: ' . $_POST['config']);
			wp_send_json_error(array('message' => __('Invalid configuration data format', 'amfm-maps')));
			return;
		}
		
		error_log('AMFM Maps: Decoded config: ' . print_r($config, true));
		
	   // Sanitize and validate configuration
	   $sanitized_config = array();
	   // Accept any filter type (dynamic)
	   foreach ($config as $type => $type_config) {
		   if (is_array($type_config)) {
			   $sanitized_type = array(
				   'enabled' => !empty($type_config['enabled']),
				   'label' => isset($type_config['label']) ? sanitize_text_field($type_config['label']) : '',
				   'limit' => isset($type_config['limit']) ? absint($type_config['limit']) : 0,
				   'sort_order' => (isset($type_config['sort_order']) && in_array($type_config['sort_order'], array('asc', 'desc'))) ? $type_config['sort_order'] : 'asc',
				   'order' => isset($type_config['order']) ? intval($type_config['order']) : 0
			   );
			   // Sanitize options (allow custom label and order)
			   if (isset($type_config['options']) && is_array($type_config['options'])) {
				   $sanitized_options = array();
				   foreach ($type_config['options'] as $opt) {
					   $sanitized_options[] = array(
						   'label' => isset($opt['label']) ? sanitize_text_field($opt['label']) : '',
						   'value' => isset($opt['value']) ? sanitize_text_field($opt['value']) : '',
						   'order' => isset($opt['order']) ? intval($opt['order']) : 0
					   );
				   }
				   $sanitized_type['options'] = $sanitized_options;
			   }
			   $sanitized_config[$type] = $sanitized_type;
		   }
	   }
	   if (empty($sanitized_config)) {
		   error_log('AMFM Maps: No valid configuration data after sanitization');
		   wp_send_json_error(array('message' => __('No valid configuration data provided', 'amfm-maps')));
		   return;
	   }
	   error_log('AMFM Maps: Sanitized config: ' . print_r($sanitized_config, true));
	   $result = self::save_filter_config($sanitized_config);
	   error_log('AMFM Maps: Save result: ' . ($result ? 'success' : 'failed'));
	   if ($result) {
		   wp_send_json_success(array('message' => __('Filter configuration saved successfully', 'amfm-maps')));
	   } else {
		   wp_send_json_error(array('message' => __('Failed to save filter configuration', 'amfm-maps')));
	   }
	}
	
	/**
	 * AJAX handler for getting available filters
	 */
	public function ajax_get_available_filters()
	{
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amfm_maps_ajax_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		$available_filters = self::get_available_filters();
		$filter_config = self::get_filter_config();
		
		// Merge available filters with current config
		foreach ($available_filters as $type => $data) {
			if (isset($filter_config[$type])) {
				$available_filters[$type] = array_merge($data, $filter_config[$type]);
			}
		}
		
		wp_send_json_success($available_filters);
	}

	/**
	 * Test AJAX handler
	 */
}
