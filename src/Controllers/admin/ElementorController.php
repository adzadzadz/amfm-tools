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
            'title' => 'Widgets',
            'active_tab' => 'elementor',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION
        ];

        echo View::render('admin/elementor', $this->getElementorData($view_data), true, 'layouts/main');
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
     * Initialize assets using AssetManager - framework auto-hook
     */
    public function actionInit()
    {
        // Register elementor admin styles with Bootstrap dependency
        \AdzWP\Core\AssetManager::registerStyle('amfm-elementor-style', [
            'url' => AMFM_TOOLS_URL . 'assets/css/admin-style.css',
            'dependencies' => ['bootstrap-css'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'media' => 'all'
        ]);
        
        // Register elementor admin script with Bootstrap and jQuery dependencies
        \AdzWP\Core\AssetManager::registerScript('amfm-elementor-script', [
            'url' => AMFM_TOOLS_URL . 'assets/js/admin-script.js',
            'dependencies' => ['jquery', 'bootstrap-js'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'amfm_ajax',
                'data' => [
                    'ajax_url' => \admin_url('admin-ajax.php'),
                    'elementor_nonce' => $this->createNonce('amfm_elementor_widgets_nonce')
                ]
            ]
        ]);
    }

    /**
     * AJAX: Update Elementor widgets - framework auto-hook
     */
    public function actionWpAjaxAmfmElementorWidgetsUpdate()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxElementorWidgetsUpdate();
    }

    /**
     * AJAX: Toggle individual Elementor widget - framework auto-hook
     */
    public function actionWpAjaxAmfmToggleElementorWidget()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxToggleElementorWidget();
    }
}