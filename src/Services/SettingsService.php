<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * Settings Service - handles plugin settings and configuration
 * 
 * Centralizes settings management with proper validation and defaults
 */
class SettingsService extends Service
{
    /**
     * Core components that cannot be disabled
     */
    private const CORE_COMPONENTS = ['acf_helper', 'import_export'];

    /**
     * Update component settings
     */
    public function updateComponentSettings(array $components): bool
    {
        $components = array_map('sanitize_text_field', $components);
        $components = array_unique($components);
        
        return update_option('amfm_enabled_components', $components) !== false;
    }

    /**
     * Update Elementor widgets settings
     */
    public function updateElementorWidgets(array $widgets): bool
    {
        $widgets = array_map('sanitize_text_field', $widgets);
        
        return update_option('amfm_elementor_enabled_widgets', $widgets) !== false;
    }

    /**
     * Update excluded keywords
     */
    public function updateExcludedKeywords(string $keywordsText): bool
    {
        $keywordsText = sanitize_textarea_field($keywordsText);
        
        // Normalize line endings and convert to array, filtering out empty lines
        $keywordsText = str_replace(["\r\n", "\r"], "\n", $keywordsText);
        $keywordsArray = array_filter(array_map('trim', explode("\n", $keywordsText)), function($keyword) {
            return $keyword !== '';
        });
        
        return update_option('amfm_excluded_keywords', $keywordsArray) !== false;
    }

    /**
     * Get enabled components with defaults
     */
    public function getEnabledComponents(): array
    {
        $all_components = ['acf_helper', 'import_export', 'text_utilities', 'optimization', 'dkv_shortcode', 'limit_words', 'upload_limit'];
        $core_components = ['acf_helper', 'import_export'];
        $enabled_components = [];

        // Check each component's individual option
        foreach ($all_components as $component) {
            // Core components are always enabled
            if (in_array($component, $core_components)) {
                $enabled_components[] = $component;
                continue;
            }

            // Check component-specific option with default enabled for new installations
            $option_name = "amfm_components_{$component}";
            $is_enabled = get_option($option_name);

            // If option doesn't exist, set default to true and enable it
            if ($is_enabled === false) {
                update_option($option_name, true);
                $is_enabled = true;
            }

            // Convert string boolean to proper boolean (critical for live site compatibility)
            if (is_string($is_enabled)) {
                $is_enabled = $is_enabled === 'true' || $is_enabled === '1';
            } else {
                $is_enabled = (bool) $is_enabled;
            }

            if ($is_enabled) {
                $enabled_components[] = $component;
            }
        }

        return $enabled_components;
    }

    /**
     * Get enabled Elementor widgets
     */
    public function getEnabledElementorWidgets(): array
    {
        return get_option('amfm_elementor_enabled_widgets', []);
    }

    /**
     * Get excluded keywords
     */
    public function getExcludedKeywords(): array
    {
        return get_option('amfm_excluded_keywords', []);
    }

    /**
     * Get toggleable components (excludes core components)
     */
    public function getToggleableComponents(): array
    {
        $all_components = ['acf_helper', 'import_export', 'text_utilities', 'optimization', 'dkv_shortcode', 'limit_words', 'upload_limit'];
        $core_components = ['acf_helper', 'import_export'];
        return array_diff($all_components, $core_components);
    }

    /**
     * Check if a component is enabled
     */
    public function isComponentEnabled(string $component): bool
    {
        return in_array($component, $this->getEnabledComponents(), true);
    }

    /**
     * Check if an Elementor widget is enabled
     */
    public function isElementorWidgetEnabled(string $widget): bool
    {
        return in_array($widget, $this->getEnabledElementorWidgets(), true);
    }

    /**
     * Handle excluded keywords form submission
     */
    public function handleExcludedKeywordsUpdate(): void
    {
        if (!$this->verifyNonce('amfm_excluded_keywords_nonce', 'amfm_excluded_keywords_update') || 
            !current_user_can('manage_options')) {
            return;
        }

        $excludedKeywords = $_POST['excluded_keywords'] ?? '';
        
        if ($this->updateExcludedKeywords($excludedKeywords)) {
            $this->addSuccessNotice('Excluded keywords updated successfully!');
        } else {
            $this->addErrorNotice('Failed to update excluded keywords.');
        }
    }

