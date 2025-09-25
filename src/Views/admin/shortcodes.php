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
                                                    onclick="loadShortcodeContent('<?php echo esc_attr($shortcode_key); ?>', 'docs')"
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
// Wait for amfm_ajax to be available or create a temporary placeholder
if (typeof amfm_ajax === 'undefined') {
    // Create temporary object that will be overridden by wp_localize_script
    window.amfm_ajax = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        shortcode_nonce: '<?php echo wp_create_nonce('amfm_component_settings_nonce'); ?>',
        shortcode_content_nonce: '<?php echo wp_create_nonce('amfm_shortcode_content'); ?>',
        dkv_config_nonce: '<?php echo wp_create_nonce('amfm_dkv_config_update'); ?>'
    };
}

// PHP data for JavaScript
const amfmPhpData = {
    currentKeywords: <?php echo json_encode($keywords_text ?? ''); ?>,
    currentFallback: <?php echo json_encode($currentFallback); ?>,
    currentCacheDuration: <?php echo $currentCacheDuration; ?>
};

// Shortcode content functions - AJAX-based loading
function loadShortcodeContent(shortcodeKey, mode = 'docs') {
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    // Show loading state
    title.textContent = 'Loading...';
    body.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Loading content...</div>';
    
    // Prepare AJAX data
    const formData = new FormData();
    formData.append('action', 'amfm_load_shortcode_content');
    formData.append('shortcode_key', shortcodeKey);
    formData.append('mode', mode);
    formData.append('nonce', amfm_ajax.shortcode_content_nonce);
    
    fetch(amfm_ajax.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            title.textContent = data.data.title;
            body.innerHTML = data.data.content;
            
            // Reinitialize any dynamic content
            if (mode === 'config' && shortcodeKey === 'dkv') {
                reinitializeDkvFields();
            }
        } else {
            title.textContent = 'Error';
            body.innerHTML = '<div class="alert alert-danger">Failed to load content: ' + (data.data || 'Unknown error') + '</div>';
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        title.textContent = 'Error';
        body.innerHTML = '<div class="alert alert-danger">Network error occurred</div>';
    });
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