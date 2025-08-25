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

        // Display any import results
        $this->displayImportResults();

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
     * Display import results if any
     */
    private function displayImportResults(): void
    {
        // Check for unified import results
        if (isset($_GET['imported']) && $_GET['imported'] === 'data') {
            $results = get_transient('amfm_unified_csv_import_results');
            if ($results) {
                $this->showImportNotice($results, 'Data');
                delete_transient('amfm_unified_csv_import_results');
            }
        }
    }

    /**
     * Show import notice
     */
    private function showImportNotice($results, $type): void
    {
        $class = $results['errors'] > 0 ? 'notice-warning' : 'notice-success';
        ?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
            <p><strong><?php echo esc_html($type); ?> Import Results:</strong></p>
            <p>Success: <?php echo esc_html($results['success']); ?> | Errors: <?php echo esc_html($results['errors']); ?></p>
            <?php if (!empty($results['details'])): ?>
                <details>
                    <summary>View Details</summary>
                    <ul>
                        <?php foreach ($results['details'] as $detail): ?>
                            <li><?php echo esc_html($detail); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
        <?php
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
            /* Import/Export specific styles */
            .amfm-import-export-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                gap: 30px;
                margin: 40px 0;
                max-width: 1200px;
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

            .amfm-card-actions {
                display: flex;
                justify-content: center;
                margin-top: 30px;
            }

            .amfm-primary-button {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                color: white;
                padding: 12px 30px;
                font-size: 1rem;
                font-weight: 500;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                min-width: 120px;
            }

            .amfm-primary-button:hover {
                background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            }

            .amfm-primary-button:active {
                transform: translateY(0);
            }

            /* Mobile responsiveness */
            @media (max-width: 768px) {
                .amfm-import-export-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                    margin: 20px 0;
                }

                .amfm-import-export-card {
                    padding: 20px;
                }

                .amfm-card-icon {
                    width: 50px;
                    height: 50px;
                    font-size: 2rem;
                }

                .amfm-card-title {
                    font-size: 1.25rem;
                }
            }

            /* Drawer form improvements */
            .amfm-drawer .amfm-form {
                padding: 0;
            }

            .amfm-drawer .amfm-form-group {
                margin-bottom: 25px;
            }

            .amfm-drawer .amfm-form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #2c3e50;
            }

            .amfm-drawer .amfm-checkbox-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
                margin-top: 10px;
            }

            .amfm-drawer .amfm-checkbox-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 5px 0;
                cursor: pointer;
            }

            .amfm-drawer .amfm-radio-group {
                display: flex;
                gap: 20px;
                margin-top: 10px;
            }

            .amfm-drawer .amfm-radio-item {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }

            .amfm-drawer .amfm-form-actions {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e1e5e9;
                text-align: center;
            }

            .amfm-drawer .button-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                padding: 12px 30px;
                font-size: 1rem;
                font-weight: 500;
                border-radius: 8px;
                min-width: 150px;
            }

            .amfm-drawer .button-primary:hover {
                background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
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

        ?>
        <div class="wrap amfm-admin-page">
            <div class="amfm-container">
                <!-- Enhanced Header -->
                <div class="amfm-header">
                    <div class="amfm-header-content">
                        <div class="amfm-header-main">
                            <div class="amfm-header-logo">
                                <span class="amfm-icon">📊</span>
                            </div>
                            <div class="amfm-header-text">
                                <h1>Import/Export</h1>
                                <p class="amfm-subtitle">Data Management Tools</p>
                            </div>
                        </div>
                        <div class="amfm-header-actions">
                            <div class="amfm-version-badge">
                                v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import/Export Content -->
                <div class="amfm-tab-content">
                    <div class="amfm-import-export-grid">
                        <!-- Export Data Card -->
                        <div class="amfm-import-export-card">
                            <div class="amfm-card-header">
                                <div class="amfm-card-icon">📤</div>
                                <h3 class="amfm-card-title">Export Data</h3>
                            </div>
                            <div class="amfm-card-body">
                                <p class="amfm-card-description">Export your posts, pages, and custom post types with their metadata to CSV format for backup or migration purposes.</p>
                                <div class="amfm-card-actions">
                                    <button type="button" 
                                            class="amfm-primary-button" 
                                            onclick="openImportExportDrawer('export')">
                                        Export
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Import Data Card -->
                        <div class="amfm-import-export-card">
                            <div class="amfm-card-header">
                                <div class="amfm-card-icon">📥</div>
                                <h3 class="amfm-card-title">Import Data</h3>
                            </div>
                            <div class="amfm-card-body">
                                <p class="amfm-card-description">Import data from CSV files to update posts with content, taxonomies, ACF fields, and other metadata seamlessly.</p>
                                <div class="amfm-card-actions">
                                    <button type="button" 
                                            class="amfm-primary-button" 
                                            onclick="openImportExportDrawer('import')">
                                        Import
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import/Export Drawer -->
        <div id="amfm-import-export-drawer" class="amfm-drawer">
            <div class="amfm-drawer-overlay" onclick="closeImportExportDrawer()"></div>
            <div class="amfm-drawer-content">
                <div class="amfm-drawer-header">
                    <h2 id="amfm-drawer-title">Data Management</h2>
                    <button type="button" class="amfm-drawer-close" onclick="closeImportExportDrawer()">&times;</button>
                </div>
                <div class="amfm-drawer-body" id="amfm-drawer-body">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
        <?php
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