    /**
     * Handle DKV configuration form submission
     */
    public function handleDkvConfigUpdate(): void
    {
        if (!$this->verifyNonce('amfm_dkv_config_nonce', 'amfm_dkv_config_update') || 
            !current_user_can('manage_options')) {
            return;
        }

        $excludedKeywords = $_POST['dkv_excluded_keywords'] ?? '';
        $defaultFallback = sanitize_text_field($_POST['dkv_default_fallback'] ?? '');
        $cacheDuration = absint($_POST['dkv_cache_duration'] ?? 24);
        
        $success = true;
        
        // Update excluded keywords
        if (!$this->updateExcludedKeywords($excludedKeywords)) {
            $success = false;
        }
        
        // Update other DKV settings
        if (!update_option('amfm_dkv_default_fallback', $defaultFallback)) {
            $success = false;
        }
        
        if (!update_option('amfm_dkv_cache_duration', $cacheDuration)) {
            $success = false;
        }
        
        if ($success) {
            $this->addSuccessNotice('DKV configuration updated successfully!');
        } else {
            $this->addErrorNotice('Failed to update DKV configuration.');
        }
    }

    /**
     * Handle Elementor widgets form submission
     */
    public function handleElementorWidgetsUpdate(): void
    {
        if (!$this->verifyNonce('amfm_elementor_widgets_nonce', 'amfm_elementor_widgets_update') || 
            !current_user_can('manage_options')) {
            return;
        }

        $enabledWidgets = $_POST['enabled_widgets'] ?? [];
        $enabledWidgets = is_array($enabledWidgets) ? $enabledWidgets : [];
        
        if ($this->updateElementorWidgets($enabledWidgets)) {
            $this->addSuccessNotice('Elementor widget settings updated successfully!');
        } else {
            $this->addErrorNotice('Failed to update Elementor widget settings.');
        }
    }

    /**
     * Get DKV default fallback
     */
    public function getDkvDefaultFallback(): string
    {
        return get_option('amfm_dkv_default_fallback', '');
    }

    /**
     * Get DKV cache duration
     */
    public function getDkvCacheDuration(): int
    {
        return get_option('amfm_dkv_cache_duration', 24);
    }

