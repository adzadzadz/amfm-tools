<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use App\Services\ACFService;

class ACFController extends Controller
{
    public $actions = [
        'admin_menu' => 'addAdminMenu',
    ];

    private $acfService;

    protected function bootstrap()
    {
        $this->acfService = new ACFService();
    }

    /**
     * Initialize ACF assets - framework auto-hook
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
        
        // Register ACF admin styles with Bootstrap and FontAwesome dependencies
        \AdzWP\Core\AssetManager::registerStyle('amfm-acf-style', [
            'url' => AMFM_TOOLS_URL . 'assets/css/admin-style.css',
            'dependencies' => ['bootstrap-css', 'fontawesome'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'media' => 'all'
        ]);
        
        // Register ACF admin script with Bootstrap and jQuery dependencies
        \AdzWP\Core\AssetManager::registerScript('amfm-acf-script', [
            'url' => AMFM_TOOLS_URL . 'assets/js/admin-script.js',
            'dependencies' => ['jquery', 'bootstrap-js'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'in_footer' => true
        ]);
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu()
    {
        add_submenu_page(
            'amfm-tools',
            'ACF',
            'ACF',
            'manage_options',
            'amfm-acf-fields',
            [$this, 'renderACFFieldsPage']
        );
    }

    /**
     * Render ACF fields page
     */
    public function renderACFFieldsPage()
    {
        $active_groups = $this->acfService->getActiveFieldGroups();
        $configured_groups = $this->acfService->getFieldGroups();
        $post_types = $this->acfService->getPostTypes();
        
        // Get WordPress registered post types for comparison
        $wp_post_types = get_post_types(['public' => true], 'objects');
        
        // Get ACF status information
        $acf_status = $this->getACFStatus();
        
        // Prepare data for the view
        $data = compact('active_groups', 'configured_groups', 'post_types', 'wp_post_types', 'acf_status');
        $data['title'] = 'ACF Management';
        $data['subtitle'] = 'Manage Advanced Custom Fields, field groups, and custom post types';
        $data['active_tab'] = 'acf';
        
        // Use the main layout
        echo \AdzWP\Core\View::render('admin/acf-fields', $data, true, 'layouts/main');
    }

    /**
     * Get field group statistics
     */
    public function getFieldGroupStats()
    {
        $configured = $this->acfService->getFieldGroups();
        $active = $this->acfService->getActiveFieldGroups();
        
        return [
            'configured_count' => count($configured),
            'active_count' => count($active),
            'field_counts' => array_map(function($group) {
                return isset($group['fields']) ? count($group['fields']) : 0;
            }, $configured)
        ];
    }
    
    /**
     * Get ACF status information
     */
    private function getACFStatus()
    {
        $status = [
            'plugin_active' => function_exists('acf_get_field_groups'),
            'version' => '',
            'pro_active' => false,
            'local_json_path' => '',
            'functions_available' => []
        ];
        
        if ($status['plugin_active']) {
            // Get ACF version
            if (function_exists('acf_get_setting')) {
                $status['version'] = acf_get_setting('version') ?: 'Unknown';
                $status['local_json_path'] = acf_get_setting('save_json') ?: 'Not set';
            }
            
            // Check if Pro version
            $status['pro_active'] = function_exists('acf_pro_get_license');
            
            // Check available functions
            $functions_to_check = [
                'acf_get_field_groups',
                'acf_add_local_field_group', 
                'acf_get_local_field_groups',
                'acf_register_block_type',
                'acf_add_options_page'
            ];
            
            foreach ($functions_to_check as $func) {
                $status['functions_available'][$func] = function_exists($func);
            }
        }
        
        return $status;
    }
}