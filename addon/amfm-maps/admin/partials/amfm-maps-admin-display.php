<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://adzbyte.com/
 * @since      1.0.0
 *
 * @package    Amfm_Maps
 * @subpackage Amfm_Maps/admin/partials
 */

// Get current settings
$json_url = get_option('amfm_maps_json_url', '');
$global_shortname_filter = get_option('amfm_maps_global_shortname_filter', 'all');
$sync_interval = get_option('amfm_maps_sync_interval', 'none');
$last_sync = get_option('amfm_maps_last_sync', '');
$sync_status = get_option('amfm_maps_sync_status', '');

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['amfm_maps_nonce'], 'amfm_maps_save_settings')) {
    $json_url = sanitize_url($_POST['json_url']);
    $global_shortname_filter = sanitize_text_field($_POST['global_shortname_filter']);
    $sync_interval = sanitize_text_field($_POST['sync_interval']);
    
    update_option('amfm_maps_json_url', $json_url);
    update_option('amfm_maps_global_shortname_filter', $global_shortname_filter);
    update_option('amfm_maps_sync_interval', $sync_interval);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'amfm-maps') . '</p></div>';
}

// Handle manual sync
if (isset($_POST['manual_sync']) && wp_verify_nonce($_POST['amfm_maps_sync_nonce'], 'amfm_maps_manual_sync')) {
    // Create an instance of the admin class to call the sync method
    $version = defined('AMFM_MAPS_VERSION') ? AMFM_MAPS_VERSION : '1.0.0';
    $admin_instance = new Amfm_Maps_Admin('amfm-maps', $version);
    $result = $admin_instance->manual_sync_data();
    
    if ($result['success']) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Data synced successfully!', 'amfm-maps') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(__('Sync failed: %s', 'amfm-maps'), $result['message']) . '</p></div>';
    }
}

/**
 * Simple function to copy JSON to clipboard (added via JavaScript)
 */

?>