    /**
     * AJAX handler for DKV configuration update
     */
    public function ajaxDkvConfigUpdate(): void
    {
        // Verify nonce and capabilities
        if (!check_ajax_referer('amfm_dkv_config_update', 'amfm_dkv_config_nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
            return;
        }

        $excludedKeywords = $_POST['dkv_excluded_keywords'] ?? '';
        $defaultFallback = sanitize_text_field($_POST['dkv_default_fallback'] ?? '');
        $cacheDuration = absint($_POST['dkv_cache_duration'] ?? 24);
        
        // Update settings - trust the individual methods to handle validation
        $this->updateExcludedKeywords($excludedKeywords);
        update_option('amfm_dkv_default_fallback', $defaultFallback);
        update_option('amfm_dkv_cache_duration', $cacheDuration);
        
        wp_send_json_success(['message' => 'DKV configuration updated successfully!']);
    }

    /**
     * AJAX handler for Elementor widgets form update
     */
    public function ajaxElementorWidgetsUpdate(): void
    {
        if (!check_ajax_referer('amfm_elementor_widgets_update', 'amfm_elementor_widgets_nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $enabledWidgets = $_POST['enabled_widgets'] ?? [];
        $enabledWidgets = is_array($enabledWidgets) ? $enabledWidgets : [];
        
        if ($this->updateElementorWidgets($enabledWidgets)) {
            wp_send_json_success('Elementor widget settings updated successfully');
        } else {
            wp_send_json_error('Failed to update Elementor widget settings');
        }
    }

    /**
     * Handle component settings form submission
     */
    public function handleComponentSettingsUpdate(): void
    {
        if (!$this->verifyNonce('amfm_component_settings_nonce', 'amfm_component_settings_update') || 
            !current_user_can('manage_options')) {
            return;
        }

        $enabledComponents = $_POST['enabled_components'] ?? [];
        $enabledComponents = is_array($enabledComponents) ? $enabledComponents : [];
        
        if ($this->updateComponentSettings($enabledComponents)) {
            $this->addSuccessNotice('Component settings updated successfully!');
        } else {
            $this->addErrorNotice('Failed to update component settings.');
        }
    }

    /**
     * AJAX handler for component settings form update
     */
    public function ajaxComponentSettingsUpdate(): void
    {
        if (!check_ajax_referer('amfm_component_settings_update', 'amfm_component_settings_nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $enabledComponents = $_POST['enabled_components'] ?? [];
        $enabledComponents = is_array($enabledComponents) ? $enabledComponents : [];
        
        if ($this->updateComponentSettings($enabledComponents)) {
            wp_send_json_success('Component settings updated successfully');
        } else {
            wp_send_json_error('Failed to update component settings');
        }
    }

    /**
     * AJAX handler for single component toggle
     */
    public function ajaxToggleComponent(): void
    {
        if (!check_ajax_referer('amfm_component_settings_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $componentKey = sanitize_text_field($_POST['component'] ?? '');
        $enabled = filter_var($_POST['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        // Handle edge case where filter_var might not work as expected
        if ($enabled === null) {
            $enabled = ($_POST['enabled'] ?? '') === 'true' || ($_POST['enabled'] ?? '') === '1';
        }
        
        if (empty($componentKey)) {
            wp_send_json_error('Invalid component');
        }

        // Use framework config system with WordPress options persistence
        $config = \Adz::config();
        
        // Determine config path and WordPress option name based on component type
        $option_name = '';
        if (in_array($componentKey, ['dkv', 'limit_words', 'text_util'])) {
            $config->set("shortcodes.{$componentKey}", $enabled);
            $option_name = "amfm_shortcodes_{$componentKey}";
        } elseif (strpos($componentKey, '_widget') !== false) {
            $config->set("elementor.widgets.{$componentKey}", $enabled);
            $option_name = "amfm_elementor_widgets_{$componentKey}";
        } else {
            $config->set("components.{$componentKey}", $enabled);
            $option_name = "amfm_components_{$componentKey}";

            // Handle special component-specific logic
            if ($componentKey === 'upload_limit') {
                // Force update by deleting first to ensure it's saved properly
                delete_option('amfm_image_upload_limit_enabled');
                add_option('amfm_image_upload_limit_enabled', $enabled);
            }
        }

        // Force update by deleting and re-adding the option
        // This ensures the value is saved even if WordPress thinks it's unchanged
        delete_option($option_name);
        $update_success = add_option($option_name, $enabled);

        // Trigger shortcode re-registration if needed
        if (in_array($componentKey, ['dkv', 'limit_words', 'text_util'])) {
            do_action('amfm_shortcodes_changed');
        }

        // Verify the option was actually saved and return debug info
        $option_name = "amfm_components_{$componentKey}";
        if (in_array($componentKey, ['dkv', 'limit_words', 'text_util'])) {
            $option_name = "amfm_shortcodes_{$componentKey}";
        } elseif (strpos($componentKey, '_widget') !== false) {
            $option_name = "amfm_elementor_widgets_{$componentKey}";
        }

        $stored_value = get_option($option_name);

        wp_send_json_success([
            'message' => 'Component status updated',
            'component' => $componentKey,
            'requested_state' => $enabled,
            'stored_value' => $stored_value,
            'update_success' => $update_success,
            'option_name' => $option_name
        ]);
    }

    /**
     * AJAX handler for single widget toggle
     */
    public function ajaxToggleElementorWidget(): void
    {
        if (!check_ajax_referer('amfm_elementor_widgets_update', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $widgetKey = sanitize_text_field($_POST['widget'] ?? '');
        $enabled = filter_var($_POST['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (empty($widgetKey)) {
            wp_send_json_error('Invalid widget');
        }

        $enabledWidgets = $this->getEnabledElementorWidgets();

        if ($enabled && !in_array($widgetKey, $enabledWidgets, true)) {
            $enabledWidgets[] = $widgetKey;
        } elseif (!$enabled) {
            $enabledWidgets = array_diff($enabledWidgets, [$widgetKey]);
        }

        if ($this->updateElementorWidgets($enabledWidgets)) {
            wp_send_json_success('Widget status updated');
        } else {
            wp_send_json_error('Failed to update widget status');
        }
    }

    /**
     * Verify nonce
     */
    private function verifyNonce(string $nonceField, string $nonceAction): bool
    {
        return isset($_POST[$nonceField]) && wp_verify_nonce($_POST[$nonceField], $nonceAction);
    }

    /**
     * Add success notice
     */
    private function addSuccessNotice(string $message): void
    {
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
    }

    /**
     * Add error notice
     */
    private function addErrorNotice(string $message): void
    {
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
    }
}