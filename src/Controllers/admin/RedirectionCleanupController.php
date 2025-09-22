<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\RedirectionCleanupService;

/**
 * Redirection Cleanup Admin Controller
 *
 * Handles CSV upload and URL replacement interface
 */
class RedirectionCleanupController extends Controller
{
    private RedirectionCleanupService $cleanupService;

    public function __construct()
    {
        parent::__construct();
        $this->cleanupService = new RedirectionCleanupService();
    }

    /**
     * Add admin menu - framework auto-hook
     */
    public function actionAdminMenu()
    {
        add_submenu_page(
            'amfm-tools',
            __('Redirection Cleanup', 'amfm-tools'),
            __('Redirection Cleanup', 'amfm-tools'),
            'manage_options',
            'amfm-tools-redirection-cleanup',
            [$this, 'renderAdminPage']
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function actionAdminEnqueueScripts($hook)
    {
        if ($hook !== 'amfm-tools_page_amfm-tools-redirection-cleanup') {
            return;
        }

        wp_enqueue_script(
            'amfm-redirection-cleanup',
            AMFM_TOOLS_URL . 'assets/js/redirection-cleanup.js',
            ['jquery'],
            AMFM_TOOLS_VERSION,
            true
        );

        wp_enqueue_style(
            'amfm-redirection-cleanup',
            AMFM_TOOLS_URL . 'assets/css/redirection-cleanup.css',
            [],
            AMFM_TOOLS_VERSION
        );

        wp_localize_script('amfm-redirection-cleanup', 'amfmRedirectionCleanup', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amfm_redirection_cleanup'),
            'strings' => [
                'uploading' => __('Processing CSV...', 'amfm-tools'),
                'analyzing' => __('Analyzing content...', 'amfm-tools'),
                'processing' => __('Processing replacements...', 'amfm-tools'),
                'complete' => __('Process complete!', 'amfm-tools'),
                'error' => __('An error occurred', 'amfm-tools'),
                'confirm_process' => __('This will replace URLs in your content. Continue?', 'amfm-tools'),
                'confirm_clear' => __('This will remove all imported data. Are you sure?', 'amfm-tools')
            ]
        ]);
    }

    /**
     * Render admin page
     */
    public function renderAdminPage()
    {
        $notice = '';

        // Handle CSV upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            check_admin_referer('amfm_upload_csv', 'amfm_csv_nonce');

            $result = $this->cleanupService->processUploadedCsv($_FILES['csv_file']);

            if ($result['success']) {
                $notice = '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
            } else {
                $notice = '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }

        $currentData = $this->cleanupService->getCurrentData();
        $recentJobs = $this->cleanupService->getRecentJobs();

        $view_data = [
            'title' => 'Redirection Cleanup',
            'notice' => $notice,
            'current_data' => $currentData,
            'recent_jobs' => $recentJobs,
            'has_csv' => !empty($currentData['csv_file']),
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION
        ];

        echo View::render('admin/redirection-cleanup', $view_data, true, 'layouts/main');
    }

    /**
     * AJAX: Analyze content
     */
    public function actionWpAjaxAnalyzeContent()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $result = $this->cleanupService->analyzeContent();
        wp_send_json($result);
    }

    /**
     * AJAX: Process replacements
     */
    public function actionWpAjaxProcessReplacements()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $options = [
            'dry_run' => isset($_POST['dry_run']) && $_POST['dry_run'] === 'true',
            'content_types' => isset($_POST['content_types']) ? (array) $_POST['content_types'] : ['posts', 'postmeta'],
            'batch_size' => 50
        ];

        $result = $this->cleanupService->processReplacements($options);
        wp_send_json($result);
    }

    /**
     * AJAX: Clear all data
     */
    public function actionWpAjaxClearRedirectionData()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $success = $this->cleanupService->clearAllData();

        wp_send_json([
            'success' => $success,
            'message' => $success ? 'All data cleared successfully' : 'Failed to clear data'
        ]);
    }

    /**
     * AJAX: Fix malformed URLs
     */
    public function actionWpAjaxFixMalformedUrls()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $result = $this->cleanupService->fixMalformedUrls();
        wp_send_json($result);
    }

    /**
     * AJAX: Get current CSV stats
     */
    public function actionWpAjaxGetCsvStats()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $data = $this->cleanupService->getCurrentData();
        wp_send_json([
            'success' => true,
            'data' => $data
        ]);
    }
}