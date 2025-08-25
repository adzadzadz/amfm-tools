<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'shortcodes';
$available_shortcodes = $available_shortcodes ?? [];
$enabled_shortcodes = $enabled_shortcodes ?? [];
$excluded_keywords = $excluded_keywords ?? [];
$keywords_text = $keywords_text ?? '';

// Get DKV config values
$settingsService = new \App\Services\SettingsService();
$currentFallback = $settingsService->getDkvDefaultFallback();
$currentCacheDuration = $settingsService->getDkvCacheDuration();
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


        <!-- Shortcodes Content -->
        <div class="amfm-tab-content">
            <!-- Shortcode Management Section -->
            <div class="amfm-shortcodes-section">
                <div class="amfm-shortcodes-header">
                    <h2>
                        <span class="amfm-shortcodes-icon">📄</span>
                        Shortcode Management
                    </h2>
                    <p>Enable or disable individual shortcodes. Disabled shortcodes will not be loaded, improving performance and reducing resource usage.</p>
                </div>

                <form method="post" class="amfm-component-settings-form">
                    <?php wp_nonce_field('amfm_component_settings_update', 'amfm_component_settings_nonce'); ?>
                    
                    <div class="amfm-components-grid">
                        <?php foreach ($available_shortcodes as $shortcode_key => $shortcode_info) : ?>
                            <?php 
                            $is_core = $shortcode_info['status'] === 'Core Feature';
                            $is_enabled = in_array($shortcode_key, $enabled_shortcodes);
                            ?>
                            <div class="amfm-component-card <?php echo $is_enabled ? 'amfm-component-enabled' : 'amfm-component-disabled'; ?> <?php echo $is_core ? 'amfm-component-core' : ''; ?>">
                                <div class="amfm-component-header">
                                    <div class="amfm-component-icon"><?php echo esc_html($shortcode_info['icon']); ?></div>
                                    <div class="amfm-component-toggle">
                                        <?php if ($is_core) : ?>
                                            <span class="amfm-core-label">Core</span>
                                            <input type="hidden" name="enabled_components[]" value="<?php echo esc_attr($shortcode_key); ?>">
                                        <?php else : ?>
                                            <label class="amfm-toggle-switch">
                                                <input type="checkbox" 
                                                       name="enabled_components[]" 
                                                       value="<?php echo esc_attr($shortcode_key); ?>"
                                                       <?php checked(in_array($shortcode_key, $enabled_shortcodes)); ?>
                                                       class="amfm-component-checkbox"
                                                       data-component="<?php echo esc_attr($shortcode_key); ?>">
                                                <span class="amfm-toggle-slider"></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="amfm-component-body">
                                    <h3 class="amfm-component-title"><?php echo esc_html($shortcode_info['name']); ?></h3>
                                    <p class="amfm-component-description"><?php echo esc_html($shortcode_info['description']); ?></p>
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
                                                data-shortcode="<?php echo esc_attr($shortcode_key); ?>"
                                                onclick="openShortcodeDrawer('<?php echo esc_attr($shortcode_key); ?>', 'documentation')">
                                            Documentation
                                        </button>
                                        <button type="button" 
                                                class="amfm-info-button amfm-config-button" 
                                                data-shortcode="<?php echo esc_attr($shortcode_key); ?>"
                                                onclick="openShortcodeDrawer('<?php echo esc_attr($shortcode_key); ?>', 'config')">
                                            Config
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

<!-- Shortcode Documentation Drawer -->
<div id="amfm-shortcode-drawer" class="amfm-drawer">
    <div class="amfm-drawer-overlay" onclick="closeShortcodeDrawer()"></div>
    <div class="amfm-drawer-content">
        <div class="amfm-drawer-header">
            <h2 id="amfm-drawer-title">Shortcode Documentation</h2>
            <button type="button" class="amfm-drawer-close" onclick="closeShortcodeDrawer()">&times;</button>
        </div>
        <div class="amfm-drawer-body" id="amfm-drawer-body">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<script>
// PHP data for JavaScript
const amfmPhpData = {
    currentKeywords: <?php echo json_encode($keywords_text ?? ''); ?>,
    currentFallback: <?php echo json_encode($currentFallback); ?>,
    currentCacheDuration: <?php echo $currentCacheDuration; ?>
};

