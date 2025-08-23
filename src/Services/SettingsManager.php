<?php

namespace App\Services;

class SettingsManager {
    
    /**
     * Handle excluded keywords update
     */
    public function handleExcludedKeywordsUpdate() {
        if (!isset($_POST['amfm_excluded_keywords_nonce']) || 
            !wp_verify_nonce($_POST['amfm_excluded_keywords_nonce'], 'amfm_excluded_keywords_update')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $excluded_keywords = isset($_POST['excluded_keywords']) ? 
            sanitize_textarea_field($_POST['excluded_keywords']) : '';
        
        // Convert to array, filtering out empty lines
        $keywords_array = array_filter(array_map('trim', explode("\n", $excluded_keywords)));
        
        update_option('amfm_excluded_keywords', $keywords_array);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Excluded keywords updated successfully!</p></div>';
        });
    }

    /**
     * Handle Elementor widgets update
     */
    public function handleElementorWidgetsUpdate() {
        if (!isset($_POST['amfm_elementor_widgets_nonce']) || 
            !wp_verify_nonce($_POST['amfm_elementor_widgets_nonce'], 'amfm_elementor_widgets_update')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $enabled_widgets = isset($_POST['enabled_widgets']) ? 
            array_map('sanitize_text_field', $_POST['enabled_widgets']) : [];
        
        update_option('amfm_elementor_enabled_widgets', $enabled_widgets);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Elementor widget settings updated successfully!</p></div>';
        });
    }

    /**
     * Handle component settings update
     */
    public function handleComponentSettingsUpdate() {
        if (!isset($_POST['amfm_component_settings_nonce']) || 
            !wp_verify_nonce($_POST['amfm_component_settings_nonce'], 'amfm_component_settings_update')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $enabled_components = isset($_POST['enabled_components']) ? 
            array_map('sanitize_text_field', $_POST['enabled_components']) : [];
        
        // Always ensure core components are included
        $core_components = ['acf_helper', 'import_export'];
        $enabled_components = array_merge($enabled_components, $core_components);
        $enabled_components = array_unique($enabled_components);
        
        update_option('amfm_enabled_components', $enabled_components);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Component settings updated successfully!</p></div>';
        });
    }

    /**
     * AJAX handler for component settings update
     */
    public function ajaxComponentSettingsUpdate() {
        if (!check_ajax_referer('amfm_component_settings_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $component_key = isset($_POST['component']) ? sanitize_text_field($_POST['component']) : '';
        $enabled = isset($_POST['enabled']) ? filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if (empty($component_key)) {
            wp_send_json_error('Invalid component');
            return;
        }

        $enabled_components = get_option('amfm_enabled_components', []);
        
        // Core components cannot be disabled
        $core_components = ['acf_helper', 'import_export'];
        if (in_array($component_key, $core_components)) {
            wp_send_json_error('Core components cannot be disabled');
            return;
        }

        if ($enabled && !in_array($component_key, $enabled_components)) {
            $enabled_components[] = $component_key;
        } elseif (!$enabled) {
            $enabled_components = array_diff($enabled_components, [$component_key]);
        }

        update_option('amfm_enabled_components', array_values($enabled_components));
        
        wp_send_json_success('Component status updated');
    }

    /**
     * AJAX handler for Elementor widgets update
     */
    public function ajaxElementorWidgetsUpdate() {
        if (!check_ajax_referer('amfm_elementor_widgets_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $widget_key = isset($_POST['widget']) ? sanitize_text_field($_POST['widget']) : '';
        $enabled = isset($_POST['enabled']) ? filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if (empty($widget_key)) {
            wp_send_json_error('Invalid widget');
            return;
        }

        $enabled_widgets = get_option('amfm_elementor_enabled_widgets', []);

        if ($enabled && !in_array($widget_key, $enabled_widgets)) {
            $enabled_widgets[] = $widget_key;
        } elseif (!$enabled) {
            $enabled_widgets = array_diff($enabled_widgets, [$widget_key]);
        }

        update_option('amfm_elementor_enabled_widgets', array_values($enabled_widgets));
        
        wp_send_json_success('Widget status updated');
    }
}