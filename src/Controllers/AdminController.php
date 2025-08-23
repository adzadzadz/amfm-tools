<?php

namespace App\Controllers;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\CSVImporter;
use App\Services\DataExporter;
use App\Services\AjaxHandler;
use App\Services\SettingsManager;

class AdminController extends Controller
{
    protected $csvImporter;
    protected $dataExporter;
    protected $ajaxHandler;
    protected $settingsManager;

    public $actions = [
        'admin_menu' => 'addAdminMenu',
        'admin_enqueue_scripts' => 'enqueueAdminAssets',
        'admin_init' => 'handleAdminInit',
        // AJAX actions
        'wp_ajax_amfm_get_post_type_taxonomies' => 'ajaxGetPostTypeTaxonomies',
        'wp_ajax_amfm_export_data' => 'ajaxExportData',
        'wp_ajax_amfm_component_settings_update' => 'ajaxComponentSettingsUpdate',
        'wp_ajax_amfm_elementor_widgets_update' => 'ajaxElementorWidgetsUpdate',
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Initialize service classes
        $this->csvImporter = new CSVImporter();
        $this->dataExporter = new DataExporter();
        $this->ajaxHandler = new AjaxHandler();
        $this->settingsManager = new SettingsManager();
    }

    /**
     * Handle admin initialization
     */
    public function handleAdminInit()
    {
        // Handle CSV imports
        $this->csvImporter->handleKeywordsUpload();
        $this->csvImporter->handleCategoriesUpload();
        
        // Handle data export
        $this->dataExporter->handleExport();
        
        // Handle settings updates
        $this->settingsManager->handleExcludedKeywordsUpdate();
        $this->settingsManager->handleElementorWidgetsUpdate();
        $this->settingsManager->handleComponentSettingsUpdate();
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu()
    {
        // Check if main AMFM menu exists, if not create it
        if (!$this->mainMenuExists()) {
            \add_menu_page(
                \__('AMFM', 'amfm-tools'),
                \__('AMFM', 'amfm-tools'),
                'manage_options',
                'amfm',
                [$this, 'renderAdminPage'],
                'dashicons-admin-tools',
                2
            );
        }
        
        // Add Tools submenu
        \add_submenu_page(
            'amfm',
            \__('Tools', 'amfm-tools'),
            \__('Tools', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            [$this, 'renderAdminPage']
        );
    }

    /**
     * Check if main menu exists
     */
    private function mainMenuExists()
    {
        global $menu;
        if (!is_array($menu)) {
            return false;
        }
        
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'amfm') {
                return true;
            }
        }
        return false;
    }

    /**
     * Render admin page
     */
    public function renderAdminPage()
    {
        $active_tab = isset($_GET['tab']) ? \sanitize_text_field($_GET['tab']) : 'dashboard';
        
        // Check for import results
        $results = null;
        $category_results = null;
        $show_results = false;
        $show_category_results = false;
        
        if (isset($_GET['imported'])) {
            if ($_GET['imported'] === 'categories') {
                $category_results = \get_transient('amfm_category_csv_import_results');
                $show_category_results = true;
                \delete_transient('amfm_category_csv_import_results');
            } elseif ($_GET['imported'] === 'keywords') {
                $results = \get_transient('amfm_csv_import_results');
                $show_results = true;
                \delete_transient('amfm_csv_import_results');
            }
        }

        // Prepare data for views
        $view_data = [
            'active_tab' => $active_tab,
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION,
            'show_results' => $show_results,
            'show_category_results' => $show_category_results,
            'results' => $results,
            'category_results' => $category_results
        ];

        // Render the appropriate tab
        switch ($active_tab) {
            case 'dashboard':
                echo View::render('admin/dashboard', $this->getDashboardData($view_data));
                break;
            case 'import-export':
                echo View::render('admin/import-export', $this->getImportExportData($view_data));
                break;
            case 'shortcodes':
                echo View::render('admin/shortcodes', $this->getShortcodesData($view_data));
                break;
            case 'elementor':
                echo View::render('admin/elementor', $this->getElementorData($view_data));
                break;
            default:
                echo View::render('admin/main', $view_data);
        }
    }

