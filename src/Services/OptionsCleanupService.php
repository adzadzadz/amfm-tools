<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * Options Cleanup Service - removes old/unused WordPress options
 */
class OptionsCleanupService extends Service
{
    /**
     * Get list of deprecated/unused AMFM options
     */
    public function getDeprecatedOptions(): array
    {
        $deprecated = [
            // Old upload limit option name
            'amfm_image_upload_limit_enabled',

            // Legacy options that might exist
            'amfm_enabled_components',  // Old array-based storage

            // Old analysis and cache data
            'amfm_redirection_cleanup_analysis_cache',
            'amfm_redirection_cleanup_full_analysis',
        ];

        // Add dynamic deprecated options (temporary data)
        global $wpdb;

        // Get all temporary CSV import options
        $csv_imports = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE 'amfm_csv_import_%'"
        );
        $deprecated = array_merge($deprecated, $csv_imports);

        // Get all old job data
        $job_data = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE 'amfm_redirection_cleanup_job_%'"
        );
        $deprecated = array_merge($deprecated, $job_data);

        return $deprecated;
    }

    /**
     * Get all AMFM-related options for review
     */
    public function getAllAmfmOptions(): array
    {
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'amfm_%'
             ORDER BY option_name",
            ARRAY_A
        );

        return $options;
    }

    /**
     * Clean up deprecated options
     */
    public function cleanupDeprecatedOptions(): array
    {
        $deprecated = $this->getDeprecatedOptions();
        $cleaned = [];

        foreach ($deprecated as $option_name) {
            if (get_option($option_name) !== false) {
                if (delete_option($option_name)) {
                    $cleaned[] = $option_name;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Get cleanup statistics
     */
    public function getCleanupStats(): array
    {
        $all_options = $this->getAllAmfmOptions();
        $deprecated = $this->getDeprecatedOptions();

        $deprecated_found = [];
        foreach ($all_options as $option) {
            if (in_array($option['option_name'], $deprecated)) {
                $deprecated_found[] = $option['option_name'];
            }
        }

        return [
            'total_amfm_options' => count($all_options),
            'deprecated_options' => $deprecated_found,
            'deprecated_count' => count($deprecated_found),
            'all_options' => $all_options
        ];
    }

    /**
     * AJAX handler for cleanup action
     */
    public function ajaxCleanupOptions(): void
    {
        if (!check_ajax_referer('amfm_cleanup_nonce', 'nonce', false) ||
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $action = sanitize_text_field($_POST['cleanup_action'] ?? 'preview');

        if ($action === 'preview') {
            $stats = $this->getCleanupStats();
            wp_send_json_success([
                'message' => 'Cleanup preview generated',
                'stats' => $stats
            ]);
        } elseif ($action === 'execute') {
            $cleaned = $this->cleanupDeprecatedOptions();
            wp_send_json_success([
                'message' => count($cleaned) > 0 ? 'Cleanup completed successfully' : 'No deprecated options found',
                'cleaned_options' => $cleaned,
                'cleaned_count' => count($cleaned)
            ]);
        } else {
            wp_send_json_error('Invalid action');
        }
    }
}