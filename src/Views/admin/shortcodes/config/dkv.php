<?php
if (!defined('ABSPATH')) exit;

// Get current settings
$settingsService = new \App\Services\SettingsService();
$currentKeywords = implode("\n", $settingsService->getExcludedKeywords());
$currentFallback = $settingsService->getDkvDefaultFallback();
$currentCacheDuration = $settingsService->getDkvCacheDuration();
?>

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
                ><?php echo esc_textarea($currentKeywords); ?></textarea>
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
                    value="<?php echo esc_attr($currentFallback); ?>"
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
                    <option value="1" <?php selected($currentCacheDuration, 1); ?>>1 hour</option>
                    <option value="6" <?php selected($currentCacheDuration, 6); ?>>6 hours</option>
                    <option value="24" <?php selected($currentCacheDuration, 24); ?>>24 hours (default)</option>
                    <option value="72" <?php selected($currentCacheDuration, 72); ?>>3 days</option>
                    <option value="168" <?php selected($currentCacheDuration, 168); ?>>1 week</option>
                </select>
            </div>
            
        </div>
    </div>
</div>