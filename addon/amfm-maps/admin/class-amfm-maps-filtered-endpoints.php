<?php

/**
 * The Filtered Endpoints functionality of the AMFM Maps plugin.
 *
 * @link       https://adzbyte.com/
 * @since      1.0.0
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin
 */

/**
 * The Filtered Endpoints functionality.
 *
 * Manages filtered JSON endpoints by organization and state.
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Maps_Filtered_Endpoints
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
	 * Initialize hooks and actions
	 */
	public function init_hooks()
	{
		// Register REST API endpoints
		add_action('rest_api_init', array($this, 'register_rest_routes'));
		
		// Clear cache when master data updates
		add_action('amfm_maps_data_updated', array($this, 'clear_filtered_cache'));
		
		// Clear cache when locations configuration changes
		add_action('updated_option', array($this, 'on_option_updated'), 10, 3);
	}

	/**
	 * Register REST API routes for filtered endpoints
	 */
	public function register_rest_routes()
	{
		// Main filtered endpoint: /amfm-maps/v1/{org}/{state}
		register_rest_route('amfm-maps/v1', '/(?P<org>[a-zA-Z0-9]+)/(?P<state>[a-zA-Z]{2})', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_filtered_locations'),
			'permission_callback' => '__return_true', // Public endpoint
			'args' => array(
				'org' => array(
					'required' => true,
					'validate_callback' => array($this, 'validate_org'),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'state' => array(
					'required' => true,
					'validate_callback' => array($this, 'validate_state'),
					'sanitize_callback' => array($this, 'sanitize_state'),
				),
			),
		));

		// Organization endpoint: /amfm-maps/v1/{org}/all
		register_rest_route('amfm-maps/v1', '/(?P<org>[a-zA-Z0-9]+)/all', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_org_locations'),
			'permission_callback' => '__return_true', // Public endpoint
			'args' => array(
				'org' => array(
					'required' => true,
					'validate_callback' => array($this, 'validate_org'),
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		));

		// Organizations list endpoint: /amfm-maps/v1/organizations
		register_rest_route('amfm-maps/v1', '/organizations', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_organizations'),
			'permission_callback' => '__return_true', // Public endpoint
		));

		// Debug endpoint: /amfm-maps/v1/debug
		register_rest_route('amfm-maps/v1', '/debug', array(
			'methods' => 'GET',
			'callback' => array($this, 'debug_data_values'),
			'permission_callback' => '__return_true', // Public endpoint
		));

		// States for organization endpoint: /amfm-maps/v1/{org}/states
		register_rest_route('amfm-maps/v1', '/(?P<org>[a-zA-Z0-9]+)/states', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_org_states'),
			'permission_callback' => '__return_true', // Public endpoint
			'args' => array(
				'org' => array(
					'required' => true,
					'validate_callback' => array($this, 'validate_org'),
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		));
		
		// Add direct CORS support - more aggressive approach
		add_action('init', array($this, 'handle_cors_headers'));
	}

	/**
	 * Get filtered locations by organization and state
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_filtered_locations($request)
	{
		// Send CORS headers immediately
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		
		$org = $request->get_param('org');
		$state = $request->get_param('state');
		
		// Check cache first
		$cache_key = "amfm_maps_filtered_{$org}_{$state}";
		$cached_data = get_transient($cache_key);
		
		if ($cached_data !== false) {
			return new WP_REST_Response($cached_data, 200);
		}

		// Get master data
		$master_data = $this->get_master_data();
		if (empty($master_data)) {
			return new WP_Error('no_data', 'No location data available', array('status' => 404));
		}

		// Filter data by org/state FIRST
		$filtered_data = $this->filter_locations($master_data, $org, $state);
		
		if (empty($filtered_data)) {
			return new WP_Error('no_matches', "No locations found for organization '{$org}' in state '{$state}'", array('status' => 404));
		}

		// THEN apply column configuration formatting
		$formatted_data = $this->apply_column_configuration($filtered_data);

		// Cache the formatted result for 1 hour
		set_transient($cache_key, $formatted_data, HOUR_IN_SECONDS);

		$response = new WP_REST_Response($formatted_data, 200);
		$response->header('X-Total-Count', count($formatted_data));
		$response->header('X-Organization', $org);
		$response->header('X-State', $state);
		
		return $response;
	}

	/**
	 * Get all locations for an organization
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_org_locations($request)
	{
		// Send CORS headers immediately
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		
		$org = $request->get_param('org');
		
		// Check cache first
		$cache_key = "amfm_maps_org_{$org}_all";
		$cached_data = get_transient($cache_key);
		
		if ($cached_data !== false) {
			return new WP_REST_Response($cached_data, 200);
		}

		// Get master data
		$master_data = $this->get_master_data();
		if (empty($master_data)) {
			return new WP_Error('no_data', 'No location data available', array('status' => 404));
		}

		// Filter by organization only
		$filtered_data = array_filter($master_data, function($location) use ($org) {
			$location_org = isset($location['(Internal) Shortname']) ? strtolower(trim($location['(Internal) Shortname'])) : '';
			return $location_org === strtolower($org);
		});

		// Re-index array
		$filtered_data = array_values($filtered_data);
		
		if (empty($filtered_data)) {
			return new WP_Error('no_matches', "No locations found for organization '{$org}'", array('status' => 404));
		}

		// Apply column configuration formatting
		$formatted_data = $this->apply_column_configuration($filtered_data);

		// Cache the formatted result for 1 hour
		set_transient($cache_key, $formatted_data, HOUR_IN_SECONDS);

		$response = new WP_REST_Response($formatted_data, 200);
		$response->header('X-Total-Count', count($formatted_data));
		$response->header('X-Organization', $org);
		
		return $response;
	}

	/**
	 * Get list of available organizations
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_organizations($request)
	{
		// Send CORS headers immediately
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		
		// Check cache first
		$cache_key = "amfm_maps_organizations";
		$cached_data = get_transient($cache_key);
		
		if ($cached_data !== false) {
			return new WP_REST_Response($cached_data, 200);
		}

		// Get master data
		$master_data = $this->get_master_data();
		if (empty($master_data)) {
			return new WP_Error('no_data', 'No location data available', array('status' => 404));
		}

		$organizations = array();
		foreach ($master_data as $location) {
			$org = isset($location['(Internal) Shortname']) ? trim($location['(Internal) Shortname']) : '';
			if (!empty($org) && !in_array($org, $organizations)) {
				$organizations[] = $org;
			}
		}

		sort($organizations);
		
		// Get counts for each organization
		$org_data = array();
		foreach ($organizations as $org) {
			$count = count(array_filter($master_data, function($location) use ($org) {
				$location_org = isset($location['(Internal) Shortname']) ? trim($location['(Internal) Shortname']) : '';
				return $location_org === $org;
			}));
			
			$org_data[] = array(
				'organization' => $org,
				'count' => $count,
				'endpoint' => rest_url("amfm-maps/v1/{$org}/all")
			);
		}

		// Cache the result for 1 hour
		set_transient($cache_key, $org_data, HOUR_IN_SECONDS);

		return new WP_REST_Response($org_data, 200);
	}

	/**
	 * Get available states for an organization
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_org_states($request)
	{
		// Send CORS headers immediately
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		
		$org = $request->get_param('org');
		
		// Check cache first
		$cache_key = "amfm_maps_org_{$org}_states";
		$cached_data = get_transient($cache_key);
		
		if ($cached_data !== false) {
			return new WP_REST_Response($cached_data, 200);
		}

		// Get master data
		$master_data = $this->get_master_data();
		if (empty($master_data)) {
			return new WP_Error('no_data', 'No location data available', array('status' => 404));
		}

		// Filter by organization and collect states
		$states = array();
		foreach ($master_data as $location) {
			$location_org = isset($location['(Internal) Shortname']) ? strtolower(trim($location['(Internal) Shortname'])) : '';
			$location_state = isset($location['State']) ? strtoupper(trim($location['State'])) : '';
			
			if ($location_org === strtolower($org) && !empty($location_state) && !in_array($location_state, $states)) {
				$states[] = $location_state;
			}
		}

		sort($states);
		
		if (empty($states)) {
			return new WP_Error('no_matches', "No states found for organization '{$org}'", array('status' => 404));
		}

		// Get counts for each state
		$state_data = array();
		foreach ($states as $state) {
			$count = count($this->filter_locations($master_data, $org, $state));
			
			$state_data[] = array(
				'state' => $state,
				'count' => $count,
				'endpoint' => rest_url("amfm-maps/v1/{$org}/" . strtolower($state))
			);
		}

		// Cache the result for 1 hour
		set_transient($cache_key, $state_data, HOUR_IN_SECONDS);

		$response = new WP_REST_Response($state_data, 200);
		$response->header('X-Organization', $org);
		
		return $response;
	}

	/**
	 * Debug data values endpoint
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function debug_data_values($request)
	{
		// Send CORS headers immediately
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		
		// Get master data
		$master_data = $this->get_master_data();
		if (empty($master_data)) {
			return new WP_Error('no_data', 'No location data available', array('status' => 404));
		}

		$debug_info = array(
			'total_items' => count($master_data),
			'organizations' => array(),
			'states' => array(),
			'org_state_combinations' => array(),
			'first_item_sample' => array(),
		);

		// Analyze first item
		if (!empty($master_data[0])) {
			$first_item = $master_data[0];
			$debug_info['first_item_sample'] = array(
				'(Internal) Shortname' => isset($first_item['(Internal) Shortname']) ? $first_item['(Internal) Shortname'] : 'NOT_FOUND',
				'State' => isset($first_item['State']) ? $first_item['State'] : 'NOT_FOUND',
				'Business name' => isset($first_item['Business name']) ? $first_item['Business name'] : 'NOT_FOUND',
				'City' => isset($first_item['City']) ? $first_item['City'] : 'NOT_FOUND',
			);
		}

		// Collect unique values
		$org_counts = array();
		$state_counts = array();
		$combinations = array();

		foreach ($master_data as $location) {
			$org = isset($location['(Internal) Shortname']) ? trim($location['(Internal) Shortname']) : '';
			$state = isset($location['State']) ? trim($location['State']) : '';

			// Count organizations
			if (!empty($org)) {
				$org_lower = strtolower($org);
				$org_counts[$org_lower] = ($org_counts[$org_lower] ?? 0) + 1;
			}

			// Count states
			if (!empty($state)) {
				$state_upper = strtoupper($state);
				$state_counts[$state_upper] = ($state_counts[$state_upper] ?? 0) + 1;
			}

			// Track combinations
			if (!empty($org) && !empty($state)) {
				$combo_key = strtolower($org) . '|' . strtoupper($state);
				$combinations[$combo_key] = ($combinations[$combo_key] ?? 0) + 1;
			}
		}

		$debug_info['organizations'] = $org_counts;
		$debug_info['states'] = $state_counts;
		$debug_info['org_state_combinations'] = $combinations;

		// Test specific amfm+ca filter
		$amfm_ca_count = 0;
		foreach ($master_data as $location) {
			$location_org = isset($location['(Internal) Shortname']) ? strtolower(trim($location['(Internal) Shortname'])) : '';
			$location_state = isset($location['State']) ? strtoupper(trim($location['State'])) : '';
			
			if ($location_org === 'amfm' && $location_state === 'CA') {
				$amfm_ca_count++;
			}
		}

		$debug_info['amfm_ca_filter_test'] = $amfm_ca_count;

		return new WP_REST_Response($debug_info, 200);
	}

	/**
	 * Filter locations by organization and state
	 *
	 * @param array $data Master location data
	 * @param string $org Organization shortname
	 * @param string $state State code
	 * @return array Filtered locations
	 */
	private function filter_locations($data, $org, $state)
	{
		$filtered = array_filter($data, function($location) use ($org, $state) {
			$location_org = isset($location['(Internal) Shortname']) ? strtolower(trim($location['(Internal) Shortname'])) : '';
			$location_state = isset($location['State']) ? strtoupper(trim($location['State'])) : '';
			
			return $location_org === strtolower($org) && $location_state === strtoupper($state);
		});

		// Re-index array to maintain proper JSON structure
		return array_values($filtered);
	}

	/**
	 * Get master location data
	 *
	 * @return array|null Master location data
	 */
	private function get_master_data()
	{
		// ALWAYS get raw data first - we need all fields for filtering
		$raw_data = get_option('amfm_maps_json_data', null);
		
		if (empty($raw_data) && class_exists('Amfm_Maps_Admin')) {
			$raw_data = Amfm_Maps_Admin::get_json_data();
		}
		
		return is_array($raw_data) ? $raw_data : null;
	}

	/**
	 * Apply column configuration to raw data
	 *
	 * @param array $raw_data Raw location data
	 * @return array Transformed data respecting column configuration
	 */
	private function apply_column_configuration($raw_data)
	{
		// Get locations configuration
		$config = $this->get_locations_config();
		
		if (empty($config['columns'])) {
			return $raw_data; // No configuration, return raw data
		}
		
		// Get visible columns only
		$visible_columns = array_filter($config['columns'], function($col) {
			return !empty($col['visible']);
		});
		
		if (empty($visible_columns)) {
			return $raw_data; // No visible columns configured, return all data
		}
		
		// Transform each location to include only visible columns
		$transformed = array();
		foreach ($raw_data as $location) {
			$transformed_location = array();
			
			foreach ($visible_columns as $column) {
				$key = $column['key'];
				if (isset($location[$key])) {
					$transformed_location[$key] = $location[$key];
				} elseif (!empty($column['custom'])) {
					// Handle custom columns (could be computed or default value)
					$transformed_location[$key] = $this->compute_custom_value($location, $key);
				}
			}
			
			$transformed[] = $transformed_location;
		}
		
		return $transformed;
	}

	/**
	 * Get locations configuration (copied from Locations Manager)
	 *
	 * @return array Locations configuration
	 */
	private function get_locations_config()
	{
		$default_config = array(
			'columns' => array(
				array('key' => 'Business name', 'label' => 'Business Name', 'visible' => true),
				array('key' => 'Region', 'label' => 'Region', 'visible' => true),
				array('key' => 'City', 'label' => 'City', 'visible' => true),
				array('key' => 'State', 'label' => 'State', 'visible' => true),
				array('key' => 'Complete Address', 'label' => 'Address', 'visible' => true),
				array('key' => 'URL', 'label' => 'Website', 'visible' => true),
			)
		);
		
		$saved_config = get_option('amfm_locations_config', null);
		
		if ($saved_config === null) {
			return $default_config;
		}
		
		return $saved_config;
	}

	/**
	 * Compute custom column value
	 *
	 * @param array $location Original location data
	 * @param string $key Custom column key
	 * @return string Computed value
	 */
	private function compute_custom_value($location, $key)
	{
		// This can be extended for custom computed values
		// For now, return empty string for custom fields
		return '';
	}

	/**
	 * Validate organization parameter
	 *
	 * @param string $value
	 * @param WP_REST_Request $request
	 * @param string $param
	 * @return bool
	 */
	public function validate_org($value, $request, $param)
	{
		// Allow alphanumeric characters only
		return preg_match('/^[a-zA-Z0-9]+$/', $value);
	}

	/**
	 * Validate state parameter
	 *
	 * @param string $value
	 * @param WP_REST_Request $request
	 * @param string $param
	 * @return bool
	 */
	public function validate_state($value, $request, $param)
	{
		// Must be exactly 2 letters
		return preg_match('/^[a-zA-Z]{2}$/', $value);
	}

	/**
	 * Sanitize state parameter to uppercase
	 *
	 * @param string $value
	 * @return string
	 */
	public function sanitize_state($value)
	{
		return strtoupper(sanitize_text_field($value));
	}

	/**
	 * Handle option updates to clear cache when configuration changes
	 *
	 * @param string $option_name
	 * @param mixed $old_value
	 * @param mixed $new_value
	 */
	public function on_option_updated($option_name, $old_value, $new_value)
	{
		// Clear cache when locations configuration or transformed data changes
		if (in_array($option_name, array('amfm_locations_config', 'amfm_locations_transformed_data'))) {
			$this->clear_filtered_cache();
		}
	}

	/**
	 * Clear all filtered data cache
	 */
	public function clear_filtered_cache()
	{
		global $wpdb;
		
		// Delete all transients that start with our cache prefix
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_amfm_maps_filtered_%' OR option_name LIKE '_transient_amfm_maps_org_%' OR option_name LIKE '_transient_amfm_maps_organizations'");
		
		// Also delete timeout transients
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_amfm_maps_filtered_%' OR option_name LIKE '_transient_timeout_amfm_maps_org_%' OR option_name LIKE '_transient_timeout_amfm_maps_organizations'");
	}

	/**
	 * Get endpoint statistics for admin display
	 *
	 * @return array Statistics about available endpoints
	 */
	public function get_endpoint_stats()
	{
		$master_data = $this->get_master_data();
		if (empty($master_data)) {
			return array(
				'total_locations' => 0,
				'organizations' => array(),
				'total_endpoints' => 0
			);
		}

		$organizations = array();
		$total_endpoints = 0;

		// Group by organization and state
		foreach ($master_data as $location) {
			$org = isset($location['(Internal) Shortname']) ? trim($location['(Internal) Shortname']) : '';
			$state = isset($location['State']) ? strtoupper(trim($location['State'])) : '';
			
			if (empty($org) || empty($state)) {
				continue;
			}

			if (!isset($organizations[$org])) {
				$organizations[$org] = array(
					'name' => $org,
					'states' => array(),
					'total_locations' => 0
				);
			}

			if (!isset($organizations[$org]['states'][$state])) {
				$organizations[$org]['states'][$state] = 0;
				$total_endpoints++;
			}

			$organizations[$org]['states'][$state]++;
			$organizations[$org]['total_locations']++;
		}

		return array(
			'total_locations' => count($master_data),
			'organizations' => $organizations,
			'total_endpoints' => $total_endpoints
		);
	}

	/**
	 * Add CORS support for REST API endpoints
	 */
	public function add_cors_support()
	{
		// Add CORS headers to our API responses
		add_filter('rest_pre_serve_request', array($this, 'add_cors_headers'), 15, 4);
		
		// Also add headers directly to the response object for better compatibility
		add_filter('rest_post_dispatch', array($this, 'add_cors_to_response'), 15, 3);
	}

	/**
	 * Add CORS headers to REST API responses
	 */
	public function add_cors_headers($served, $result, $request, $server)
	{
		// Only add headers for our endpoints
		$route = $request->get_route();
		if (strpos($route, '/amfm-maps/v1/') === 0) {
			// Always set CORS headers unconditionally
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
			header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
			// Remove Allow-Credentials when using wildcard origin
			// header('Access-Control-Allow-Credentials: true');
		}
		
		return $served;
	}

	/**
	 * Add CORS headers directly to the response object
	 */
	public function add_cors_to_response($response, $server, $request)
	{
		// Only add headers for our endpoints
		$route = $request->get_route();
		if (strpos($route, '/amfm-maps/v1/') === 0) {
			// Always set CORS headers regardless of Origin presence
			$response->header('Access-Control-Allow-Origin', '*');
			$response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
			$response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
			// Remove Allow-Credentials when using wildcard origin for better compatibility
			// $response->header('Access-Control-Allow-Credentials', 'true');
		}
		
		return $response;
	}

	/**
	 * Handle CORS headers directly
	 */
	public function handle_cors_headers()
	{
		// Check if this is a request to our API endpoint
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		if (strpos($request_uri, '/wp-json/amfm-maps/v1/') !== false) {
			// Send CORS headers immediately
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
			header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		}
	}
}