<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\CsvImportService;
use App\Services\DataExportService;
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
        new DataExportService();
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
        $dataExportService = $this->service('data_export');
        
        // Handle CSV imports for categories (dashboard stats)
        if ($csvImportService) {
            $csvImportService->handleCategoriesUpload();
        }
        
        // Handle data export
        if ($dataExportService) {
            $dataExportService->handleDirectExport();
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
            'active_tab' => 'dashboard',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION,
            'show_category_results' => $show_category_results,
            'category_results' => $category_results
        ];

        // Render dashboard page
        echo View::render('admin/dashboard', $this->getDashboardData($view_data));
    }

    /**
     * Get dashboard tab data
     */
    private function getDashboardData(array $base_data): array
    {
        $available_components = [
            'acf_helper' => [
                'name' => 'ACF Helper',
                'description' => 'Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery.',
                'icon' => '🔧',
                'status' => 'Core Feature'
            ],
            'text_utilities' => [
                'name' => 'Text Utilities',
                'description' => 'Provides text processing shortcodes like [limit_words] for content formatting.',
                'icon' => '📝',
                'status' => 'Available'
            ],
            'optimization' => [
                'name' => 'Performance Optimization',
                'description' => 'Gravity Forms optimization and performance enhancements for faster page loading.',
                'icon' => '⚡',
                'status' => 'Available'
            ],
            'shortcodes' => [
                'name' => 'Shortcode System',
                'description' => 'DKV shortcode and other dynamic content shortcodes with advanced filtering options.',
                'icon' => '📄',
                'status' => 'Available'
            ],
            'elementor_widgets' => [
                'name' => 'Elementor Widgets',
                'description' => 'Custom Elementor widgets including Related Posts widget with keyword-based matching.',
                'icon' => '🎨',
                'status' => 'Available'
            ],
            'import_export' => [
                'name' => 'Import/Export Tools',
                'description' => 'Comprehensive data management for importing keywords, categories, and exporting posts with ACF fields.',
                'icon' => '📊',
                'status' => 'Core Feature'
            ]
        ];
        
        // Direct instantiation to bypass service resolution issues
        $settingsService = new \App\Services\SettingsService();
        $enabled_components = $settingsService->getEnabledComponents();
        
        return array_merge($base_data, [
            'available_components' => $available_components,
            'enabled_components' => $enabled_components
        ]);
    }





    /**
     * Enqueue admin assets - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm-tools') !== false && !strpos($hook_suffix, 'amfm-tools-')) {
            \wp_enqueue_style(
                'amfm-admin-style',
                AMFM_TOOLS_URL . 'assets/css/admin-style.css',
                [],
                AMFM_TOOLS_VERSION
            );
            
            \wp_enqueue_script(
                'amfm-admin-script',
                AMFM_TOOLS_URL . 'assets/js/admin-script.js',
                ['jquery'],
                AMFM_TOOLS_VERSION,
                true
            );

            // Localize script for AJAX
            \wp_localize_script('amfm-admin-script', 'amfm_ajax', [
                'ajax_url' => \admin_url('admin-ajax.php'),
                'export_nonce' => $this->createNonce('amfm_export_nonce')
            ]);
        }
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
}