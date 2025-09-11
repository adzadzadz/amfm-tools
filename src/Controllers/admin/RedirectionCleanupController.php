<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use AdzWP\Core\View;
use App\Services\RedirectionCleanupService;

/**
 * Redirection Cleanup Admin Controller
 * 
 * Handles the admin interface for cleaning up internal redirections
 * by updating URLs throughout the WordPress site to point directly
 * to their final destinations.
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
            AMFM_TOOLS_URL . 'dist/js/redirection-cleanup.js',
            ['jquery'],
            AMFM_TOOLS_VERSION,
            true
        );

        wp_enqueue_style(
            'amfm-redirection-cleanup',
            AMFM_TOOLS_URL . 'dist/css/redirection-cleanup.css',
            [],
            AMFM_TOOLS_VERSION
        );

        wp_localize_script('amfm-redirection-cleanup', 'amfmRedirectionCleanup', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amfm_redirection_cleanup'),
            'strings' => [
                'analyzing' => __('Analyzing redirections...', 'amfm-tools'),
                'processing' => __('Processing content...', 'amfm-tools'),
                'complete' => __('Process complete!', 'amfm-tools'),
                'error' => __('An error occurred', 'amfm-tools'),
                'confirm_start' => __('This will update URLs throughout your site. Continue?', 'amfm-tools'),
                'confirm_rollback' => __('This will revert all changes. Are you sure?', 'amfm-tools')
            ]
        ]);
    }

    /**
     * Render admin page
     */
    public function renderAdminPage()
    {
        // Check if RankMath is active
        if (!$this->cleanupService->isRankMathActive()) {
            $view_data = [
                'title' => 'Redirection Cleanup',
                'error' => 'RankMath plugin is required but not active.',
                'plugin_url' => AMFM_TOOLS_URL,
                'plugin_version' => AMFM_TOOLS_VERSION
            ];
            echo View::render('admin/redirection-cleanup-error', $view_data, true, 'layouts/main');
            return;
        }

        // Get current analysis data
        $analysisData = $this->cleanupService->getAnalysisData();
        
        $view_data = [
            'title' => 'Redirection Cleanup',
            'active_tab' => 'redirection-cleanup',
            'analysis' => $analysisData,
            'can_process' => $analysisData['total_redirections'] > 0,
            'processing_options' => $this->getProcessingOptions(),
            'recent_jobs' => $this->cleanupService->getRecentJobs(),
            'plugin_url' => AMFM_TOOLS_URL,
            'plugin_version' => AMFM_TOOLS_VERSION
        ];

        echo View::render('admin/redirection-cleanup', $view_data, true, 'layouts/main');
    }

    /**
     * AJAX: Analyze redirections
     */
    public function actionWpAjaxAnalyzeRedirections()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        try {
            $analysis = $this->cleanupService->analyzeRedirections();
            wp_send_json_success($analysis);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Start cleanup process
     */
    public function actionWpAjaxStartCleanup()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $options = wp_unslash($_POST['options'] ?? []);
        
        try {
            $jobId = $this->cleanupService->startCleanupProcess($options);
            wp_send_json_success(['job_id' => $jobId]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Get cleanup progress
     */
    public function actionWpAjaxGetCleanupProgress()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $jobId = sanitize_text_field($_POST['job_id'] ?? '');
        
        if (empty($jobId)) {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }

        try {
            $progress = $this->cleanupService->getJobProgress($jobId);
            wp_send_json_success($progress);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Rollback changes
     */
    public function actionWpAjaxRollbackCleanup()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $jobId = sanitize_text_field($_POST['job_id'] ?? '');
        
        if (empty($jobId)) {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }

        try {
            $result = $this->cleanupService->rollbackChanges($jobId);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX: Get job details
     */
    public function actionWpAjaxGetJobDetails()
    {
        check_ajax_referer('amfm_redirection_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'amfm-tools'));
        }

        $jobId = sanitize_text_field($_POST['job_id'] ?? '');
        
        if (empty($jobId)) {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }

        try {
            $details = $this->cleanupService->getJobDetails($jobId);
            wp_send_json_success($details);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get processing options for the UI
     */
    private function getProcessingOptions(): array
    {
        return [
            'content_types' => [
                'posts' => [
                    'label' => __('Posts & Pages Content', 'amfm-tools'),
                    'description' => __('Update URLs in post/page content and excerpts', 'amfm-tools'),
                    'default' => true
                ],
                'custom_fields' => [
                    'label' => __('Custom Fields & Meta Data', 'amfm-tools'),
                    'description' => __('Update URLs in ACF fields and post meta', 'amfm-tools'),
                    'default' => true
                ],
                'menus' => [
                    'label' => __('Navigation Menus', 'amfm-tools'),
                    'description' => __('Update menu item URLs', 'amfm-tools'),
                    'default' => true
                ],
                'widgets' => [
                    'label' => __('Widgets & Customizer', 'amfm-tools'),
                    'description' => __('Update URLs in widget content and theme settings', 'amfm-tools'),
                    'default' => false
                ]
            ],
            'processing' => [
                'batch_size' => [
                    'label' => __('Batch Size', 'amfm-tools'),
                    'description' => __('Number of items to process per batch', 'amfm-tools'),
                    'default' => 50,
                    'min' => 10,
                    'max' => 200
                ],
                'dry_run' => [
                    'label' => __('Dry Run Mode', 'amfm-tools'),
                    'description' => __('Analyze what would be changed without making actual updates', 'amfm-tools'),
                    'default' => false
                ],
                'create_backup' => [
                    'label' => __('Create Backup', 'amfm-tools'),
                    'description' => __('Create database backup before processing', 'amfm-tools'),
                    'default' => true
                ]
            ],
            'url_handling' => [
                'include_relative' => [
                    'label' => __('Include Relative URLs', 'amfm-tools'),
                    'description' => __('Process relative URLs (/page) in addition to absolute URLs', 'amfm-tools'),
                    'default' => true
                ],
                'handle_query_params' => [
                    'label' => __('Handle Query Parameters', 'amfm-tools'),
                    'description' => __('Process URLs with query strings (?param=value)', 'amfm-tools'),
                    'default' => false
                ]
            ]
        ];
    }
}