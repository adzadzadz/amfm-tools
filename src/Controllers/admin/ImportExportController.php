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

            .amfm-file-input {
                width: 100%;
                padding: 10px;
                border: 2px dashed #d1d5db;
                border-radius: 8px;
                background: #f9fafb;
                transition: border-color 0.3s ease;
            }

            .amfm-file-input:focus {
                border-color: #667eea;
                outline: none;
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
                                <!-- Will be populated by JavaScript -->
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
                        
                        <div class="amfm-info-box">
                            <h4>📋 Import Requirements</h4>
                            <p>Your CSV file should match the columns from the Export Data function. The system will automatically detect and import the following data:</p>
                            <ul class="amfm-requirements-list">
                                <li><strong>ID</strong> - Post ID (required)</li>
                                <li><strong>Post Title</strong> - Will update post title if provided</li>
                                <li><strong>Post Content</strong> - Will update post content if provided</li>
                                <li><strong>Post Excerpt</strong> - Will update post excerpt if provided</li>
                                <li><strong>Taxonomies</strong> - Will assign categories/tags based on column names</li>
                                <li><strong>ACF Fields</strong> - Will update ACF fields based on column names</li>
                                <li><strong>Featured Image URL</strong> - Will set featured image from URL</li>
                            </ul>
                        </div>

                        <div class="amfm-form-group">
                            <label for="csv_file">Select CSV File:</label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="amfm-file-input">
                        </div>

                        <div class="amfm-info-box">
                            <h4>💡 Pro Tips</h4>
                            <ul class="amfm-requirements-list">
                                <li>Export first to see the exact column format</li>
                                <li>Keep the ID column - it\'s required to identify posts</li>
                                <li>Leave cells empty to skip updating that field</li>
                                <li>Use the same column names as the export</li>
                            </ul>
                        </div>

                        <div class="amfm-form-actions">
                            <button type="submit" class="button button-primary">
                                Import Data
                            </button>
                        </div>
                    </form>
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
            'page_title' => 'Import/Export',
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
     * AJAX handler to get post type taxonomies
     */
    public function ajaxGetPostTypeTaxonomies(): void
    {
        check_ajax_referer('amfm_ajax', 'nonce');

        $post_type = sanitize_key($_POST['post_type']);
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        
        $html = '';
        foreach ($taxonomies as $taxonomy) {
            $html .= sprintf(
                '<label class="amfm-checkbox"><input type="checkbox" name="specific_taxonomies[]" value="%s"> %s</label>',
                esc_attr($taxonomy->name),
                esc_html($taxonomy->label)
            );
        }

        wp_send_json_success($html);
    }

    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add Import/Export submenu under AMFM Tools
        add_submenu_page(
            'amfm-tools',
            __('Import/Export', 'amfm-tools'),
            __('Import/Export', 'amfm-tools'),
            'manage_options',
            'amfm-tools-import-export',
            [$this, 'displayPage']
        );
    }

    /**
     * Register AJAX handlers
     */
    public function actionInit()
    {
        add_action('wp_ajax_amfm_get_post_type_taxonomies', [$this, 'ajaxGetPostTypeTaxonomies']);
    }
}