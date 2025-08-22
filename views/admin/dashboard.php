<?php
if (!defined('ABSPATH')) exit;

// Get enabled components (default to all enabled for first-time users)
$default_components = array('acf_helper', 'text_utilities', 'optimization', 'shortcodes', 'elementor_widgets', 'import_export');
$enabled_components = get_option('amfm_enabled_components', $default_components);

// Available components
$available_components = array(
    'acf_helper' => array(
        'name' => 'ACF Helper',
        'description' => 'Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery.',
        'icon' => '🔧',
        'status' => 'Core Feature'
    ),
    'text_utilities' => array(
        'name' => 'Text Utilities',
        'description' => 'Provides text processing shortcodes like [limit_words] for content formatting.',
        'icon' => '📝',
        'status' => 'Available'
    ),
    'optimization' => array(
        'name' => 'Performance Optimization',
        'description' => 'Gravity Forms optimization and performance enhancements for faster page loading.',
        'icon' => '⚡',
        'status' => 'Available'
    ),
    'shortcodes' => array(
        'name' => 'Shortcode System',
        'description' => 'DKV shortcode and other dynamic content shortcodes with advanced filtering options.',
        'icon' => '📄',
        'status' => 'Available'
    ),
    'elementor_widgets' => array(
        'name' => 'Elementor Widgets',
        'description' => 'Custom Elementor widgets including Related Posts and other dynamic content widgets.',
        'icon' => '🎨',
        'status' => 'Available'
    ),
    'import_export' => array(
        'name' => 'Import/Export Tools',
        'description' => 'CSV import/export functionality for keywords, categories, and other data management.',
        'icon' => '📊',
        'status' => 'Core Feature'
    ),
);
?>

<div class="amfm-tab-content">
    <div class="amfm-dashboard">
        <h2>Plugin Components</h2>
        <p>Manage which AMFM Tools components are active on your site. Core features cannot be disabled.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('amfm_component_settings', 'amfm_nonce'); ?>
            
            <div class="amfm-components-grid">
                <?php foreach ($available_components as $component_key => $component) : ?>
                    <div class="amfm-component-card <?php echo in_array($component_key, $enabled_components) ? 'enabled' : 'disabled'; ?>">
                        <div class="amfm-component-header">
                            <span class="amfm-component-icon"><?php echo $component['icon']; ?></span>
                            <div class="amfm-component-info">
                                <h3><?php echo esc_html($component['name']); ?></h3>
                                <span class="amfm-component-status"><?php echo esc_html($component['status']); ?></span>
                            </div>
                            <?php if ($component['status'] !== 'Core Feature') : ?>
                                <label class="amfm-toggle">
                                    <input type="checkbox" 
                                           name="amfm_components[]" 
                                           value="<?php echo esc_attr($component_key); ?>"
                                           <?php checked(in_array($component_key, $enabled_components)); ?>>
                                    <span class="amfm-toggle-slider"></span>
                                </label>
                            <?php else : ?>
                                <span class="amfm-core-badge">Core</span>
                                <input type="hidden" name="amfm_components[]" value="<?php echo esc_attr($component_key); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="amfm-component-description">
                            <p><?php echo esc_html($component['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php submit_button('Save Component Settings', 'primary', 'save_components'); ?>
        </form>

        <div class="amfm-info-cards">
            <div class="amfm-info-card">
                <h3>🚀 Getting Started</h3>
                <p>AMFM Tools is now active! Core features are automatically enabled.</p>
                <ul>
                    <li>✅ ACF Helper is managing keyword cookies</li>
                    <li>✅ Import/Export tools are ready to use</li>
                    <li>⚙️ Enable optional components above as needed</li>
                </ul>
            </div>
            
            <div class="amfm-info-card">
                <h3>📋 Quick Actions</h3>
                <div class="amfm-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=import-export'); ?>" class="amfm-quick-btn">
                        📊 Import/Export Data
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=shortcodes'); ?>" class="amfm-quick-btn">
                        📄 View Shortcodes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>