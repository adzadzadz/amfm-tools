<?php
// Get enabled components
$enabled_components = get_option('amfm_enabled_components', array('acf_helper', 'text_utilities', 'optimization', 'shortcodes', 'elementor_widgets', 'import_export'));
$plugin_version = defined('AMFM_TOOLS_VERSION') ? AMFM_TOOLS_VERSION : '2.2.1';
?>

<div class="amfm-dashboard-content">
    <div class="amfm-dashboard-header">
        <h1>
            <span class="amfm-logo">🧰</span>
            AMFM Tools
            <span class="amfm-version">v<?php echo esc_html($plugin_version); ?></span>
        </h1>
        <p class="amfm-subtitle">Comprehensive WordPress functionality toolkit</p>
    </div>

    <div class="amfm-grid">
        <div class="amfm-card amfm-stats-card">
            <h3>📊 Quick Stats</h3>
            <div class="amfm-stats-grid">
                <div class="amfm-stat">
                    <div class="amfm-stat-number"><?php echo count($enabled_components); ?></div>
                    <div class="amfm-stat-label">Active Components</div>
                </div>
                <div class="amfm-stat">
                    <div class="amfm-stat-number"><?php echo wp_count_posts('post')->publish; ?></div>
                    <div class="amfm-stat-label">Posts</div>
                </div>
                <div class="amfm-stat">
                    <div class="amfm-stat-number"><?php echo wp_count_posts('page')->publish; ?></div>
                    <div class="amfm-stat-label">Pages</div>
                </div>
            </div>
        </div>

        <div class="amfm-card amfm-component-card">
            <h3>🔧 Component Management</h3>
            <form method="post" action="">
                <?php wp_nonce_field('amfm_component_settings', 'amfm_component_nonce'); ?>
                <div class="amfm-component-list">
                    <label class="amfm-component-item">
                        <input type="checkbox" name="components[]" value="acf_helper" <?php checked(in_array('acf_helper', $enabled_components)); ?> disabled>
                        <span class="amfm-component-name">🏷️ ACF Helper</span>
                        <span class="amfm-component-status core">Core</span>
                    </label>
                    <label class="amfm-component-item">
                        <input type="checkbox" name="components[]" value="text_utilities" <?php checked(in_array('text_utilities', $enabled_components)); ?>>
                        <span class="amfm-component-name">📝 Text Utilities</span>
                        <span class="amfm-component-status">Optional</span>
                    </label>
                    <label class="amfm-component-item">
                        <input type="checkbox" name="components[]" value="optimization" <?php checked(in_array('optimization', $enabled_components)); ?>>
                        <span class="amfm-component-name">⚡ Performance Optimization</span>
                        <span class="amfm-component-status">Optional</span>
                    </label>
                    <label class="amfm-component-item">
                        <input type="checkbox" name="components[]" value="shortcodes" <?php checked(in_array('shortcodes', $enabled_components)); ?>>
                        <span class="amfm-component-name">📄 Shortcode System</span>
                        <span class="amfm-component-status">Optional</span>
                    </label>
                    <label class="amfm-component-item">
                        <input type="checkbox" name="components[]" value="elementor_widgets" <?php checked(in_array('elementor_widgets', $enabled_components)); ?>>
                        <span class="amfm-component-name">🧩 Elementor Widgets</span>
                        <span class="amfm-component-status">Optional</span>
                    </label>
                    <label class="amfm-component-item">
                        <input type="checkbox" name="components[]" value="import_export" <?php checked(in_array('import_export', $enabled_components)); ?> disabled>
                        <span class="amfm-component-name">📊 Import/Export</span>
                        <span class="amfm-component-status core">Core</span>
                    </label>
                </div>
                <div class="amfm-component-actions">
                    <button type="submit" class="button button-primary amfm-save-btn">
                        💾 Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="amfm-grid amfm-grid-full">
        <div class="amfm-card amfm-info-card">
            <h3>ℹ️ Plugin Information</h3>
            <div class="amfm-info-grid">
                <div class="amfm-info-item">
                    <strong>Version:</strong> <?php echo esc_html($plugin_version); ?>
                </div>
                <div class="amfm-info-item">
                    <strong>Author:</strong> Adrian T. Saycon
                </div>
                <div class="amfm-info-item">
                    <strong>Framework:</strong> ADZ WordPress Plugin Framework
                </div>
                <div class="amfm-info-item">
                    <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?>
                </div>
            </div>
        </div>
    </div>
</div>