    /**
     * Get dashboard tab data
     */
    private function getDashboardData($base_data)
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
        
        $enabled_components = \get_option('amfm_enabled_components', array_keys($available_components));
        
        return array_merge($base_data, [
            'available_components' => $available_components,
            'enabled_components' => $enabled_components
        ]);
    }

    /**
     * Get import/export tab data
     */
    private function getImportExportData($base_data)
    {
        // Get all post types
        $post_types = \get_post_types(['show_ui' => true], 'objects');
        
        // Remove unwanted post types
        unset(
            $post_types['revision'],
            $post_types['nav_menu_item'],
            $post_types['custom_css'],
            $post_types['customize_changeset'],
            $post_types['acf-field-group'],
            $post_types['acf-field']
        );

        // Get selected post type if any
        $selected_post_type = isset($_POST['export_post_type']) ? \sanitize_key($_POST['export_post_type']) : '';
        
        // Get taxonomies for selected post type
        $post_type_taxonomies = [];
        if ($selected_post_type) {
            $post_type_taxonomies = \get_object_taxonomies($selected_post_type, 'objects');
        }
        
        // Get all ACF field groups
        $all_field_groups = [];
        if (function_exists('acf_get_field_groups')) {
            $all_field_groups = \acf_get_field_groups();
        }
        
        return array_merge($base_data, [
            'post_types' => $post_types,
            'selected_post_type' => $selected_post_type,
            'post_type_taxonomies' => $post_type_taxonomies,
            'all_field_groups' => $all_field_groups
        ]);
    }

    /**
     * Get shortcodes tab data
     */
    private function getShortcodesData($base_data)
    {
        // Get current excluded keywords
        $excluded_keywords = \get_option('amfm_excluded_keywords', null);
        if ($excluded_keywords === null) {
            // Initialize with defaults if not set
            $excluded_keywords = [
                'co-occurring',
                'life adjustment transition',
                'comorbidity',
                'comorbid',
                'co-morbidity',
                'co-morbid'
            ];
            \update_option('amfm_excluded_keywords', $excluded_keywords);
        }
        
        $keywords_text = is_array($excluded_keywords) ? implode("\n", $excluded_keywords) : '';
        
        return array_merge($base_data, [
            'excluded_keywords' => $excluded_keywords,
            'keywords_text' => $keywords_text
        ]);
    }

    /**
     * Get Elementor tab data
     */
    private function getElementorData($base_data)
    {
        $available_widgets = [
            'amfm_related_posts' => [
                'name' => 'AMFM Related Posts',
                'description' => 'Display related posts based on ACF keywords with customizable layouts and styling options.',
                'icon' => '📰'
            ]
        ];
        
        $enabled_widgets = \get_option('amfm_elementor_enabled_widgets', array_keys($available_widgets));
        
        return array_merge($base_data, [
            'available_widgets' => $available_widgets,
            'enabled_widgets' => $enabled_widgets
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm') !== false) {
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
                'export_nonce' => \wp_create_nonce('amfm_export_nonce'),
                'component_nonce' => \wp_create_nonce('amfm_component_settings_nonce'),
                'elementor_nonce' => \wp_create_nonce('amfm_elementor_widgets_nonce')
            ]);
        }
    }

    /**
     * AJAX: Get post type taxonomies
     */
    public function ajaxGetPostTypeTaxonomies()
    {
        $this->ajaxHandler->getPostTypeTaxonomies();
    }

    /**
     * AJAX: Export data
     */
    public function ajaxExportData()
    {
        $this->ajaxHandler->exportData();
    }

    /**
     * AJAX: Update component settings
     */
    public function ajaxComponentSettingsUpdate()
    {
        $this->settingsManager->ajaxComponentSettingsUpdate();
    }

    /**
     * AJAX: Update Elementor widgets
     */
    public function ajaxElementorWidgetsUpdate()
    {
        $this->settingsManager->ajaxElementorWidgetsUpdate();
    }
}