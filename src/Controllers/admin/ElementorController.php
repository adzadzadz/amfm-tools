<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\SettingsService;

/**
 * Elementor Admin Controller - handles Elementor-related admin interface
 * 
 * Independent controller managing its own menu and pages
 */
class ElementorController extends Controller
{
    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add Elementor submenu under AMFM Tools
        \add_submenu_page(
            'amfm-tools',
            \__('Elementor', 'amfm-tools'),
            \__('Elementor', 'amfm-tools'),
            'manage_options',
            'amfm-tools-elementor',
            [$this, 'renderAdminPage']
        );
    }

    /**
     * Render admin page
     */
    public function renderAdminPage()
    {
        // Prepare data for views
        $view_data = [
            'active_tab' => 'elementor',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION
        ];

        echo View::render('admin/elementor', $this->getElementorData($view_data));
    }

    /**
     * Get Elementor tab data
     */
    private function getElementorData(array $base_data): array
    {
        $available_widgets = [
            'amfm_related_posts' => [
                'name' => 'AMFM Related Posts',
                'description' => 'Display related posts based on ACF keywords with customizable layouts and styling options.',
                'icon' => '📰'
            ]
        ];
        
        $settingsService = new SettingsService();
        $enabled_widgets = $settingsService->getEnabledElementorWidgets();
        
        return array_merge($base_data, [
            'available_widgets' => $available_widgets,
            'enabled_widgets' => $enabled_widgets
        ]);
    }

    /**
     * Enqueue admin assets - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm-tools-elementor') !== false) {
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
                'elementor_nonce' => $this->createNonce('amfm_elementor_widgets_nonce')
            ]);
        }
    }

    /**
     * AJAX: Update Elementor widgets - framework auto-hook
     */
    public function actionWpAjaxAmfmElementorWidgetsUpdate()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxElementorWidgetsUpdate();
    }
}