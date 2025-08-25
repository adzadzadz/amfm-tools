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


        <!-- Dashboard Content -->
        <div class="amfm-tab-content">
            <!-- Overview Section -->
            <div class="amfm-dashboard-section">
                <div class="amfm-dashboard-header">
                    <h2>
                        <span class="amfm-dashboard-icon">🎛️</span>
                        Plugin Overview
                    </h2>
                    <p>Welcome to AMFM Tools - your comprehensive solution for Advanced Custom Field management and performance optimization.</p>
                </div>

                <div class="amfm-components-overview">
                    <div class="amfm-components-grid">
                        <?php foreach ($available_components as $component_key => $component_info) : ?>
                            <?php 
                            $is_core = $component_info['status'] === 'Core Feature';
                            $is_enabled = in_array($component_key, $enabled_components);
                            ?>
                            <div class="amfm-component-card amfm-component-overview <?php echo $is_enabled ? 'amfm-component-enabled' : 'amfm-component-disabled'; ?> <?php echo $is_core ? 'amfm-component-core' : ''; ?>">
                                <div class="amfm-component-header">
                                    <div class="amfm-component-icon"><?php echo esc_html($component_info['icon']); ?></div>
                                    <div class="amfm-component-status-badge">
                                        <?php if ($is_core) : ?>
                                            <span class="amfm-core-label">Core</span>
                                        <?php else : ?>
                                            <span class="amfm-status-badge <?php echo $is_enabled ? 'status-enabled' : 'status-disabled'; ?>">
                                                <?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="amfm-component-body">
                                    <h3 class="amfm-component-title"><?php echo esc_html($component_info['name']); ?></h3>
                                    <p class="amfm-component-description"><?php echo esc_html($component_info['description']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation Section -->
            <div class="amfm-dashboard-section">
                <div class="amfm-dashboard-header">
                    <h2>
                        <span class="amfm-dashboard-icon">🚀</span>
                        Quick Access
                    </h2>
                    <p>Navigate to specific sections to configure and manage your AMFM Tools components.</p>
                </div>

                <div class="amfm-quick-nav-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-shortcodes')); ?>" class="amfm-quick-nav-card">
                        <div class="amfm-quick-nav-icon">📄</div>
                        <div class="amfm-quick-nav-content">
                            <h3>Shortcodes</h3>
                            <p>Configure and manage shortcode functionality</p>
                            <span class="amfm-nav-arrow">→</span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-utilities')); ?>" class="amfm-quick-nav-card">
                        <div class="amfm-quick-nav-icon">🔧</div>
                        <div class="amfm-quick-nav-content">
                            <h3>Utilities</h3>
                            <p>Manage utility components and settings</p>
                            <span class="amfm-nav-arrow">→</span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export')); ?>" class="amfm-quick-nav-card">
                        <div class="amfm-quick-nav-icon">📊</div>
                        <div class="amfm-quick-nav-content">
                            <h3>Import/Export</h3>
                            <p>Data management and migration tools</p>
                            <span class="amfm-nav-arrow">→</span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-elementor')); ?>" class="amfm-quick-nav-card">
                        <div class="amfm-quick-nav-icon">🎨</div>
                        <div class="amfm-quick-nav-content">
                            <h3>Elementor</h3>
                            <p>Custom Elementor widgets and settings</p>
                            <span class="amfm-nav-arrow">→</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Features Overview Section -->
            <div class="amfm-dashboard-section">
                <div class="amfm-dashboard-header">
                    <h2>
                        <span class="amfm-dashboard-icon">✨</span>
                        Key Features
                    </h2>
                    <p>Discover the powerful features available in AMFM Tools to enhance your WordPress site.</p>
                </div>

                <div class="amfm-features-grid">
                    <div class="amfm-feature-card">
                        <div class="amfm-feature-icon">🍪</div>
                        <div class="amfm-feature-content">
                            <h3>ACF Keyword Management</h3>
                            <p>Automatically capture and store ACF keywords in browser cookies for dynamic content display across your site.</p>
                            <ul class="amfm-feature-list">
                                <li>Automatic keyword detection</li>
                                <li>Cookie-based storage</li>
                                <li>Dynamic content integration</li>
                            </ul>
                        </div>
                    </div>

                    <div class="amfm-feature-card">
                        <div class="amfm-feature-icon">📝</div>
                        <div class="amfm-feature-content">
                            <h3>DKV Shortcode System</h3>
                            <p>Powerful shortcode system for displaying dynamic keyword-based content with advanced filtering and customization options.</p>
                            <ul class="amfm-feature-list">
                                <li>Category-based filtering</li>
                                <li>Text transformation</li>
                                <li>Fallback content support</li>
                            </ul>
                        </div>
                    </div>

                    <div class="amfm-feature-card">
                        <div class="amfm-feature-icon">⚡</div>
                        <div class="amfm-feature-content">
                            <h3>Performance Optimization</h3>
                            <p>Built-in optimization features for Gravity Forms and general site performance improvements.</p>
                            <ul class="amfm-feature-list">
                                <li>Form loading optimization</li>
                                <li>Script management</li>
                                <li>Resource optimization</li>
                            </ul>
                        </div>
                    </div>

                    <div class="amfm-feature-card">
                        <div class="amfm-feature-icon">🎨</div>
                        <div class="amfm-feature-content">
                            <h3>Elementor Integration</h3>
                            <p>Custom Elementor widgets including Related Posts with keyword-based matching and advanced styling options.</p>
                            <ul class="amfm-feature-list">
                                <li>Related Posts widget</li>
                                <li>Keyword-based matching</li>
                                <li>Customizable layouts</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information Section -->
            <div class="amfm-dashboard-section">
                <div class="amfm-dashboard-info">
                    <div class="amfm-info-grid">
                        <div class="amfm-info-card">
                            <h3>📋 Plugin Information</h3>
                            <div class="amfm-plugin-details">
                                <div class="amfm-detail-item">
                                    <strong>Version:</strong> <?php echo esc_html(AMFM_TOOLS_VERSION ?? '2.1.0'); ?>
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Author:</strong> Adrian T. Saycon
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Website:</strong> <a href="https://adzbyte.com/" target="_blank">adzbyte.com</a>
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Components:</strong> <?php echo count($available_components); ?> total
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Active Components:</strong> <?php echo count($enabled_components); ?>
                                </div>
                            </div>
                        </div>
                        <div class="amfm-info-card">
                            <h3>💡 Getting Started</h3>
                            <ul>
                                <li>Visit <strong>Shortcodes</strong> to configure DKV shortcode settings</li>
                                <li>Use <strong>Utilities</strong> to enable/disable performance features</li>
                                <li>Access <strong>Import/Export</strong> for data management</li>
                                <li>Configure <strong>Elementor</strong> widgets for enhanced page building</li>
                                <li>All changes are applied immediately without page refresh</li>
                                <li>Check WordPress debug logs if you encounter any issues</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>