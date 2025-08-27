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
     * Add admin menu
     */
    public function addAdminMenu()
    {
        add_submenu_page(
            'amfm-tools',
            'ACF Fields',
            'ACF Fields',
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
        
        include AMFM_TOOLS_PATH . 'src/Views/admin/acf-fields.php';
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
}