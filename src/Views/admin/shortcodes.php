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

<!-- Modern Bootstrap 5 Shortcode Management -->
<div class="container-fluid px-0">
    <div class="row g-3">
        <!-- Main Content -->
        <div class="col-12">
            <!-- Shortcodes Grid -->
            <form method="post" id="amfm-shortcode-form">
                <?php wp_nonce_field('amfm_component_settings_update', 'amfm_component_settings_nonce'); ?>
                
                <div class="row g-3">
                    <?php if (empty($available_shortcodes)): ?>
                        <!-- No Shortcodes Available -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center py-5">
                                    <div class="text-muted mb-4">
                                        <i class="fas fa-code fs-1 opacity-25"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No Shortcodes Available</h5>
                                    <p class="text-muted small mb-0">No shortcodes are currently registered by this plugin.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($available_shortcodes as $shortcode_key => $shortcode_info): 
                            $is_core = $shortcode_info['status'] === 'Core Feature';
                            $is_enabled = in_array($shortcode_key, $enabled_shortcodes);
                        ?>
                            <div class="col-lg-6 col-xl-4">
                                <div class="card border-0 shadow-sm h-100 hover-lift">
                                    <div class="card-body p-4">
                                        <!-- Shortcode Header -->
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded bg-primary bg-opacity-10 p-2 me-3">
                                                    <i class="<?php echo esc_attr($shortcode_info['icon'] ?? 'fas fa-code'); ?> text-primary" style="font-size: 1.25rem;"></i>
                                                </div>
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?php echo esc_html($shortcode_info['name']); ?></h5>
                                                </div>
                                            </div>
                                            
                                            <!-- Toggle Switch -->
                                            <?php if ($is_core): ?>
                                                <span class="badge bg-warning text-dark px-3 py-2">Core</span>
                                                <input type="hidden" name="enabled_components[]" value="<?php echo esc_attr($shortcode_key); ?>">
                                            <?php else: ?>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input shortcode-toggle" 
                                                           type="checkbox" 
                                                           role="switch"
                                                           id="shortcode-<?php echo esc_attr($shortcode_key); ?>"
                                                           name="enabled_components[]" 
                                                           value="<?php echo esc_attr($shortcode_key); ?>"
                                                           <?php checked($is_enabled); ?>
                                                           style="cursor: pointer;">
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Shortcode Description -->
                                        <p class="text-muted mb-3 small"><?php echo esc_html($shortcode_info['description']); ?></p>

                                        <!-- Status Badge -->
                                        <div class="mb-3">
                                            <?php if ($is_core): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                    Always Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge <?php echo $is_enabled ? 'bg-success' : 'bg-secondary'; ?> bg-opacity-10 <?php echo $is_enabled ? 'text-success' : 'text-secondary'; ?> px-3 py-2">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                    <?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2 mt-auto">
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm flex-fill"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#amfm-shortcode-drawer"
                                                    onclick="loadShortcodeContent('<?php echo esc_attr($shortcode_key); ?>', 'documentation')">
                                                <i class="fas fa-book me-1"></i>
                                                Docs
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm flex-fill"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#amfm-shortcode-drawer"
                                                    onclick="loadShortcodeContent('<?php echo esc_attr($shortcode_key); ?>', 'config')">
                                                <i class="fas fa-cog me-1"></i>
                                                Config
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Shortcode Documentation/Config Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="amfm-shortcode-drawer" aria-labelledby="amfm-drawer-title">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="amfm-drawer-title">Shortcode Documentation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="amfm-drawer-body">
        <!-- Content will be loaded dynamically -->
    </div>
</div>

<script>
// Localize AJAX data
const amfm_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    shortcode_nonce: '<?php echo wp_create_nonce('amfm_component_settings_nonce'); ?>',
    dkv_config_nonce: '<?php echo wp_create_nonce('amfm_dkv_config_update'); ?>'
};

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

// Shortcode content functions
function loadShortcodeContent(shortcodeKey, mode = 'documentation') {
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (shortcodeData[shortcodeKey]) {
        const data = shortcodeData[shortcodeKey];
        
        if (mode === 'config') {
            title.textContent = data.name + ' Configuration';
            body.innerHTML = getConfigContent(shortcodeKey, data);
            
            // Reinitialize field values after loading config content
            if (shortcodeKey === 'dkv_shortcode') {
                reinitializeDkvFields();
            }
        } else {
            title.textContent = data.name + ' Documentation';
            body.innerHTML = data.content;
        }
    }
}

// Reinitialize DKV field values with current data
function reinitializeDkvFields() {
    // Use setTimeout to ensure DOM elements are rendered
    setTimeout(() => {
        // Fetch current values from server to ensure we have the latest data
        fetchCurrentDkvValues().then((values) => {
            // Update excluded keywords
            const keywordsField = document.getElementById('dkv_excluded_keywords');
            if (keywordsField) {
                keywordsField.value = values.keywords || '';
            }
            
            // Update default fallback
            const fallbackField = document.getElementById('dkv_default_fallback');
            if (fallbackField) {
                fallbackField.value = values.fallback || '';
            }
            
            // Update cache duration
            const cacheField = document.getElementById('dkv_cache_duration');
            if (cacheField) {
                cacheField.value = values.cacheDuration || '24';
            }
            
            console.log('DKV fields reinitialized with current values from server');
        }).catch((error) => {
            console.error('Failed to fetch current DKV values:', error);
            // Fallback to PHP data if server fetch fails
            const keywordsField = document.getElementById('dkv_excluded_keywords');
            if (keywordsField && amfmPhpData.currentKeywords !== undefined) {
                keywordsField.value = amfmPhpData.currentKeywords;
            }
            
            const fallbackField = document.getElementById('dkv_default_fallback');
            if (fallbackField && amfmPhpData.currentFallback !== undefined) {
                fallbackField.value = amfmPhpData.currentFallback;
            }
            
            const cacheField = document.getElementById('dkv_cache_duration');
            if (cacheField && amfmPhpData.currentCacheDuration !== undefined) {
                cacheField.value = amfmPhpData.currentCacheDuration;
            }
            
            console.log('DKV fields reinitialized with fallback PHP values');
        });
    }, 100);
}

