<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!function_exists('acf_get_local_field_groups')): ?>
        <div class="notice notice-warning">
            <p><strong>Advanced Custom Fields (ACF) is not active.</strong> This page shows AMFM Tools ACF field configurations, but ACF needs to be activated to use them.</p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="amfm-stats-grid">
        <div class="amfm-stat-card">
            <h3>Configured Field Groups</h3>
            <div class="amfm-stat-number"><?php echo count($configured_groups); ?></div>
        </div>
        <div class="amfm-stat-card">
            <h3>Active Field Groups</h3>
            <div class="amfm-stat-number"><?php echo count($active_groups); ?></div>
        </div>
        <div class="amfm-stat-card">
            <h3>Custom Post Types</h3>
            <div class="amfm-stat-number"><?php echo count($post_types); ?></div>
        </div>
        <div class="amfm-stat-card">
            <h3>Total Fields</h3>
            <div class="amfm-stat-number">
                <?php 
                $total_fields = 0;
                foreach ($configured_groups as $group) {
                    $total_fields += isset($group['fields']) ? count($group['fields']) : 0;
                }
                echo $total_fields;
                ?>
            </div>
        </div>
    </div>

    <!-- Custom Post Types Section -->
    <div class="amfm-admin-section">
        <h2>Custom Post Types</h2>
        <div class="amfm-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Post Type</th>
                        <th>Labels</th>
                        <th>Status</th>
                        <th>Menu Position</th>
                        <th>Supports</th>
                        <th>Has Archive</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($post_types as $post_type => $config): ?>
                        <?php 
                        $is_registered = post_type_exists($post_type);
                        $wp_post_type = get_post_type_object($post_type);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($post_type); ?></strong>
                                <?php if ($is_registered): ?>
                                    <span class="amfm-status-badge amfm-status-active">Registered</span>
                                <?php else: ?>
                                    <span class="amfm-status-badge amfm-status-inactive">Not Registered</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($config['labels']['name'] ?? 'N/A'); ?></strong><br>
                                <small><?php echo esc_html($config['labels']['singular_name'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <?php if ($config['public']): ?>
                                    <span class="amfm-badge amfm-badge-success">Public</span>
                                <?php else: ?>
                                    <span class="amfm-badge amfm-badge-secondary">Private</span>
                                <?php endif; ?>
                                
                                <?php if ($config['show_in_rest']): ?>
                                    <span class="amfm-badge amfm-badge-info">REST API</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($config['menu_position'] ?? 'Default'); ?></td>
                            <td>
                                <?php if (!empty($config['supports'])): ?>
                                    <?php foreach ($config['supports'] as $support): ?>
                                        <code><?php echo esc_html($support); ?></code>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <em>None</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $config['has_archive'] ? '<span class="amfm-badge amfm-badge-success">Yes</span>' : '<span class="amfm-badge amfm-badge-secondary">No</span>'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ACF Field Groups Section -->
    <div class="amfm-admin-section">
        <h2>ACF Field Groups</h2>
        
        <?php foreach ($configured_groups as $group_key => $group): ?>
            <?php 
            $is_active = isset($active_groups[$group_key]);
            $field_count = isset($group['fields']) ? count($group['fields']) : 0;
            ?>
            <div class="amfm-field-group">
                <div class="amfm-field-group-header">
                    <h3>
                        <?php echo esc_html($group['title']); ?>
                        <?php if ($is_active): ?>
                            <span class="amfm-status-badge amfm-status-active">Active</span>
                        <?php else: ?>
                            <span class="amfm-status-badge amfm-status-inactive">Inactive</span>
                        <?php endif; ?>
                    </h3>
                    <div class="amfm-field-group-meta">
                        <span class="amfm-badge amfm-badge-secondary"><?php echo $field_count; ?> fields</span>
                        <span class="amfm-badge amfm-badge-info">Position: <?php echo esc_html($group['position'] ?? 'normal'); ?></span>
                        <code><?php echo esc_html($group_key); ?></code>
                    </div>
                </div>

                <!-- Location Rules -->
                <?php if (!empty($group['location'])): ?>
                    <div class="amfm-field-group-locations">
                        <strong>Location Rules:</strong>
                        <?php foreach ($group['location'] as $location_group): ?>
                            <div class="amfm-location-rule">
                                <?php foreach ($location_group as $rule): ?>
                                    <span class="amfm-badge amfm-badge-outline">
                                        <?php echo esc_html($rule['param']); ?> 
                                        <?php echo esc_html($rule['operator']); ?> 
                                        <?php echo esc_html($rule['value']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Fields List -->
                <?php if (!empty($group['fields'])): ?>
                    <div class="amfm-fields-list">
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['fields'] as $field): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($field['label']); ?></strong></td>
                                        <td><code><?php echo esc_html($field['name']); ?></code></td>
                                        <td>
                                            <span class="amfm-badge amfm-badge-info"><?php echo esc_html($field['type']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($field['required'])): ?>
                                                <span class="amfm-badge amfm-badge-warning">Required</span>
                                            <?php else: ?>
                                                <span class="amfm-badge amfm-badge-secondary">Optional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo esc_html($field['key']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.amfm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.amfm-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.amfm-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    font-weight: normal;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amfm-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.amfm-admin-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 20px 0;
    overflow: hidden;
}

.amfm-admin-section h2 {
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
    margin: 0;
    padding: 15px 20px;
    font-size: 16px;
}

.amfm-table-container {
    overflow-x: auto;
}

.amfm-field-group {
    border-bottom: 1px solid #eee;
    padding: 20px;
}

.amfm-field-group:last-child {
    border-bottom: none;
}

.amfm-field-group-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.amfm-field-group-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.amfm-field-group-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.amfm-field-group-locations {
    margin-bottom: 15px;
    font-size: 14px;
}

.amfm-location-rule {
    display: flex;
    gap: 5px;
    margin: 5px 0;
    flex-wrap: wrap;
}

.amfm-fields-list {
    margin-top: 15px;
}

.amfm-status-badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amfm-status-active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.amfm-status-inactive {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.amfm-badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: 500;
    display: inline-block;
    margin: 2px;
}

.amfm-badge-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.amfm-badge-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.amfm-badge-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.amfm-badge-secondary {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

.amfm-badge-outline {
    background: transparent;
    color: #6c757d;
    border: 1px solid #6c757d;
}

@media (max-width: 782px) {
    .amfm-field-group-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .amfm-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>