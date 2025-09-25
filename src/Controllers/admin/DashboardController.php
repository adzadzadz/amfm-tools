<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\CsvImportService;
use App\Services\CsvExportService;
use App\Services\AjaxService;
use App\Services\SettingsService;

/**
 * Dashboard Controller - handles the main dashboard interface and functionality
 * 
 * Uses the framework's auto hook registration and service dependency injection
 */
class DashboardController extends Controller
{
    /**
     * Initialize services on WordPress init
     */
    public function actionWpInit()
    {
        // Services are auto-instantiated when accessed via magic properties
        // This ensures proper dependency injection and service registration
        new CsvImportService();
        new CsvExportService();
        new AjaxService();
        new SettingsService();
    }

    /**
     * Handle admin initialization - framework auto-hook
     */
    public function actionAdminInit()
    {
        // Get service instances using the service() method
        $csvImportService = $this->service('csv_import');
        $csvExportService = $this->service('csv_export');
        
        // Handle CSV imports for categories (dashboard stats)
        if ($csvImportService) {
            $csvImportService->handleCategoriesUpload();
        }
        
        // Handle data export
        if ($csvExportService) {
            $csvExportService->handleExport();
        }
    }

    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add main AMFM Tools menu
        \add_menu_page(
            \__('AMFM Tools', 'amfm-tools'),
            \__('AMFM Tools', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            [$this, 'renderAdminPage'],
            'dashicons-admin-tools',
            2
        );
        
        // Add Dashboard submenu (same slug as main menu for default page)
        \add_submenu_page(
            'amfm-tools',
            \__('Dashboard', 'amfm-tools'),
            \__('Dashboard', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            [$this, 'renderAdminPage']
        );
    }


    /**
     * Render admin page
     */
    public function renderAdminPage()
    {
        // Check for import results (category imports for dashboard stats)
        $category_results = null;
        $show_category_results = false;
        
        if (isset($_GET['imported']) && $_GET['imported'] === 'categories') {
            $category_results = \get_transient('amfm_category_csv_import_results');
            $show_category_results = true;
            \delete_transient('amfm_category_csv_import_results');
        }

        // Prepare data for dashboard view
        $view_data = [
            'title' => 'Dashboard',
            'active_tab' => 'dashboard',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION,
            'show_category_results' => $show_category_results,
            'category_results' => $category_results
        ];

        // Render dashboard page
        echo View::render('admin/dashboard', $view_data, true, 'layouts/main');
    }

    /**
     * Initialize assets using AssetManager - framework auto-hook
     */
    public function actionInit()
    {
        // Register FontAwesome for modern icons
        \AdzWP\Core\AssetManager::registerStyle('fontawesome', [
            'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            'dependencies' => [],
            'version' => '6.0.0',
            'contexts' => ['plugin'],
            'media' => 'all'
        ]);
        
        // Register admin styles with Bootstrap and FontAwesome dependencies
        \AdzWP\Core\AssetManager::registerStyle('amfm-admin-style', [
            'url' => AMFM_TOOLS_URL . 'assets/css/admin-style.css',
            'dependencies' => ['bootstrap-css', 'fontawesome'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'media' => 'all'
        ]);
        
        // Register admin script with Bootstrap and jQuery dependencies
        \AdzWP\Core\AssetManager::registerScript('amfm-admin-script', [
            'url' => AMFM_TOOLS_URL . 'assets/js/admin-script.js',
            'dependencies' => ['jquery', 'bootstrap-js'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'amfm_ajax',
                'data' => [
                    'ajax_url' => \admin_url('admin-ajax.php'),
                    'export_nonce' => $this->createNonce('amfm_export_nonce'),
                    'update_channel_nonce' => $this->createNonce('amfm_update_channel_nonce')
                ]
            ]
        ]);

        // AssetManager handles localization via the 'localize' parameter above
    }

    /**
     * Enqueue scripts for all AMFM Tools admin pages - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook)
    {
        // Only load on AMFM Tools admin pages
        if (strpos($hook, 'amfm-tools') === false) {
            return;
        }

        // Enqueue the script with localized AJAX data
        wp_enqueue_script(
            'amfm-admin-script',
            AMFM_TOOLS_URL . 'assets/js/admin-script.js',
            ['jquery'],
            AMFM_TOOLS_VERSION,
            true
        );

        // Localize the script with comprehensive AJAX data for all pages
        wp_localize_script('amfm-admin-script', 'amfm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'export_nonce' => $this->createNonce('amfm_export_nonce'),
            'update_channel_nonce' => $this->createNonce('amfm_update_channel_nonce'),
            'component_nonce' => $this->createNonce('amfm_component_settings_nonce'),
            'shortcode_nonce' => $this->createNonce('amfm_component_settings_nonce'),
            'shortcode_content_nonce' => $this->createNonce('amfm_shortcode_content'),
            'dkv_config_nonce' => $this->createNonce('amfm_dkv_config_update'),
            'elementor_nonce' => $this->createNonce('amfm_elementor_widgets_nonce')
        ]);
    }

    /**
     * AJAX: Get post type taxonomies - framework auto-hook
     */
    public function actionWpAjaxAmfmGetPostTypeTaxonomies()
    {
        $ajaxService = $this->service('ajax');
        if ($ajaxService) {
            $ajaxService->getPostTypeTaxonomies();
        }
    }
    
    /**
     * AJAX: Get ACF field groups - framework auto-hook
     */
    public function actionWpAjaxAmfmGetAcfFieldGroups()
    {
        $ajaxService = $this->service('ajax');
        if ($ajaxService) {
            $ajaxService->getAcfFieldGroups();
        }
    }

    /**
     * AJAX: Export data - framework auto-hook
     */
    public function actionWpAjaxAmfmExportData()
    {
        $ajaxService = $this->service('ajax');
        if ($ajaxService) {
            $ajaxService->exportData();
        }
    }

    /**
     * AJAX: Update channel change - framework auto-hook
     */
    public function actionWpAjaxAmfmUpdateChannel()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'amfm_update_channel_nonce')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $channel = sanitize_text_field($_POST['channel'] ?? '');

        if (!in_array($channel, ['stable', 'development'])) {
            wp_send_json_error('Invalid channel');
        }

        // Get the plugin updater service and update the channel
        $updater = new \App\Services\PluginUpdaterService();

        if ($updater->setUpdateChannel($channel)) {
            wp_send_json_success([
                'message' => 'Update channel changed to ' . $channel,
                'channel' => $channel
            ]);
        } else {
            wp_send_json_error('Failed to update channel');
        }
    }
}