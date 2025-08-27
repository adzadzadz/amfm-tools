<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'elementor';
$available_widgets = $available_widgets ?? [];
$enabled_widgets = $enabled_widgets ?? [];
?>

<!-- Elementor Tab Content -->
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
                                    <div class="amfm-component-actions">
                                        <button type="button" 
                                                class="amfm-info-button amfm-doc-button" 
                                                data-widget="<?php echo esc_attr($widget_key); ?>"
                                                onclick="openWidgetDrawer('<?php echo esc_attr($widget_key); ?>', 'documentation')">
                                            Documentation
                                        </button>
                                        <button type="button" 
                                                class="amfm-info-button amfm-config-button" 
                                                data-widget="<?php echo esc_attr($widget_key); ?>"
                                                onclick="openWidgetDrawer('<?php echo esc_attr($widget_key); ?>', 'config')">
                                            Config
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </form>
            </div>

<!-- Widget Documentation/Config Drawer -->
<div id="amfm-widget-drawer" class="amfm-drawer">
    <div class="amfm-drawer-overlay" onclick="closeWidgetDrawer()"></div>
    <div class="amfm-drawer-content">
        <div class="amfm-drawer-header">
            <h2 id="amfm-drawer-title">Widget Documentation</h2>
            <button type="button" class="amfm-drawer-close" onclick="closeWidgetDrawer()">&times;</button>
        </div>
        <div class="amfm-drawer-body" id="amfm-drawer-body">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
// Widget documentation and configuration data
const widgetData = {
    'amfm_related_posts': {
        name: 'AMFM Related Posts',
        description: 'Display related posts based on ACF keywords with customizable layouts and styling options.',
        documentation: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Overview:</h3>
                    <p>The AMFM Related Posts widget provides powerful content discovery features for your Elementor pages. It automatically finds and displays related posts based on ACF (Advanced Custom Fields) keywords.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Key Features:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Keyword Matching</strong> - Automatically finds related posts based on ACF keywords
                        </div>
                        <div class="amfm-attribute">
                            <strong>Multiple Layouts</strong> - Choose from grid, list, or carousel display options
                        </div>
                        <div class="amfm-attribute">
                            <strong>Customizable Styling</strong> - Full control over typography, colors, and spacing
                        </div>
                        <div class="amfm-attribute">
                            <strong>Query Controls</strong> - Filter by post type, category, tags, and custom taxonomies
                        </div>
                        <div class="amfm-attribute">
                            <strong>Performance Optimized</strong> - Efficient queries with built-in caching
                        </div>
                        <div class="amfm-attribute">
                            <strong>Responsive Design</strong> - Mobile-first approach with breakpoint controls
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>How to Use:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Step 1: Add Widget</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Open Elementor editor and search for "AMFM" in the widgets panel
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Step 2: Configure Settings</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Set keyword source, number of posts, layout options in the Content tab
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Step 3: Style Widget</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Customize appearance using the Style tab for colors, typography, and spacing
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>Requirements:</h3>
                    <ul>
                        <li>Elementor (Free or Pro) must be installed and active</li>
                        <li>Advanced Custom Fields (ACF) plugin must be active</li>
                        <li>Posts must have ACF keyword fields populated for matching</li>
                        <li>This widget must be enabled in the Elementor management section</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Best Practices:</h3>
                    <ul>
                        <li><strong>Keyword Strategy:</strong> Use descriptive, relevant keywords in your ACF fields</li>
                        <li><strong>Performance:</strong> Limit the number of displayed posts for better page speed</li>
                        <li><strong>Responsive:</strong> Test widget appearance on different screen sizes</li>
                        <li><strong>Content Quality:</strong> Ensure related posts have engaging titles and excerpts</li>
                        <li><strong>SEO Benefits:</strong> Related posts improve internal linking and user engagement</li>
                    </ul>
                </div>
            </div>
        `,
        config: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Widget Configuration:</h3>
                    <p>Configure the AMFM Related Posts widget settings through the Elementor editor panel.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Content Settings:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Keyword Source</strong> - Choose between 'AMFM Keywords', 'AMFM Other Keywords', or 'Both Fields'
                        </div>
                        <div class="amfm-attribute">
                            <strong>Number of Posts</strong> - Set how many related posts to display (default: 3)
                        </div>
                        <div class="amfm-attribute">
                            <strong>Post Types</strong> - Select which post types to include in results
                        </div>
                        <div class="amfm-attribute">
                            <strong>Exclude Current Post</strong> - Automatically exclude the current post from results
                        </div>
                        <div class="amfm-attribute">
                            <strong>Fallback Behavior</strong> - Choose what to show when no related posts are found
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Layout Settings:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Layout Type</strong> - Grid, List, or Carousel display options
                        </div>
                        <div class="amfm-attribute">
                            <strong>Columns</strong> - Number of columns for grid layout (1-4)
                        </div>
                        <div class="amfm-attribute">
                            <strong>Show Featured Image</strong> - Display post thumbnails
                        </div>
                        <div class="amfm-attribute">
                            <strong>Show Title</strong> - Display post titles with optional length limit
                        </div>
                        <div class="amfm-attribute">
                            <strong>Show Excerpt</strong> - Display post excerpts with customizable length
                        </div>
                        <div class="amfm-attribute">
                            <strong>Show Date</strong> - Display post publication date
                        </div>
                        <div class="amfm-attribute">
                            <strong>Show Author</strong> - Display post author information
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Style Configuration:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Typography</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Set font family, size, weight, and color for titles and content
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Spacing</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Control margins, padding, and gaps between elements
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Colors</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Customize background, text, and accent colors
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Responsive</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Set different configurations for tablet and mobile breakpoints
                        </div>
                    </div>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Configuration Tips:</h3>
                    <ul>
                        <li><strong>Start Simple:</strong> Begin with basic settings and gradually add complexity</li>
                        <li><strong>Test Thoroughly:</strong> Preview changes across different devices and screen sizes</li>
                        <li><strong>Performance Impact:</strong> More posts and complex layouts may affect page speed</li>
                        <li><strong>Content Strategy:</strong> Align widget settings with your content strategy and user needs</li>
                        <li><strong>A/B Testing:</strong> Try different configurations to see what works best for your audience</li>
                    </ul>
                </div>
            </div>
        `
    }
};

// Drawer functions
function openWidgetDrawer(widgetKey, mode = 'documentation') {
    const drawer = document.getElementById('amfm-widget-drawer');
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (widgetData[widgetKey]) {
        const data = widgetData[widgetKey];
        
        if (mode === 'documentation') {
            title.textContent = data.name + ' Documentation';
            body.innerHTML = data.documentation;
        } else if (mode === 'config') {
            title.textContent = data.name + ' Configuration';
            body.innerHTML = data.config;
        }
        
        drawer.classList.add('amfm-drawer-open');
        document.body.style.overflow = 'hidden';
    }
}

function closeWidgetDrawer() {
    const drawer = document.getElementById('amfm-widget-drawer');
    drawer.classList.remove('amfm-drawer-open');
    document.body.style.overflow = '';
}

// Close drawer with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeWidgetDrawer();
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

.amfm-config-button {
    background: #46b450;
}

.amfm-config-button:hover {
    background: #3d9c46;
}

/* Force 1/3 width for component cards even with single card */
.amfm-components-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    max-width: 1200px;
}

.amfm-component-card {
    max-width: 380px; /* Force maximum width to maintain 1/3 sizing */
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
    
    .amfm-components-grid {
        grid-template-columns: 1fr;
    }
    
    .amfm-component-card {
        max-width: none;
    }
}
</style>