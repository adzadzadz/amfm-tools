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
                        <p class="amfm-subtitle">Advanced Features Management</p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Utilities Content -->
        <div class="amfm-tab-content">
            <!-- Utility Management Section -->
            <div class="amfm-shortcodes-section">
                <div class="amfm-shortcodes-header">
                    <h2>
                        <span class="amfm-shortcodes-icon">🔧</span>
                        Utility Management
                    </h2>
                    <p>Enable or disable individual utilities. Disabled utilities will not be loaded, improving performance and reducing resource usage.</p>
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
                                    <div class="amfm-component-actions">
                                        <button type="button" 
                                                class="amfm-info-button amfm-doc-button" 
                                                data-utility="<?php echo esc_attr($utility_key); ?>"
                                                onclick="openUtilityDrawer('<?php echo esc_attr($utility_key); ?>', 'documentation')">
                                            Documentation
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Utility Documentation Drawer -->
<div id="amfm-utility-drawer" class="amfm-drawer">
    <div class="amfm-drawer-overlay" onclick="closeUtilityDrawer()"></div>
    <div class="amfm-drawer-content">
        <div class="amfm-drawer-header">
            <h2 id="amfm-drawer-title">Utility Documentation</h2>
            <button type="button" class="amfm-drawer-close" onclick="closeUtilityDrawer()">&times;</button>
        </div>
        <div class="amfm-drawer-body" id="amfm-drawer-body">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
