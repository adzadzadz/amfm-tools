<?php

namespace App\Services;

/**
 * Redirection Cleanup Service
 * 
 * Core service for analyzing and cleaning up internal redirections
 * by updating URLs throughout WordPress to point directly to final destinations.
 */
class RedirectionCleanupService
{
    private const OPTION_PREFIX = 'amfm_redirection_cleanup_';
    private const TABLE_REDIRECTIONS = 'rank_math_redirections';
    private const TABLE_CACHE = 'rank_math_redirections_cache';
    
    private $wpdb;
    private $siteUrl;
    private $homeUrl;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->siteUrl = rtrim(site_url(), '/');
        $this->homeUrl = rtrim(home_url(), '/');
    }

    /**
     * Check if RankMath is active
     */
    public function isRankMathActive(): bool
    {
        return class_exists('RankMath\\Helper') && 
               $this->wpdb->get_var("SHOW TABLES LIKE '{$this->wpdb->prefix}" . self::TABLE_REDIRECTIONS . "'");
    }

    /**
     * Get current analysis data for the dashboard
     */
    public function getAnalysisData(): array
    {
        $cached = get_option(self::OPTION_PREFIX . 'analysis_cache', []);
        
        if (!empty($cached) && (time() - $cached['timestamp']) < 300) { // 5-minute cache
            return $cached['data'];
        }

        $data = [
            'total_redirections' => $this->getTotalRedirections(),
            'active_redirections' => $this->getActiveRedirections(),
            'redirect_chains' => $this->countRedirectChains(),
            'last_analyzed' => current_time('mysql'),
            'estimated_content_items' => $this->estimateContentItems(),
            'top_redirected_sources' => $this->getTopRedirectedSources(),
            'redirect_types' => $this->getRedirectTypeBreakdown()
        ];

        update_option(self::OPTION_PREFIX . 'analysis_cache', [
            'timestamp' => time(),
            'data' => $data
        ], false);

        return $data;
    }

    /**
     * Perform comprehensive redirection analysis
     */
    public function analyzeRedirections(): array
    {
        set_time_limit(300); // 5 minutes max
        
        $redirections = $this->getAllActiveRedirections();
        $urlMap = $this->buildUrlMapping($redirections);
        $contentScan = $this->scanContentForUrls($urlMap);
        
        $analysis = [
            'total_redirections' => count($redirections),
            'url_mappings' => count($urlMap),
            'redirect_chains_resolved' => $this->countResolvedChains($urlMap),
            'content_analysis' => $contentScan,
            'estimated_updates' => $this->estimateRequiredUpdates($contentScan),
            'processing_time_estimate' => $this->estimateProcessingTime($contentScan),
            'url_mapping' => $urlMap,
            'analysis_timestamp' => current_time('mysql')
        ];

        // Cache the full analysis
        update_option(self::OPTION_PREFIX . 'full_analysis', $analysis, false);
        
        return $analysis;
    }

    /**
     * Start the cleanup process
     */
    public function startCleanupProcess(array $options): string
    {
        // Validate options
        $options = wp_parse_args($options, [
            'content_types' => ['posts', 'custom_fields', 'menus'],
            'batch_size' => 50,
            'dry_run' => false,
            'create_backup' => true,
            'include_relative' => true,
            'handle_query_params' => false
        ]);

        // Convert string boolean values to actual booleans
        $options['dry_run'] = filter_var($options['dry_run'], FILTER_VALIDATE_BOOLEAN);
        $options['create_backup'] = false; // Temporarily disabled for performance
        $options['include_relative'] = filter_var($options['include_relative'], FILTER_VALIDATE_BOOLEAN);
        $options['handle_query_params'] = filter_var($options['handle_query_params'], FILTER_VALIDATE_BOOLEAN);

        // Generate unique job ID
        $jobId = wp_generate_uuid4();
        
        // Get URL mapping from cached analysis
        $fullAnalysis = get_option(self::OPTION_PREFIX . 'full_analysis', []);
        if (empty($fullAnalysis['url_mapping'])) {
            throw new \Exception('No analysis data found. Please run analysis first.');
        }

        // Create backup if requested
        if ($options['create_backup'] && !$options['dry_run']) {
            $this->createBackup($jobId);
        }

        // Initialize job tracking
        $jobData = [
            'id' => $jobId,
            'status' => 'initialized',
            'options' => $options,
            'url_mapping' => $fullAnalysis['url_mapping'],
            'started_at' => current_time('mysql'),
            'progress' => [
                'total_items' => 0,
                'processed_items' => 0,
                'updated_items' => 0,
                'current_step' => 'initializing',
                'errors' => []
            ],
            'results' => [
                'posts_updated' => 0,
                'custom_fields_updated' => 0,
                'menus_updated' => 0,
                'widgets_updated' => 0,
                'total_url_replacements' => 0
            ]
        ];

        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);

        // Schedule background processing
        wp_schedule_single_event(time() + 5, 'amfm_process_redirection_cleanup', [$jobId]);

        return $jobId;
    }

    /**
     * Process cleanup job (called by cron)
     */
    public function processCleanupJob(string $jobId): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        if (empty($jobData)) {
            return;
        }

        try {
            $jobData['status'] = 'processing';
            $this->updateJobProgress($jobId, $jobData);

            $urlMapping = $jobData['url_mapping'];
            $options = $jobData['options'];

            // Ensure boolean options are properly converted (fix for string 'false' bug)
            $options['dry_run'] = filter_var($options['dry_run'], FILTER_VALIDATE_BOOLEAN);
            $options['create_backup'] = filter_var($options['create_backup'], FILTER_VALIDATE_BOOLEAN);
            $options['include_relative'] = filter_var($options['include_relative'], FILTER_VALIDATE_BOOLEAN);
            $options['handle_query_params'] = filter_var($options['handle_query_params'], FILTER_VALIDATE_BOOLEAN);

            // Process each content type
            if (in_array('posts', $options['content_types'])) {
                $this->processPostsContent($jobId, $urlMapping, $options);
            }

            if (in_array('custom_fields', $options['content_types'])) {
                $this->processCustomFields($jobId, $urlMapping, $options);
            }

            if (in_array('menus', $options['content_types'])) {
                $this->processMenus($jobId, $urlMapping, $options);
            }

            if (in_array('widgets', $options['content_types'])) {
                $this->processWidgets($jobId, $urlMapping, $options);
            }

            // Mark as completed
            $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
            $jobData['status'] = 'completed';
            $jobData['completed_at'] = current_time('mysql');
            $jobData['progress']['current_step'] = 'completed';
            $this->updateJobProgress($jobId, $jobData);

            // Log completion
            $this->logJobEvent($jobId, 'info', 'Cleanup process completed successfully');

        } catch (\Exception $e) {
            $jobData['status'] = 'error';
            $jobData['error'] = $e->getMessage();
            $jobData['progress']['current_step'] = 'error';
            $this->updateJobProgress($jobId, $jobData);
            
            $this->logJobEvent($jobId, 'error', 'Process failed: ' . $e->getMessage());
        }
    }

    /**
     * Get job progress
     */
    public function getJobProgress(string $jobId): array
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        
        if (empty($jobData)) {
            throw new \Exception('Job not found');
        }

        return [
            'id' => $jobId,
            'status' => $jobData['status'],
            'progress' => $jobData['progress'],
            'results' => $jobData['results'] ?? [],
            'started_at' => $jobData['started_at'],
            'completed_at' => $jobData['completed_at'] ?? null,
            'error' => $jobData['error'] ?? null
        ];
    }

    /**
     * Get job details
     */
    public function getJobDetails(string $jobId): array
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        
        if (empty($jobData)) {
            throw new \Exception('Job not found');
        }

        return [
            'job' => $jobData,
            'logs' => $this->getJobLogs($jobId),
            'backup_info' => $this->getBackupInfo($jobId)
        ];
    }

    /**
     * Get recent jobs
     */
    public function getRecentJobs(int $limit = 10): array
    {
        $allOptions = wp_load_alloptions();
        $jobs = [];

        foreach ($allOptions as $key => $value) {
            if (strpos($key, self::OPTION_PREFIX . 'job_') === 0) {
                $jobData = maybe_unserialize($value);
                if (is_array($jobData) && isset($jobData['id'])) {
                    $jobs[] = [
                        'id' => $jobData['id'],
                        'status' => $jobData['status'],
                        'started_at' => $jobData['started_at'],
                        'completed_at' => $jobData['completed_at'] ?? null,
                        'options' => $jobData['options'],
                        'results' => $jobData['results'] ?? []
                    ];
                }
            }
        }

        // Sort by started_at desc
        usort($jobs, function($a, $b) {
            return strtotime($b['started_at']) - strtotime($a['started_at']);
        });

        return array_slice($jobs, 0, $limit);
    }

    /**
     * Rollback changes for a job
     */
    public function rollbackChanges(string $jobId): array
    {
        $backupInfo = $this->getBackupInfo($jobId);
        
        if (empty($backupInfo)) {
            throw new \Exception('No backup found for this job');
        }

        // Restore from backup
        $restored = $this->restoreFromBackup($jobId);
        
        if ($restored) {
            $this->logJobEvent($jobId, 'info', 'Changes rolled back successfully');
            
            return [
                'success' => true,
                'message' => 'Changes have been rolled back successfully',
                'restored_tables' => $restored
            ];
        } else {
            throw new \Exception('Failed to restore from backup');
        }
    }

    /**
     * Build URL mapping from redirections
     */
    private function buildUrlMapping(array $redirections): array
    {
        $urlMap = [];
        
        foreach ($redirections as $redirect) {
            $sources = maybe_unserialize($redirect['sources']);
            $destination = $redirect['url_to'];
            
            if (!is_array($sources)) {
                continue;
            }

            // Process each source pattern
            foreach ($sources as $source) {
                if (empty($source['pattern'])) {
                    continue;
                }

                $pattern = $source['pattern'];
                $comparison = $source['comparison'] ?? 'exact';
                
                // For exact matches, add to mapping
                if ($comparison === 'exact') {
                    // Handle both absolute and relative URLs
                    $absoluteUrl = $this->normalizeUrl($pattern);
                    $relativeUrl = $this->makeRelativeUrl($pattern);
                    
                    if ($absoluteUrl) {
                        $finalDestination = $this->resolveFinalDestination($destination, $redirections);
                        $urlMap[$absoluteUrl] = $finalDestination;
                        
                        if ($relativeUrl && $relativeUrl !== $absoluteUrl) {
                            $urlMap[$relativeUrl] = $finalDestination;
                        }
                    }
                }
            }
        }

        return $urlMap;
    }

    /**
     * Resolve redirect chains to final destination
     */
    private function resolveFinalDestination(string $url, array $redirections, array $visited = []): string
    {
        // Prevent infinite loops
        if (in_array($url, $visited) || count($visited) > 10) {
            return $url;
        }

        $visited[] = $url;

        // Look for further redirections
        foreach ($redirections as $redirect) {
            $sources = maybe_unserialize($redirect['sources']);
            if (!is_array($sources)) {
                continue;
            }

            foreach ($sources as $source) {
                if ($source['comparison'] === 'exact' && 
                    $this->normalizeUrl($source['pattern']) === $this->normalizeUrl($url)) {
                    return $this->resolveFinalDestination($redirect['url_to'], $redirections, $visited);
                }
            }
        }

        return $url;
    }

    /**
     * Get all active redirections from database
     */
    private function getAllActiveRedirections(): array
    {
        $table = $this->wpdb->prefix . self::TABLE_REDIRECTIONS;
        
        return $this->wpdb->get_results(
            "SELECT id, sources, url_to, header_code, hits 
             FROM {$table} 
             WHERE status = 'active' 
             ORDER BY hits DESC",
            ARRAY_A
        );
    }

    /**
     * Scan content for URLs that need updating
     */
    private function scanContentForUrls(array $urlMap): array
    {
        $urlsToFind = array_keys($urlMap);
        $contentScan = [
            'posts' => 0,
            'custom_fields' => 0,
            'menus' => 0,
            'widgets' => 0,
            'total_matches' => 0
        ];

        // Scan posts and pages
        $postMatches = $this->scanPostsForUrls($urlsToFind);
        $contentScan['posts'] = $postMatches;

        // Scan custom fields
        $metaMatches = $this->scanCustomFieldsForUrls($urlsToFind);
        $contentScan['custom_fields'] = $metaMatches;

        // Scan menus
        $menuMatches = $this->scanMenusForUrls($urlsToFind);
        $contentScan['menus'] = $menuMatches;

        // Scan widgets
        $widgetMatches = $this->scanWidgetsForUrls($urlsToFind);
        $contentScan['widgets'] = $widgetMatches;

        $contentScan['total_matches'] = $postMatches + $metaMatches + $menuMatches + $widgetMatches;

        return $contentScan;
    }

    /**
     * Process posts and pages content
     */
    private function processPostsContent(string $jobId, array $urlMapping, array $options): void
    {
        $batchSize = $options['batch_size'];
        $isDryRun = $options['dry_run'];
        $offset = 0;
        $totalUpdated = 0;
        $totalUrlReplacements = 0;

        $this->updateJobStep($jobId, 'processing_posts');

        do {
            $posts = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT ID, post_content, post_excerpt
                 FROM {$this->wpdb->posts}
                 WHERE post_status IN ('publish', 'private', 'draft', 'future')
                 LIMIT %d OFFSET %d",
                $batchSize,
                $offset
            ), ARRAY_A);

            $batchUrlReplacements = 0;

            foreach ($posts as $post) {
                $contentUpdated = false;
                $originalContent = $post['post_content'];
                $originalExcerpt = $post['post_excerpt'];

                $contentResult = $this->replaceUrlsInContentWithDetails($originalContent, $urlMapping);
                $excerptResult = $this->replaceUrlsInContentWithDetails($originalExcerpt, $urlMapping);

                $newContent = $contentResult['content'];
                $newExcerpt = $excerptResult['content'];
                $urlChanges = array_merge($contentResult['changes'], $excerptResult['changes']);

                if ($newContent !== $originalContent || $newExcerpt !== $originalExcerpt) {
                    if (!$isDryRun) {
                        $this->wpdb->update(
                            $this->wpdb->posts,
                            [
                                'post_content' => $newContent,
                                'post_excerpt' => $newExcerpt
                            ],
                            ['ID' => $post['ID']],
                            ['%s', '%s'],
                            ['%d']
                        );
                    }

                    $totalUpdated++;
                    $contentUpdated = true;

                    // Count total URL replacements for this post
                    $postUrlReplacements = 0;
                    foreach ($urlChanges as $change) {
                        $postUrlReplacements += $change['count'];
                        $this->logJobEvent($jobId, 'info', sprintf(
                            'Post ID %d: %s %d occurrences of URL from "%s" to "%s"',
                            $post['ID'],
                            $isDryRun ? 'Found' : 'Updated',
                            $change['count'],
                            $change['old'],
                            $change['new']
                        ));
                    }
                    $batchUrlReplacements += $postUrlReplacements;
                }
            }

            $totalUrlReplacements += $batchUrlReplacements;
            $offset += $batchSize;

            // Update progress
            $this->incrementJobProgress($jobId, count($posts), $totalUpdated, 'results', 'posts_updated', $batchUrlReplacements);

        } while (count($posts) === $batchSize);
    }

    /**
     * Process custom fields and meta data
     */
    private function processCustomFields(string $jobId, array $urlMapping, array $options): void
    {
        $batchSize = $options['batch_size'];
        $isDryRun = $options['dry_run'];
        $offset = 0;
        $totalUpdated = 0;
        $totalUrlReplacements = 0;

        $this->updateJobStep($jobId, 'processing_custom_fields');

        do {
            $metaFields = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT meta_id, post_id, meta_key, meta_value
                 FROM {$this->wpdb->postmeta}
                 WHERE meta_value LIKE %s
                    OR meta_value LIKE %s
                 LIMIT %d OFFSET %d",
                '%' . $this->siteUrl . '%',
                '%' . $this->homeUrl . '%',
                $batchSize,
                $offset
            ), ARRAY_A);

            $batchUrlReplacements = 0;

            foreach ($metaFields as $meta) {
                $originalValue = $meta['meta_value'];
                $result = $this->replaceUrlsInContentWithDetails($originalValue, $urlMapping);
                $newValue = $result['content'];
                $urlChanges = $result['changes'];

                if ($newValue !== $originalValue) {
                    if (!$isDryRun) {
                        $this->wpdb->update(
                            $this->wpdb->postmeta,
                            ['meta_value' => $newValue],
                            ['meta_id' => $meta['meta_id']],
                            ['%s'],
                            ['%d']
                        );
                    }

                    $totalUpdated++;

                    // Count total URL replacements for this meta field
                    $metaUrlReplacements = 0;
                    foreach ($urlChanges as $change) {
                        $metaUrlReplacements += $change['count'];
                        $this->logJobEvent($jobId, 'info', sprintf(
                            'Meta ID %d (Post %d, Key: %s): %s %d occurrences of URL from "%s" to "%s"',
                            $meta['meta_id'],
                            $meta['post_id'],
                            $meta['meta_key'],
                            $isDryRun ? 'Found' : 'Updated',
                            $change['count'],
                            $change['old'],
                            $change['new']
                        ));
                    }
                    $batchUrlReplacements += $metaUrlReplacements;
                }
            }

            $totalUrlReplacements += $batchUrlReplacements;
            $offset += $batchSize;
            
            // Update progress
            $this->incrementJobProgress($jobId, count($metaFields), $totalUpdated, 'results', 'custom_fields_updated', $batchUrlReplacements);

        } while (count($metaFields) === $batchSize);
    }

    /**
     * Process navigation menus
     */
    private function processMenus(string $jobId, array $urlMapping, array $options): void
    {
        $isDryRun = $options['dry_run'];
        $totalUpdated = 0;
        $totalUrlReplacements = 0;

        $this->updateJobStep($jobId, 'processing_menus');

        $menuItems = $this->wpdb->get_results(
            "SELECT p.ID, p.post_title, pm.meta_value as menu_url
             FROM {$this->wpdb->posts} p
             JOIN {$this->wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'nav_menu_item'
             AND pm.meta_key = '_menu_item_url'
             AND pm.meta_value != ''",
            ARRAY_A
        );

        foreach ($menuItems as $item) {
            $originalUrl = $item['menu_url'];
            $newUrl = $urlMapping[$originalUrl] ?? null;

            if ($newUrl && $newUrl !== $originalUrl) {
                if (!$isDryRun) {
                    $this->wpdb->update(
                        $this->wpdb->postmeta,
                        ['meta_value' => $newUrl],
                        [
                            'post_id' => $item['ID'],
                            'meta_key' => '_menu_item_url'
                        ],
                        ['%s'],
                        ['%d', '%s']
                    );
                }

                $totalUpdated++;
                $totalUrlReplacements++; // Each menu item update is one URL replacement

                $this->logJobEvent($jobId, 'info', sprintf(
                    'Menu Item "%s": %s URL from %s to %s',
                    $item['post_title'],
                    $isDryRun ? 'Would update' : 'Updated',
                    $originalUrl,
                    $newUrl
                ));
            }
        }

        // Update progress
        $this->incrementJobProgress($jobId, count($menuItems), $totalUpdated, 'results', 'menus_updated', $totalUrlReplacements);
    }

    /**
     * Process widgets and customizer settings
     */
    private function processWidgets(string $jobId, array $urlMapping, array $options): void
    {
        $isDryRun = $options['dry_run'];
        $totalUpdated = 0;
        $totalUrlReplacements = 0;

        $this->updateJobStep($jobId, 'processing_widgets');

        // Get all options that might contain URLs
        $urlOptions = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT option_id, option_name, option_value
                 FROM {$this->wpdb->options}
                 WHERE option_value LIKE %s
                    OR option_value LIKE %s
                 AND option_name NOT LIKE %s",
                '%' . $this->siteUrl . '%',
                '%' . $this->homeUrl . '%',
                self::OPTION_PREFIX . '%'
            ),
            ARRAY_A
        );

        foreach ($urlOptions as $option) {
            $originalValue = $option['option_value'];
            $result = $this->replaceUrlsInContentWithDetails($originalValue, $urlMapping);
            $newValue = $result['content'];
            $urlChanges = $result['changes'];

            if ($newValue !== $originalValue) {
                if (!$isDryRun) {
                    update_option($option['option_name'], maybe_unserialize($newValue));
                }

                $totalUpdated++;

                // Count total URL replacements for this option
                $optionUrlReplacements = 0;
                foreach ($urlChanges as $change) {
                    $optionUrlReplacements += $change['count'];
                    $this->logJobEvent($jobId, 'info', sprintf(
                        'Option "%s": %s %d occurrences of URL from "%s" to "%s"',
                        $option['option_name'],
                        $isDryRun ? 'Found' : 'Updated',
                        $change['count'],
                        $change['old'],
                        $change['new']
                    ));
                }
                $totalUrlReplacements += $optionUrlReplacements;
            }
        }

        // Update progress
        $this->incrementJobProgress($jobId, count($urlOptions), $totalUpdated, 'results', 'widgets_updated', $totalUrlReplacements);
    }

    /**
     * Replace URLs in content
     */
    private function replaceUrlsInContent(string $content, array $urlMapping): string
    {
        $result = $this->replaceUrlsInContentWithDetails($content, $urlMapping);
        return $result['content'];
    }

    /**
     * Replace URLs in content with detailed tracking
     */
    private function replaceUrlsInContentWithDetails(string $content, array $urlMapping): array
    {
        if (empty($content) || empty($urlMapping)) {
            return [
                'content' => $content,
                'changes' => []
            ];
        }

        $updatedContent = $content;
        $changes = [];

        foreach ($urlMapping as $oldUrl => $newUrl) {
            $totalCount = 0;

            // Only replace URLs in specific contexts, not plain text

            // Replace in href attributes (with quotes)
            $updatedContent = str_replace('href="' . $oldUrl . '"', 'href="' . $newUrl . '"', $updatedContent, $count);
            $totalCount += $count;

            // Replace in href attributes (with single quotes)
            $updatedContent = str_replace("href='" . $oldUrl . "'", "href='" . $newUrl . "'", $updatedContent, $count);
            $totalCount += $count;

            // Replace in src attributes (with quotes)
            $updatedContent = str_replace('src="' . $oldUrl . '"', 'src="' . $newUrl . '"', $updatedContent, $count);
            $totalCount += $count;

            // Replace in src attributes (with single quotes)
            $updatedContent = str_replace("src='" . $oldUrl . "'", "src='" . $newUrl . "'", $updatedContent, $count);
            $totalCount += $count;

            // Replace absolute URLs in content (with domain)
            $siteUrl = rtrim(site_url(), '/');
            $homeUrl = rtrim(home_url(), '/');

            // Normalize URLs to prevent double slashes
            $normalizedOldUrl = ltrim($oldUrl, '/');
            $normalizedNewUrl = rtrim($newUrl, '/');

            // Try both with and without trailing slash for old URL
            $absoluteOldUrlNoSlash = $siteUrl . '/' . $normalizedOldUrl;
            $absoluteOldUrlWithSlash = $siteUrl . '/' . $normalizedOldUrl . '/';

            $updatedContent = str_replace($absoluteOldUrlNoSlash, $normalizedNewUrl, $updatedContent, $count);
            $totalCount += $count;
            $updatedContent = str_replace($absoluteOldUrlWithSlash, $normalizedNewUrl, $updatedContent, $count);
            $totalCount += $count;

            // Same for home URL
            $absoluteOldUrlNoSlash = $homeUrl . '/' . $normalizedOldUrl;
            $absoluteOldUrlWithSlash = $homeUrl . '/' . $normalizedOldUrl . '/';

            $updatedContent = str_replace($absoluteOldUrlNoSlash, $normalizedNewUrl, $updatedContent, $count);
            $totalCount += $count;
            $updatedContent = str_replace($absoluteOldUrlWithSlash, $normalizedNewUrl, $updatedContent, $count);
            $totalCount += $count;

            // Replace relative URLs that start with slash
            if (strpos($oldUrl, '/') === 0) {
                $updatedContent = str_replace($oldUrl, $newUrl, $updatedContent, $count);
                $totalCount += $count;
            }

            // Replace URL-encoded versions in attributes only
            $encodedOldUrl = urlencode($oldUrl);
            $encodedNewUrl = urlencode($newUrl);
            $updatedContent = str_replace('href="' . $encodedOldUrl . '"', 'href="' . $encodedNewUrl . '"', $updatedContent, $count);
            $totalCount += $count;
            $updatedContent = str_replace('src="' . $encodedOldUrl . '"', 'src="' . $encodedNewUrl . '"', $updatedContent, $count);
            $totalCount += $count;

            // If any replacements were made, track the change
            if ($totalCount > 0) {
                $changes[] = [
                    'old' => $oldUrl,
                    'new' => $newUrl,
                    'count' => $totalCount
                ];
            }
        }

        // Clean up any double slashes that might have been created (except in http:// or https://)
        $updatedContent = preg_replace('#(?<!http:)(?<!https:)//+#', '/', $updatedContent);

        return [
            'content' => $updatedContent,
            'changes' => $changes
        ];
    }

    /**
     * Utility methods for URL handling
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        
        // Handle relative URLs
        if (strpos($url, '/') === 0) {
            return $this->siteUrl . $url;
        }
        
        return $url;
    }

    private function makeRelativeUrl(string $url): string
    {
        $url = $this->normalizeUrl($url);
        
        if (strpos($url, $this->siteUrl) === 0) {
            return substr($url, strlen($this->siteUrl));
        }
        
        if (strpos($url, $this->homeUrl) === 0) {
            return substr($url, strlen($this->homeUrl));
        }
        
        return $url;
    }

    /**
     * Database and analysis helper methods
     */
    private function getTotalRedirections(): int
    {
        $table = $this->wpdb->prefix . self::TABLE_REDIRECTIONS;
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    private function getActiveRedirections(): int
    {
        $table = $this->wpdb->prefix . self::TABLE_REDIRECTIONS;
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
    }

    private function countRedirectChains(): int
    {
        // This is a simplified count - in reality you'd need to analyze the actual chains
        $table = $this->wpdb->prefix . self::TABLE_REDIRECTIONS;
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND hits > 10");
    }

    private function estimateContentItems(): int
    {
        $posts = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE post_status IN ('publish', 'private', 'draft')");
        $meta = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->postmeta}");
        $options = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->options}");
        
        return $posts + $meta + $options;
    }

    private function getTopRedirectedSources(int $limit = 10): array
    {
        $table = $this->wpdb->prefix . self::TABLE_REDIRECTIONS;
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT sources, url_to, hits 
             FROM {$table} 
             WHERE status = 'active' 
             ORDER BY hits DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
    }

    private function getRedirectTypeBreakdown(): array
    {
        $table = $this->wpdb->prefix . self::TABLE_REDIRECTIONS;
        
        return $this->wpdb->get_results(
            "SELECT header_code, COUNT(*) as count 
             FROM {$table} 
             WHERE status = 'active' 
             GROUP BY header_code 
             ORDER BY count DESC",
            ARRAY_A
        );
    }

    private function scanPostsForUrls(array $urls): int
    {
        if (empty($urls)) {
            return 0;
        }

        $whereClause = '';
        $placeholders = [];
        
        foreach ($urls as $url) {
            $whereClause .= $whereClause ? ' OR ' : '';
            $whereClause .= '(post_content LIKE %s OR post_excerpt LIKE %s)';
            $placeholders[] = '%' . $url . '%';
            $placeholders[] = '%' . $url . '%';
        }

        if (empty($whereClause)) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT ID) FROM {$this->wpdb->posts} WHERE {$whereClause}",
            $placeholders
        ));
    }

    private function scanCustomFieldsForUrls(array $urls): int
    {
        if (empty($urls)) {
            return 0;
        }

        $whereClause = '';
        $placeholders = [];
        
        foreach ($urls as $url) {
            $whereClause .= $whereClause ? ' OR ' : '';
            $whereClause .= 'meta_value LIKE %s';
            $placeholders[] = '%' . $url . '%';
        }

        if (empty($whereClause)) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT meta_id) FROM {$this->wpdb->postmeta} WHERE {$whereClause}",
            $placeholders
        ));
    }

    private function scanMenusForUrls(array $urls): int
    {
        if (empty($urls)) {
            return 0;
        }

        $whereClause = '';
        $placeholders = [];
        
        foreach ($urls as $url) {
            $whereClause .= $whereClause ? ' OR ' : '';
            $whereClause .= 'pm.meta_value = %s';
            $placeholders[] = $url;
        }

        if (empty($whereClause)) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$this->wpdb->posts} p
             JOIN {$this->wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'nav_menu_item'
             AND pm.meta_key = '_menu_item_url'
             AND ({$whereClause})",
            $placeholders
        ));
    }

    private function scanWidgetsForUrls(array $urls): int
    {
        if (empty($urls)) {
            return 0;
        }

        $whereClause = '';
        $placeholders = [];
        
        foreach ($urls as $url) {
            $whereClause .= $whereClause ? ' OR ' : '';
            $whereClause .= 'option_value LIKE %s';
            $placeholders[] = '%' . $url . '%';
        }

        if (empty($whereClause)) {
            return 0;
        }

        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT option_id) FROM {$this->wpdb->options} WHERE {$whereClause}",
            $placeholders
        ));
    }

    /**
     * Job tracking and progress methods
     */
    private function updateJobProgress(string $jobId, array $jobData): void
    {
        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);
    }

    private function updateJobStep(string $jobId, string $step): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        $jobData['progress']['current_step'] = $step;
        $this->updateJobProgress($jobId, $jobData);
    }

    private function incrementJobProgress(string $jobId, int $processed, int $updated, string $resultKey, string $resultField, int $urlReplacements = 0): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        $jobData['progress']['processed_items'] = ($jobData['progress']['processed_items'] ?? 0) + $processed;
        $jobData['progress']['updated_items'] = ($jobData['progress']['updated_items'] ?? 0) + $updated;
        $jobData[$resultKey][$resultField] = ($jobData[$resultKey][$resultField] ?? 0) + $updated;
        $jobData[$resultKey]['total_url_replacements'] = ($jobData[$resultKey]['total_url_replacements'] ?? 0) + $urlReplacements;
        $this->updateJobProgress($jobId, $jobData);
    }

    private function logJobEvent(string $jobId, string $level, string $message): void
    {
        $logs = get_option(self::OPTION_PREFIX . 'logs_' . $jobId, []);
        $logs[] = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message
        ];
        
        // Keep only last 1000 log entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        update_option(self::OPTION_PREFIX . 'logs_' . $jobId, $logs, false);
    }

    private function getJobLogs(string $jobId): array
    {
        return get_option(self::OPTION_PREFIX . 'logs_' . $jobId, []);
    }

    /**
     * Backup and restore methods
     */
    private function createBackup(string $jobId): void
    {
        // Create backup of critical tables before processing
        $tables = ['posts', 'postmeta', 'options'];
        $backupData = [];

        // Set longer timeout for backup operations
        set_time_limit(300); // 5 minutes

        foreach ($tables as $table) {
            $fullTable = $this->wpdb->prefix . $table;
            $backupTable = $fullTable . '_backup_' . $jobId;

            $this->logJobEvent($jobId, 'info', "Creating backup of {$table} table...");

            // Create empty backup table with same structure
            $this->wpdb->query("CREATE TABLE {$backupTable} LIKE {$fullTable}");

            // Copy data in chunks to avoid memory/timeout issues
            $batchSize = 1000;
            $offset = 0;
            $totalCopied = 0;

            do {
                $copied = $this->wpdb->query($this->wpdb->prepare(
                    "INSERT INTO {$backupTable} SELECT * FROM {$fullTable} LIMIT %d OFFSET %d",
                    $batchSize,
                    $offset
                ));

                $totalCopied += $copied;
                $offset += $batchSize;

                // Log progress for large tables
                if ($totalCopied > 0 && $totalCopied % 5000 === 0) {
                    $this->logJobEvent($jobId, 'info', "Backed up {$totalCopied} rows from {$table} table");
                }

            } while ($copied === $batchSize);

            $backupData[$table] = [
                'original' => $fullTable,
                'backup' => $backupTable,
                'created' => current_time('mysql'),
                'rows_backed_up' => $totalCopied
            ];

            $this->logJobEvent($jobId, 'info', "Completed backup of {$table} table ({$totalCopied} rows)");
        }

        update_option(self::OPTION_PREFIX . 'backup_' . $jobId, $backupData, false);

        $this->logJobEvent($jobId, 'info', 'Database backup completed successfully');
    }

    private function getBackupInfo(string $jobId): array
    {
        return get_option(self::OPTION_PREFIX . 'backup_' . $jobId, []);
    }

    private function restoreFromBackup(string $jobId): array
    {
        $backupInfo = $this->getBackupInfo($jobId);
        
        if (empty($backupInfo)) {
            return [];
        }
        
        $restored = [];
        
        foreach ($backupInfo as $table => $info) {
            $originalTable = $info['original'];
            $backupTable = $info['backup'];
            
            // Check if backup table exists
            $tableExists = $this->wpdb->get_var("SHOW TABLES LIKE '{$backupTable}'");
            
            if ($tableExists) {
                // Restore from backup
                $this->wpdb->query("DROP TABLE IF EXISTS {$originalTable}_temp");
                $this->wpdb->query("RENAME TABLE {$originalTable} TO {$originalTable}_temp");
                $this->wpdb->query("CREATE TABLE {$originalTable} AS SELECT * FROM {$backupTable}");
                $this->wpdb->query("DROP TABLE {$originalTable}_temp");
                
                $restored[] = $table;
            }
        }
        
        return $restored;
    }

    private function countResolvedChains(array $urlMap): int
    {
        // Count how many redirect chains were resolved
        $chainCount = 0;
        foreach ($urlMap as $source => $destination) {
            if (isset($urlMap[$destination])) {
                $chainCount++;
            }
        }
        return $chainCount;
    }

    private function estimateRequiredUpdates(array $contentScan): int
    {
        return array_sum($contentScan);
    }

    private function estimateProcessingTime(array $contentScan): string
    {
        $totalItems = array_sum($contentScan);
        $estimatedSeconds = max(30, $totalItems / 10); // Rough estimate
        
        if ($estimatedSeconds < 60) {
            return sprintf('%d seconds', $estimatedSeconds);
        } elseif ($estimatedSeconds < 3600) {
            return sprintf('%d minutes', round($estimatedSeconds / 60));
        } else {
            return sprintf('%.1f hours', $estimatedSeconds / 3600);
        }
    }
}