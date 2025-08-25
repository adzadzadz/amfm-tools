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
                        <p class="amfm-subtitle">Advanced Custom Field Management & Performance Optimization Tools</p>
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
            <div class="amfm-elementor-section">
                <div class="amfm-elementor-header">
                    <h2>
                        <span class="amfm-elementor-icon">🎨</span>
                        Elementor Widget Management
                    </h2>
                    <p>Enable or disable individual Elementor widgets provided by this plugin. Disabled widgets will not be loaded in the Elementor editor.</p>
                </div>

                <form method="post" class="amfm-elementor-widgets-form">
                    <?php wp_nonce_field('amfm_elementor_widgets_update', 'amfm_elementor_widgets_nonce'); ?>
                    
                    <div class="amfm-widgets-grid">
                        <?php foreach ($available_widgets as $widget_key => $widget_info) : ?>
                            <div class="amfm-widget-card <?php echo in_array($widget_key, $enabled_widgets) ? 'amfm-widget-enabled' : 'amfm-widget-disabled'; ?>">
                                <div class="amfm-widget-header">
                                    <div class="amfm-widget-icon"><?php echo esc_html($widget_info['icon']); ?></div>
                                    <div class="amfm-widget-toggle">
                                        <label class="amfm-toggle-switch">
                                            <input type="checkbox" 
                                                   name="enabled_widgets[]" 
                                                   value="<?php echo esc_attr($widget_key); ?>"
                                                   <?php checked(in_array($widget_key, $enabled_widgets)); ?>
                                                   class="amfm-widget-checkbox"
                                                   data-widget="<?php echo esc_attr($widget_key); ?>">
                                            <span class="amfm-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="amfm-widget-body">
                                    <h3 class="amfm-widget-title"><?php echo esc_html($widget_info['name']); ?></h3>
                                    <p class="amfm-widget-description"><?php echo esc_html($widget_info['description']); ?></p>
                                    <div class="amfm-widget-status">
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

                <div class="amfm-elementor-info">
                    <h3>💡 Tips</h3>
                    <ul>
                        <li>Disabling widgets can improve Elementor editor performance by reducing loaded components</li>
                        <li>Disabled widgets will not appear in the Elementor widget panel</li>
                        <li>Changes are saved automatically when toggled</li>
                        <li>Re-enabling a widget restores all its functionality without data loss</li>
                    </ul>
                </div>

                <div class="amfm-widget-features">
                    <h3>📋 Widget Features</h3>
                    
                    <div class="amfm-feature-section">
                        <h4>AMFM Related Posts Widget</h4>
                        <p>The Related Posts widget provides powerful content discovery features:</p>
                        <ul>
                            <li><strong>Keyword Matching:</strong> Automatically finds related posts based on ACF keywords</li>
                            <li><strong>Multiple Layouts:</strong> Choose from grid, list, or carousel display options</li>
                            <li><strong>Customizable Styling:</strong> Full control over typography, colors, and spacing</li>
                            <li><strong>Query Controls:</strong> Filter by post type, category, tags, and custom taxonomies</li>
                            <li><strong>Performance Optimized:</strong> Efficient queries with built-in caching</li>
                            <li><strong>Responsive Design:</strong> Mobile-first approach with breakpoint controls</li>
                        </ul>
                    </div>

                    <div class="amfm-feature-usage">
                        <h4>How to Use</h4>
                        <ol>
                            <li>Open Elementor editor on any page or post</li>
                            <li>Search for "AMFM" in the widgets panel</li>
                            <li>Drag the "AMFM Related Posts" widget to your page</li>
                            <li>Configure the widget settings in the left panel</li>
                            <li>Customize the appearance using the Style tab</li>
                            <li>Preview and publish your changes</li>
                        </ol>
                    </div>

                    <div class="amfm-feature-requirements">
                        <h4>Requirements</h4>
                        <ul>
                            <li>Elementor (Free or Pro) must be installed and active</li>
                            <li>Advanced Custom Fields (ACF) plugin must be active</li>
                            <li>Posts must have ACF keyword fields populated for matching</li>
                            <li>The "Elementor Widgets" component must be enabled in the Dashboard</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>