// Shortcode documentation data
const shortcodeData = {
    'dkv_shortcode': {
        name: 'DKV Shortcode',
        shortcode: '[dkv]',
        description: 'Displays a random keyword from your stored keywords with customizable formatting.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Basic Usage:</h3>
                    <div class="amfm-code-block">
                        <code>[dkv]</code>
                    </div>
                    <p>Returns a random keyword from the regular keywords.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Available Attributes:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>pre</strong> - Text to display before the keyword (default: empty)
                        </div>
                        <div class="amfm-attribute">
                            <strong>post</strong> - Text to display after the keyword (default: empty)
                        </div>
                        <div class="amfm-attribute">
                            <strong>fallback</strong> - Text to display if no keyword is available (default: empty)
                        </div>
                        <div class="amfm-attribute">
                            <strong>other_keywords</strong> - Use other keywords instead of regular keywords (default: false)
                        </div>
                        <div class="amfm-attribute">
                            <strong>include</strong> - Only show keywords from specified categories (comma-separated)
                        </div>
                        <div class="amfm-attribute">
                            <strong>exclude</strong> - Hide keywords from specified categories (comma-separated)
                        </div>
                        <div class="amfm-attribute">
                            <strong>text</strong> - Transform keyword case: lowercase, uppercase, capitalize
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Examples:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[dkv pre="Best " post=" services"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "Best web design services" (if "web design" is a keyword)
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[dkv other_keywords="true" pre="Learn " post=" today"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "Learn WordPress today" (using other keywords)
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[dkv fallback="digital marketing"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → Shows a random keyword, or "digital marketing" if none available
                        </div>
                    </div>

                    <h4>Category Filtering Examples:</h4>
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[dkv include="i"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "BCBS" (only shows insurance keywords, strips "i:" prefix)
                        </div>
                    </div>
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[dkv include="i,c,v" text="lowercase"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "depression" (insurance, condition, or vendor keywords in lowercase)
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>How It Works:</h3>
                    <ul>
                        <li>Keywords are stored in browser cookies when visiting pages with ACF keyword fields</li>
                        <li>Regular keywords come from the "amfm_keywords" field</li>
                        <li>Other keywords come from the "amfm_other_keywords" field</li>
                        <li><strong>Category Format:</strong> Keywords can be categorized using "category:keyword" format (e.g., "i:BCBS", "c:Depression")</li>
                        <li><strong>Category Filtering:</strong> Use include/exclude to filter by categories; prefixes are automatically stripped for display</li>
                        <li><strong>Text Transformation:</strong> Apply CSS-like text transformations (lowercase, uppercase, capitalize)</li>
                        <li>Keywords are automatically filtered using the global exclusion list</li>
                        <li>A random keyword is selected each time the shortcode is displayed</li>
                        <li>Spaces in pre/post attributes are preserved (e.g., pre=" " will add a space)</li>
                        <li>If no keywords are available and no fallback is set, nothing is displayed</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Usage Tips:</h3>
                    <ul>
                        <li>Use the shortcode in posts, pages, widgets, and theme files</li>
                        <li>Keywords are updated automatically when users visit pages</li>
                        <li>Set meaningful fallback text for better user experience</li>
                        <li>Use pre/post attributes to create natural sentences</li>
                        <li>The other_keywords attribute gives you access to alternative keyword sets</li>
                        <li><strong>Category Organization:</strong> Store keywords with prefixes like "i:Insurance" or "c:Condition" for better organization</li>
                        <li><strong>Smart Filtering:</strong> Combine include/exclude with other attributes for targeted content</li>
                        <li><strong>Case Consistency:</strong> Use text attribute to maintain consistent formatting across your site</li>
                        <li>Keywords are automatically filtered using the exclusion list</li>
                    </ul>
                </div>
            </div>
        `
    },
    'limit_words': {
        name: 'Limit Words',
        shortcode: '[limit_words]',
        description: 'Text processing shortcode for content formatting and word limiting.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Basic Usage:</h3>
                    <div class="amfm-code-block">
                        <code>[limit_words content="Your long text here" limit="10"]</code>
                    </div>
                    <p>Limits the specified content to a maximum number of words.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Available Attributes:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>content</strong> - The text content to limit (required)
                        </div>
                        <div class="amfm-attribute">
                            <strong>limit</strong> - Maximum number of words to display (default: 20)
                        </div>
                        <div class="amfm-attribute">
                            <strong>more</strong> - Text to append when content is truncated (default: "...")
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Examples:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[limit_words content="This is a very long sentence that needs to be shortened." limit="5"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "This is a very long..."
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[limit_words content="Short text" limit="10"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "Short text" (no truncation needed)
                        </div>
                    </div>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Usage Tips:</h3>
                    <ul>
                        <li>Perfect for excerpt creation and content previews</li>
                        <li>Useful in loops and widget areas with space constraints</li>
                        <li>Maintains word boundaries - never cuts words in half</li>
                        <li>Customize the "more" indicator to match your site's style</li>
                    </ul>
                </div>
            </div>
        `
    },
    'text_utilities': {
        name: 'Text Utilities',
        shortcode: '[text_util]',
        description: 'Collection of text processing and formatting shortcodes.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Basic Usage:</h3>
                    <div class="amfm-code-block">
                        <code>[text_util action="uppercase" content="your text here"]</code>
                    </div>
                    <p>Applies various text transformations and utilities to content.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Available Actions:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>uppercase</strong> - Convert text to UPPERCASE
                        </div>
                        <div class="amfm-attribute">
                            <strong>lowercase</strong> - Convert text to lowercase
                        </div>
                        <div class="amfm-attribute">
                            <strong>capitalize</strong> - Convert Text To Title Case
                        </div>
                        <div class="amfm-attribute">
                            <strong>trim</strong> - Remove leading and trailing whitespace
                        </div>
                        <div class="amfm-attribute">
                            <strong>word_count</strong> - Return the number of words
                        </div>
                        <div class="amfm-attribute">
                            <strong>char_count</strong> - Return the number of characters
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Examples:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[text_util action="capitalize" content="hello world"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "Hello World"
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <code>[text_util action="word_count" content="This is a test sentence"]</code>
                        </div>
                        <div class="amfm-example-result">
                            → "5"
                        </div>
                    </div>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Usage Tips:</h3>
                    <ul>
                        <li>Chain multiple utilities together for complex transformations</li>
                        <li>Useful for dynamic content formatting</li>
                        <li>Great for creating consistent text styling across your site</li>
                        <li>Word and character counts are useful for content analytics</li>
                    </ul>
                </div>
            </div>
        `
    }
};

// Drawer functions
function openShortcodeDrawer(shortcodeKey, mode = 'documentation') {
    const drawer = document.getElementById('amfm-shortcode-drawer');
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (shortcodeData[shortcodeKey]) {
        const data = shortcodeData[shortcodeKey];
        
        if (mode === 'config') {
            title.textContent = data.name + ' Configuration';
            body.innerHTML = getConfigContent(shortcodeKey, data);
        } else {
            title.textContent = data.name + ' Documentation';
            body.innerHTML = data.content;
        }
        
        drawer.classList.add('amfm-drawer-open');
        document.body.style.overflow = 'hidden';
    }
}

function getConfigContent(shortcodeKey, data) {
    if (shortcodeKey === 'dkv_shortcode') {
        // Get current values from PHP data
        const currentKeywords = amfmPhpData.currentKeywords;
        const currentFallback = amfmPhpData.currentFallback;
        const currentCacheDuration = amfmPhpData.currentCacheDuration;
        
        return `
            <div class="amfm-shortcode-config">
                <div class="amfm-config-section">
                    <h3>DKV Shortcode Configuration</h3>
                    <p>Configure global settings that affect how the DKV shortcode behaves across your site.</p>
                    
                    <div class="amfm-shortcode-config-form" id="dkv-config-form">
                        
                        <div class="amfm-config-group">
                            <label for="dkv_excluded_keywords">
                                <strong>Excluded Keywords</strong>
                                <span class="amfm-config-description">Keywords to exclude from all DKV shortcode output (one per line)</span>
                            </label>
                            <div class="amfm-field-status" id="dkv_excluded_keywords_status"></div>
                            <textarea 
                                id="dkv_excluded_keywords" 
                                name="dkv_excluded_keywords" 
                                rows="10" 
                                class="amfm-config-textarea"
                                placeholder="Enter keywords to exclude, one per line..."
                                onchange="autoSaveDkvField('dkv_excluded_keywords')"
                                onblur="autoSaveDkvField('dkv_excluded_keywords')"
                            >${currentKeywords}</textarea>
                        </div>
                        
                        <div class="amfm-config-group">
                            <label for="dkv_default_fallback">
                                <strong>Default Fallback Text</strong>
                                <span class="amfm-config-description">Default text to show when no keywords are available (optional)</span>
                            </label>
                            <div class="amfm-field-status" id="dkv_default_fallback_status"></div>
                            <input 
                                type="text" 
                                id="dkv_default_fallback" 
                                name="dkv_default_fallback" 
                                class="amfm-config-input"
                                placeholder="e.g., your business name"
                                value="${currentFallback}"
                                onchange="autoSaveDkvField('dkv_default_fallback')"
                                onblur="autoSaveDkvField('dkv_default_fallback')"
                            />
                        </div>
                        
                        <div class="amfm-config-group">
                            <label for="dkv_cache_duration">
                                <strong>Cache Duration (hours)</strong>
                                <span class="amfm-config-description">How long to cache keywords in browser cookies</span>
                            </label>
                            <div class="amfm-field-status" id="dkv_cache_duration_status"></div>
                            <select id="dkv_cache_duration" name="dkv_cache_duration" class="amfm-config-select" onchange="autoSaveDkvField('dkv_cache_duration')">
                                <option value="1" ${currentCacheDuration == 1 ? 'selected' : ''}>1 hour</option>
                                <option value="6" ${currentCacheDuration == 6 ? 'selected' : ''}>6 hours</option>
                                <option value="24" ${currentCacheDuration == 24 ? 'selected' : ''}>24 hours (default)</option>
                                <option value="72" ${currentCacheDuration == 72 ? 'selected' : ''}>3 days</option>
                                <option value="168" ${currentCacheDuration == 168 ? 'selected' : ''}>1 week</option>
                            </select>
                        </div>
                        
                    </div>
                </div>
            </div>
        `;
    }
    
    // Default config for other shortcodes
    return `
        <div class="amfm-shortcode-config">
            <div class="amfm-config-section">
                <h3>${data.name} Configuration</h3>
                <p>Configuration options for ${data.name} will be available in a future update.</p>
                
                <div class="amfm-config-placeholder">
                    <h4>Coming Soon</h4>
                    <p>Advanced configuration options for this shortcode are being developed.</p>
                </div>
            </div>
        </div>
    `;
}


// Auto-save individual DKV config fields
function autoSaveDkvField(fieldId) {
    const field = document.getElementById(fieldId);
    const statusDiv = document.getElementById(fieldId + '_status');
    
    if (!field || !statusDiv) return;
    
    // Show saving status
    showFieldStatus(fieldId, 'saving', 'Saving...');
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'amfm_dkv_config_update');
    formData.append('amfm_dkv_config_nonce', amfm_ajax.dkv_config_nonce);
    
    // Add the specific field value
    if (fieldId === 'dkv_excluded_keywords') {
        formData.append('dkv_excluded_keywords', field.value);
        // Include other current values to avoid overwriting
        formData.append('dkv_default_fallback', document.getElementById('dkv_default_fallback')?.value || '');
        formData.append('dkv_cache_duration', document.getElementById('dkv_cache_duration')?.value || '24');
    } else if (fieldId === 'dkv_default_fallback') {
        formData.append('dkv_default_fallback', field.value);
        // Include other current values
        formData.append('dkv_excluded_keywords', document.getElementById('dkv_excluded_keywords')?.value || '');
        formData.append('dkv_cache_duration', document.getElementById('dkv_cache_duration')?.value || '24');
    } else if (fieldId === 'dkv_cache_duration') {
        formData.append('dkv_cache_duration', field.value);
        // Include other current values
        formData.append('dkv_excluded_keywords', document.getElementById('dkv_excluded_keywords')?.value || '');
        formData.append('dkv_default_fallback', document.getElementById('dkv_default_fallback')?.value || '');
    }
    
    fetch(amfm_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFieldStatus(fieldId, 'success', 'Saved');
            // Auto-hide success message after 3 seconds
            setTimeout(() => {
                hideFieldStatus(fieldId);
            }, 3000);
        } else {
            const errorMessage = data.data?.message || data.message || 'Save failed';
            showFieldStatus(fieldId, 'error', errorMessage);
            console.error('DKV Field Save Error:', data);
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        showFieldStatus(fieldId, 'error', 'Network error');
    });
}

// Field status functions
function showFieldStatus(fieldId, type, message) {
    const statusDiv = document.getElementById(fieldId + '_status');
    if (!statusDiv) return;
    
    statusDiv.className = `amfm-field-status amfm-field-status-${type}`;
    statusDiv.textContent = message;
    statusDiv.style.display = 'block';
}

function hideFieldStatus(fieldId) {
    const statusDiv = document.getElementById(fieldId + '_status');
    if (!statusDiv) return;
    
    statusDiv.style.display = 'none';
    statusDiv.textContent = '';
    statusDiv.className = 'amfm-field-status';
}

function closeShortcodeDrawer() {
    const drawer = document.getElementById('amfm-shortcode-drawer');
    drawer.classList.remove('amfm-drawer-open');
    document.body.style.overflow = '';
}

// Close drawer with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeShortcodeDrawer();
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
    background: #3a9540;
}

/* Field Status Indicators */
.amfm-field-status {
    display: none;
    padding: 6px 8px;
    margin: 4px 0;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    animation: fadeIn 0.2s ease-out;
}

.amfm-field-status-saving {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.amfm-field-status-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.amfm-field-status-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Config Styles */
.amfm-shortcode-config {
    max-width: 100%;
}

.amfm-config-section {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.amfm-config-group {
    margin-bottom: 20px;
}

.amfm-config-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.amfm-config-description {
    display: block;
    font-weight: 400;
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

.amfm-config-textarea,
.amfm-config-input,
.amfm-config-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.2s ease;
}

.amfm-config-textarea:focus,
.amfm-config-input:focus,
.amfm-config-select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
}

.amfm-config-textarea {
    resize: vertical;
    min-height: 120px;
    font-family: 'Courier New', monospace;
}


.amfm-config-placeholder {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.amfm-config-placeholder h4 {
    margin: 0 0 12px 0;
    color: #333;
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