<?php
if (!defined('ABSPATH')) exit;

// Extract variables for easier access
$plugin_version = $plugin_version ?? AMFM_TOOLS_VERSION;
?>

<!-- Dashboard Content -->
<div style="padding: 0 32px;">
            <div class="amfm-dashboard-grid">
                <!-- Plugin Status Card -->
                <div class="amfm-dashboard-card amfm-status-card">
                    <div class="amfm-card-header">
                        <h3>Plugin Status</h3>
                        <span class="amfm-status-badge amfm-status-active">Active</span>
                    </div>
                    <div class="amfm-card-body">
                        <div class="amfm-stats-row">
                            <div class="amfm-stat">
                                <span class="amfm-stat-label">Version</span>
                                <span class="amfm-stat-value"><?php echo esc_html($plugin_version); ?></span>
                            </div>
                            <div class="amfm-stat">
                                <span class="amfm-stat-label">ACF Status</span>
                                <span class="amfm-stat-value"><?php echo function_exists('acf_get_field_groups') ? 'Active' : 'Missing'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="amfm-dashboard-card amfm-stats-card">
                    <div class="amfm-card-header">
                        <h3>Quick Stats</h3>
                    </div>
                    <div class="amfm-card-body">
                        <?php
                        global $wpdb;
                        
                        // Count posts with ACF keywords
                        $posts_with_keywords = 0;
                        if (function_exists('acf_get_field_groups')) {
                            $posts_with_keywords = (int) $wpdb->get_var(
                                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
                                 WHERE meta_key IN ('amfm_keywords', 'amfm_other_keywords') 
                                 AND meta_value != ''"
                            );
                        }
                        
                        // Count excluded keywords
                        $excluded_keywords = get_option('amfm_excluded_keywords', []);
                        $excluded_keywords_count = is_array($excluded_keywords) ? count($excluded_keywords) : 0;
                        ?>
                        <div class="amfm-stats-row">
                            <div class="amfm-stat">
                                <span class="amfm-stat-label">Posts with Keywords</span>
                                <span class="amfm-stat-value"><?php echo $posts_with_keywords; ?></span>
                            </div>
                            <div class="amfm-stat">
                                <span class="amfm-stat-label">Excluded Keywords</span>
                                <span class="amfm-stat-value"><?php echo $excluded_keywords_count; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="amfm-dashboard-card amfm-actions-card">
                    <div class="amfm-card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="amfm-card-body">
                        <div class="amfm-quick-actions">
                            <a href="<?php echo admin_url('admin.php?page=amfm-tools-import-export'); ?>" class="amfm-quick-action">
                                <span class="amfm-action-icon">📊</span>
                                <span class="amfm-action-text">Import/Export Data</span>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=amfm-tools-shortcodes'); ?>" class="amfm-quick-action">
                                <span class="amfm-action-icon">📄</span>
                                <span class="amfm-action-text">Manage Shortcodes</span>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=amfm-tools-utilities'); ?>" class="amfm-quick-action">
                                <span class="amfm-action-icon">🔧</span>
                                <span class="amfm-action-text">System Utilities</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .amfm-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin: 24px 0;
        }

        .amfm-dashboard-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e1e5e9;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .amfm-card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .amfm-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .amfm-card-body {
            padding: 20px 24px;
        }

        .amfm-status-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
        }

        .amfm-status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .amfm-stats-row {
            display: flex;
            gap: 24px;
        }

        .amfm-stat {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .amfm-stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .amfm-stat-value {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .amfm-quick-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .amfm-quick-action {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.2s ease;
        }

        .amfm-quick-action:hover {
            background: #e9ecef;
            color: #2c3e50;
            text-decoration: none;
        }

        .amfm-action-icon {
            font-size: 16px;
        }

        .amfm-action-text {
            font-weight: 500;
        }
        </style>
</div>