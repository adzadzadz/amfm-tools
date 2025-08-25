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

        // Handle keywords import
        if (isset($_FILES['csv_file'])) {
            $importService = $this->getCsvImportService();
            if ($importService) {
                $importService->handleKeywordsUpload();
            }
        }

        // Handle categories import
        if (isset($_FILES['category_csv_file'])) {
            $importService = $this->getCsvImportService();
            if ($importService) {
                $importService->handleCategoriesUpload();
            }
        }
    }

    /**
     * Display import results if any
     */
    private function displayImportResults(): void
    {
        // Check for keywords import results
        if (isset($_GET['imported']) && $_GET['imported'] === 'keywords') {
            $results = get_transient('amfm_csv_import_results');
            if ($results) {
                $this->showImportNotice($results, 'Keywords');
                delete_transient('amfm_csv_import_results');
            }
        }

        // Check for categories import results
        if (isset($_GET['imported']) && $_GET['imported'] === 'categories') {
            $results = get_transient('amfm_category_csv_import_results');
            if ($results) {
                $this->showImportNotice($results, 'Categories');
                delete_transient('amfm_category_csv_import_results');
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

        wp_enqueue_style(
            'amfm-import-export-css',
            $plugin_url . 'assets/css/import-export.css',
            ['amfm-admin-style'],
            $version
        );

        ?>
        <div class="wrap amfm-import-export-page">
            <h1><?php echo esc_html__('Import/Export', 'amfm-tools'); ?></h1>
            
            <div class="amfm-cards-container">
                <!-- Export Card -->
                <div class="amfm-card">
                    <h2><?php echo esc_html__('Export Data', 'amfm-tools'); ?></h2>
                    <p><?php echo esc_html__('Export your posts, pages, and custom post types with their metadata to CSV format.', 'amfm-tools'); ?></p>
                    
                    <form method="post" action="" class="amfm-export-form">
                        <?php wp_nonce_field('amfm_export_nonce', 'amfm_export_nonce'); ?>
                        
                        <div class="amfm-form-group">
                            <label for="export_post_type"><?php echo esc_html__('Select Post Type:', 'amfm-tools'); ?></label>
                            <select name="export_post_type" id="export_post_type" required>
                                <option value=""><?php echo esc_html__('Select a post type', 'amfm-tools'); ?></option>
                                <?php echo $this->getPostTypesOptions(); ?>
                            </select>
                        </div>

                        <div class="amfm-form-group">
                            <label><?php echo esc_html__('Export Options:', 'amfm-tools'); ?></label>
                            <label class="amfm-checkbox">
                                <input type="checkbox" name="export_options[]" value="taxonomies" checked>
                                <?php echo esc_html__('Include Taxonomies', 'amfm-tools'); ?>
                            </label>
                            <?php if (function_exists('acf_get_field_groups')): ?>
                            <label class="amfm-checkbox">
                                <input type="checkbox" name="export_options[]" value="acf_fields" checked>
                                <?php echo esc_html__('Include ACF Fields', 'amfm-tools'); ?>
                            </label>
                            <?php endif; ?>
                            <label class="amfm-checkbox">
                                <input type="checkbox" name="export_options[]" value="featured_image">
                                <?php echo esc_html__('Include Featured Image URL', 'amfm-tools'); ?>
                            </label>
                        </div>

                        <div class="amfm-form-group amfm-taxonomy-selection" style="display:none;">
                            <label><?php echo esc_html__('Taxonomy Selection:', 'amfm-tools'); ?></label>
                            <label class="amfm-radio">
                                <input type="radio" name="taxonomy_selection" value="all" checked>
                                <?php echo esc_html__('All Taxonomies', 'amfm-tools'); ?>
                            </label>
                            <label class="amfm-radio">
                                <input type="radio" name="taxonomy_selection" value="selected">
                                <?php echo esc_html__('Select Specific Taxonomies', 'amfm-tools'); ?>
                            </label>
                            <div class="amfm-specific-taxonomies" style="display:none;">
                                <!-- Will be populated by JavaScript based on post type -->
                            </div>
                        </div>

                        <?php if (function_exists('acf_get_field_groups')): ?>
                        <div class="amfm-form-group amfm-acf-selection" style="display:none;">
                            <label><?php echo esc_html__('ACF Field Selection:', 'amfm-tools'); ?></label>
                            <label class="amfm-radio">
                                <input type="radio" name="acf_selection" value="all" checked>
                                <?php echo esc_html__('All ACF Fields', 'amfm-tools'); ?>
                            </label>
                            <label class="amfm-radio">
                                <input type="radio" name="acf_selection" value="selected">
                                <?php echo esc_html__('Select Specific Field Groups', 'amfm-tools'); ?>
                            </label>
                            <div class="amfm-specific-acf-groups" style="display:none;">
                                <?php echo $this->getAcfFieldGroupsCheckboxes(); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="amfm_export" value="1" class="button button-primary">
                            <?php echo esc_html__('Export to CSV', 'amfm-tools'); ?>
                        </button>
                    </form>
                </div>

                <!-- Import Keywords Card -->
                <div class="amfm-card">
                    <h2><?php echo esc_html__('Import Keywords', 'amfm-tools'); ?></h2>
                    <p><?php echo esc_html__('Import keywords for posts from a CSV file. CSV must have ID and Keywords columns.', 'amfm-tools'); ?></p>
                    
                    <form method="post" action="" enctype="multipart/form-data" class="amfm-import-form">
                        <?php wp_nonce_field('amfm_csv_import', 'amfm_csv_import_nonce'); ?>
                        
                        <div class="amfm-form-group">
                            <label for="csv_file"><?php echo esc_html__('Select CSV File:', 'amfm-tools'); ?></label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        </div>

                        <div class="amfm-form-info">
                            <p><strong><?php echo esc_html__('CSV Format:', 'amfm-tools'); ?></strong></p>
                            <code>ID,Keywords</code><br>
                            <code>123,"keyword1, keyword2, keyword3"</code>
                        </div>

                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Import Keywords', 'amfm-tools'); ?>
                        </button>
                    </form>
                </div>

                <!-- Import Categories Card -->
                <div class="amfm-card">
                    <h2><?php echo esc_html__('Import Categories', 'amfm-tools'); ?></h2>
                    <p><?php echo esc_html__('Import and assign categories to posts from a CSV file.', 'amfm-tools'); ?></p>
                    
                    <form method="post" action="" enctype="multipart/form-data" class="amfm-import-form">
                        <?php wp_nonce_field('amfm_category_csv_import', 'amfm_category_csv_import_nonce'); ?>
                        
                        <div class="amfm-form-group">
                            <label for="category_csv_file"><?php echo esc_html__('Select CSV File:', 'amfm-tools'); ?></label>
                            <input type="file" name="category_csv_file" id="category_csv_file" accept=".csv" required>
                        </div>

                        <div class="amfm-form-info">
                            <p><strong><?php echo esc_html__('CSV Format:', 'amfm-tools'); ?></strong></p>
                            <code>ID,Categories</code><br>
                            <code>123,"Category Name"</code>
                        </div>

                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Import Categories', 'amfm-tools'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle taxonomy selection
            $('input[name="export_options[]"][value="taxonomies"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.amfm-taxonomy-selection').show();
                } else {
                    $('.amfm-taxonomy-selection').hide();
                }
            });

            // Toggle specific taxonomies
            $('input[name="taxonomy_selection"]').on('change', function() {
                if ($(this).val() === 'selected') {
                    $('.amfm-specific-taxonomies').show();
                } else {
                    $('.amfm-specific-taxonomies').hide();
                }
            });

            // Toggle ACF selection
            $('input[name="export_options[]"][value="acf_fields"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.amfm-acf-selection').show();
                } else {
                    $('.amfm-acf-selection').hide();
                }
            });

            // Toggle specific ACF groups
            $('input[name="acf_selection"]').on('change', function() {
                if ($(this).val() === 'selected') {
                    $('.amfm-specific-acf-groups').show();
                } else {
                    $('.amfm-specific-acf-groups').hide();
                }
            });

            // Update taxonomies based on post type
            $('#export_post_type').on('change', function() {
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