// Fetch current DKV values from server
function fetchCurrentDkvValues() {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('action', 'amfm_get_dkv_config');
        formData.append('nonce', amfm_ajax.dkv_config_nonce);
        
        fetch(amfm_ajax.ajax_url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resolve({
                    keywords: data.data.keywords || '',
                    fallback: data.data.fallback || '',
                    cacheDuration: data.data.cache_duration || '24'
                });
            } else {
                reject(new Error(data.data || 'Failed to fetch DKV config'));
            }
        })
        .catch(error => {
            reject(error);
        });
    });
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

// Individual shortcode toggle functionality using dedicated AJAX endpoint
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.shortcode-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function(e) {
            const shortcodeKey = this.value;
            const isEnabled = this.checked;
            const card = this.closest('.card');
            const statusBadge = card.querySelector('.badge');
            const nonceValue = amfm_ajax.shortcode_nonce;
            
            console.log('Toggle changed:', shortcodeKey, 'to', isEnabled ? 'enabled' : 'disabled');
            console.log('Using nonce:', nonceValue);
            
            // Update status badge immediately for better UX
            if (isEnabled) {
                statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
            } else {
                statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
            }
            
            // Prepare AJAX data for individual shortcode toggle
            const formData = new FormData();
            formData.append('action', 'amfm_toggle_shortcode');
            formData.append('component', shortcodeKey);
            formData.append('enabled', isEnabled ? '1' : '0');
            formData.append('nonce', nonceValue);
            
            // Send AJAX request to toggle individual shortcode
            fetch(amfm_ajax?.ajax_url || ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Shortcode status updated successfully');
                    
                    // Add brief success pulse effect
                    statusBadge.style.opacity = '0.7';
                    setTimeout(() => {
                        statusBadge.style.opacity = '1';
                    }, 200);
                } else {
                    // Error - revert toggle state
                    this.checked = !isEnabled;
                    
                    // Revert status badge
                    if (!isEnabled) {
                        statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                        statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
                    } else {
                        statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                        statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
                    }
                    
                    console.error('Failed to update shortcode status:', data.data || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                
                // Revert toggle state on error
                this.checked = !isEnabled;
                
                // Revert status badge
                if (!isEnabled) {
                    statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                    statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
                } else {
                    statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                    statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
                }
            });
        });
    });
    
    // Prevent form submission to avoid page redirects
    const form = document.getElementById('amfm-shortcode-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Initialize Bootstrap offcanvas
    const offcanvasElement = document.getElementById('amfm-shortcode-drawer');
    if (offcanvasElement && typeof bootstrap !== 'undefined') {
        new bootstrap.Offcanvas(offcanvasElement);
    }
});
</script>

<style>
/* Modern Shortcode Management Styles */
#amfm-shortcode-drawer {
    width: 600px;
    top: 32px; /* Account for WordPress admin bar */
    height: calc(100vh - 32px);
}

/* Responsive admin bar height adjustments */
@media screen and (max-width: 782px) {
    #amfm-shortcode-drawer {
        top: 46px;
        height: calc(100vh - 46px);
    }
}

@media screen and (max-width: 600px) {
    #amfm-shortcode-drawer {
        top: 0;
        height: 100vh;
        width: 90%;
    }
}

/* Offcanvas header styling */
#amfm-shortcode-drawer .offcanvas-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#amfm-shortcode-drawer .offcanvas-title {
    font-size: 18px;
    color: #333;
    margin: 0;
}

/* Card Animations */
.hover-lift {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
}

/* Enhanced Bootstrap 5 Form Switch */
.form-check.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    margin-left: -3.5em;
    background-color: #6c757d;
    border-color: #6c757d;
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
    background-position: left center !important;
    background-repeat: no-repeat !important;
    background-size: contain !important;
    border-radius: 3em !important;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.form-check.form-switch .form-check-input:checked {
    background-position: right center !important;
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
}

.form-check.form-switch .form-check-input:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-check.form-switch .form-check-input:focus:not(:checked) {
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
}

.form-check.form-switch .form-check-input:checked:focus {
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
}

/* Remove WordPress/Admin checkmark */
.form-check.form-switch .form-check-input::before,
.form-check.form-switch .form-check-input:checked::before {
    content: none !important;
    display: none !important;
}

.documentation-content .card {
    border: 1px solid rgba(0,0,0,0.1) !important;
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
    .amfm-component-actions {
        flex-direction: column;
    }
    
    .amfm-info-button {
        justify-content: center;
    }
}
</style>