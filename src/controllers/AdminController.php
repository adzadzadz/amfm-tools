<?php

namespace adz\controllers;

use AdzWP\WordPressController as Controller;
use adz\models\ImportModel;
use adz\models\ExportModel;
use adz\models\SettingsModel;

class AdminController extends Controller {

    public $actions = [
        'admin_menu' => 'setupAdminMenu',
        'admin_enqueue_scripts' => 'enqueueAdminAssets',
        'admin_init' => 'handleAdminInit',
        'wp_ajax_amfm_get_post_type_taxonomies' => 'ajaxGetPostTypeTaxonomies',
        'wp_ajax_amfm_export_data' => 'ajaxExportData',
        'wp_ajax_amfm_component_settings_update' => 'ajaxComponentSettingsUpdate',
        'wp_ajax_amfm_elementor_widgets_update' => 'ajaxElementorWidgetsUpdate'
    ];

    protected function bootstrap()
    {
        
    }

    public function setupAdminMenu()
    {
        // Check if main AMFM menu exists, if not create it
        if (!$this->mainMenuExists()) {
            add_menu_page(
                __('AMFM', 'amfm-tools'),
                __('AMFM', 'amfm-tools'),
                'manage_options',
                'amfm',
                array($this, 'renderAdminPage'),
                'dashicons-admin-tools',
                2
            );
        }
        
        // Add Tools submenu
        add_submenu_page(
            'amfm',
            __('Tools', 'amfm-tools'),
            __('Tools', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            array($this, 'renderAdminPage')
        );
    }

    public function enqueueAdminAssets($hook = null)
    {
        if (!$this->isAmfmAdminPage($hook)) {
            return;
        }

        wp_enqueue_style(
            'amfm-admin-style',
            plugin_dir_url(__FILE__) . '../assets/css/main.css',
            array(),
            '2.2.1'
        );

        wp_enqueue_script(
            'amfm-admin-script',
            plugin_dir_url(__FILE__) . '../assets/js/main.js',
            array('jquery'),
            '2.2.1',
            true
        );

        wp_localize_script('amfm-admin-script', 'amfm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amfm_nonce')
        ));
    }

    public function handleAdminInit()
    {
        $this->handleCsvUpload();
        $this->handleCategoryCsvUpload();
        $this->handleExport();
        $this->handleExcludedKeywordsUpdate();
        $this->handleElementorWidgetsUpdate();
        $this->handleComponentSettingsUpdate();
    }

    public function renderAdminPage()
    {
        $this->view('admin.dashboard');
    }

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

    private function isAmfmAdminPage($hook)
    {
        return strpos($hook, 'amfm') !== false;
    }

    // AJAX handlers
    public function ajaxGetPostTypeTaxonomies()
    {
        check_ajax_referer('amfm_nonce', 'nonce');
        
        $post_type = sanitize_text_field($_POST['post_type']);
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        
        wp_send_json_success($taxonomies);
    }

    public function ajaxExportData()
    {
        check_ajax_referer('amfm_nonce', 'nonce');
        
        // Export logic will be moved to ExportModel
        wp_send_json_success('Export functionality moved to model');
    }

    public function ajaxComponentSettingsUpdate()
    {
        check_ajax_referer('amfm_nonce', 'nonce');
        
        $components = $_POST['components'] ?? array();
        update_option('amfm_enabled_components', $components);
        
        wp_send_json_success('Components updated');
    }

    public function ajaxElementorWidgetsUpdate()
    {
        check_ajax_referer('amfm_nonce', 'nonce');
        
        $widgets = $_POST['widgets'] ?? array();
        update_option('amfm_elementor_widgets', $widgets);
        
        wp_send_json_success('Widgets updated');
    }

    private function handleCsvUpload()
    {
        if (!isset($_POST['amfm_csv_import_nonce']) || 
            !wp_verify_nonce($_POST['amfm_csv_import_nonce'], 'amfm_csv_import')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_FILES['csv_file'])) {
            $import_model = new ImportModel();
            $results = $import_model->processCsvUpload($_FILES['csv_file']);
            
            set_transient('amfm_csv_import_results', $results, 300);
            wp_redirect(admin_url('admin.php?page=amfm-tools&tab=import-export&imported=keywords'));
            exit;
        }
    }

    private function handleCategoryCsvUpload()
    {
        if (!isset($_POST['amfm_category_csv_import_nonce']) || 
            !wp_verify_nonce($_POST['amfm_category_csv_import_nonce'], 'amfm_category_csv_import')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_FILES['category_csv_file'])) {
            $import_model = new ImportModel();
            $results = $import_model->processCategoryCsvUpload($_FILES['category_csv_file']);
            
            set_transient('amfm_category_csv_import_results', $results, 300);
            wp_redirect(admin_url('admin.php?page=amfm-tools&tab=import-export&imported=categories'));
            exit;
        }
    }

    private function handleExport()
    {
        if (!isset($_POST['amfm_export_nonce']) || 
            !wp_verify_nonce($_POST['amfm_export_nonce'], 'amfm_export')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $export_model = new ExportModel();
        // Export handling logic will be in the model
    }

    private function handleExcludedKeywordsUpdate()
    {
        if (!isset($_POST['amfm_excluded_keywords_nonce']) || 
            !wp_verify_nonce($_POST['amfm_excluded_keywords_nonce'], 'amfm_excluded_keywords')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings_model = new SettingsModel();
        // Settings update logic will be in the model
    }

    private function handleElementorWidgetsUpdate()
    {
        if (!isset($_POST['amfm_elementor_widgets_nonce']) || 
            !wp_verify_nonce($_POST['amfm_elementor_widgets_nonce'], 'amfm_elementor_widgets')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings_model = new SettingsModel();
        // Elementor widgets update logic will be in the model
    }

    private function handleComponentSettingsUpdate()
    {
        if (!isset($_POST['amfm_component_nonce']) || 
            !wp_verify_nonce($_POST['amfm_component_nonce'], 'amfm_component_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $components = $_POST['components'] ?? array();
        update_option('amfm_enabled_components', $components);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Component settings updated successfully!</p></div>';
        });
    }

}