// Utility documentation data
const utilityData = {
    'acf_helper': {
        name: 'ACF Helper',
        description: 'Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Overview:</h3>
                    <p>The ACF Helper utility is a core component that automatically manages keyword cookies based on ACF (Advanced Custom Fields) data. It enhances WordPress sites with dynamic content capabilities.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Key Features:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Automatic Cookie Management</strong> - Stores keywords from ACF fields in browser cookies for dynamic content
                        </div>
                        <div class="amfm-attribute">
                            <strong>Multi-field Support</strong> - Handles both 'amfm_keywords' and 'amfm_other_keywords' fields
                        </div>
                        <div class="amfm-attribute">
                            <strong>Category Processing</strong> - Processes categorized keywords with prefixes (e.g., "i:Insurance", "c:Condition")
                        </div>
                        <div class="amfm-attribute">
                            <strong>Cookie Expiration</strong> - Configurable cookie duration (default: 24 hours)
                        </div>
                        <div class="amfm-attribute">
                            <strong>Cross-page Persistence</strong> - Keywords remain available across different pages during user session
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>How It Works:</h3>
                    <ul>
                        <li>Automatically detects ACF keyword fields on page load</li>
                        <li>Extracts and processes keywords from ACF fields</li>
                        <li>Stores keywords in browser cookies with configurable expiration</li>
                        <li>Provides foundation for DKV shortcode and other dynamic content features</li>
                        <li>Filters keywords against global exclusion list</li>
                        <li>Handles both regular and categorized keywords</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Benefits:</h3>
                    <ul>
                        <li><strong>Performance:</strong> Reduces database queries for keyword retrieval</li>
                        <li><strong>Dynamic Content:</strong> Enables personalized content based on page context</li>
                        <li><strong>SEO Enhancement:</strong> Supports keyword-based content optimization</li>
                        <li><strong>User Experience:</strong> Provides consistent keyword availability across sessions</li>
                        <li><strong>Developer Friendly:</strong> Simple integration with existing ACF workflows</li>
                    </ul>
                </div>
            </div>
        `
    },
    'optimization': {
        name: 'Performance Optimization',
        description: 'Gravity Forms optimization and performance enhancements for faster page loading.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Overview:</h3>
                    <p>The Performance Optimization utility provides comprehensive performance enhancements, particularly focusing on Gravity Forms optimization and general WordPress performance improvements.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Optimization Features:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Gravity Forms Optimization</strong> - Reduces form loading times and improves rendering performance
                        </div>
                        <div class="amfm-attribute">
                            <strong>Script Optimization</strong> - Minimizes unnecessary script loading and improves page speed
                        </div>
                        <div class="amfm-attribute">
                            <strong>Style Optimization</strong> - Optimizes CSS delivery and reduces render-blocking resources
                        </div>
                        <div class="amfm-attribute">
                            <strong>Resource Management</strong> - Intelligently loads resources only when needed
                        </div>
                        <div class="amfm-attribute">
                            <strong>Caching Integration</strong> - Works with existing caching solutions for maximum performance
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Performance Benefits:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Faster Page Load Times</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Reduced time to first contentful paint and improved Core Web Vitals scores
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Improved Form Performance</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Gravity Forms load faster with optimized script and style delivery
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Reduced Server Load</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Lower CPU usage and memory consumption through smart resource management
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>When to Enable:</h3>
                    <ul>
                        <li>Sites with multiple Gravity Forms</li>
                        <li>High traffic websites requiring optimal performance</li>
                        <li>Sites with performance issues or slow loading times</li>
                        <li>E-commerce sites with form-heavy checkout processes</li>
                        <li>Sites that need to improve Core Web Vitals scores</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Best Practices:</h3>
                    <ul>
                        <li><strong>Enable by Default:</strong> This utility is automatically enabled for optimal performance</li>
                        <li><strong>Monitor Performance:</strong> Use tools like Google PageSpeed Insights to measure improvements</li>
                        <li><strong>Test Forms:</strong> Verify all Gravity Forms functionality after enabling</li>
                        <li><strong>Cache Compatibility:</strong> Works well with popular caching plugins</li>
                        <li><strong>Regular Updates:</strong> Keep the plugin updated for latest optimizations</li>
                    </ul>
                </div>
            </div>
        `
    },
    'import_export': {
        name: 'Import/Export Tools',
        description: 'Comprehensive data management for importing keywords, categories, and exporting posts with ACF fields.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Overview:</h3>
                    <p>The Import/Export Tools provide comprehensive data management capabilities for WordPress sites, enabling efficient transfer of keywords, categories, and post data with full ACF field support.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Import Capabilities:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>CSV Keyword Import</strong> - Bulk import keywords from CSV files with validation
                        </div>
                        <div class="amfm-attribute">
                            <strong>Category Import</strong> - Import category structures and mappings
                        </div>
                        <div class="amfm-attribute">
                            <strong>Data Validation</strong> - Automatic validation and error reporting during imports
                        </div>
                        <div class="amfm-attribute">
                            <strong>Progress Tracking</strong> - Real-time progress indicators for large imports
                        </div>
                        <div class="amfm-attribute">
                            <strong>Error Handling</strong> - Comprehensive error logging and recovery options
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Export Capabilities:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Post Data Export</strong> - Export posts with all associated metadata
                        </div>
                        <div class="amfm-attribute">
                            <strong>ACF Fields Export</strong> - Full support for Advanced Custom Fields data
                        </div>
                        <div class="amfm-attribute">
                            <strong>Multiple Formats</strong> - Support for CSV, JSON, and other export formats
                        </div>
                        <div class="amfm-attribute">
                            <strong>Selective Export</strong> - Choose specific post types, fields, or date ranges
                        </div>
                        <div class="amfm-attribute">
                            <strong>Custom Field Mapping</strong> - Advanced mapping options for complex data structures
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Common Use Cases:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Site Migration</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Transfer content between WordPress sites with full ACF field preservation
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Data Backup</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Create comprehensive backups of posts and custom field data
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Bulk Content Updates</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Import large datasets of keywords and categories efficiently
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>Features:</h3>
                    <ul>
                        <li>Drag-and-drop CSV upload interface</li>
                        <li>Real-time progress tracking for large imports</li>
                        <li>Detailed error reporting and validation</li>
                        <li>Support for categorized keywords (e.g., "i:Insurance", "c:Condition")</li>
                        <li>Flexible export options with custom field selection</li>
                        <li>Batch processing for large datasets</li>
                        <li>Compatibility with standard WordPress import/export formats</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Best Practices:</h3>
                    <ul>
                        <li><strong>Backup First:</strong> Always backup your site before major imports</li>
                        <li><strong>Test Small Batches:</strong> Test with small datasets before bulk operations</li>
                        <li><strong>Validate Data:</strong> Review CSV formats and data structure before import</li>
                        <li><strong>Monitor Performance:</strong> Large imports may require increased server resources</li>
                        <li><strong>Use Staging:</strong> Test imports on staging sites first</li>
                    </ul>
                </div>
            </div>
        `
    }
};

// Drawer functions
function openUtilityDrawer(utilityKey, mode = 'documentation') {
    const drawer = document.getElementById('amfm-utility-drawer');
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (utilityData[utilityKey]) {
        const data = utilityData[utilityKey];
        
        title.textContent = data.name + ' Documentation';
        body.innerHTML = data.content;
        
        drawer.classList.add('amfm-drawer-open');
        document.body.style.overflow = 'hidden';
    }
}

function closeUtilityDrawer() {
    const drawer = document.getElementById('amfm-utility-drawer');
    drawer.classList.remove('amfm-drawer-open');
    document.body.style.overflow = '';
}

// Close drawer with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUtilityDrawer();
    }
});
</script>

<style>
/* Drawer Styles */
.amfm-drawer {
    position: fixed;
    top: 32px; /* Account for WordPress admin bar */
    left: 0;
    width: 100%;
    height: calc(100% - 32px);
    z-index: 10000;
    visibility: hidden;
    opacity: 0;
    transition: all 0.3s ease;
}

/* Responsive admin bar height */
@media screen and (max-width: 782px) {
    .amfm-drawer {
        top: 46px;
        height: calc(100% - 46px);
    }
}

@media screen and (max-width: 600px) {
    .amfm-drawer {
        top: 0;
        height: 100%;
    }
}

.amfm-drawer.amfm-drawer-open {
    visibility: visible;
    opacity: 1;
}

.amfm-drawer-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.amfm-drawer-content {
    position: absolute;
    top: 0;
    right: -600px;
    width: 600px;
    height: 100%;
    background: #fff;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    transition: right 0.3s ease;
    overflow-y: auto;
}

.amfm-drawer-open .amfm-drawer-content {
    right: 0;
}

.amfm-drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
    position: sticky;
    top: 0;
    z-index: 10;
}

.amfm-drawer-header h2 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.amfm-drawer-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    line-height: 1;
}

.amfm-drawer-close:hover {
    color: #333;
}

.amfm-drawer-body {
    padding: 20px;
}

/* Component Actions */
.amfm-component-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 8px;
}

.amfm-info-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
    text-decoration: none;
}

.amfm-info-button:hover {
    background: #005a87;
    color: white;
}

/* Documentation Styles */
.amfm-shortcode-docs h3 {
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 8px;
    margin-top: 30px;
    margin-bottom: 15px;
}

.amfm-shortcode-docs h4 {
    color: #555;
    margin-top: 25px;
    margin-bottom: 12px;
}

.amfm-code-block {
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 12px;
    margin: 10px 0;
    font-family: 'Courier New', monospace;
}

.amfm-code-block code {
    background: none;
    padding: 0;
    font-size: 14px;
    color: #d14;
}

.amfm-attributes-list {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
}

.amfm-attribute {
    margin-bottom: 8px;
    padding: 8px;
    background: white;
    border-radius: 4px;
    border-left: 3px solid #0073aa;
}

.amfm-example {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 4px solid #46b450;
}

.amfm-example-code {
    margin-bottom: 8px;
}

.amfm-example-result {
    color: #666;
    font-style: italic;
}

.amfm-shortcode-note ul,
.amfm-usage-tips ul {
    margin-left: 20px;
}

.amfm-shortcode-note li,
.amfm-usage-tips li {
    margin-bottom: 8px;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .amfm-drawer-content {
        width: 90%;
        right: -90%;
    }
    
    .amfm-component-actions {
        flex-direction: column;
    }
    
    .amfm-info-button {
        justify-content: center;
    }
}
</style>