<div class="wrap amfm-maps-wrap">
    <div class="amfm-maps-header">
        <h1 class="amfm-maps-title">
            <?php echo esc_html(get_admin_page_title()); ?>
        </h1>
        <p class="amfm-maps-subtitle"><?php _e('Configure your Maps data source and synchronization settings', 'amfm-maps'); ?></p>
    </div>
    
    <!-- Tab Navigation -->
    <div class="amfm-maps-tabs">
        <div class="amfm-maps-tab-nav">
            <button class="amfm-maps-tab-button active" data-tab="configuration">
                <i class="dashicons dashicons-admin-settings"></i>
                <?php _e('Configuration', 'amfm-maps'); ?>
            </button>
            <button class="amfm-maps-tab-button" data-tab="data-view">
                <i class="dashicons dashicons-database"></i>
                <?php _e('Data View', 'amfm-maps'); ?>
            </button>
            <button class="amfm-maps-tab-button" data-tab="locations-manager">
                <i class="dashicons dashicons-admin-tools"></i>
                <?php _e('Locations Manager', 'amfm-maps'); ?>
            </button>
        </div>
    </div>
    
    <!-- Tab Content -->
    <div class="amfm-maps-tab-content">
        <!-- Configuration Tab -->
        <div id="tab-configuration" class="amfm-maps-tab-pane active">
            <div class="amfm-maps-container">
        <div class="amfm-maps-main">
            <!-- Data Source Configuration -->
            <div class="amfm-maps-panel">
                <div class="amfm-maps-panel-header">
                    <h2 class="amfm-maps-panel-title">
                        <i class="dashicons dashicons-admin-links"></i>
                        <?php _e('Data Source', 'amfm-maps'); ?>
                    </h2>
                    <p class="amfm-maps-panel-description"><?php _e('Configure the JSON data source for your maps', 'amfm-maps'); ?></p>
                </div>
                
                <div class="amfm-maps-panel-body">
                    <form method="post" action="">
                        <?php wp_nonce_field('amfm_maps_save_settings', 'amfm_maps_nonce'); ?>
                        
                        <div class="amfm-maps-form-group">
                            <label for="json_url" class="amfm-maps-label">
                                <?php _e('JSON Data URL', 'amfm-maps'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="url" 
                                id="json_url" 
                                name="json_url" 
                                value="<?php echo esc_url($json_url); ?>" 
                                class="amfm-maps-input amfm-maps-input-large"
                                placeholder="https://example.com/api/maps-data.json"
                                required
                            />
                            <p class="amfm-maps-help-text">
                                <?php _e('Enter the URL that returns JSON data for your maps. This data will be fetched and cached locally.', 'amfm-maps'); ?>
                            </p>
                        </div>
                        
                        <div class="amfm-maps-form-group">
                            <label for="global_shortname_filter" class="amfm-maps-label">
                                <?php _e('Global Location Filter', 'amfm-maps'); ?>
                            </label>
                            <select id="global_shortname_filter" name="global_shortname_filter" class="amfm-maps-select">
                                <option value="all" <?php selected($global_shortname_filter, 'all'); ?>><?php _e('All', 'amfm-maps'); ?></option>
                                <option value="amfm" <?php selected($global_shortname_filter, 'amfm'); ?>><?php _e('AMFM', 'amfm-maps'); ?></option>
                                <option value="mp" <?php selected($global_shortname_filter, 'mp'); ?>><?php _e('MP', 'amfm-maps'); ?></option>
                                <option value="mc" <?php selected($global_shortname_filter, 'mc'); ?>><?php _e('MC', 'amfm-maps'); ?></option>
                            </select>
                            <p class="amfm-maps-help-text">
                                <?php _e('Filter all map locations globally by organization type.', 'amfm-maps'); ?>
                            </p>
                        </div>
                        
                        <div class="amfm-maps-form-group">
                            <label for="sync_interval" class="amfm-maps-label">
                                <?php _e('Sync Interval', 'amfm-maps'); ?>
                            </label>
                            <select id="sync_interval" name="sync_interval" class="amfm-maps-select">
                                <option value="none" <?php selected($sync_interval, 'none'); ?>><?php _e('None (Manual only)', 'amfm-maps'); ?></option>
                                <option value="daily" <?php selected($sync_interval, 'daily'); ?>><?php _e('Daily', 'amfm-maps'); ?></option>
                                <option value="weekly" <?php selected($sync_interval, 'weekly'); ?>><?php _e('Weekly', 'amfm-maps'); ?></option>
                                <option value="monthly" <?php selected($sync_interval, 'monthly'); ?>><?php _e('Monthly', 'amfm-maps'); ?></option>
                            </select>
                            <p class="amfm-maps-help-text">
                                <?php _e('Choose how often the data should be automatically synchronized from the source URL.', 'amfm-maps'); ?>
                            </p>
                        </div>
                        
                        <div class="amfm-maps-form-actions">
                            <button type="submit" name="submit" class="amfm-maps-button amfm-maps-button-primary">
                                <i class="dashicons dashicons-yes"></i>
                                <?php _e('Save Settings', 'amfm-maps'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Sync Status & Manual Sync -->
            <div class="amfm-maps-panel">
                <div class="amfm-maps-panel-header">
                    <h2 class="amfm-maps-panel-title">
                        <i class="dashicons dashicons-update"></i>
                        <?php _e('Data Synchronization', 'amfm-maps'); ?>
                    </h2>
                    <p class="amfm-maps-panel-description"><?php _e('Monitor and control data synchronization', 'amfm-maps'); ?></p>
                </div>
                
                <div class="amfm-maps-panel-body">
                    <div class="amfm-maps-sync-info">
                        <div class="amfm-maps-sync-stat">
                            <div class="amfm-maps-sync-stat-label"><?php _e('Last Sync', 'amfm-maps'); ?></div>
                            <div class="amfm-maps-sync-stat-value">
                                <?php 
                                if (!empty($last_sync)) {
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync)));
                                } else {
                                    echo '<span class="amfm-maps-muted">' . __('Never', 'amfm-maps') . '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="amfm-maps-sync-stat">
                            <div class="amfm-maps-sync-stat-label"><?php _e('Status', 'amfm-maps'); ?></div>
                            <div class="amfm-maps-sync-stat-value">
                                <?php
                                $status_class = '';
                                $status_text = __('Unknown', 'amfm-maps');
                                
                                switch ($sync_status) {
                                    case 'success':
                                        $status_class = 'success';
                                        $status_text = __('Success', 'amfm-maps');
                                        break;
                                    case 'error':
                                        $status_class = 'error';
                                        $status_text = __('Error', 'amfm-maps');
                                        break;
                                    default:
                                        $status_class = 'muted';
                                        $status_text = __('Not synced', 'amfm-maps');
                                        break;
                                }
                                ?>
                                <span class="amfm-maps-status amfm-maps-status-<?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="amfm-maps-sync-stat">
                            <div class="amfm-maps-sync-stat-label"><?php _e('Next Sync', 'amfm-maps'); ?></div>
                            <div class="amfm-maps-sync-stat-value">
                                <?php
                                if ($sync_interval === 'none') {
                                    echo '<span class="amfm-maps-muted">' . __('Manual only', 'amfm-maps') . '</span>';
                                } else {
                                    echo '<span class="amfm-maps-muted">' . sprintf(__('Based on %s schedule', 'amfm-maps'), $sync_interval) . '</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" action="" class="amfm-maps-sync-form">
                        <?php wp_nonce_field('amfm_maps_manual_sync', 'amfm_maps_sync_nonce'); ?>
                        <button type="submit" name="manual_sync" class="amfm-maps-button amfm-maps-button-secondary" <?php echo empty($json_url) ? 'disabled' : ''; ?>>
                            <i class="dashicons dashicons-update"></i>
                            <?php _e('Sync Now', 'amfm-maps'); ?>
                        </button>
                        <?php if (empty($json_url)): ?>
                            <p class="amfm-maps-help-text amfm-maps-warning">
                                <?php _e('Please configure a JSON URL first before syncing.', 'amfm-maps'); ?>
                            </p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Filter Configuration -->
            <div class="amfm-maps-panel" id="filter-configuration">
                <div class="amfm-maps-panel-header">
                    <h2 class="amfm-maps-panel-title">
                        <i class="dashicons dashicons-filter"></i>
                        <?php _e('Filter Configuration', 'amfm-maps'); ?>
                    </h2>
                    <p class="amfm-maps-panel-description"><?php _e('Configure which filter types are available and their settings', 'amfm-maps'); ?></p>
                </div>
                
                <div class="amfm-maps-panel-body">
                    <div id="amfm-filter-config">
                        <div class="amfm-maps-loading" id="filter-loading">
                            <i class="dashicons dashicons-update-alt"></i>
                            <?php _e('Loading filter data...', 'amfm-maps'); ?>
                        </div>
                        
                        <div id="filter-config-content" style="display: none;">
                            <div class="amfm-maps-filter-notice">
                                <p><?php _e('Configure filter types based on your JSON data. These settings will be used by the filter widgets.', 'amfm-maps'); ?></p>
                            </div>
                            
                            <div id="filter-types-container">
                                <!-- Filter types will be loaded here via AJAX -->
                            </div>
                            
                            <div class="amfm-maps-form-actions">
                                <button type="button" id="save-filter-config" class="amfm-maps-button amfm-maps-button-primary">
                                    <i class="dashicons dashicons-yes"></i>
                                    <?php _e('Save Filter Configuration', 'amfm-maps'); ?>
                                </button>
                                <button type="button" id="refresh-filter-data" class="amfm-maps-button amfm-maps-button-secondary">
                                    <i class="dashicons dashicons-update"></i>
                                    <?php _e('Refresh Data', 'amfm-maps'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="filter-no-data" style="display: none;">
                            <div class="amfm-maps-notice amfm-maps-notice-warning">
                                <p><?php _e('No JSON data available. Please sync your data first to configure filters.', 'amfm-maps'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar with additional info -->
        <div class="amfm-maps-sidebar">
            <div class="amfm-maps-panel">
                <div class="amfm-maps-panel-header">
                    <h3 class="amfm-maps-panel-title">
                        <i class="dashicons dashicons-info"></i>
                        <?php _e('About JSON Data', 'amfm-maps'); ?>
                    </h3>
                </div>
                <div class="amfm-maps-panel-body">
                    <p class="amfm-maps-small-text">
                        <?php _e('The JSON data should contain map locations, markers, or other geographic data that will be used by your maps.', 'amfm-maps'); ?>
                    </p>
                    <p class="amfm-maps-small-text">
                        <?php _e('Automatic synchronization helps keep your maps updated with the latest data from your source.', 'amfm-maps'); ?>
                    </p>
                </div>
            </div>
            
            <div class="amfm-maps-panel">
                <div class="amfm-maps-panel-header">
                    <h3 class="amfm-maps-panel-title">
                        <i class="dashicons dashicons-performance"></i>
                        <?php _e('Performance Tips', 'amfm-maps'); ?>
                    </h3>
                </div>
                <div class="amfm-maps-panel-body">
                    <ul class="amfm-maps-tips-list">
                        <li><?php _e('Use HTTPS URLs for better security', 'amfm-maps'); ?></li>
                        <li><?php _e('Smaller JSON files load faster', 'amfm-maps'); ?></li>
                        <li><?php _e('Daily sync is recommended for frequently updated data', 'amfm-maps'); ?></li>
                        <li><?php _e('Weekly or monthly sync for static data', 'amfm-maps'); ?></li>
                    </ul>
                </div>
            </div>
            
            </div>
        </div>
        </div>
        
        <!-- Data View Tab -->
        <div id="tab-data-view" class="amfm-maps-tab-pane">
            <div class="amfm-maps-container">
                <div class="amfm-maps-main amfm-maps-full-width">
                    <?php
                    // Get stored JSON data
                    $json_data = get_option('amfm_maps_json_data', null);
                    $last_sync = get_option('amfm_maps_last_sync', '');
                    $sync_status = get_option('amfm_maps_sync_status', '');
                    ?>
                    
                    <!-- Debug Information Panel -->
                    <div class="amfm-maps-panel">
                        <div class="amfm-maps-panel-header">
                            <h3 class="amfm-maps-panel-title">
                                <i class="dashicons dashicons-admin-tools"></i>
                                <?php _e('Debug Information', 'amfm-maps'); ?>
                            </h3>
                            <p class="amfm-maps-panel-description"><?php _e('Technical details about the stored JSON data', 'amfm-maps'); ?></p>
                        </div>
                        <div class="amfm-maps-panel-body">
                            <?php
                            // Check if JSON data exists
                            $debug_json_data = get_option('amfm_maps_json_data', null);
                            $has_data = !empty($debug_json_data);
                            $data_type = gettype($debug_json_data);
                            $data_size = 0;
                            $data_count = 0;
                            
                            if ($has_data) {
                                if (is_array($debug_json_data) || is_object($debug_json_data)) {
                                    $data_count = is_array($debug_json_data) ? count($debug_json_data) : count((array)$debug_json_data);
                                }
                                $data_size = strlen(serialize($debug_json_data));
                            }
                            ?>
                            
                            <div class="amfm-maps-debug-item">
                                <strong><?php _e('JSON Data Status:', 'amfm-maps'); ?></strong>
                                <span class="amfm-maps-status amfm-maps-status-<?php echo $has_data ? 'success' : 'error'; ?>">
                                    <?php echo $has_data ? __('Present', 'amfm-maps') : __('Not Found', 'amfm-maps'); ?>
                                </span>
                            </div>
                            
                            <div class="amfm-maps-debug-item">
                                <strong><?php _e('Data Type:', 'amfm-maps'); ?></strong>
                                <code><?php echo esc_html($data_type); ?></code>
                            </div>
                            
                            <?php if ($has_data): ?>
                                <div class="amfm-maps-debug-item">
                                    <strong><?php _e('Element Count:', 'amfm-maps'); ?></strong>
                                    <code><?php echo esc_html($data_count); ?></code>
                                </div>
                                
                                <div class="amfm-maps-debug-item">
                                    <strong><?php _e('Serialized Size:', 'amfm-maps'); ?></strong>
                                    <code><?php echo esc_html(number_format($data_size)); ?> bytes</code>
                                </div>
                                
                                <div class="amfm-maps-debug-item">
                                    <strong><?php _e('Option Key:', 'amfm-maps'); ?></strong>
                                    <code>amfm_maps_json_data</code>
                                </div>
                            <?php else: ?>
                                <div class="amfm-maps-debug-item">
                                    <span class="amfm-maps-warning">
                                        <?php _e('No JSON data found. Try syncing data first.', 'amfm-maps'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Raw debug output for troubleshooting -->
                            <details class="amfm-maps-debug-details">
                                <summary><?php _e('Raw Debug Output', 'amfm-maps'); ?></summary>
                                <pre class="amfm-maps-debug-raw"><?php 
                                    echo "Option exists: " . (get_option('amfm_maps_json_data', false) !== false ? 'YES' : 'NO') . "\n";
                                    echo "Is empty: " . (empty($debug_json_data) ? 'YES' : 'NO') . "\n";
                                    echo "Is null: " . (is_null($debug_json_data) ? 'YES' : 'NO') . "\n";
                                    echo "Type: " . $data_type . "\n";
                                    if ($has_data && $data_count > 0) {
                                        echo "Sample: " . esc_html(substr(json_encode($debug_json_data), 0, 100)) . "...\n";
                                    }
                                ?></pre>
                            </details>
                        </div>
                    </div>
                    
                    <!-- Data Overview Panel -->
                    <div class="amfm-maps-panel">
                        <div class="amfm-maps-panel-header">
                            <h2 class="amfm-maps-panel-title">
                                <i class="dashicons dashicons-database"></i>
                                <?php _e('Stored JSON Data', 'amfm-maps'); ?>
                            </h2>
                            <p class="amfm-maps-panel-description">
                                <?php 
                                if (!empty($json_data)) {
                                    printf(__('Data structure and contents from your JSON source. Last updated: %s', 'amfm-maps'), 
                                           $last_sync ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_sync)) : __('Never', 'amfm-maps'));
                                } else {
                                    _e('No data available. Please configure and sync your JSON data source first.', 'amfm-maps');
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div class="amfm-maps-panel-body">
                            <?php if (!empty($json_data)): ?>
                                <!-- Data Statistics -->
                                <div class="amfm-maps-data-stats">
                                    <div class="amfm-maps-stat-item">
                                        <div class="amfm-maps-stat-number"><?php echo is_array($json_data) ? count($json_data) : 1; ?></div>
                                        <div class="amfm-maps-stat-label"><?php _e('Items', 'amfm-maps'); ?></div>
                                    </div>
                                    <div class="amfm-maps-stat-item">
                                        <div class="amfm-maps-stat-number"><?php echo strlen(json_encode($json_data)); ?></div>
                                        <div class="amfm-maps-stat-label"><?php _e('Data Size (bytes)', 'amfm-maps'); ?></div>
                                    </div>
                                    <div class="amfm-maps-stat-item">
                                        <div class="amfm-maps-stat-number"><?php echo gettype($json_data); ?></div>
                                        <div class="amfm-maps-stat-label"><?php _e('Data Type', 'amfm-maps'); ?></div>
                                    </div>
                                    <div class="amfm-maps-stat-item">
                                        <div class="amfm-maps-stat-number"><?php echo $last_sync ? human_time_diff(strtotime($last_sync), current_time('timestamp')) . ' ago' : 'Never'; ?></div>
                                        <div class="amfm-maps-stat-label"><?php _e('Last Updated', 'amfm-maps'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Raw JSON View -->
                                <div class="amfm-maps-raw-json">
                                    <h3><?php _e('Raw JSON Data', 'amfm-maps'); ?></h3>
                                    <div class="amfm-maps-json-container">
                                        <button class="amfm-maps-copy-button" onclick="copyToClipboard('json-raw-data')">
                                            <i class="dashicons dashicons-admin-page"></i>
                                            <?php _e('Copy to Clipboard', 'amfm-maps'); ?>
                                        </button>
                                        <pre id="json-raw-data" class="amfm-maps-json-code"><?php echo esc_html(json_encode($json_data, JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <!-- No Data State -->
                                <div class="amfm-maps-no-data">
                                    <div class="amfm-maps-no-data-icon">
                                        <i class="dashicons dashicons-database"></i>
                                    </div>
                                    <h3><?php _e('No Data Available', 'amfm-maps'); ?></h3>
                                    <p><?php _e('There is no JSON data to display. Please configure your data source in the Configuration tab and perform a sync.', 'amfm-maps'); ?></p>
                                    <button class="amfm-maps-button amfm-maps-button-primary" onclick="switchTab('configuration')">
                                        <i class="dashicons dashicons-admin-settings"></i>
                                        <?php _e('Go to Configuration', 'amfm-maps'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Locations Manager Tab -->
        <div id="tab-locations-manager" class="amfm-maps-tab-pane">
            <?php
            // Get the locations manager instance and display its content
            global $amfm_maps_locations_manager;
            if ($amfm_maps_locations_manager && method_exists($amfm_maps_locations_manager, 'display_locations_manager_content')) {
                $amfm_maps_locations_manager->display_locations_manager_content();
            } else {
                echo '<div class="amfm-maps-container"><div class="amfm-maps-main amfm-maps-full-width">';
                echo '<div class="notice notice-error"><p>' . __('Locations Manager not available. Please check plugin configuration.', 'amfm-maps') . '</p></div>';
                echo '</div></div>';
            }
            ?>
        </div>
    </div>
</div>

        </div>
    </div>
</div>
