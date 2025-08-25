<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;

class ImportExportController extends Controller
{
    /**
     * Display import/export page
     */
    public function displayPage(): void
    {
        // Enqueue React app and assets
        $this->enqueueAssets();

        // Render the view (just the React mount point)
        echo View::render('admin/import-export');
    }

    /**
     * Enqueue assets for import/export page
     */
    private function enqueueAssets(): void
    {
        $plugin_url = \plugin_dir_url(dirname(__DIR__, 2));
        $version = defined('AMFM_TOOLS_VERSION') ? AMFM_TOOLS_VERSION : '1.0.0';

        // Enqueue React bundle (includes React and ReactDOM)
        \wp_enqueue_script(
            'amfm-import-export-react',
            $plugin_url . 'assets/js/react/import-export.bundle.js',
            [],
            $version,
            true
        );

        // Enqueue React styles
        \wp_enqueue_style(
            'amfm-import-export-css',
            $plugin_url . 'assets/css/react/import-export.bundle.css',
            [],
            $version
        );

        // Pass data to React via window globals (before script loads)
        \wp_add_inline_script('amfm-import-export-react', "
            window.amfmPostTypesOptions = '" . \esc_js($this->getPostTypesOptions()) . "';
            window.amfmFieldGroups = " . \wp_json_encode($this->getFieldGroups()) . ";
            window.amfmAcfFieldGroupsHtml = '" . \esc_js($this->getAcfFieldGroupsHtml()) . "';
            window.amfmExportNonce = '" . \esc_js(\wp_nonce_field('amfm_export_nonce', 'amfm_export_nonce', true, false)) . "';
            window.amfmKeywordsNonce = '" . \esc_js(\wp_nonce_field('amfm_keywords_import_nonce', 'amfm_keywords_import_nonce', true, false)) . "';
            window.amfmCategoriesNonce = '" . \esc_js(\wp_nonce_field('amfm_categories_import_nonce', 'amfm_categories_import_nonce', true, false)) . "';
            window.amfmImportExport = {
                ajax_url: '" . \esc_js(\admin_url('admin-ajax.php')) . "'
            };
        ", 'before');
    }

    /**
     * Get post types options HTML
     */
    private function getPostTypesOptions(): string
    {
        $post_types = \get_post_types(['show_ui' => true], 'objects');
        
        // Remove unwanted post types
        unset($post_types['revision'], $post_types['nav_menu_item'], 
              $post_types['custom_css'], $post_types['customize_changeset'],
              $post_types['acf-field-group'], $post_types['acf-field']);

        $options = '';
        foreach ($post_types as $post_type) {
            $options .= sprintf(
                '<option value="%s">%s</option>',
                \esc_attr($post_type->name),
                \esc_html($post_type->label)
            );
        }
        
        return $options;
    }

    /**
     * Get field groups array
     */
    private function getFieldGroups(): array
    {
        if (\function_exists('acf_get_field_groups')) {
            return \acf_get_field_groups();
        }
        return [];
    }

    /**
     * Get ACF field groups HTML
     */
    private function getAcfFieldGroupsHtml(): string
    {
        $all_field_groups = $this->getFieldGroups();
        $html = '';
        
        if (!empty($all_field_groups)) {
            foreach ($all_field_groups as $group) {
                $html .= sprintf(
                    '<label><input type="checkbox" name="specific_acf_groups[]" value="%s"> <span>%s</span></label>',
                    \esc_attr($group['key']),
                    \esc_html($group['title'])
                );
            }
        } else {
            $html = '<p class="amfm-no-fields">No ACF field groups found. Make sure ACF is active and has field groups configured.</p>';
        }
        
        return $html;
    }

    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add Import/Export submenu under AMFM Tools
        \add_submenu_page(
            'amfm-tools',
            \__('Import/Export', 'amfm-tools'),
            \__('Import/Export', 'amfm-tools'),
            'manage_options',
            'amfm-tools-import-export',
            [$this, 'displayPage']
        );
    }

    /**
     * Handle admin initialization - framework auto-hook
     */
    public function actionAdminInit()
    {
        // Get service instances using the service() method
        $csvImportService = $this->service('csv_import');
        
        // Handle CSV imports
        if ($csvImportService) {
            $csvImportService->handleKeywordsUpload();
        }
    }

    /**
     * Enqueue admin assets - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm-tools-import-export') !== false) {
            // Assets are already enqueued in displayPage method
            // This method is here for consistency with other controllers
        }
    }
}