<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;

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
        $plugin_url = plugin_dir_url(dirname(__DIR__, 2));
        $version = defined('AMFM_TOOLS_VERSION') ? AMFM_TOOLS_VERSION : '1.0.0';

        // Enqueue styles
        wp_enqueue_style(
            'amfm-admin-style',
            $plugin_url . 'assets/css/admin-style.css',
            [],
            $version
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'amfm-import-export-js',
            $plugin_url . 'assets/js/import-export.js',
            ['jquery'],
            $version,
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

        // Add custom styles for import/export page
        wp_add_inline_style('amfm-admin-style', '
            /* Import/Export single column layout */
            .amfm-import-export-single-column {
                display: flex;
                flex-direction: column;
                gap: 30px;
                margin: 40px 0;
                max-width: 800px;
                margin-left: auto;
                margin-right: auto;
            }

            .amfm-import-export-card {
                background: #fff;
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                border: 1px solid #e1e5e9;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .amfm-import-export-card:hover {
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
                transform: translateY(-2px);
            }

            .amfm-card-header {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                gap: 15px;
            }

            .amfm-card-icon {
                font-size: 2.5rem;
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                color: white;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            }

            .amfm-card-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0;
                color: #2c3e50;
            }

            .amfm-card-body {
                margin-top: 20px;
            }

            .amfm-card-description {
                font-size: 1rem;
                line-height: 1.6;
                color: #5a6c7d;
                margin: 0 0 30px 0;
            }

            /* Form styles */
            .amfm-form {
                padding: 0;
            }

            .amfm-form-group {
                margin-bottom: 25px;
            }

            .amfm-form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #2c3e50;
            }

            .amfm-form-group-label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #2c3e50;
            }

            .amfm-form-group select {
                width: 100%;
                padding: 10px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                background: #fff;
            }

            .amfm-checkbox-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
                margin-top: 10px;
            }

            .amfm-checkbox-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 5px 0;
                cursor: pointer;
            }

            .amfm-radio-group {
                display: flex;
                gap: 20px;
                margin-top: 10px;
            }

            .amfm-radio-item {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }

            .amfm-form-actions {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e1e5e9;
                text-align: center;
            }

            .button-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border: none !important;
                padding: 12px 30px !important;
                font-size: 1rem !important;
                font-weight: 500 !important;
                border-radius: 8px !important;
                min-width: 150px !important;
                color: white !important;
                transition: all 0.3s ease !important;
            }

            .button-primary:hover {
                background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%) !important;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4) !important;
                transform: translateY(-1px) !important;
            }

            .amfm-info-box {
                background: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 20px;
                margin: 20px 0;
                border-radius: 0 8px 8px 0;
            }

            .amfm-info-box h4 {
                margin: 0 0 10px 0;
                color: #2c3e50;
                font-weight: 600;
            }

            .amfm-requirements-list {
                margin: 10px 0 0 20px;
                list-style: disc;
            }

            .amfm-requirements-list li {
                margin: 5px 0;
                color: #5a6c7d;
            }

            /* File upload styling */
            .amfm-file-upload-wrapper {
                position: relative;
                display: block;
            }

            .amfm-file-input {
                position: absolute;
                width: 1px;
                height: 1px;
                opacity: 0;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
            }

            .amfm-file-upload-display {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 20px;
                border: 2px dashed #d1d5db;
                border-radius: 12px;
                background: #f9fafb;
                transition: all 0.3s ease;
                cursor: pointer;
                min-height: 80px;
                width: 100%;
                box-sizing: border-box;
            }

            .amfm-file-upload-display:hover {
                border-color: #667eea;
                background: #f0f9ff;
            }

            .amfm-file-upload-wrapper.dragover .amfm-file-upload-display {
                border-color: #667eea;
                background: #e0f2fe;
                transform: scale(1.02);
            }

            .amfm-file-upload-wrapper.file-selected .amfm-file-upload-display {
                border-color: #10b981;
                background: #ecfdf5;
            }

            .amfm-file-upload-icon {
                font-size: 2rem;
                color: #667eea;
                flex-shrink: 0;
            }

            .amfm-file-upload-text {
                flex-grow: 1;
            }

            .amfm-file-placeholder {
                display: block;
                color: #6b7280;
                font-size: 1rem;
                font-weight: 500;
            }

            .amfm-file-selected {
                display: block;
                color: #059669;
                font-weight: 600;
                margin-top: 5px;
            }

            /* Import results styling */
            .amfm-import-results {
                margin-top: 30px;
                padding: 25px;
                background: #f8fafc;
                border-radius: 12px;
                border: 1px solid #e2e8f0;
            }

            .amfm-import-results h3 {
                margin: 0 0 15px 0;
                color: #2c3e50;
                font-size: 1.25rem;
                font-weight: 600;
            }

            .amfm-import-success {
                background: #d1fae5;
                border-color: #10b981;
                color: #065f46;
            }

            .amfm-import-error {
                background: #fee2e2;
                border-color: #ef4444;
                color: #991b1b;
            }

            .amfm-import-results-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }

            .amfm-import-results-table th,
            .amfm-import-results-table td {
                padding: 10px 15px;
                text-align: left;
                border-bottom: 1px solid #e2e8f0;
            }

            .amfm-import-results-table th {
                background: #f1f5f9;
                font-weight: 600;
                color: #334155;
            }

            .amfm-loading {
                display: flex;
                align-items: center;
                gap: 10px;
                color: #6b7280;
            }

            .amfm-loading-spinner {
                width: 20px;
                height: 20px;
                border: 2px solid #e5e7eb;
                border-top: 2px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        ');

        // Create page content using template service
        $templateService = $this->service('page_template');
        
        // Create page content with embedded forms instead of using cards
        $page_content = '
        <div class="amfm-import-export-single-column">
            <!-- Export Section -->
            <div class="amfm-import-export-card">
                <div class="amfm-card-header">
                    <div class="amfm-card-icon">📤</div>
                    <h2 class="amfm-card-title">Export Data</h2>
                </div>
                <div class="amfm-card-body">
                    <p class="amfm-card-description">Export your posts, pages, and custom post types with their metadata to CSV format for backup or migration purposes.</p>
                    
                    <form method="post" action="" class="amfm-form" id="amfm-export-form">
                        <input type="hidden" name="amfm_export_nonce" value="' . wp_create_nonce('amfm_export_nonce') . '" />
                        
                        <div class="amfm-form-group">
                            <label for="export_post_type">Select Post Type:</label>
                            <select name="export_post_type" id="export_post_type" required>
                                <option value="">Select a post type</option>
                                ' . $this->getPostTypesOptions() . '
                            </select>
                        </div>

                        <div class="amfm-form-group amfm-export-options" style="display:none;">
                            <label>Export Options:</label>
                            <div class="amfm-checkbox-grid">
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="post_data">
                                    <span>Include Post Data</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="taxonomies">
                                    <span>Include Taxonomies</span>
                                </label>
                                ' . (function_exists('acf_get_field_groups') ? '
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="acf_fields">
                                    <span>Include ACF Fields</span>
                                </label>
                                ' : '') . '
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="featured_image">
                                    <span>Include Featured Image URL</span>
                                </label>
                            </div>
                        </div>

                        <div class="amfm-form-group amfm-post-data-selection" style="display:none;">
                            <label>Post Data Selection:</label>
                            <div class="amfm-radio-group">
                                <label class="amfm-radio-item">
                                    <input type="radio" name="post_data_selection" value="all" checked>
                                    <span>All Post Columns</span>
                                </label>
                                <label class="amfm-radio-item">
                                    <input type="radio" name="post_data_selection" value="selected">
                                    <span>Select Specific Columns</span>
                                </label>
                            </div>
                            <div class="amfm-specific-post-columns amfm-checkbox-grid" style="display:none;">
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_title" checked>
                                    <span>Post Title</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_content">
                                    <span>Post Content</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_excerpt">
                                    <span>Post Excerpt</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_status">
                                    <span>Post Status</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_date">
                                    <span>Post Date</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_modified">
                                    <span>Post Modified Date</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_author">
                                    <span>Post Author</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_name">
                                    <span>Post Slug</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="menu_order">
                                    <span>Menu Order</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="comment_status">
                                    <span>Comment Status</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="ping_status">
                                    <span>Ping Status</span>
                                </label>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="specific_post_columns[]" value="post_parent">
                                    <span>Post Parent</span>
                                </label>
                            </div>
                        </div>

                        <div class="amfm-form-group amfm-taxonomy-selection" style="display:none;">
                            <label>Taxonomy Selection:</label>
                            <div class="amfm-radio-group">
                                <label class="amfm-radio-item">
                                    <input type="radio" name="taxonomy_selection" value="all" checked>
                                    <span>All Taxonomies</span>
                                </label>
                                <label class="amfm-radio-item">
                                    <input type="radio" name="taxonomy_selection" value="selected">
                                    <span>Select Specific Taxonomies</span>
                                </label>
                            </div>
                            <div class="amfm-specific-taxonomies amfm-checkbox-grid" style="display:none;">
                                ' . $this->getAllTaxonomiesCheckboxes() . '
                            </div>
                        </div>

                        ' . (function_exists('acf_get_field_groups') ? '
                        <div class="amfm-form-group amfm-acf-selection" style="display:none;">
                            <label>ACF Field Selection:</label>
                            <div class="amfm-radio-group">
                                <label class="amfm-radio-item">
                                    <input type="radio" name="acf_selection" value="all" checked>
                                    <span>All ACF Fields</span>
                                </label>
                                <label class="amfm-radio-item">
                                    <input type="radio" name="acf_selection" value="selected">
                                    <span>Select Specific Field Groups</span>
                                </label>
                            </div>
                            <div class="amfm-specific-acf-groups amfm-checkbox-grid" style="display:none;">
                                ' . $this->getAcfFieldGroupsCheckboxes() . '
                            </div>
                        </div>
                        ' : '') . '

                        <div class="amfm-form-actions">
                            <button type="submit" name="amfm_export" value="1" class="button button-primary">
                                Export to CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import Section -->
            <div class="amfm-import-export-card">
                <div class="amfm-card-header">
                    <div class="amfm-card-icon">📥</div>
                    <h2 class="amfm-card-title">Import Data</h2>
                </div>
                <div class="amfm-card-body">
                    <p class="amfm-card-description">Import data from CSV files to update posts with content, taxonomies, ACF fields, and other metadata seamlessly.</p>
                    
                    <form method="post" action="" enctype="multipart/form-data" class="amfm-form" id="amfm-import-form">
                        <input type="hidden" name="amfm_csv_import_nonce" value="' . wp_create_nonce('amfm_csv_import') . '" />
                        
                        <div class="amfm-form-group">
                            <div class="amfm-file-upload-wrapper">
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="amfm-file-input">
                                <label for="csv_file" class="amfm-file-upload-display">
                                    <div class="amfm-file-upload-icon">📎</div>
                                    <div class="amfm-file-upload-text">
                                        <span class="amfm-file-placeholder">Choose CSV file or drag & drop here</span>
                                        <span class="amfm-file-selected" style="display:none;"></span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="amfm-form-actions">
                            <button type="submit" class="button button-primary" id="amfm-import-submit">
                                Import Data
                            </button>
                        </div>
                    </form>
                    
                    <!-- Import Results Section -->
                    <div id="amfm-import-results" class="amfm-import-results" style="display:none;">
                        <h3>Import Results</h3>
                        <div id="amfm-import-results-content"></div>
                    </div>
                </div>
            </div>
        </div>';

        // Check for import results
        $show_results = false;
        $results = null;
        $results_type = 'Data Import';
        
        if (isset($_GET['imported']) && $_GET['imported'] === 'data') {
            $results = get_transient('amfm_unified_csv_import_results');
            if ($results) {
                $show_results = true;
                delete_transient('amfm_unified_csv_import_results');
            }
        }

        // Render the page using template
        $templateService->displayPage([
            'page_title' => 'Export/Import',
            'page_subtitle' => 'Data Management Tools',
            'page_icon' => '📊',
            'page_content' => $page_content,
            'show_results' => $show_results,
            'results' => $results,
            'results_type' => $results_type
        ]);
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