<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;

class ImportExportController extends Controller
{
    /**
     * Get CSV export service
     */
    private function getCsvExportService()
    {
        return $this->service('csv_export');
    }

    /**
     * Get CSV import service
     */
    private function getCsvImportService()
    {
        return $this->service('csv_import');
    }

    /**
     * Display import/export page
     */
    public function displayPage(): void
    {
        // Process forms if submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleFormSubmissions();
        }

        // Render the page
        $this->renderPage();
    }

    /**
     * Handle form submissions
     */
    private function handleFormSubmissions(): void
    {
        // Handle export
        if (isset($_POST['amfm_export'])) {
            $exportService = $this->getCsvExportService();
            if ($exportService) {
                $exportService->handleExport();
            }
        }

        // Handle unified CSV import
        if (isset($_FILES['csv_file'])) {
            $importService = $this->getCsvImportService();
            if ($importService) {
                $importService->handleUnifiedCsvUpload();
            }
        }
    }


    /**
     * Render the main page
     */
    private function renderPage(): void
    {
        // Check for import results
        $show_results = false;
        $results = null;
        
        if (isset($_GET['imported']) && $_GET['imported'] === 'data') {
            $results = get_transient('amfm_unified_csv_import_results');
            if ($results) {
                $show_results = true;
                delete_transient('amfm_unified_csv_import_results');
            }
        }

        // Prepare data for view
        $view_data = [
            'title' => 'Import & Export Tools',
            'active_tab' => 'import-export',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION,
            'show_results' => $show_results,
            'results' => $results,
            'post_types_options' => $this->getPostTypesOptions(),
            'acf_field_groups' => $this->getAcfFieldGroupsCheckboxes(),
            'all_taxonomies' => $this->getAllTaxonomiesCheckboxes(),
            'has_acf' => function_exists('acf_get_field_groups'),
            'export_nonce' => wp_create_nonce('amfm_export_nonce'),
            'import_nonce' => wp_create_nonce('amfm_csv_import'),
            'ajax_nonce' => wp_create_nonce('amfm_ajax')
        ];

        // Render the page using View with layout
        echo View::render('admin/import-export', $view_data, true, 'layouts/main');
    }

    /**
     * Get post types options HTML
     */
    private function getPostTypesOptions(): string
    {
        $post_types = get_post_types(['show_ui' => true], 'objects');
        
        // Remove unwanted post types
        unset($post_types['revision'], $post_types['nav_menu_item'], 
              $post_types['custom_css'], $post_types['customize_changeset'],
              $post_types['acf-field-group'], $post_types['acf-field']);

        $options = '';
        foreach ($post_types as $post_type) {
            $options .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($post_type->name),
                esc_html($post_type->label)
            );
        }
        
        return $options;
    }

    /**
     * Get ACF field groups checkboxes
     */
    private function getAcfFieldGroupsCheckboxes(): string
    {
        $html = '';
        
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            
            if (!empty($field_groups)) {
                foreach ($field_groups as $group) {
                    $html .= sprintf(
                        '<label class="amfm-checkbox"><input type="checkbox" name="specific_acf_groups[]" value="%s"> %s</label>',
                        esc_attr($group['key']),
                        esc_html($group['title'])
                    );
                }
            } else {
                $html = '<p class="amfm-no-fields">No ACF field groups found.</p>';
            }
        }
        
        return $html;
    }

    /**
     * Get all available taxonomies checkboxes
     */
    private function getAllTaxonomiesCheckboxes(): string
    {
        $html = '';
        
        // Get all public taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $html .= sprintf(
                    '<label class="amfm-checkbox-item"><input type="checkbox" name="specific_taxonomies[]" value="%s"> <span>%s</span></label>',
                    esc_attr($taxonomy->name),
                    esc_html($taxonomy->label)
                );
            }
        } else {
            $html = '<p class="amfm-no-fields">No taxonomies found.</p>';
        }
        
        return $html;
    }


    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add Export/Import submenu under AMFM Tools
        add_submenu_page(
            'amfm-tools',
            __('Export/Import', 'amfm-tools'),
            __('Export/Import', 'amfm-tools'),
            'manage_options',
            'amfm-tools-import-export',
            [$this, 'displayPage']
        );

        // Add Debug submenu (only in development/debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_submenu_page(
                'amfm-tools',
                __('Export/Import Debug', 'amfm-tools'),
                __('Debug Tests', 'amfm-tools'),
                'manage_options',
                'amfm-tools-debug',
                [$this, 'displayDebugPage']
            );
        }
    }

    /**
     * Enqueue admin assets - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm-tools-import-export') !== false) {
            wp_enqueue_style(
                'amfm-admin-style',
                AMFM_TOOLS_URL . 'assets/css/admin-style.css',
                [],
                AMFM_TOOLS_VERSION
            );
            
            wp_enqueue_script(
                'amfm-import-export-js',
                AMFM_TOOLS_URL . 'assets/js/import-export.js',
                ['jquery'],
                AMFM_TOOLS_VERSION,
                true
            );

            // Pass data to JavaScript
            wp_localize_script('amfm-import-export-js', 'amfmData', [
                'exportNonce' => wp_create_nonce('amfm_export_nonce'),
                'importNonce' => wp_create_nonce('amfm_csv_import'),
                'ajaxNonce' => wp_create_nonce('amfm_ajax'),
                'postTypesOptions' => $this->getPostTypesOptions(),
                'acfFieldGroups' => $this->getAcfFieldGroupsCheckboxes(),
                'hasAcf' => function_exists('acf_get_field_groups'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ]);
        }
    }

    /**
     * Register AJAX handlers
     */
    public function actionInit()
    {
        // AJAX handlers for import functionality
        add_action('wp_ajax_amfm_csv_import', [$this, 'ajaxCsvImport']);
        add_action('wp_ajax_amfm_csv_preview', [$this, 'ajaxCsvPreview']);
        add_action('wp_ajax_amfm_csv_import_batch', [$this, 'ajaxCsvImportBatch']);
        add_action('wp_ajax_amfm_get_test_posts_for_csv', [$this, 'ajaxGetTestPostsForCsv']);
        add_action('wp_ajax_amfm_debug_system_info', [$this, 'ajaxDebugSystemInfo']);
        add_action('wp_ajax_amfm_test_connection', [$this, 'ajaxTestConnection']);
    }

    /**
     * AJAX handler for CSV import
     */
    public function ajaxCsvImport(): void
    {
        // Verify nonce and user capabilities
        if (!wp_verify_nonce($_POST['amfm_csv_import_nonce'] ?? '', 'amfm_csv_import') ||
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied or invalid nonce.');
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error occurred.');
            return;
        }

        try {
            // Use direct instantiation instead of framework service method
            require_once plugin_dir_path(dirname(__DIR__, 2)) . 'src/Services/CsvImportService.php';
            $importService = new \App\Services\CsvImportService();
            
            if (!$importService) {
                wp_send_json_error('Import service not available.');
                return;
            }

            // Process the import using AJAX-specific method
            $result = $importService->processUnifiedCsvForAjax();
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Import completed successfully!',
                    'total_processed' => $result['success'] + $result['errors'],
                    'updated' => $result['success'],
                    'errors' => $result['details'] ?? []
                ]);
            } else {
                wp_send_json_error('Import failed - no data processed.');
            }
            
        } catch (\Exception $e) {
            wp_send_json_error('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to get test posts for CSV generation
     */
    public function ajaxGetTestPostsForCsv(): void
    {
        check_ajax_referer('amfm_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
            return;
        }

        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'posts_per_page' => 5,
            'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        if (empty($posts)) {
            wp_send_json_error('No posts found for testing.');
            return;
        }

        $testPosts = array_map(function($post) {
            return [
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_content' => wp_trim_words($post->post_content, 10)
            ];
        }, $posts);

        wp_send_json_success($testPosts);
    }

    /**
     * AJAX handler for debug system information
     */
    public function ajaxDebugSystemInfo(): void
    {
        check_ajax_referer('amfm_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
            return;
        }

        $systemInfo = [
            'CSV Import Service Available' => $this->service('csv_import') !== null,
            'CSV Export Service Available' => $this->service('csv_export') !== null,
            'ACF Plugin Active' => function_exists('acf_get_field_groups'),
            'WordPress AJAX Available' => defined('DOING_AJAX'),
            'File Upload Enabled' => ini_get('file_uploads'),
            'Max Upload Size' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Memory Limit' => ini_get('memory_limit'),
            'WordPress Debug Mode' => defined('WP_DEBUG') && WP_DEBUG,
            'Current User Can Manage Options' => current_user_can('manage_options'),
            'Plugin Directory Writable' => is_writable(plugin_dir_path(dirname(__DIR__, 2)))
        ];

        wp_send_json_success($systemInfo);
    }

    /**
     * Display debug page
     */
    public function displayDebugPage(): void
    {
        echo '<div class="wrap"><h1>Debug Information</h1>';
        echo '<p>Debug functionality has been removed for production.</p>';
        echo '</div>';
    }

    /**
     * Simple AJAX test endpoint
     */
    public function ajaxTestConnection(): void
    {
        error_log('AMFM Test: Connection test called');
        wp_send_json_success([
            'message' => 'Connection test successful!',
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ]);
    }

    /**
     * AJAX handler for CSV preview
     */
    public function ajaxCsvPreview(): void
    {
        // Verify nonce and user capabilities
        if (!wp_verify_nonce($_POST['amfm_csv_import_nonce'] ?? '', 'amfm_csv_import') ||
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied or invalid nonce.');
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error occurred.');
            return;
        }

        try {
            require_once plugin_dir_path(dirname(__DIR__, 2)) . 'src/Services/CsvImportService.php';
            $importService = new \App\Services\CsvImportService();
            
            $result = $importService->previewCsvForAjax();
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            wp_send_json_error('Preview failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler for CSV batch import
     */
    public function ajaxCsvImportBatch(): void
    {
        // Verify nonce and user capabilities
        if (!wp_verify_nonce($_POST['amfm_csv_import_nonce'] ?? '', 'amfm_csv_import') ||
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied or invalid nonce.');
            return;
        }

        $batchData = json_decode(stripslashes($_POST['batch_data'] ?? ''), true);
        if (!$batchData || !isset($batchData['rows'])) {
            wp_send_json_error('Invalid batch data.');
            return;
        }

        try {
            require_once plugin_dir_path(dirname(__DIR__, 2)) . 'src/Services/CsvImportService.php';
            $importService = new \App\Services\CsvImportService();
            
            $result = $importService->processBatch($batchData);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            wp_send_json_error('Batch import failed: ' . $e->getMessage());
        }
    }
}