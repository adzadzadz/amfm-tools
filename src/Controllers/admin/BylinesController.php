<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;

class BylinesController extends Controller
{
    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        \add_submenu_page(
            'amfm-tools',
            \__('Bylines', 'amfm-tools'),
            \__('Bylines', 'amfm-tools'),
            'manage_options',
            'amfm-tools-bylines',
            [$this, 'renderAdminPage']
        );
    }


    /**
     * Initialize assets - framework auto-hook
     */
    public function actionInit()
    {
        // Register CSS
        \AdzWP\Core\AssetManager::registerStyle('amfm-admin-style', [
            'url' => AMFM_TOOLS_URL . 'assets/css/admin-style.css',
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin']
        ]);

        // Register JS
        \AdzWP\Core\AssetManager::registerScript('amfm-bylines-admin', [
            'url' => AMFM_TOOLS_URL . 'assets/js/admin-script.js',
            'dependencies' => ['jquery', 'jquery-ui-sortable'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'amfmLocalize',
                'data' => [
                    'ajax_url' => \admin_url('admin-ajax.php'),
                    'updateStaffOrderNonce' => $this->createNonce('update_staff_order_nonce')
                ]
            ]
        ]);
    }

    /**
     * Add categories and tags support to pages
     */
    public function actionInit2()
    {
        \register_taxonomy_for_object_type('category', 'page');
        \register_taxonomy_for_object_type('post_tag', 'page');
    }

    /**
     * Render main bylines admin page
     */
    public function renderAdminPage()
    {
        // Get staff count for header
        $staff_query = new \WP_Query([
            'post_type' => 'staff',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        $staff_count = $staff_query->found_posts;
        
        // Create header right section with staff count
        $header_right = '<div class="d-flex align-items-center justify-content-md-end justify-content-start gap-2">';
        $header_right .= '<span class="badge bg-primary text-white fs-6 px-3 py-2">' . $staff_count . ' staff members</span>';
        $header_right .= '</div>';
        
        $view_data = [
            'title' => 'Staff Management',
            'subtitle' => 'Drag and drop to reorder staff members. Click on a card to edit in WordPress.',
            'header_icon' => 'fas fa-users',
            'header_right' => $header_right,
            'active_tab' => 'bylines'
        ];

        echo View::render('admin/bylines', $view_data, true, 'layouts/main');
    }





    /**
     * AJAX: Update staff order - framework auto-hook
     */
    public function actionWpAjaxAmfmUpdateStaffOrder()
    {
        \check_ajax_referer('update_staff_order_nonce', 'nonce');
        
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $i = 1;
            
            foreach ($ids as $index => $id) {
                if ($index === 0) {
                    \update_field('amfm_sort', 1, $id);
                } else {
                    \update_field('amfm_sort', $i, $id);
                }
                $i++;
            }
            
            \wp_send_json_success('success');
        } else {
            \wp_send_json_error('Invalid data');
        }
    }
}