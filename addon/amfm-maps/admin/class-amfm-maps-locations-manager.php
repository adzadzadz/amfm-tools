<?php

/**
 * The Locations Manager functionality of the AMFM Maps plugin.
 *
 * @link       https://adzbyte.com/
 * @since      1.0.0
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin
 */

/**
 * The Locations Manager functionality.
 *
 * Manages the transformation and customization of JSON location data.
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Maps_Locations_Manager
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
		// Add AJAX handlers
		add_action('wp_ajax_amfm_locations_get_data', array($this, 'ajax_get_locations_data'));
		add_action('wp_ajax_amfm_locations_save_config', array($this, 'ajax_save_locations_config'));
		add_action('wp_ajax_amfm_locations_reset_config', array($this, 'ajax_reset_locations_config'));
		add_action('wp_ajax_amfm_clear_filtered_cache', array($this, 'ajax_clear_filtered_cache'));
		add_action('wp_ajax_amfm_debug_data_status', array($this, 'ajax_debug_data_status'));
		
		// Register REST API endpoint
		add_action('rest_api_init', array($this, 'register_rest_routes'));
		
		// Disable authentication for our endpoints
		add_filter('rest_authentication_errors', array($this, 'disable_auth_for_endpoints'));
	}

	/**
	 * Get the locations manager tab content
	 */
	public function get_tab_content()
	{
		ob_start();
		$this->display_locations_manager_content();
		return ob_get_clean();
	}

	/**
	 * Display the Locations Manager content (for tab)
	 */
	public function display_locations_manager_content()
	{
		?>
		<div class="amfm-maps-container">
			<div class="amfm-maps-main amfm-maps-full-width">
				<div class="notice notice-info">
					<p><?php echo esc_html__('Manage and customize the location data structure. You can rearrange columns, rename fields, and control which data is exposed via the REST API.', 'amfm-maps'); ?></p>
				</div>

			<div id="amfm-locations-manager">
				<div class="amfm-locations-toolbar">
					<button type="button" class="button button-primary" id="save-locations-config">
						<?php echo esc_html__('Save Configuration', 'amfm-maps'); ?>
					</button>
					<button type="button" class="button" id="reset-locations-config">
						<?php echo esc_html__('Reset to Default', 'amfm-maps'); ?>
					</button>
					<button type="button" class="button" id="add-custom-column">
						<?php echo esc_html__('Add Custom Column', 'amfm-maps'); ?>
					</button>
				</div>

				<div class="amfm-locations-content">
					<div class="amfm-columns-manager">
						<h2><?php echo esc_html__('Column Configuration', 'amfm-maps'); ?></h2>
						<p class="description"><?php echo esc_html__('Drag to reorder columns. Check/uncheck to show/hide in the API output.', 'amfm-maps'); ?></p>
						<div id="columns-list" class="sortable-columns">
							<!-- Columns will be loaded here via AJAX -->
						</div>
					</div>

					<div class="amfm-preview-section">
						<h2><?php echo esc_html__('Data Preview', 'amfm-maps'); ?></h2>
						<p class="description"><?php echo esc_html__('Preview of the first 5 locations with current configuration.', 'amfm-maps'); ?></p>
						<div id="preview-container">
							<table class="wp-list-table widefat fixed striped">
								<thead id="preview-header">
									<!-- Headers will be loaded here -->
								</thead>
								<tbody id="preview-data">
									<!-- Data will be loaded here -->
								</tbody>
							</table>
						</div>
					</div>

					<div class="amfm-api-info">
						<h2><?php echo esc_html__('REST API Endpoints', 'amfm-maps'); ?></h2>
						<p><?php echo esc_html__('Access your location data via these endpoints:', 'amfm-maps'); ?></p>
						
						<div class="amfm-endpoints-list">
							<h3><?php echo esc_html__('Main Endpoint', 'amfm-maps'); ?></h3>
							<div class="amfm-endpoint-item">
								<code><?php echo esc_url(rest_url('amfm-maps/v1/locations')); ?></code>
								<button type="button" class="button" onclick="window.open('<?php echo rest_url('amfm-maps/v1/locations'); ?>', '_blank')">
									<?php echo esc_html__('Test', 'amfm-maps'); ?>
								</button>
							</div>
							
							<h3><?php echo esc_html__('Filtered Endpoints', 'amfm-maps'); ?></h3>
							<div id="filtered-endpoints-container">
								<div class="amfm-maps-loading">Loading filtered endpoints...</div>
							</div>
							
							<div class="amfm-endpoint-examples">
								<h4><?php echo esc_html__('Example URLs:', 'amfm-maps'); ?></h4>
								<ul>
									<li><code><?php echo esc_url(rest_url('amfm-maps/v1/amfm/ca')); ?></code> - AMFM locations in California</li>
									<li><code><?php echo esc_url(rest_url('amfm-maps/v1/mc/va')); ?></code> - MC locations in Virginia</li>
									<li><code><?php echo esc_url(rest_url('amfm-maps/v1/amfm/all')); ?></code> - All AMFM locations</li>
									<li><code><?php echo esc_url(rest_url('amfm-maps/v1/organizations')); ?></code> - List all organizations</li>
									<li><code><?php echo esc_url(rest_url('amfm-maps/v1/amfm/states')); ?></code> - States with AMFM locations</li>
								</ul>
							</div>
							
							<div class="amfm-cache-controls">
								<h4><?php echo esc_html__('Cache Management', 'amfm-maps'); ?></h4>
								<button type="button" class="button" id="clear-filtered-cache">
									<?php echo esc_html__('Clear Filtered Cache', 'amfm-maps'); ?>
								</button>
								<button type="button" class="button" id="debug-sync-status">
									<?php echo esc_html__('Debug Sync Status', 'amfm-maps'); ?>
								</button>
								<p class="description"><?php echo esc_html__('Clear cached filtered data or debug sync issues.', 'amfm-maps'); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<style>
			#amfm-locations-manager {
				margin-top: 20px;
			}
			.amfm-locations-toolbar {
				margin-bottom: 20px;
				padding: 15px;
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 3px;
			}
			.amfm-locations-toolbar button {
				margin-right: 10px;
			}
			.amfm-columns-manager,
			.amfm-preview-section,
			.amfm-api-info {
				background: #fff;
				padding: 20px;
				margin-bottom: 20px;
				border: 1px solid #ccd0d4;
				border-radius: 3px;
			}
			.sortable-columns {
				margin-top: 15px;
				max-height: 400px;
				overflow-y: auto;
				border: 1px solid #ddd;
				padding: 10px;
				background: #f9f9f9;
			}
			.column-item {
				padding: 10px;
				margin-bottom: 5px;
				background: #fff;
				border: 1px solid #ddd;
				cursor: move;
				display: flex;
				align-items: center;
				justify-content: space-between;
			}
			.column-item.ui-sortable-helper {
				opacity: 0.6;
			}
			.column-item input[type="checkbox"] {
				margin-right: 10px;
			}
			.column-item input[type="text"] {
				flex: 1;
				margin-left: 10px;
			}
			.column-item .dashicons {
				color: #666;
				cursor: pointer;
			}
			.column-item .dashicons-trash:hover {
				color: #dc3232;
			}
			#preview-container {
				margin-top: 15px;
				overflow-x: auto;
			}
			.amfm-api-info code {
				display: inline-block;
				padding: 8px 12px;
				background: #f3f4f5;
				margin: 10px 0;
			}
			.amfm-endpoints-grid {
				margin: 15px 0;
			}
			.amfm-org-section {
				background: #f9f9f9;
				padding: 15px;
				margin-bottom: 15px;
				border: 1px solid #ddd;
				border-radius: 3px;
			}
			.amfm-org-actions {
				margin: 10px 0;
			}
			.amfm-org-actions .button {
				margin-right: 10px;
			}
			.amfm-states-list {
				margin-top: 10px;
				padding: 10px;
				background: #fff;
				border: 1px solid #ddd;
			}
			.amfm-state-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 5px 0;
				border-bottom: 1px solid #eee;
			}
			.amfm-state-item:last-child {
				border-bottom: none;
			}
			.amfm-endpoint-item {
				display: flex;
				align-items: center;
				gap: 10px;
				margin: 10px 0;
			}
			.amfm-endpoint-examples ul {
				margin: 10px 0;
				padding-left: 20px;
			}
			.amfm-endpoint-examples li {
				margin: 5px 0;
			}
			.amfm-cache-controls {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid #ddd;
			}
			/* Modern notification styles */
			.amfm-notice {
				background: #fff;
				border-radius: 6px;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
				margin: 0 0 20px 0;
				animation: slideInDown 0.3s ease-out;
			}
			.amfm-notice-success {
				border-left: 4px solid #00a32a;
			}
			.amfm-notice-error {
				border-left: 4px solid #d63638;
			}
			.amfm-notice-content {
				display: flex;
				align-items: center;
				padding: 12px 16px;
			}
			.amfm-notice .dashicons {
				margin-right: 8px;
				font-size: 18px;
				width: 18px;
				height: 18px;
			}
			.amfm-notice-success .dashicons {
				color: #00a32a;
			}
			.amfm-notice-error .dashicons {
				color: #d63638;
			}
			.amfm-notice-message {
				flex: 1;
				font-weight: 500;
				color: #1d2327;
			}
			.amfm-notice-dismiss {
				background: none;
				border: none;
				font-size: 18px;
				font-weight: bold;
				color: #8c8f94;
				cursor: pointer;
				padding: 0;
				margin-left: 12px;
				width: 20px;
				height: 20px;
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 50%;
				transition: all 0.2s ease;
			}
			.amfm-notice-dismiss:hover {
				background: #f0f0f1;
				color: #1d2327;
			}
			@keyframes slideInDown {
				from {
					transform: translateY(-20px);
					opacity: 0;
				}
				to {
					transform: translateY(0);
					opacity: 1;
				}
			}
			/* Loading spinner */
			.amfm-loading-spinner {
				text-align: center;
				padding: 20px;
				color: #8c8f94;
			}
			.amfm-loading-spinner .dashicons {
				animation: spin 1s linear infinite;
				margin-right: 8px;
			}
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var currentConfig = null;
			
			// Modern notification system
			function showNotice(type, message, autoHide = true) {
				// Remove any existing notices
				$('.amfm-notice').remove();
				
				var noticeClass = 'amfm-notice amfm-notice-' + type;
				var iconClass = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
				
				var noticeHtml = '<div class="' + noticeClass + '">' +
					'<div class="amfm-notice-content">' +
						'<span class="dashicons ' + iconClass + '"></span>' +
						'<span class="amfm-notice-message">' + message + '</span>' +
						'<button type="button" class="amfm-notice-dismiss">&times;</button>' +
					'</div>' +
				'</div>';
				
				// Insert at the top of the locations manager container
				$('#amfm-locations-manager').prepend(noticeHtml);
				
				// Auto-hide after 5 seconds if success
				if (autoHide && type === 'success') {
					setTimeout(function() {
						$('.amfm-notice-success').fadeOut(400, function() {
							$(this).remove();
						});
					}, 5000);
				}
			}
			
			// Handle notice dismissal
			$(document).on('click', '.amfm-notice-dismiss', function() {
				$(this).closest('.amfm-notice').fadeOut(400, function() {
					$(this).remove();
				});
			});
			
			// Load initial data
			loadLocationsData();
			loadFilteredEndpoints();
			
			// Make columns sortable
			$('#columns-list').sortable({
				placeholder: 'ui-state-highlight',
				update: function() {
					updatePreview();
				}
			});
			
			// Load locations data and configuration
			function loadLocationsData() {
				$.post(ajaxurl, {
					action: 'amfm_locations_get_data',
					nonce: '<?php echo wp_create_nonce('amfm_locations_nonce'); ?>'
				}, function(response) {
					if (response.success) {
						currentConfig = response.data.config;
						renderColumns(response.data.columns);
						updatePreview();
					} else {
						showNotice('error', 'Failed to load locations data', false);
					}
				});
			}
			
			// Render columns list
			function renderColumns(columns) {
				var html = '';
				columns.forEach(function(col) {
					var checked = col.visible ? 'checked' : '';
					var isCustom = col.custom ? 'data-custom="true"' : '';
					html += '<div class="column-item" data-key="' + col.key + '" ' + isCustom + '>';
					html += '<span class="dashicons dashicons-menu"></span>';
					html += '<input type="checkbox" ' + checked + ' />';
					html += '<strong>' + col.key + '</strong>';
					html += '<input type="text" value="' + col.label + '" placeholder="Display name" />';
					if (col.custom) {
						html += '<span class="dashicons dashicons-trash" title="Remove column"></span>';
					}
					html += '</div>';
				});
				$('#columns-list').html(html);
			}
			
			// Update preview
			function updatePreview() {
				var columns = getColumnConfig();
				var visibleColumns = columns.filter(function(col) {
					return col.visible;
				});
				
				// Update preview headers
				var headerHtml = '<tr>';
				visibleColumns.forEach(function(col) {
					headerHtml += '<th>' + col.label + '</th>';
				});
				headerHtml += '</tr>';
				$('#preview-header').html(headerHtml);
				
				// Load preview data
				$.post(ajaxurl, {
					action: 'amfm_locations_get_data',
					nonce: '<?php echo wp_create_nonce('amfm_locations_nonce'); ?>',
					preview: true,
					columns: columns
				}, function(response) {
					if (response.success && response.data.preview) {
						var bodyHtml = '';
						response.data.preview.forEach(function(row) {
							bodyHtml += '<tr>';
							visibleColumns.forEach(function(col) {
								var value = row[col.key] || '';
								if (typeof value === 'object') {
									value = JSON.stringify(value);
								}
								bodyHtml += '<td>' + value + '</td>';
							});
							bodyHtml += '</tr>';
						});
						$('#preview-data').html(bodyHtml);
					}
				});
			}
			
			// Get current column configuration
			function getColumnConfig() {
				var columns = [];
				$('#columns-list .column-item').each(function() {
					var $item = $(this);
					columns.push({
						key: $item.data('key'),
						label: $item.find('input[type="text"]').val(),
						visible: $item.find('input[type="checkbox"]').is(':checked'),
						custom: $item.data('custom') === true
					});
				});
				return columns;
			}
			
			// Handle checkbox changes
			$(document).on('change', '.column-item input[type="checkbox"]', function() {
				updatePreview();
			});
			
			// Handle label changes
			$(document).on('blur', '.column-item input[type="text"]', function() {
				updatePreview();
			});
			
			// Save configuration
			$('#save-locations-config').click(function() {
				var $button = $(this);
				$button.prop('disabled', true);
				
				var config = {
					columns: getColumnConfig()
				};
				
				$.post(ajaxurl, {
					action: 'amfm_locations_save_config',
					nonce: '<?php echo wp_create_nonce('amfm_locations_nonce'); ?>',
					config: JSON.stringify(config)
				}, function(response) {
					$button.prop('disabled', false);
					if (response.success) {
						showNotice('success', 'Configuration saved successfully!');
					} else {
						showNotice('error', 'Failed to save configuration: ' + response.data.message);
					}
				});
			});
			
			// Reset configuration
			$('#reset-locations-config').click(function() {
				if (confirm('Are you sure you want to reset to default configuration?')) {
					$.post(ajaxurl, {
						action: 'amfm_locations_reset_config',
						nonce: '<?php echo wp_create_nonce('amfm_locations_nonce'); ?>'
					}, function(response) {
						if (response.success) {
							showNotice('success', 'Configuration reset successfully!');
							loadLocationsData();
						} else {
							showNotice('error', 'Failed to reset configuration');
						}
					});
				}
			});
			
			// Add custom column
			$('#add-custom-column').click(function() {
				var columnName = prompt('Enter column name:');
				if (columnName) {
					var html = '<div class="column-item" data-key="' + columnName + '" data-custom="true">';
					html += '<span class="dashicons dashicons-menu"></span>';
					html += '<input type="checkbox" checked />';
					html += '<strong>' + columnName + '</strong>';
					html += '<input type="text" value="' + columnName + '" placeholder="Display name" />';
					html += '<span class="dashicons dashicons-trash" title="Remove column"></span>';
					html += '</div>';
					$('#columns-list').append(html);
					updatePreview();
				}
			});
			
			// Remove custom column
			$(document).on('click', '.column-item .dashicons-trash', function() {
				if (confirm('Remove this column?')) {
					$(this).closest('.column-item').remove();
					updatePreview();
				}
			});
			
			// Clear filtered cache
			$('#clear-filtered-cache').click(function() {
				var $button = $(this);
				$button.prop('disabled', true).text('Clearing...');
				
				$.post(ajaxurl, {
					action: 'amfm_clear_filtered_cache',
					nonce: '<?php echo wp_create_nonce('amfm_locations_nonce'); ?>'
				}, function(response) {
					$button.prop('disabled', false).text('<?php echo esc_js(__('Clear Filtered Cache', 'amfm-maps')); ?>');
					if (response.success) {
						showNotice('success', 'Cache cleared successfully!');
						loadFilteredEndpoints(); // Reload endpoints
					} else {
						showNotice('error', 'Failed to clear cache: ' + response.data.message);
					}
				});
			});
			
			// Debug sync status
			$('#debug-sync-status').click(function() {
				var debugUrl = '<?php echo plugins_url('debug-sync.php', __FILE__); ?>';
				window.open(debugUrl, '_blank');
			});
			
			// Load filtered endpoints data
			function loadFilteredEndpoints() {
				$('#filtered-endpoints-container').html('<div class="amfm-loading-spinner"><span class="dashicons dashicons-update-alt"></span> Loading filtered endpoints...</div>');
				
				$.get('<?php echo rest_url('amfm-maps/v1/organizations'); ?>')
					.done(function(orgs) {
						var html = '';
						if (orgs && orgs.length > 0) {
							html += '<div class="amfm-endpoints-grid">';
							
							orgs.forEach(function(org) {
								html += '<div class="amfm-org-section">';
								html += '<h4>' + org.organization.toUpperCase() + ' (' + org.count + ' locations)</h4>';
								html += '<div class="amfm-org-actions">';
								html += '<button class="button" onclick="window.open(\'' + org.endpoint + '\', \'_blank\')">';
								html += 'View All ' + org.organization.toUpperCase() + ' Locations</button>';
								html += '<button class="button load-states" data-org="' + org.organization + '">View States</button>';
								html += '</div>';
								html += '<div class="amfm-states-container" id="states-' + org.organization + '" style="display:none;"></div>';
								html += '</div>';
							});
							
							html += '</div>';
						} else {
							html = '<div class="notice notice-warning"><p>No organizations found. Please sync your data first.</p></div>';
						}
						
						$('#filtered-endpoints-container').html(html);
					})
					.fail(function() {
						$('#filtered-endpoints-container').html('<div class="amfm-notice amfm-notice-error"><div class="amfm-notice-content"><span class="dashicons dashicons-warning"></span><span class="amfm-notice-message">Failed to load endpoint information. Please check if data has been synced.</span></div></div>');
					});
			}
			
			// Load states for organization
			$(document).on('click', '.load-states', function() {
				var $button = $(this);
				var org = $button.data('org');
				var $container = $('#states-' + org);
				
				if ($container.is(':visible')) {
					$container.hide();
					$button.text('View States');
					return;
				}
				
				$button.prop('disabled', true).text('Loading...');
				
				$.get('<?php echo rest_url('amfm-maps/v1/'); ?>' + org + '/states')
					.done(function(states) {
						var html = '<div class="amfm-states-list">';
						
						if (states && states.length > 0) {
							states.forEach(function(state) {
								html += '<div class="amfm-state-item">';
								html += '<strong>' + state.state + '</strong> (' + state.count + ' locations)';
								html += '<button class="button button-small" onclick="window.open(\'' + state.endpoint + '\', \'_blank\')">View JSON</button>';
								html += '</div>';
							});
						} else {
							html += '<p>No states found for this organization.</p>';
						}
						
						html += '</div>';
						$container.html(html).show();
						$button.prop('disabled', false).text('Hide States');
					})
					.fail(function() {
						$container.html('<div class="notice notice-error"><p>Failed to load states.</p></div>').show();
						$button.prop('disabled', false).text('View States');
					});
			});
		});
		</script>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to get locations data
	 */
	public function ajax_get_locations_data()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'amfm_locations_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		// Get original JSON data
		$original_data = get_option('amfm_maps_json_data', array());
		
		if (empty($original_data)) {
			// Try to load from Amfm_Maps_Admin
			$original_data = Amfm_Maps_Admin::get_json_data();
		}
		
		// Get saved configuration
		$config = $this->get_locations_config();
		
		// If preview requested with specific columns
		$preview_data = null;
		if (!empty($_POST['preview']) && !empty($_POST['columns'])) {
			$columns = json_decode(stripslashes($_POST['columns']), true);
			$preview_data = $this->transform_data($original_data, $columns, 5);
		} elseif (!empty($_POST['preview'])) {
			$preview_data = $this->transform_data($original_data, $config['columns'], 5);
		}
		
		// Get all available columns from the data
		$all_columns = $this->extract_columns($original_data);
		
		// Merge with configured columns
		$columns = $this->merge_columns_config($all_columns, $config['columns']);
		
		wp_send_json_success(array(
			'columns' => $columns,
			'config' => $config,
			'preview' => $preview_data
		));
	}

	/**
	 * Extract all columns from data
	 */
	private function extract_columns($data)
	{
		$columns = array();
		
		if (!empty($data) && is_array($data)) {
			// Get all unique keys from first few records
			$sample_size = min(10, count($data));
			for ($i = 0; $i < $sample_size; $i++) {
				if (isset($data[$i]) && is_array($data[$i])) {
					foreach (array_keys($data[$i]) as $key) {
						if (!in_array($key, $columns)) {
							$columns[] = $key;
						}
					}
				}
			}
		}
		
		return $columns;
	}

	/**
	 * Merge columns with configuration
	 */
	private function merge_columns_config($all_columns, $config_columns)
	{
		$result = array();
		$processed_keys = array();
		
		// First, add configured columns in order
		foreach ($config_columns as $col) {
			$result[] = array(
				'key' => $col['key'],
				'label' => $col['label'],
				'visible' => $col['visible'],
				'custom' => !empty($col['custom'])
			);
			$processed_keys[] = $col['key'];
		}
		
		// Then add any new columns from data that aren't configured yet
		foreach ($all_columns as $key) {
			if (!in_array($key, $processed_keys)) {
				$result[] = array(
					'key' => $key,
					'label' => $this->humanize_key($key),
					'visible' => false,
					'custom' => false
				);
			}
		}
		
		return $result;
	}

	/**
	 * Convert key to human-readable label
	 */
	private function humanize_key($key)
	{
		// Remove parentheses content
		$label = preg_replace('/\([^)]*\)/', '', $key);
		// Replace underscores and hyphens with spaces
		$label = str_replace(array('_', '-'), ' ', $label);
		// Trim and capitalize
		$label = ucwords(trim($label));
		return $label;
	}

	/**
	 * Get locations configuration
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
	 * AJAX handler to save locations configuration
	 */
	public function ajax_save_locations_config()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'amfm_locations_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		if (!isset($_POST['config'])) {
			wp_send_json_error(array('message' => __('Invalid configuration data', 'amfm-maps')));
			return;
		}
		
		$config = json_decode(stripslashes($_POST['config']), true);
		
		if (!is_array($config)) {
			wp_send_json_error(array('message' => __('Invalid configuration format', 'amfm-maps')));
			return;
		}
		
		// Sanitize configuration
		$sanitized_config = array(
			'columns' => array()
		);
		
		if (!empty($config['columns']) && is_array($config['columns'])) {
			foreach ($config['columns'] as $col) {
				$sanitized_config['columns'][] = array(
					'key' => sanitize_text_field($col['key']),
					'label' => sanitize_text_field($col['label']),
					'visible' => !empty($col['visible']),
					'custom' => !empty($col['custom'])
				);
			}
		}
		
		// Save configuration
		update_option('amfm_locations_config', $sanitized_config);
		
		// Transform and save the data
		$this->update_transformed_data();
		
		wp_send_json_success(array('message' => __('Configuration saved successfully', 'amfm-maps')));
	}

	/**
	 * AJAX handler to reset locations configuration
	 */
	public function ajax_reset_locations_config()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'amfm_locations_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		// Delete the configuration option
		delete_option('amfm_locations_config');
		delete_option('amfm_locations_transformed_data');
		
		wp_send_json_success(array('message' => __('Configuration reset successfully', 'amfm-maps')));
	}

	/**
	 * Transform data based on configuration
	 */
	private function transform_data($data, $columns, $limit = null)
	{
		if (empty($data) || !is_array($data)) {
			return array();
		}
		
		$transformed = array();
		$visible_columns = array_filter($columns, function($col) {
			return !empty($col['visible']);
		});
		
		$count = 0;
		foreach ($data as $row) {
			if ($limit !== null && $count >= $limit) {
				break;
			}
			
			$new_row = array();
			foreach ($visible_columns as $col) {
				$key = $col['key'];
				$label = !empty($col['label']) ? $col['label'] : $key;
				
				if (isset($row[$key])) {
					$new_row[$key] = $row[$key];
				} elseif (!empty($col['custom'])) {
					// Custom columns might have computed values
					$new_row[$key] = $this->compute_custom_value($row, $key);
				} else {
					$new_row[$key] = '';
				}
			}
			
			$transformed[] = $new_row;
			$count++;
		}
		
		return $transformed;
	}

	/**
	 * Compute custom column value
	 */
	private function compute_custom_value($row, $key)
	{
		// This can be extended to compute custom values based on other fields
		// For now, return empty string for custom fields
		return '';
	}

	/**
	 * Update transformed data in database
	 */
	private function update_transformed_data()
	{
		$original_data = get_option('amfm_maps_json_data', array());
		
		if (empty($original_data)) {
			$original_data = Amfm_Maps_Admin::get_json_data();
		}
		
		$config = $this->get_locations_config();
		$transformed_data = $this->transform_data($original_data, $config['columns']);
		
		update_option('amfm_locations_transformed_data', $transformed_data);
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes()
	{
		register_rest_route('amfm-maps/v1', '/locations', array(
			'methods' => 'GET',
			'callback' => array($this, 'rest_get_locations'),
			'permission_callback' => '__return_true',
			'args' => array(
				'_wpnonce' => array(
					'required' => false,
				),
				'per_page' => array(
					'type' => 'integer',
					'default' => 100,
					'minimum' => 1,
					'maximum' => 500,
				),
				'page' => array(
					'type' => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'region' => array(
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'state' => array(
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		));
		
		// Add direct CORS support - more aggressive approach
		add_action('init', array($this, 'handle_cors_headers'));
	}

	/**
	 * AJAX handler to clear filtered cache
	 */
	public function ajax_clear_filtered_cache()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'amfm_locations_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		// Clear filtered cache using the filtered endpoints class
		global $amfm_maps_filtered_endpoints;
		if ($amfm_maps_filtered_endpoints && method_exists($amfm_maps_filtered_endpoints, 'clear_filtered_cache')) {
			$amfm_maps_filtered_endpoints->clear_filtered_cache();
			wp_send_json_success(array('message' => __('Filtered cache cleared successfully', 'amfm-maps')));
		} else {
			wp_send_json_error(array('message' => __('Filtered endpoints not available', 'amfm-maps')));
		}
	}

	/**
	 * AJAX handler to debug data status
	 */
	public function ajax_debug_data_status()
	{
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'amfm_locations_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed', 'amfm-maps')));
			return;
		}
		
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions', 'amfm-maps')));
			return;
		}
		
		$debug_info = array(
			'json_data_exists' => !empty(get_option('amfm_maps_json_data', null)),
			'transformed_data_exists' => !empty(get_option('amfm_locations_transformed_data', null)),
			'sync_status' => get_option('amfm_maps_sync_status', 'unknown'),
			'last_sync' => get_option('amfm_maps_last_sync', ''),
			'json_url' => get_option('amfm_maps_json_url', ''),
			'json_data_count' => 0,
			'sample_data_available' => file_exists(plugin_dir_path(__FILE__) . '../sample_data.json')
		);
		
		// Count items in JSON data
		$json_data = get_option('amfm_maps_json_data', null);
		if (is_array($json_data)) {
			$debug_info['json_data_count'] = count($json_data);
		}
		
		wp_send_json_success($debug_info);
	}

	/**
	 * REST API callback to get locations
	 */
	public function rest_get_locations($request)
	{
		// Send CORS headers immediately
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
		
		// Get transformed data if available, otherwise use original
		$data = get_option('amfm_locations_transformed_data', null);
		
		if ($data === null) {
			// Fallback to original data with default transformation
			$original_data = get_option('amfm_maps_json_data', array());
			if (empty($original_data)) {
				$original_data = Amfm_Maps_Admin::get_json_data();
			}
			
			$config = $this->get_locations_config();
			$data = $this->transform_data($original_data, $config['columns']);
		}
		
		// Apply filters if provided
		if ($request->get_param('region')) {
			$region = $request->get_param('region');
			$data = array_filter($data, function($item) use ($region) {
				return isset($item['Region']) && $item['Region'] === $region;
			});
		}
		
		if ($request->get_param('state')) {
			$state = $request->get_param('state');
			$data = array_filter($data, function($item) use ($state) {
				return isset($item['State']) && $item['State'] === $state;
			});
		}
		
		// Re-index array
		$data = array_values($data);
		
		// Pagination
		$per_page = $request->get_param('per_page');
		$page = $request->get_param('page');
		$total = count($data);
		$total_pages = ceil($total / $per_page);
		
		$start = ($page - 1) * $per_page;
		$data = array_slice($data, $start, $per_page);
		
		$response = new WP_REST_Response($data);
		$response->header('X-WP-Total', $total);
		$response->header('X-WP-TotalPages', $total_pages);
		// Also add CORS headers to response object
		$response->header('Access-Control-Allow-Origin', '*');
		$response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
		$response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
		
		return $response;
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

	/**
	 * Disable authentication for our REST API endpoints
	 */
	public function disable_auth_for_endpoints($result)
	{
		// Check if this is a REST API request to our endpoints
		if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/amfm-maps/v1/') !== false) {
			return true; // Allow access without authentication
		}
		
		return $result;
	}
}