<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class AdminController extends Controller
{
    public $actions = [
        'admin_menu' => 'addAdminMenu',
        'admin_enqueue_scripts' => 'enqueueAdminAssets',
        'wp_ajax_save_amfm_components' => 'saveComponentSettings',
        'admin_init' => 'handleFormSubmissions'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Additional initialization if needed
    }

    public function addAdminMenu()
    {
        // Check if main AMFM menu exists, if not create it
        if (!$this->mainMenuExists()) {
            add_menu_page(
                __('AMFM', 'amfm-tools'), // Page title
                __('AMFM', 'amfm-tools'), // Menu title
                'manage_options', // Capability
                'amfm', // Menu slug
                [$this, 'renderAdminPage'], // Callback function
                'dashicons-admin-tools', // Icon
                2 // Position
            );
        }
        
        // Add Tools submenu
        add_submenu_page(
            'amfm',
            __('Tools', 'amfm-tools'),
            __('Tools', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            [$this, 'renderAdminPage']
        );
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

    public function renderAdminPage()
    {
        include AMFM_TOOLS_PATH . 'views/admin/main.php';
    }

    public function enqueueAdminAssets($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm') !== false) {
            wp_enqueue_style(
                'amfm-admin-style',
                AMFM_TOOLS_URL . 'assets/css/main.css',
                [],
                AMFM_TOOLS_VERSION
            );
            
            wp_enqueue_script(
                'amfm-admin-script',
                AMFM_TOOLS_URL . 'assets/js/main.js',
                ['jquery'],
                AMFM_TOOLS_VERSION,
                true
            );

            // Localize script for AJAX
            wp_localize_script('amfm-admin-script', 'amfm_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('amfm_ajax_nonce')
            ]);
        }
    }

    public function handleFormSubmissions()
    {
        // Handle component settings form submission
        if (isset($_POST['save_components']) && check_admin_referer('amfm_component_settings', 'amfm_nonce')) {
            $components = isset($_POST['amfm_components']) ? array_map('sanitize_text_field', $_POST['amfm_components']) : [];
            
            // Always ensure core components are included
            $core_components = ['acf_helper', 'import_export'];
            $components = array_merge($components, $core_components);
            $components = array_unique($components);
            
            update_option('amfm_enabled_components', $components);
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Component settings saved successfully!</p></div>';
            });
        }
    }
}
