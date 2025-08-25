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
                    <div class="amfm-components-grid">
                        <!-- Export Data Card -->
                        <div class="amfm-component-card amfm-component-enabled">
                            <div class="amfm-component-header">
                                <div class="amfm-component-icon">📤</div>
                                <div class="amfm-component-toggle">
                                    <span class="amfm-core-label">Core</span>
                                </div>
                            </div>
                            <div class="amfm-component-body">
                                <h3 class="amfm-component-title">Export Data</h3>
                                <p class="amfm-component-description">Export your posts, pages, and custom post types with their metadata to CSV format.</p>
                                <div class="amfm-component-status">
                                    <span class="amfm-status-indicator"></span>
                                    <span class="amfm-status-text">Always Active</span>
                                </div>
                                <div class="amfm-component-actions">
                                    <button type="button" 
                                            class="amfm-info-button amfm-doc-button" 
                                            onclick="openImportExportDrawer('export')">
                                        Export Data
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Import Data Card -->
                        <div class="amfm-component-card amfm-component-enabled">
                            <div class="amfm-component-header">
                                <div class="amfm-component-icon">📥</div>
                                <div class="amfm-component-toggle">
                                    <span class="amfm-core-label">Core</span>
                                </div>
                            </div>
                            <div class="amfm-component-body">
                                <h3 class="amfm-component-title">Import Data</h3>
                                <p class="amfm-component-description">Import data from CSV files to update posts with keywords, categories, and other metadata.</p>
                                <div class="amfm-component-status">
                                    <span class="amfm-status-indicator"></span>
                                    <span class="amfm-status-text">Always Active</span>
                                </div>
                                <div class="amfm-component-actions">
                                    <button type="button" 
                                            class="amfm-info-button amfm-config-button" 
                                            onclick="openImportExportDrawer('import')">
                                        Import Data
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

        <script>
        // Import/Export drawer data
        const importExportData = {
            'export': {
                title: 'Export Data',
                content: `
                    <form method="post" action="" class="amfm-form" id="amfm-export-form">
                        <?php wp_nonce_field('amfm_export_nonce', 'amfm_export_nonce'); ?>
                        
                        <div class="amfm-form-group">
                            <label for="export_post_type">Select Post Type:</label>
                            <select name="export_post_type" id="export_post_type" required>
                                <option value="">Select a post type</option>
                                <?php echo $this->getPostTypesOptions(); ?>
                            </select>
                        </div>

                        <div class="amfm-form-group">
                            <label>Export Options:</label>
                            <div class="amfm-checkbox-grid">
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="taxonomies" checked>
                                    <span>Include Taxonomies</span>
                                </label>
                                <?php if (function_exists('acf_get_field_groups')): ?>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="acf_fields" checked>
                                    <span>Include ACF Fields</span>
                                </label>
                                <?php endif; ?>
                                <label class="amfm-checkbox-item">
                                    <input type="checkbox" name="export_options[]" value="featured_image">
                                    <span>Include Featured Image URL</span>
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

                        <?php if (function_exists('acf_get_field_groups')): ?>
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
                                <?php echo $this->getAcfFieldGroupsCheckboxes(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="amfm-form-actions">
                            <button type="submit" name="amfm_export" value="1" class="button button-primary">
                                Export to CSV
                            </button>
                        </div>
                    </form>
                `
            },
            'import': {
                title: 'Import Data',
                content: `
                    <form method="post" action="" enctype="multipart/form-data" class="amfm-form" id="amfm-import-form">
                        <?php wp_nonce_field('amfm_csv_import', 'amfm_csv_import_nonce'); ?>
                        
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
                                <li>Keep the ID column - it's required to identify posts</li>
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
                `
            }
        };

        // Drawer functionality
        function openImportExportDrawer(type) {
            const drawer = document.getElementById('amfm-import-export-drawer');
            const title = document.getElementById('amfm-drawer-title');
            const body = document.getElementById('amfm-drawer-body');
            
            if (importExportData[type]) {
                title.textContent = importExportData[type].title;
                body.innerHTML = importExportData[type].content;
                drawer.classList.add('amfm-drawer-open');
                document.body.style.overflow = 'hidden';
                
                // Initialize form handlers after content is loaded
                initializeFormHandlers();
            }
        }

        function closeImportExportDrawer() {
            const drawer = document.getElementById('amfm-import-export-drawer');
            drawer.classList.remove('amfm-drawer-open');
            document.body.style.overflow = '';
        }

        function initializeFormHandlers() {
            // Re-initialize jQuery handlers for the new form elements
            jQuery(document).ready(function($) {
                // Toggle taxonomy selection
                $('input[name="export_options[]"][value="taxonomies"]').off('change').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.amfm-taxonomy-selection').show();
                    } else {
                        $('.amfm-taxonomy-selection').hide();
                    }
                });

                // Toggle specific taxonomies
                $('input[name="taxonomy_selection"]').off('change').on('change', function() {
                    if ($(this).val() === 'selected') {
                        $('.amfm-specific-taxonomies').show();
                    } else {
                        $('.amfm-specific-taxonomies').hide();
                    }
                });

                // Toggle ACF selection
                $('input[name="export_options[]"][value="acf_fields"]').off('change').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('.amfm-acf-selection').show();
                    } else {
                        $('.amfm-acf-selection').hide();
                    }
                });

                // Toggle specific ACF groups
                $('input[name="acf_selection"]').off('change').on('change', function() {
                    if ($(this).val() === 'selected') {
                        $('.amfm-specific-acf-groups').show();
                    } else {
                        $('.amfm-specific-acf-groups').hide();
                    }
                });

                // Update taxonomies based on post type
                $('#export_post_type').off('change').on('change', function() {
                    var postType = $(this).val();
                    if (postType) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'amfm_get_post_type_taxonomies',
                                post_type: postType,
                                nonce: '<?php echo wp_create_nonce('amfm_ajax'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.amfm-specific-taxonomies').html(response.data);
                                }
                            }
                        });
                    }
                });
            });
        }

        // Close drawer with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImportExportDrawer();
            }
        });
        </script>
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