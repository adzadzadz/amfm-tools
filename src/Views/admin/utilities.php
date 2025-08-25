<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'utilities';
$available_utilities = $available_utilities ?? [];
$enabled_utilities = $enabled_utilities ?? [];
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
                            <span class="amfm-header-stat-number"><?php echo count($available_utilities); ?></span>
                            <span class="amfm-header-stat-label">Available</span>
                        </div>
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($enabled_utilities); ?></span>
                            <span class="amfm-header-stat-label">Enabled</span>
                        </div>
                    </div>
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Utilities Content -->
        <div class="amfm-tab-content">
            <!-- Utility Management Section -->
            <div class="amfm-utilities-section">
                <div class="amfm-utilities-header">
                    <h2>
                        <span class="amfm-utilities-icon">🔧</span>
                        Utility Component Management
                    </h2>
                    <p>Enable or disable individual utility components. Disabled utilities will not be loaded, improving performance and reducing resource usage.</p>
                </div>

                <form method="post" class="amfm-component-settings-form">
                    <?php wp_nonce_field('amfm_component_settings_update', 'amfm_component_settings_nonce'); ?>
                    
                    <div class="amfm-components-grid">
                        <?php foreach ($available_utilities as $utility_key => $utility_info) : ?>
                            <?php 
                            $is_core = $utility_info['status'] === 'Core Feature';
                            $is_enabled = in_array($utility_key, $enabled_utilities);
                            ?>
                            <div class="amfm-component-card <?php echo $is_enabled ? 'amfm-component-enabled' : 'amfm-component-disabled'; ?> <?php echo $is_core ? 'amfm-component-core' : ''; ?>">
                                <div class="amfm-component-header">
                                    <div class="amfm-component-icon"><?php echo esc_html($utility_info['icon']); ?></div>
                                    <div class="amfm-component-toggle">
                                        <?php if ($is_core) : ?>
                                            <span class="amfm-core-label">Core</span>
                                            <input type="hidden" name="enabled_components[]" value="<?php echo esc_attr($utility_key); ?>">
                                        <?php else : ?>
                                            <label class="amfm-toggle-switch">
                                                <input type="checkbox" 
                                                       name="enabled_components[]" 
                                                       value="<?php echo esc_attr($utility_key); ?>"
                                                       <?php checked(in_array($utility_key, $enabled_utilities)); ?>
                                                       class="amfm-component-checkbox"
                                                       data-component="<?php echo esc_attr($utility_key); ?>">
                                                <span class="amfm-toggle-slider"></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="amfm-component-body">
                                    <h3 class="amfm-component-title"><?php echo esc_html($utility_info['name']); ?></h3>
                                    <p class="amfm-component-description"><?php echo esc_html($utility_info['description']); ?></p>
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
            </div>

            <!-- Utility Categories Section -->
            <div class="amfm-utilities-section">
                <div class="amfm-utilities-header">
                    <h2>
                        <span class="amfm-utilities-icon">📂</span>
                        Utility Categories
                    </h2>
                    <p>Utilities are organized into different categories based on their primary function and use cases.</p>
                </div>

                <div class="amfm-utility-categories">
                    <div class="amfm-category-grid">
                        <div class="amfm-category-card amfm-category-core">
                            <div class="amfm-category-header">
                                <div class="amfm-category-icon">🔧</div>
                                <h3>Core Utilities</h3>
                                <span class="amfm-category-badge">Essential</span>
                            </div>
                            <div class="amfm-category-body">
                                <p>Essential utilities that provide core functionality for the plugin. These cannot be disabled.</p>
                                <ul class="amfm-category-features">
                                    <li>✓ ACF Helper - Keyword cookie management</li>
                                    <li>✓ Import/Export Tools - Data management</li>
                                </ul>
                            </div>
                        </div>

                        <div class="amfm-category-card amfm-category-performance">
                            <div class="amfm-category-header">
                                <div class="amfm-category-icon">⚡</div>
                                <h3>Performance</h3>
                                <span class="amfm-category-badge">Optional</span>
                            </div>
                            <div class="amfm-category-body">
                                <p>Optimization utilities that enhance site performance and loading times.</p>
                                <ul class="amfm-category-features">
                                    <li>✓ Gravity Forms optimization</li>
                                    <li>✓ Script and style optimization</li>
                                    <li>✓ Performance monitoring</li>
                                </ul>
                            </div>
                        </div>

                        <div class="amfm-category-card amfm-category-data">
                            <div class="amfm-category-header">
                                <div class="amfm-category-icon">📊</div>
                                <h3>Data Management</h3>
                                <span class="amfm-category-badge">Optional</span>
                            </div>
                            <div class="amfm-category-body">
                                <p>Advanced data processing and migration tools for content management.</p>
                                <ul class="amfm-category-features">
                                    <li>✓ CSV import processing</li>
                                    <li>✓ Custom field export</li>
                                    <li>✓ Data validation and cleanup</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Guidelines Section -->
            <div class="amfm-utilities-section">
                <div class="amfm-utilities-header">
                    <h2>
                        <span class="amfm-utilities-icon">📋</span>
                        Usage Guidelines
                    </h2>
                    <p>Best practices and recommendations for utilizing the utility components effectively.</p>
                </div>

                <div class="amfm-guidelines-grid">
                    <div class="amfm-guideline-card">
                        <div class="amfm-guideline-header">
                            <div class="amfm-guideline-icon">🎯</div>
                            <h3>Performance Optimization</h3>
                        </div>
                        <div class="amfm-guideline-content">
                            <h4>When to Enable:</h4>
                            <ul>
                                <li>Sites with heavy Gravity Forms usage</li>
                                <li>Performance issues with form loading</li>
                                <li>Need for script optimization</li>
                            </ul>
                            <h4>Benefits:</h4>
                            <ul>
                                <li>Faster page load times</li>
                                <li>Reduced server resource usage</li>
                                <li>Better user experience</li>
                            </ul>
                        </div>
                    </div>

                    <div class="amfm-guideline-card">
                        <div class="amfm-guideline-header">
                            <div class="amfm-guideline-icon">📥</div>
                            <h3>CSV Import</h3>
                        </div>
                        <div class="amfm-guideline-content">
                            <h4>When to Enable:</h4>
                            <ul>
                                <li>Bulk keyword imports needed</li>
                                <li>Category data migration</li>
                                <li>Large dataset processing</li>
                            </ul>
                            <h4>Features:</h4>
                            <ul>
                                <li>Advanced validation</li>
                                <li>Error handling</li>
                                <li>Progress tracking</li>
                            </ul>
                        </div>
                    </div>

                    <div class="amfm-guideline-card">
                        <div class="amfm-guideline-header">
                            <div class="amfm-guideline-icon">📤</div>
                            <h3>Data Export</h3>
                        </div>
                        <div class="amfm-guideline-content">
                            <h4>When to Enable:</h4>
                            <ul>
                                <li>Data backup requirements</li>
                                <li>Content migration needs</li>
                                <li>Custom field exports</li>
                            </ul>
                            <h4>Capabilities:</h4>
                            <ul>
                                <li>Multiple format support</li>
                                <li>Custom field inclusion</li>
                                <li>Selective data export</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="amfm-utilities-section">
                <div class="amfm-utilities-header">
                    <h2>
                        <span class="amfm-utilities-icon">⚡</span>
                        Quick Actions
                    </h2>
                    <p>Common utility actions and shortcuts for efficient workflow management.</p>
                </div>

                <div class="amfm-quick-actions-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export')); ?>" class="amfm-quick-action-card">
                        <div class="amfm-quick-action-icon">📊</div>
                        <div class="amfm-quick-action-content">
                            <h3>Import/Export</h3>
                            <p>Access data management tools</p>
                        </div>
                        <div class="amfm-quick-action-arrow">→</div>
                    </a>

                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools')); ?>" class="amfm-quick-action-card">
                        <div class="amfm-quick-action-icon">🎛️</div>
                        <div class="amfm-quick-action-content">
                            <h3>Dashboard</h3>
                            <p>Main component controls</p>
                        </div>
                        <div class="amfm-quick-action-arrow">→</div>
                    </a>

                    <div class="amfm-quick-action-card amfm-action-disabled">
                        <div class="amfm-quick-action-icon">🔧</div>
                        <div class="amfm-quick-action-content">
                            <h3>System Check</h3>
                            <p>Coming Soon</p>
                        </div>
                        <div class="amfm-quick-action-arrow">→</div>
                    </div>
                </div>
            </div>

            <!-- Information Section -->
            <div class="amfm-utilities-section">
                <div class="amfm-utilities-info">
                    <div class="amfm-info-grid">
                        <div class="amfm-info-card">
                            <h3>💡 Management Tips</h3>
                            <ul>
                                <li>Core utilities cannot be disabled as they're essential for plugin functionality</li>
                                <li>Disabling unused utilities can improve site performance and reduce memory usage</li>
                                <li>Changes take effect immediately after saving - no page refresh required</li>
                                <li>Re-enabling utilities restores full functionality without data loss</li>
                                <li>Import/Export utilities work together for comprehensive data management</li>
                                <li>Monitor your site's performance after making changes to optimize configuration</li>
                            </ul>
                        </div>
                        <div class="amfm-info-card">
                            <h3>🔍 Troubleshooting</h3>
                            <ul>
                                <li>If performance issues occur, try disabling optional utilities one at a time</li>
                                <li>CSV import failures may indicate server memory limits - contact hosting provider</li>
                                <li>Export timeouts can be resolved by selecting smaller data sets</li>
                                <li>Clear browser cache after enabling/disabling utilities</li>
                                <li>Check WordPress debug logs if utilities don't respond as expected</li>
                                <li>Ensure ACF plugin is active for full utility functionality</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>