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
            'title' => 'Shortcode Management',
            'subtitle' => 'Dynamic Content & Text Processing',
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
        // Get shortcode data from the main ShortcodeController
        $shortcodeController = new \App\Controllers\ShortcodeController();
        $available_shortcodes_data = $shortcodeController->getAvailableShortcodes();
        
        // Transform data for admin display
        $available_shortcodes = [];
        foreach ($available_shortcodes_data as $tag => $info) {
            $available_shortcodes[$tag] = [
                'name' => $info['name'],
                'description' => $info['description'],
                'icon' => $this->getShortcodeIcon($tag),
                'status' => 'Available'
            ];
        }
        
        // Get current excluded keywords and shortcode status from config
        $settingsService = new SettingsService();
        $excluded_keywords = $settingsService->getExcludedKeywords();
        $config = \Adz::config();
        
        // Get enabled shortcodes from config
        $enabled_shortcodes = [];
        // Check existing shortcodes
        if ($config->get('shortcodes.dkv', true)) $enabled_shortcodes[] = 'dkv';
        if ($config->get('shortcodes.limit_words', true)) $enabled_shortcodes[] = 'limit_words';
        if ($config->get('shortcodes.text_util', true)) $enabled_shortcodes[] = 'text_util';
        
        // Check bylines shortcodes
        if ($config->get('shortcodes.amfm_info', true)) $enabled_shortcodes[] = 'amfm_info';
        if ($config->get('shortcodes.amfm_author_url', true)) $enabled_shortcodes[] = 'amfm_author_url';
        if ($config->get('shortcodes.amfm_editor_url', true)) $enabled_shortcodes[] = 'amfm_editor_url';
        if ($config->get('shortcodes.amfm_reviewer_url', true)) $enabled_shortcodes[] = 'amfm_reviewer_url';
        if ($config->get('shortcodes.amfm_bylines_grid', true)) $enabled_shortcodes[] = 'amfm_bylines_grid';
        if ($config->get('shortcodes.amfm_acf', true)) $enabled_shortcodes[] = 'amfm_acf';
        if ($config->get('shortcodes.amfm_acf_object', true)) $enabled_shortcodes[] = 'amfm_acf_object';
        
        if (empty($excluded_keywords)) {
            // Initialize with defaults if not set
            $excluded_keywords = [];
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
     * Initialize assets using AssetManager - framework auto-hook
     */
    public function actionInit()
    {
        // Register shortcodes admin styles with Bootstrap dependency
        \AdzWP\Core\AssetManager::registerStyle('amfm-shortcodes-style', [
            'url' => AMFM_TOOLS_URL . 'assets/css/admin-style.css',
            'dependencies' => ['bootstrap-css'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'media' => 'all'
        ]);
        
        // Register shortcodes admin script with Bootstrap and jQuery dependencies
        \AdzWP\Core\AssetManager::registerScript('amfm-shortcodes-script', [
            'url' => AMFM_TOOLS_URL . 'assets/js/admin-script.js',
            'dependencies' => ['jquery', 'bootstrap-js'],
            'version' => AMFM_TOOLS_VERSION,
            'contexts' => ['plugin'],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'amfm_ajax',
                'data' => [
                    'ajax_url' => \admin_url('admin-ajax.php'),
                    'dkv_config_nonce' => $this->createNonce('amfm_dkv_config_update')
                ]
            ]
        ]);
    }

    /**
     * AJAX: Update DKV configuration - framework auto-hook
     */
    public function actionWpAjaxAmfmDkvConfigUpdate()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxDkvConfigUpdate();
    }

    /**
     * AJAX: Toggle shortcode status - framework auto-hook
     */
    public function actionWpAjaxAmfmToggleShortcode()
    {
        $settingsService = new SettingsService();
        $settingsService->ajaxToggleComponent();
    }

    /**
     * AJAX: Get current DKV configuration - framework auto-hook
     */
    public function actionWpAjaxAmfmGetDkvConfig()
    {
        // Verify nonce and permissions
        if (!check_ajax_referer('amfm_dkv_config_update', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $settingsService = new SettingsService();
        
        $config = [
            'keywords' => implode("\n", $settingsService->getExcludedKeywords()),
            'fallback' => $settingsService->getDkvDefaultFallback(),
            'cache_duration' => $settingsService->getDkvCacheDuration()
        ];
        
        wp_send_json_success($config);
    }

    /**
     * AJAX: Load shortcode content for drawer - framework auto-hook
     */
    public function actionWpAjaxAmfmLoadShortcodeContent()
    {
        // Verify nonce and permissions
        if (!check_ajax_referer('amfm_shortcode_content', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $shortcode_key = sanitize_text_field($_POST['shortcode_key'] ?? '');
        $mode = sanitize_text_field($_POST['mode'] ?? 'docs'); // 'docs' or 'config'
        
        if (empty($shortcode_key)) {
            wp_send_json_error('Invalid shortcode key');
            return;
        }

        // Get shortcode info from main controller
        $shortcodeController = new \App\Controllers\ShortcodeController();
        $available_shortcodes = $shortcodeController->getAvailableShortcodes();
        
        if (!isset($available_shortcodes[$shortcode_key])) {
            wp_send_json_error('Shortcode not found');
            return;
        }

        $shortcode_info = $available_shortcodes[$shortcode_key];
        
        // Render the appropriate view
        $view_path = "admin/shortcodes/{$mode}/{$shortcode_key}";
        $fallback_path = "admin/shortcodes/{$mode}/default";
        
        try {
            // Try specific template first
            if ($this->templateExists($view_path)) {
                $content = View::render($view_path, [
                    'shortcode_key' => $shortcode_key,
                    'shortcode_info' => $shortcode_info
                ], true, false); // Return content, no layout
            } else {
                // Fall back to default template
                $content = View::render($fallback_path, [
                    'shortcode_key' => $shortcode_key,
                    'shortcode_info' => $shortcode_info,
                    'mode' => $mode
                ], true, false); // Return content, no layout
            }
            
            wp_send_json_success([
                'title' => $shortcode_info['name'] . ' ' . ucfirst($mode),
                'content' => $content
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to load content: ' . $e->getMessage());
        }
    }

    /**
     * Check if a view template exists
     */
    private function templateExists($view_path)
    {
        // Check if template file exists in the views directory
        $template_file = AMFM_TOOLS_PATH . 'src/Views/' . str_replace('.', '/', $view_path) . '.php';
        return file_exists($template_file);
    }

    /**
     * Get icon for shortcode type
     */
    private function getShortcodeIcon(string $tag): string
    {
        $icons = [
            'dkv' => 'fas fa-file-alt',
            'limit_words' => 'fas fa-edit',
            'text_util' => 'fas fa-tools',
            'amfm_info' => 'fas fa-user',
            'amfm_author_url' => 'fas fa-pen-fancy',
            'amfm_editor_url' => 'fas fa-pen',
            'amfm_reviewer_url' => 'fas fa-user-md',
            'amfm_bylines_grid' => 'fas fa-users',
            'amfm_acf' => 'fas fa-tag',
            'amfm_acf_object' => 'fas fa-folder-open'
        ];

        return $icons[$tag] ?? 'fas fa-clipboard';
    }
}