<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * Ajax Service - handles AJAX requests and responses
 * 
 * Centralizes AJAX handling for the plugin with proper security and validation
 */
class AjaxService extends Service
{
    /**
     * Handle AJAX request for getting taxonomies for a post type
     */
    public function getPostTypeTaxonomies(): void
    {
        // Verify nonce and permissions
        if (!$this->verifyAjaxNonce('amfm_export_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Validate post type parameter
        $postType = $this->getPostParameter('post_type');
        if (!$postType) {
            wp_send_json_error('No post type provided');
        }

        // Get taxonomies for the post type
        $taxonomies = get_object_taxonomies($postType, 'objects');
        
        if (empty($taxonomies)) {
            wp_send_json_error('No taxonomies found');
        }

        // Format response
        $formattedTaxonomies = [];
        foreach ($taxonomies as $taxonomy) {
            $formattedTaxonomies[] = [
                'name' => $taxonomy->name,
                'label' => $taxonomy->label
            ];
        }

        wp_send_json_success($formattedTaxonomies);
    }
    
    /**
     * Handle AJAX request for getting ACF field groups
     */
    public function getAcfFieldGroups(): void
    {
        // Verify nonce and permissions
        if (!$this->verifyAjaxNonce('amfm_export_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check if ACF is available
        if (!function_exists('acf_get_field_groups')) {
            wp_send_json_error('ACF not available');
        }

        // Get all ACF field groups
        $fieldGroups = acf_get_field_groups();
        
        if (empty($fieldGroups)) {
            wp_send_json_error('No ACF field groups found');
        }

        // Format response
        $formattedGroups = [];
        foreach ($fieldGroups as $group) {
            $formattedGroups[] = [
                'key' => $group['key'],
                'title' => $group['title']
            ];
        }

        wp_send_json_success($formattedGroups);
    }

    /**
     * Handle AJAX request for data export
     */
    public function exportData(): void
    {
        // Verify nonce and permissions
        if (!$this->verifyAjaxNonce('amfm_export_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        // Use DataExportService for the actual export logic
        $exportService = $this->service('data_export');
        
        try {
            $result = $exportService->exportData($_POST);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle AJAX request for component settings update
     */
    public function updateComponentSettings(): void
    {
        // Verify nonce and permissions
        if (!$this->verifyAjaxNonce('amfm_component_settings_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $components = $this->getPostParameter('components', []);
        $components = is_array($components) ? array_map('sanitize_text_field', $components) : [];
        
        // Use SettingsService for the actual logic
        $settingsService = $this->service('settings');
        $success = $settingsService->updateComponentSettings($components);
        
        if ($success) {
            wp_send_json_success('Component settings updated successfully');
        } else {
            wp_send_json_error('Failed to update component settings');
        }
    }

    /**
     * Handle AJAX request for Elementor widgets update
     */
    public function updateElementorWidgets(): void
    {
        // Verify nonce and permissions
        if (!$this->verifyAjaxNonce('amfm_elementor_widgets_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $widgets = $this->getPostParameter('widgets', []);
        $widgets = is_array($widgets) ? array_map('sanitize_text_field', $widgets) : [];
        
        // Use SettingsService for the actual logic
        $settingsService = $this->service('settings');
        $success = $settingsService->updateElementorWidgets($widgets);
        
        if ($success) {
            wp_send_json_success('Widget settings updated successfully');
        } else {
            wp_send_json_error('Failed to update widget settings');
        }
    }

    /**
     * Service dependencies
     */
    protected function dependencies(): array
    {
        return ['settings', 'data_export'];
    }

    /**
     * Verify AJAX nonce
     */
    private function verifyAjaxNonce(string $nonceAction): bool
    {
        if (!wp_doing_ajax()) {
            return false;
        }

        return check_ajax_referer($nonceAction, 'nonce', false);
    }

    /**
     * Get and sanitize POST parameter
     */
    private function getPostParameter(string $key, $default = null)
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        if (is_string($_POST[$key])) {
            return sanitize_key(wp_unslash($_POST[$key]));
        }

        if (is_array($_POST[$key])) {
            return array_map('sanitize_text_field', wp_unslash($_POST[$key]));
        }

        return wp_unslash($_POST[$key]);
    }
}