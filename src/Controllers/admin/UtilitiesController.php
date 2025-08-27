<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\SettingsService;

/**
 * Utilities Admin Controller - handles utilities-related admin interface
 * 
 * Independent controller managing its own menu and pages
 */
class UtilitiesController extends Controller
{
    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add Utilities submenu under AMFM Tools
        \add_submenu_page(
            'amfm-tools',
            \__('Utilities', 'amfm-tools'),
            \__('Utilities', 'amfm-tools'),
            'manage_options',
            'amfm-tools-utilities',
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
            'active_tab' => 'utilities',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION
        ];

        echo View::render('admin/utilities', $this->getUtilitiesData($view_data), true, 'layouts/main');
    }

    /**
     * Get utilities tab data
     */
    private function getUtilitiesData(array $base_data): array
    {
        $available_utilities = [
            'acf_helper' => [
                'name' => 'ACF Helper',
                'description' => 'Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery.',
                'icon' => '🔧',
                'status' => 'Core Feature'
            ],
            'import_export' => [
                'name' => 'Import/Export Tools',
                'description' => 'Comprehensive data management for importing keywords, categories, and exporting posts with ACF fields.',
                'icon' => '📊',
                'status' => 'Core Feature'
            ],
            'optimization' => [
                'name' => 'Performance Optimization',
                'description' => 'Gravity Forms optimization and performance enhancements for faster page loading.',
                'icon' => '⚡',
                'status' => 'Available'
            ]
        ];
        
        $settingsService = new SettingsService();
        $enabled_utilities = $settingsService->getEnabledComponents();
        
        // Ensure performance optimization is enabled by default
        if (!in_array('optimization', $enabled_utilities)) {
            $enabled_utilities[] = 'optimization';
            $settingsService->updateComponentSettings($enabled_utilities);
        }
        
        return array_merge($base_data, [
            'available_utilities' => $available_utilities,
            'enabled_utilities' => $enabled_utilities
        ]);
    }

    /**
     * Handle admin initialization - framework auto-hook
     */
    public function actionAdminInit()
    {
        // Handle settings updates
        $settingsService = $this->service('settings');
        if ($settingsService) {
            $settingsService->handleComponentSettingsUpdate();
        }
    }

    /**
     * Enqueue admin assets - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm-tools-utilities') !== false) {
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
                'component_nonce' => $this->createNonce('amfm_component_settings_nonce')
            ]);
        }
    }

    /**
     * AJAX: Update component settings - framework auto-hook
     */
    public function actionWpAjaxAmfmComponentSettingsUpdate()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxComponentSettingsUpdate();
    }
}