<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'elementor';
$available_widgets = $available_widgets ?? [];
$enabled_widgets = $enabled_widgets ?? [];
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
                        <p class="amfm-subtitle">Advanced Features Management</p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-header-stats">
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($available_widgets); ?></span>
                            <span class="amfm-header-stat-label">Widgets</span>
                        </div>
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($enabled_widgets); ?></span>
                            <span class="amfm-header-stat-label">Active</span>
                        </div>
                    </div>
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>


        <!-- Elementor Tab Content -->
        <div class="amfm-tab-content">
            <div class="amfm-shortcodes-section">
                <div class="amfm-shortcodes-header">
                    <h2>
                        <span class="amfm-shortcodes-icon">🎨</span>
                        Elementor Widget Management
                    </h2>
                    <p>Enable or disable individual Elementor widgets provided by this plugin. Disabled widgets will not be loaded in the Elementor editor.</p>
                </div>

                <form method="post" class="amfm-component-settings-form">
                    <?php wp_nonce_field('amfm_elementor_widgets_update', 'amfm_elementor_widgets_nonce'); ?>
                    
                    <div class="amfm-components-grid">
                        <?php foreach ($available_widgets as $widget_key => $widget_info) : ?>
                            <div class="amfm-component-card <?php echo in_array($widget_key, $enabled_widgets) ? 'amfm-component-enabled' : 'amfm-component-disabled'; ?>">
                                <div class="amfm-component-header">
                                    <div class="amfm-component-icon"><?php echo esc_html($widget_info['icon']); ?></div>
                                    <div class="amfm-component-toggle">
                                        <label class="amfm-toggle-switch">
                                            <input type="checkbox" 
                                                   name="enabled_widgets[]" 
                                                   value="<?php echo esc_attr($widget_key); ?>"
                                                   <?php checked(in_array($widget_key, $enabled_widgets)); ?>
                                                   class="amfm-component-checkbox"
                                                   data-widget="<?php echo esc_attr($widget_key); ?>">
                                            <span class="amfm-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="amfm-component-body">
                                    <h3 class="amfm-component-title"><?php echo esc_html($widget_info['name']); ?></h3>
                                    <p class="amfm-component-description"><?php echo esc_html($widget_info['description']); ?></p>
                                    <div class="amfm-component-status">
                                        <span class="amfm-status-indicator"></span>
                                        <span class="amfm-status-text">
                                            <?php echo in_array($widget_key, $enabled_widgets) ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </form>
            </div>

    </div>
</div>