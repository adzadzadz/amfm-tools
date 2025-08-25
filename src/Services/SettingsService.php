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
        
        // Always ensure core components are included
        $components = array_merge($components, self::CORE_COMPONENTS);
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
        
        // Convert to array, filtering out empty lines
        $keywordsArray = array_filter(array_map('trim', explode("\n", $keywordsText)));
        
        return update_option('amfm_excluded_keywords', $keywordsArray) !== false;
    }

    /**
     * Get enabled components with defaults
     */
    public function getEnabledComponents(): array
    {
        // Default to all available components being enabled on first install
        $default_enabled = ['acf_helper', 'import_export', 'text_utilities', 'optimization', 'shortcodes', 'elementor_widgets'];
        $components = get_option('amfm_enabled_components');
        
        // If option doesn't exist, initialize it with all components enabled
        if ($components === false) {
            update_option('amfm_enabled_components', $default_enabled);
            $components = $default_enabled;
        }
        
        // Ensure core components are always included
        return array_unique(array_merge($components, self::CORE_COMPONENTS));
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
        $enabled = filter_var($_POST['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (empty($componentKey)) {
            wp_send_json_error('Invalid component');
        }

        // Core components cannot be disabled
        if (in_array($componentKey, self::CORE_COMPONENTS, true)) {
            wp_send_json_error('Core components cannot be disabled');
        }

        $enabledComponents = $this->getEnabledComponents();
        
        if ($enabled && !in_array($componentKey, $enabledComponents, true)) {
            $enabledComponents[] = $componentKey;
        } elseif (!$enabled) {
            $enabledComponents = array_diff($enabledComponents, [$componentKey]);
        }

        if ($this->updateComponentSettings($enabledComponents)) {
            wp_send_json_success('Component status updated');
        } else {
            wp_send_json_error('Failed to update component status');
        }
    }

    /**
     * AJAX handler for single widget toggle
     */
    public function ajaxToggleElementorWidget(): void
    {
        if (!check_ajax_referer('amfm_elementor_widgets_nonce', 'nonce', false) || 
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