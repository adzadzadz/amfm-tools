<?php
if (!defined('ABSPATH')) exit;

// Extract variables for easier access
$active_tab = $active_tab ?? 'dashboard';
$available_components = $available_components ?? [];
$enabled_components = $enabled_components ?? [];
$plugin_version = $plugin_version ?? '2.1.0';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Enhanced Header -->
        <div class="amfm-header">
            <div class="amfm-header-content">
                <div class="amfm-header-main">
                    <div class="amfm-header-logo">
                        <span class="amfm-icon">🛠️</span>
                    </div>
                    <div class="amfm-header-text">
                        <h1>AMFM Tools</h1>
                        <p class="amfm-subtitle">Advanced Custom Field Management & Performance Optimization Tools</p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-header-stats">
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($available_components); ?></span>
                            <span class="amfm-header-stat-label">Components</span>
                        </div>
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($enabled_components); ?></span>
                            <span class="amfm-header-stat-label">Active</span>
                        </div>
                    </div>
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="amfm-tabs-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=dashboard')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎛️</span>
                Dashboard
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=import-export')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📊</span>
                Import/Export
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=shortcodes')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📄</span>
                Shortcodes
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=elementor')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎨</span>
                Elementor
            </a>
        </div>

        <!-- Dashboard Tab Content -->
        <div class="amfm-tab-content">
            <div class="amfm-dashboard-section">
                <div class="amfm-dashboard-header">
                    <h2>
                        <span class="amfm-dashboard-icon">🎛️</span>
                        Component Management Dashboard
                    </h2>
                    <p>Enable or disable individual plugin components. Disabled components will not be loaded, improving performance and reducing resource usage.</p>
                </div>

                <form method="post" class="amfm-component-settings-form">
                    <?php wp_nonce_field('amfm_component_settings_update', 'amfm_component_settings_nonce'); ?>
                    
                    <div class="amfm-components-grid">
                        <?php foreach ($available_components as $component_key => $component_info) : ?>
                            <?php 
                            $is_core = $component_info['status'] === 'Core Feature';
                            $is_enabled = in_array($component_key, $enabled_components);
                            ?>
                            <div class="amfm-component-card <?php echo $is_enabled ? 'amfm-component-enabled' : 'amfm-component-disabled'; ?> <?php echo $is_core ? 'amfm-component-core' : ''; ?>">
                                <div class="amfm-component-header">
                                    <div class="amfm-component-icon"><?php echo esc_html($component_info['icon']); ?></div>
                                    <div class="amfm-component-toggle">
                                        <?php if ($is_core) : ?>
                                            <span class="amfm-core-label">Core</span>
                                            <input type="hidden" name="enabled_components[]" value="<?php echo esc_attr($component_key); ?>">
                                        <?php else : ?>
                                            <label class="amfm-toggle-switch">
                                                <input type="checkbox" 
                                                       name="enabled_components[]" 
                                                       value="<?php echo esc_attr($component_key); ?>"
                                                       <?php checked(in_array($component_key, $enabled_components)); ?>
                                                       class="amfm-component-checkbox"
                                                       data-component="<?php echo esc_attr($component_key); ?>">
                                                <span class="amfm-toggle-slider"></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="amfm-component-body">
                                    <h3 class="amfm-component-title"><?php echo esc_html($component_info['name']); ?></h3>
                                    <p class="amfm-component-description"><?php echo esc_html($component_info['description']); ?></p>
                                    <div class="amfm-component-status">
                                        <span class="amfm-status-indicator"></span>
                                        <span class="amfm-status-text">
                                            <?php if ($is_core) : ?>
                                                Always Active
                                            <?php else : ?>
                                                <?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </form>

                <div class="amfm-dashboard-info">
                    <div class="amfm-info-grid">
                        <div class="amfm-info-card">
                            <h3>💡 Component Management Tips</h3>
                            <ul>
                                <li>Core features cannot be disabled as they're essential for plugin functionality</li>
                                <li>Disabling unused components can improve site performance</li>
                                <li>Changes take effect immediately after saving</li>
                                <li>Re-enabling components restores full functionality without data loss</li>
                            </ul>
                        </div>
                        <div class="amfm-info-card">
                            <h3>📋 Plugin Information</h3>
                            <div class="amfm-plugin-details">
                                <div class="amfm-detail-item">
                                    <strong>Version:</strong> <?php echo esc_html($plugin_version); ?>
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Author:</strong> Adrian T. Saycon
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Website:</strong> <a href="https://adzbyte.com/" target="_blank">adzbyte.com</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>