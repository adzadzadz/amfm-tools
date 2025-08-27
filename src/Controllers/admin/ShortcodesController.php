<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\SettingsService;

/**
 * Shortcodes Admin Controller - handles shortcodes-related admin interface
 * 
 * Independent controller managing its own menu and pages
 */
class ShortcodesController extends Controller
{
    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        // Add Shortcodes submenu under AMFM Tools
        \add_submenu_page(
            'amfm-tools',
            \__('Shortcodes', 'amfm-tools'),
            \__('Shortcodes', 'amfm-tools'),
            'manage_options',
            'amfm-tools-shortcodes',
            [$this, 'renderAdminPage']
        );
    }

    /**
     * Render admin page
     */
    public function renderAdminPage()
    {
        // Check for import results
        $results = null;
        $show_results = false;
        
        if (isset($_GET['imported']) && $_GET['imported'] === 'keywords') {
            $results = \get_transient('amfm_csv_import_results');
            $show_results = true;
            \delete_transient('amfm_csv_import_results');
        }

        // Prepare data for views
        $view_data = [
            'active_tab' => 'shortcodes',
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION,
            'show_results' => $show_results,
            'results' => $results
        ];

        echo View::render('admin/shortcodes', $this->getShortcodesData($view_data), true, 'layouts/main');
    }

    /**
     * Get shortcodes tab data
     */
    private function getShortcodesData(array $base_data): array
    {
        $available_shortcodes = [
            'dkv_shortcode' => [
                'name' => 'DKV Shortcode',
                'description' => 'Dynamic keyword-based content display with advanced filtering options.',
                'icon' => '📄',
                'status' => 'Available'
            ],
            'limit_words' => [
                'name' => 'Limit Words',
                'description' => 'Text processing shortcode for content formatting and word limiting.',
                'icon' => '📝',
                'status' => 'Available'
            ],
            'text_utilities' => [
                'name' => 'Text Utilities',
                'description' => 'Collection of text processing and formatting shortcodes.',
                'icon' => '🔧',
                'status' => 'Available'
            ]
        ];
        
        // Get current excluded keywords and enabled shortcodes from service
        $settingsService = new SettingsService();
        $excluded_keywords = $settingsService->getExcludedKeywords();
        $enabled_shortcodes = $settingsService->getEnabledComponents();
        
        if (empty($excluded_keywords)) {
            // Initialize with defaults if not set
            $excluded_keywords = [
                'co-occurring',
                'life adjustment transition',
                'comorbidity',
                'comorbid',
                'co-morbidity',
                'co-morbid'
            ];
            $settingsService->updateExcludedKeywords(implode("\n", $excluded_keywords));
        }
        
        $keywords_text = is_array($excluded_keywords) ? implode("\n", $excluded_keywords) : '';
        
        return array_merge($base_data, [
            'available_shortcodes' => $available_shortcodes,
            'enabled_shortcodes' => $enabled_shortcodes,
            'excluded_keywords' => $excluded_keywords,
            'keywords_text' => $keywords_text
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
            $settingsService->handleExcludedKeywordsUpdate();
            $settingsService->handleDkvConfigUpdate();
        }
    }

    /**
     * Enqueue admin assets - framework auto-hook
     */
    public function actionAdminEnqueueScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'amfm-tools-shortcodes') !== false) {
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
                'dkv_config_nonce' => $this->createNonce('amfm_dkv_config_update')
            ]);
        }
    }

    /**
     * AJAX: Update DKV configuration - framework auto-hook
     */
    public function actionWpAjaxAmfmDkvConfigUpdate()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxDkvConfigUpdate();
    }
}