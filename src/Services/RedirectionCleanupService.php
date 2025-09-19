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
            'result' => $jobData['result'] ?? null,  // Include CSV result data
            'type' => $jobData['type'] ?? null,      // Include job type
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
     * Resolve redirect chains in URL mapping
     *
     * @param array $urlMap URL mapping array
     * @return array Resolved URL mapping
     */
    private function resolveRedirectChains(array $urlMap): array
    {
        $resolved = [];

        foreach ($urlMap as $source => $destination) {
            $finalDestination = $destination;
            $visited = [];

            // Follow the chain to the final destination
            while (isset($urlMap[$finalDestination]) && !in_array($finalDestination, $visited) && count($visited) < 10) {
                $visited[] = $finalDestination;
                $finalDestination = $urlMap[$finalDestination];
            }

            $resolved[$source] = $finalDestination;
        }

        return $resolved;
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

    /**
     * Import redirections from CSV file
     *
     * @param string $csvFilePath Path to the CSV file
     * @return array Import results
     */
    public function importCsvRedirections(string $csvFilePath): array
    {
        if (!file_exists($csvFilePath) || !is_readable($csvFilePath)) {
            throw new \Exception(__('CSV file not found or not readable', 'amfm-tools'));
        }

        $handle = fopen($csvFilePath, 'r');
        if (!$handle) {
            throw new \Exception(__('Failed to open CSV file', 'amfm-tools'));
        }

        // Read and validate header
        $header = fgetcsv($handle);
        $validatedHeader = $this->validateCsvHeader($header);

        if (!$validatedHeader['valid']) {
            fclose($handle);
            throw new \Exception($validatedHeader['message']);
        }

        $columnMap = $validatedHeader['column_map'];
        $redirections = [];
        $errors = [];
        $skipped = [];
        $lineNumber = 1;

        // Read and validate data rows
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            try {
                // Get the raw URLs for tracking
                $source = trim($row[$columnMap['source']] ?? '');
                $finalUrl = trim($row[$columnMap['final_url']] ?? '');

                $redirection = $this->parseCsvRow($row, $columnMap);
                if ($redirection) {
                    $redirections[] = $redirection;
                } elseif (!empty($source) && !empty($finalUrl)) {
                    // Track skipped URLs (query string variations, etc.)
                    if ($this->isSameUrlWithQueryString($source, $finalUrl)) {
                        $skipped[] = sprintf('Line %d: %s → %s (query string variation)',
                            $lineNumber, $source, $finalUrl);
                    }
                }
            } catch (\Exception $e) {
                $errors[] = sprintf(__('Line %d: %s', 'amfm-tools'), $lineNumber, $e->getMessage());
                if (count($errors) > 10) {
                    $errors[] = __('Too many errors, stopping import', 'amfm-tools');
                    break;
                }
            }
        }

        fclose($handle);

        // Store imported data for processing (dual storage for reliability)
        $timestamp = time();
        $importId = 'csv_import_' . $timestamp;

        // Primary storage: Transient (fast, but can be lost with caching)
        set_transient($importId, $redirections, DAY_IN_SECONDS);

        // Fallback storage: Persistent option (always available)
        $persistent_key = 'amfm_' . $importId;
        update_option($persistent_key, $redirections, false);

        // Clean up old persistent imports (keep only last 3)
        global $wpdb;
        $old_persistent = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE 'amfm_csv_import_%'
             ORDER BY option_name DESC LIMIT 100 OFFSET 3"
        );

        foreach ($old_persistent as $old_key) {
            delete_option($old_key);
        }

        return [
            'import_id' => $importId,
            'total_rows' => $lineNumber - 1,
            'valid_redirections' => count($redirections),
            'skipped_count' => count($skipped),
            'skipped_samples' => array_slice($skipped, 0, 5),
            'errors' => $errors,
            'sample_data' => array_slice($redirections, 0, 5),
            'unique_sources' => count(array_unique(array_column($redirections, 'source'))),
            'unique_destinations' => count(array_unique(array_column($redirections, 'final_url')))
        ];
    }

    /**
     * Validate CSV header columns
     *
     * @param array $header CSV header row
     * @return array Validation result
     */
    private function validateCsvHeader(array $header): array
    {
        $requiredColumns = [
            'source' => ['Source', 'source', 'Source URL', 'source_url', 'URL'],
            'final_url' => ['Final URL', 'final_url', 'Destination', 'destination', 'Target URL', 'Redirected URL']
        ];

        $optionalColumns = [
            'type' => ['Type', 'type', 'Link Type'],
            'status_code' => ['Status Code', 'status_code', 'Response Code', 'HTTP Status'],
            'anchor' => ['Anchor', 'anchor', 'Anchor Text', 'Link Text'],
            'path' => ['Path', 'path', 'Link Path', 'XPath']
        ];

        $columnMap = [];

        // Map required columns
        foreach ($requiredColumns as $key => $variations) {
            $found = false;
            foreach ($header as $index => $column) {
                if (in_array($column, $variations, true)) {
                    $columnMap[$key] = $index;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return [
                    'valid' => false,
                    'message' => sprintf(__('Required column "%s" not found in CSV', 'amfm-tools'), $key)
                ];
            }
        }

        // Map optional columns
        foreach ($optionalColumns as $key => $variations) {
            foreach ($header as $index => $column) {
                if (in_array($column, $variations, true)) {
                    $columnMap[$key] = $index;
                    break;
                }
            }
        }

        return [
            'valid' => true,
            'column_map' => $columnMap
        ];
    }

    /**
     * Parse a CSV row into redirection data
     *
     * @param array $row CSV data row
     * @param array $columnMap Column index mapping
     * @return array|null Parsed redirection data
     */
    private function parseCsvRow(array $row, array $columnMap): ?array
    {
        // Get required fields
        $source = trim($row[$columnMap['source']] ?? '');
        $finalUrl = trim($row[$columnMap['final_url']] ?? '');

        if (empty($source) || empty($finalUrl)) {
            return null;
        }

        // Validate URLs
        $source = $this->normalizeUrl($source);
        $finalUrl = $this->normalizeUrl($finalUrl);

        if ($source === $finalUrl) {
            return null; // Skip if source and destination are the same
        }

        // Check if this is just a query string variation of the same URL
        if ($this->isSameUrlWithQueryString($source, $finalUrl)) {
            return null; // Skip pagination and query parameter variations
        }

        return [
            'source' => $source,
            'final_url' => $finalUrl,
            'type' => $row[$columnMap['type']] ?? 'Hyperlink',
            'status_code' => $row[$columnMap['status_code']] ?? '301',
            'anchor' => $row[$columnMap['anchor']] ?? '',
            'path' => $row[$columnMap['path']] ?? ''
        ];
    }

    /**
     * Process imported CSV redirections
     *
     * @param array $options Processing options
     * @return string Job ID
     */
    public function processCsvRedirections(array $options = []): string
    {
        // Get the most recent import
        global $wpdb;

        // First, try to find transients
        $imports = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_csv_import_%'
             ORDER BY option_name DESC LIMIT 1"
        );

        $redirections = null;
        $importId = null;

        if (!empty($imports)) {
            $importId = str_replace('_transient_', '', $imports[0]);
            $redirections = get_transient($importId);
        }

        // If no transient found, try to find persistent option as fallback
        if (!$redirections) {
            $persistent_imports = $wpdb->get_col(
                "SELECT option_name FROM {$wpdb->options}
                 WHERE option_name LIKE 'amfm_csv_import_%'
                 ORDER BY option_name DESC LIMIT 1"
            );

            if (!empty($persistent_imports)) {
                $importId = str_replace('amfm_', '', $persistent_imports[0]);
                $redirections = get_option($persistent_imports[0]);
            }
        }

        if (!$redirections) {
            // Debug information for troubleshooting
            $debug_info = [
                'transient_count' => count($imports),
                'persistent_count' => isset($persistent_imports) ? count($persistent_imports) : 0,
                'last_transient' => !empty($imports) ? $imports[0] : 'none',
                'last_persistent' => !empty($persistent_imports) ? $persistent_imports[0] : 'none'
            ];

            error_log('CSV Import Debug: ' . json_encode($debug_info));
            throw new \Exception(__('No CSV import found. Please import a CSV file first. Debug info logged.', 'amfm-tools'));
        }

        // Create URL mapping from CSV data
        $urlMap = [];
        foreach ($redirections as $redirection) {
            $urlMap[$redirection['source']] = $redirection['final_url'];
        }

        // Resolve redirect chains
        $urlMap = $this->resolveRedirectChains($urlMap);

        // Create a job for processing
        $jobId = 'csv_job_' . time();
        $jobData = [
            'id' => $jobId,
            'type' => 'csv_import',
            'status' => 'processing',
            'options' => $options,
            'url_map' => $urlMap,
            'total_redirections' => count($redirections),
            'unique_mappings' => count($urlMap),
            'started_at' => current_time('mysql'),
            'progress' => [
                'current' => 0,
                'total' => count($urlMap),
                'message' => __('Starting CSV redirection processing...', 'amfm-tools')
            ]
        ];

        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);

        // Schedule background processing for CSV analysis
        wp_schedule_single_event(time() + 2, 'amfm_process_csv_redirection_cleanup', [$jobId]);

        return $jobId;
    }

    /**
     * Perform dry run for CSV redirections
     *
     * @param string $jobId Job identifier
     * @param array $urlMap URL mappings
     * @param array $options Processing options
     */
    private function performCsvDryRun(string $jobId, array $urlMap, array $options): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId);

        // Initialize comprehensive analysis
        $affectedContent = [
            'posts' => 0,
            'custom_fields' => 0,
            'menus' => 0,
            'widgets' => 0
        ];

        $detailedReport = [
            'would_fix' => [],
            'cannot_fix' => [],
            'already_fixed' => []
        ];

        $urlAnalysis = [];

        // Analyze each URL mapping
        foreach ($urlMap as $oldUrl => $newUrl) {
            $urlAnalysis[$oldUrl] = [
                'new_url' => $newUrl,
                'found_in' => [],
                'total_occurrences' => 0
            ];

            // Check posts content
            if (in_array('posts', $options['content_types'] ?? [])) {
                $postCount = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(DISTINCT ID) FROM {$this->wpdb->posts}
                     WHERE (post_content LIKE %s OR post_excerpt LIKE %s)
                     AND post_status IN ('publish', 'private', 'draft')",
                    '%' . $oldUrl . '%',
                    '%' . $oldUrl . '%'
                ));

                if ($postCount > 0) {
                    $affectedContent['posts'] += $postCount;
                    $urlAnalysis[$oldUrl]['found_in'][] = 'posts';
                    $urlAnalysis[$oldUrl]['total_occurrences'] += $postCount;

                    // Get specific post details
                    $affectedPosts = $this->wpdb->get_results($this->wpdb->prepare(
                        "SELECT ID, post_title, post_type FROM {$this->wpdb->posts}
                         WHERE (post_content LIKE %s OR post_excerpt LIKE %s)
                         AND post_status IN ('publish', 'private', 'draft')
                         LIMIT 5",
                        '%' . $oldUrl . '%',
                        '%' . $oldUrl . '%'
                    ));

                    $detailedReport['would_fix'][] = [
                        'type' => 'post_content',
                        'old_url' => $oldUrl,
                        'new_url' => $newUrl,
                        'occurrences' => $postCount,
                        'sample_posts' => array_map(function($post) {
                            return [
                                'id' => $post->ID,
                                'title' => $post->post_title,
                                'type' => $post->post_type
                            ];
                        }, $affectedPosts)
                    ];
                }
            }

            // Check custom fields/meta data
            if (in_array('custom_fields', $options['content_types'] ?? [])) {
                $metaCount = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(DISTINCT meta_id) FROM {$this->wpdb->postmeta}
                     WHERE meta_value LIKE %s
                     AND meta_key NOT LIKE '\_%'",
                    '%' . $oldUrl . '%'
                ));

                if ($metaCount > 0) {
                    $affectedContent['custom_fields'] += $metaCount;
                    $urlAnalysis[$oldUrl]['found_in'][] = 'custom_fields';
                    $urlAnalysis[$oldUrl]['total_occurrences'] += $metaCount;

                    // Get specific meta field details
                    $affectedMeta = $this->wpdb->get_results($this->wpdb->prepare(
                        "SELECT pm.meta_key, p.post_title, pm.post_id
                         FROM {$this->wpdb->postmeta} pm
                         JOIN {$this->wpdb->posts} p ON pm.post_id = p.ID
                         WHERE pm.meta_value LIKE %s
                         AND pm.meta_key NOT LIKE '\_%'
                         LIMIT 5",
                        '%' . $oldUrl . '%'
                    ));

                    $detailedReport['would_fix'][] = [
                        'type' => 'custom_fields',
                        'old_url' => $oldUrl,
                        'new_url' => $newUrl,
                        'occurrences' => $metaCount,
                        'sample_fields' => array_map(function($meta) {
                            return [
                                'post_id' => $meta->post_id,
                                'post_title' => $meta->post_title,
                                'meta_key' => $meta->meta_key
                            ];
                        }, $affectedMeta)
                    ];
                }
            }

            // Check navigation menus
            if (in_array('menus', $options['content_types'] ?? [])) {
                $menuCount = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->posts}
                     WHERE post_type = 'nav_menu_item'
                     AND (post_content LIKE %s OR post_excerpt LIKE %s)",
                    '%' . $oldUrl . '%',
                    '%' . $oldUrl . '%'
                ));

                // Also check menu item meta (like URL field)
                $menuMetaCount = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->postmeta} pm
                     JOIN {$this->wpdb->posts} p ON pm.post_id = p.ID
                     WHERE p.post_type = 'nav_menu_item'
                     AND pm.meta_value LIKE %s",
                    '%' . $oldUrl . '%'
                ));

                $totalMenuOccurrences = $menuCount + $menuMetaCount;

                if ($totalMenuOccurrences > 0) {
                    $affectedContent['menus'] += $totalMenuOccurrences;
                    $urlAnalysis[$oldUrl]['found_in'][] = 'menus';
                    $urlAnalysis[$oldUrl]['total_occurrences'] += $totalMenuOccurrences;

                    $detailedReport['would_fix'][] = [
                        'type' => 'navigation_menus',
                        'old_url' => $oldUrl,
                        'new_url' => $newUrl,
                        'occurrences' => $totalMenuOccurrences,
                        'details' => [
                            'menu_content' => $menuCount,
                            'menu_meta' => $menuMetaCount
                        ]
                    ];
                }
            }

            // Check widgets and customizer settings
            if (in_array('widgets', $options['content_types'] ?? [])) {
                $widgetCount = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->options}
                     WHERE (option_name LIKE 'widget_%' OR option_name LIKE 'theme_mods_%')
                     AND option_value LIKE %s",
                    '%' . $oldUrl . '%'
                ));

                if ($widgetCount > 0) {
                    $affectedContent['widgets'] += $widgetCount;
                    $urlAnalysis[$oldUrl]['found_in'][] = 'widgets';
                    $urlAnalysis[$oldUrl]['total_occurrences'] += $widgetCount;

                    $detailedReport['would_fix'][] = [
                        'type' => 'widgets_customizer',
                        'old_url' => $oldUrl,
                        'new_url' => $newUrl,
                        'occurrences' => $widgetCount
                    ];
                }
            }
        }

        // Create summary statistics
        $totalChanges = array_sum($affectedContent);
        $affectedUrls = count(array_filter($urlAnalysis, function($analysis) {
            return $analysis['total_occurrences'] > 0;
        }));

        $summary = [
            'total_url_mappings' => count($urlMap),
            'urls_with_matches' => $affectedUrls,
            'urls_without_matches' => count($urlMap) - $affectedUrls,
            'total_potential_changes' => $totalChanges,
            'content_breakdown' => $affectedContent
        ];

        // Update job with comprehensive results
        $jobData['status'] = 'completed';
        $jobData['completed_at'] = current_time('mysql');
        $jobData['result'] = [
            'type' => 'dry_run',
            'summary' => $summary,
            'affected_content' => $affectedContent,
            'total_changes' => $totalChanges,
            'url_analysis' => $urlAnalysis,
            'detailed_report' => $detailedReport,
            'recommendations' => $this->generateDryRunRecommendations($urlAnalysis, $affectedContent)
        ];

        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);
    }

    /**
     * Generate recommendations based on dry run analysis
     *
     * @param array $urlAnalysis URL analysis results
     * @param array $affectedContent Content breakdown
     * @return array Recommendations
     */
    private function generateDryRunRecommendations(array $urlAnalysis, array $affectedContent): array
    {
        $recommendations = [];
        $totalChanges = array_sum($affectedContent);

        // Performance recommendations
        if ($totalChanges > 1000) {
            $recommendations[] = [
                'type' => 'performance',
                'level' => 'warning',
                'message' => sprintf(
                    __('Large dataset detected (%d changes). Consider processing in smaller batches or during off-peak hours.', 'amfm-tools'),
                    $totalChanges
                ),
                'action' => 'reduce_batch_size'
            ];
        }

        // Backup recommendation
        if ($totalChanges > 0) {
            $recommendations[] = [
                'type' => 'safety',
                'level' => 'info',
                'message' => __('Create a database backup before proceeding with actual changes.', 'amfm-tools'),
                'action' => 'create_backup'
            ];
        }

        // Content type specific recommendations
        if ($affectedContent['custom_fields'] > 100) {
            $recommendations[] = [
                'type' => 'content',
                'level' => 'caution',
                'message' => sprintf(
                    __('%d custom fields will be updated. Review ACF and other meta field configurations.', 'amfm-tools'),
                    $affectedContent['custom_fields']
                ),
                'action' => 'review_custom_fields'
            ];
        }

        if ($affectedContent['menus'] > 0) {
            $recommendations[] = [
                'type' => 'content',
                'level' => 'info',
                'message' => sprintf(
                    __('%d menu items will be updated. Test navigation after processing.', 'amfm-tools'),
                    $affectedContent['menus']
                ),
                'action' => 'test_navigation'
            ];
        }

        if ($affectedContent['widgets'] > 0) {
            $recommendations[] = [
                'type' => 'content',
                'level' => 'info',
                'message' => sprintf(
                    __('%d widgets/customizer settings will be updated. Check theme appearance after processing.', 'amfm-tools'),
                    $affectedContent['widgets']
                ),
                'action' => 'check_theme'
            ];
        }

        // URL pattern analysis
        $noMatchUrls = array_filter($urlAnalysis, function($analysis) {
            return $analysis['total_occurrences'] === 0;
        });

        if (count($noMatchUrls) > 0) {
            $recommendations[] = [
                'type' => 'optimization',
                'level' => 'info',
                'message' => sprintf(
                    __('%d URLs from the CSV were not found in content. Consider reviewing the URL list.', 'amfm-tools'),
                    count($noMatchUrls)
                ),
                'action' => 'review_url_list'
            ];
        }

        // Processing recommendations
        if ($totalChanges === 0) {
            $recommendations[] = [
                'type' => 'result',
                'level' => 'success',
                'message' => __('No matching content found. No changes would be made.', 'amfm-tools'),
                'action' => 'no_action_needed'
            ];
        } else {
            $recommendations[] = [
                'type' => 'result',
                'level' => 'ready',
                'message' => sprintf(
                    __('Ready to process %d potential changes across %d content types.', 'amfm-tools'),
                    $totalChanges,
                    count(array_filter($affectedContent))
                ),
                'action' => 'proceed_with_caution'
            ];
        }

        return $recommendations;
    }

    /**
     * Check if two URLs are the same except for query strings
     *
     * @param string $url1 First URL
     * @param string $url2 Second URL
     * @return bool True if URLs have same path but different query strings
     */
    private function isSameUrlWithQueryString(string $url1, string $url2): bool
    {
        // Parse both URLs
        $parsed1 = parse_url($url1);
        $parsed2 = parse_url($url2);

        // Compare scheme, host, and path
        $scheme1 = $parsed1['scheme'] ?? '';
        $scheme2 = $parsed2['scheme'] ?? '';
        $host1 = $parsed1['host'] ?? '';
        $host2 = $parsed2['host'] ?? '';
        $path1 = $parsed1['path'] ?? '/';
        $path2 = $parsed2['path'] ?? '/';

        // If base URLs are different, this is a real redirection
        if ($scheme1 !== $scheme2 || $host1 !== $host2 || $path1 !== $path2) {
            return false;
        }

        // Check if one has query string and other doesn't, or both have different query strings
        $query1 = $parsed1['query'] ?? '';
        $query2 = $parsed2['query'] ?? '';

        // Common pagination/filter parameters to ignore
        $ignoredParams = ['paged', 'page', 'p', 'orderby', 'order', 'sort', 'filter',
                         's', 'search', 'tab', 'view', 'mode', 'show', 'display',
                         'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                         'fbclid', 'gclid', 'ref', 'source'];

        // Parse query strings
        parse_str($query1, $params1);
        parse_str($query2, $params2);

        // Check if the difference is only in ignored parameters
        foreach ($ignoredParams as $param) {
            unset($params1[$param], $params2[$param]);
        }

        // If after removing ignored params they're the same, treat as same URL
        return empty(array_diff_assoc($params1, $params2)) && empty(array_diff_assoc($params2, $params1));
    }

    /**
     * Perform actual processing for CSV redirections
     *
     * @param string $jobId Job identifier
     * @param array $urlMap URL mappings
     * @param array $options Processing options
     */
    private function performCsvProcessing(string $jobId, array $urlMap, array $options): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId);
        $processed = 0;
        $updated = 0;
        $errors = [];
        $totalContentTypes = count($options['content_types'] ?? []);
        $currentType = 0;

        // Update progress to show live processing mode
        $jobData['progress']['message'] = 'Starting live URL updates...';
        $jobData['progress']['current'] = 0;
        $jobData['progress']['total'] = $totalContentTypes;
        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);

        // Process based on selected content types
        foreach ($options['content_types'] ?? [] as $contentType) {
            $currentType++;
            $jobData['progress']['message'] = "Processing {$contentType}...";
            $jobData['progress']['current'] = $currentType;
            update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);

            switch ($contentType) {
                case 'posts':
                    $result = $this->updatePostsContent($urlMap, $options['batch_size'] ?? 50);
                    $processed += $result['processed'];
                    $updated += $result['updated'];
                    break;

                case 'custom_fields':
                    $result = $this->updateCustomFields($urlMap, $options['batch_size'] ?? 50);
                    $processed += $result['processed'];
                    $updated += $result['updated'];
                    break;

                case 'menus':
                    $result = $this->updateMenuItems($urlMap);
                    $processed += $result['processed'];
                    $updated += $result['updated'];
                    break;
            }
        }

        // Update job status
        $jobData['status'] = 'completed';
        $jobData['completed_at'] = current_time('mysql');
        $jobData['progress']['message'] = "Live processing completed! Updated {$updated} items.";
        $jobData['progress']['current'] = $totalContentTypes;
        $jobData['result'] = [
            'type' => 'live_processing',
            'processed' => $processed,
            'updated' => $updated,
            'errors' => $errors,
            'summary' => [
                'total_processed_items' => $processed,
                'total_updated_items' => $updated,
                'processing_mode' => 'live'
            ]
        ];

        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);
    }

    /**
     * Update posts content with URL mappings (CSV live processing)
     */
    private function updatePostsContent(array $urlMapping, int $batchSize = 50): array
    {
        $offset = 0;
        $totalProcessed = 0;
        $totalUpdated = 0;

        do {
            $posts = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT ID, post_content, post_excerpt
                 FROM {$this->wpdb->posts}
                 WHERE post_status IN ('publish', 'private', 'draft', 'future')
                 LIMIT %d OFFSET %d",
                $batchSize,
                $offset
            ), ARRAY_A);

            foreach ($posts as $post) {
                $totalProcessed++;
                $originalContent = $post['post_content'];
                $originalExcerpt = $post['post_excerpt'];

                $contentResult = $this->replaceUrlsInContentWithDetails($originalContent, $urlMapping);
                $excerptResult = $this->replaceUrlsInContentWithDetails($originalExcerpt, $urlMapping);

                $newContent = $contentResult['content'];
                $newExcerpt = $excerptResult['content'];

                if ($newContent !== $originalContent || $newExcerpt !== $originalExcerpt) {
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
                    $totalUpdated++;
                }
            }

            $offset += $batchSize;
        } while (count($posts) === $batchSize);

        return ['processed' => $totalProcessed, 'updated' => $totalUpdated];
    }

    /**
     * Update custom fields with URL mappings (CSV live processing)
     */
    private function updateCustomFields(array $urlMapping, int $batchSize = 50): array
    {
        $offset = 0;
        $totalProcessed = 0;
        $totalUpdated = 0;

        do {
            $metas = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT meta_id, post_id, meta_key, meta_value
                 FROM {$this->wpdb->postmeta}
                 WHERE meta_value != ''
                 LIMIT %d OFFSET %d",
                $batchSize,
                $offset
            ), ARRAY_A);

            foreach ($metas as $meta) {
                $totalProcessed++;
                $originalValue = $meta['meta_value'];
                $result = $this->replaceUrlsInContentWithDetails($originalValue, $urlMapping);
                $newValue = $result['content'];

                if ($newValue !== $originalValue) {
                    $this->wpdb->update(
                        $this->wpdb->postmeta,
                        ['meta_value' => $newValue],
                        ['meta_id' => $meta['meta_id']],
                        ['%s'],
                        ['%d']
                    );
                    $totalUpdated++;
                }
            }

            $offset += $batchSize;
        } while (count($metas) === $batchSize);

        return ['processed' => $totalProcessed, 'updated' => $totalUpdated];
    }

    /**
     * Update menu items with URL mappings (CSV live processing)
     */
    private function updateMenuItems(array $urlMapping): array
    {
        $totalProcessed = 0;
        $totalUpdated = 0;

        $menuItems = $this->wpdb->get_results(
            "SELECT ID, post_content, guid
             FROM {$this->wpdb->posts}
             WHERE post_type = 'nav_menu_item'
             AND post_status = 'publish'",
            ARRAY_A
        );

        foreach ($menuItems as $item) {
            $totalProcessed++;
            $originalGuid = $item['guid'];
            $result = $this->replaceUrlsInContentWithDetails($originalGuid, $urlMapping);
            $newGuid = $result['content'];

            if ($newGuid !== $originalGuid) {
                $this->wpdb->update(
                    $this->wpdb->posts,
                    ['guid' => $newGuid],
                    ['ID' => $item['ID']],
                    ['%s'],
                    ['%d']
                );
                $totalUpdated++;
            }

            // Also check menu item meta
            $menuItemMetas = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT meta_id, meta_value
                 FROM {$this->wpdb->postmeta}
                 WHERE post_id = %d
                 AND meta_key IN ('_menu_item_url', '_menu_item_object_id')",
                $item['ID']
            ), ARRAY_A);

            foreach ($menuItemMetas as $meta) {
                $originalValue = $meta['meta_value'];
                $result = $this->replaceUrlsInContentWithDetails($originalValue, $urlMapping);
                $newValue = $result['content'];

                if ($newValue !== $originalValue) {
                    $this->wpdb->update(
                        $this->wpdb->postmeta,
                        ['meta_value' => $newValue],
                        ['meta_id' => $meta['meta_id']],
                        ['%s'],
                        ['%d']
                    );
                    $totalUpdated++;
                }
            }
        }

        return ['processed' => $totalProcessed, 'updated' => $totalUpdated];
    }

    /**
     * Background processing for CSV redirections
     *
     * @param string $jobId Job identifier
     */
    public function processCsvRedirectionCleanup(string $jobId): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        if (empty($jobData) || $jobData['type'] !== 'csv_import') {
            return;
        }

        try {
            $jobData['status'] = 'processing';
            $jobData['progress']['current_step'] = 'Starting CSV analysis...';
            update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);

            $isDryRun = $jobData['options']['dry_run'] ?? true;

            // Debug logging
            error_log('CSV Processing Debug: ' . json_encode([
                'job_id' => $jobId,
                'dry_run_option' => $jobData['options']['dry_run'] ?? 'not_set',
                'is_dry_run' => $isDryRun,
                'all_options' => $jobData['options']
            ]));

            if ($isDryRun) {
                $this->performBatchedCsvDryRun($jobId, $jobData['url_map'], $jobData['options']);
            } else {
                $this->performCsvProcessing($jobId, $jobData['url_map'], $jobData['options']);
            }

        } catch (\Exception $e) {
            $jobData['status'] = 'error';
            $jobData['error'] = $e->getMessage();
            $jobData['completed_at'] = current_time('mysql');
            update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);
        }
    }

    /**
     * Perform batched CSV dry run analysis
     *
     * @param string $jobId Job identifier
     * @param array $urlMap URL mappings
     * @param array $options Processing options
     */
    private function performBatchedCsvDryRun(string $jobId, array $urlMap, array $options): void
    {
        $batchSize = 10; // Process 10 URLs at a time
        $urlList = array_keys($urlMap);
        $totalUrls = count($urlList);
        $processed = 0;

        // Initialize comprehensive analysis
        $affectedContent = [
            'posts' => 0,
            'custom_fields' => 0,
            'menus' => 0,
            'widgets' => 0
        ];

        $detailedReport = [
            'would_fix' => [],
            'cannot_fix' => [],
            'already_fixed' => []
        ];

        $urlAnalysis = [];

        // Process URLs in batches
        for ($offset = 0; $offset < $totalUrls; $offset += $batchSize) {
            $batch = array_slice($urlList, $offset, $batchSize);
            $processed += count($batch);

            // Update progress
            $this->updateCsvJobProgress($jobId, $processed, $totalUrls,
                sprintf('Analyzing URLs %d-%d of %d...', $offset + 1, min($offset + $batchSize, $totalUrls), $totalUrls));

            // Process this batch
            foreach ($batch as $oldUrl) {
                $newUrl = $urlMap[$oldUrl];
                $urlResult = $this->analyzeSingleUrl($oldUrl, $newUrl, $options);

                if ($urlResult['total_occurrences'] > 0) {
                    $urlAnalysis[$oldUrl] = $urlResult;

                    // Add to affected content counts
                    foreach ($urlResult['found_in'] as $contentType) {
                        $affectedContent[$contentType] += $urlResult['occurrences'][$contentType] ?? 0;
                    }

                    // Add to detailed report
                    $detailedReport['would_fix'] = array_merge($detailedReport['would_fix'], $urlResult['details']);
                }
            }

            // Small delay to prevent server overload
            usleep(100000); // 0.1 second
        }

        // Generate final results
        $this->finalizeCsvDryRun($jobId, $urlAnalysis, $affectedContent, $detailedReport, $urlMap);
    }

    /**
     * Analyze a single URL for redirections
     *
     * @param string $oldUrl Source URL
     * @param string $newUrl Destination URL
     * @param array $options Processing options
     * @return array Analysis results
     */
    private function analyzeSingleUrl(string $oldUrl, string $newUrl, array $options): array
    {
        $result = [
            'new_url' => $newUrl,
            'found_in' => [],
            'total_occurrences' => 0,
            'occurrences' => [],
            'details' => []
        ];

        // Check posts content
        if (in_array('posts', $options['content_types'] ?? [])) {
            $postCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(DISTINCT ID) FROM {$this->wpdb->posts}
                 WHERE (post_content LIKE %s OR post_excerpt LIKE %s)
                 AND post_status IN ('publish', 'private', 'draft')",
                '%' . $oldUrl . '%',
                '%' . $oldUrl . '%'
            ));

            if ($postCount > 0) {
                $result['found_in'][] = 'posts';
                $result['occurrences']['posts'] = $postCount;
                $result['total_occurrences'] += $postCount;

                $result['details'][] = [
                    'type' => 'post_content',
                    'old_url' => $oldUrl,
                    'new_url' => $newUrl,
                    'occurrences' => $postCount
                ];
            }
        }

        // Check custom fields
        if (in_array('custom_fields', $options['content_types'] ?? [])) {
            $metaCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(DISTINCT meta_id) FROM {$this->wpdb->postmeta}
                 WHERE meta_value LIKE %s AND meta_key NOT LIKE '\_%'",
                '%' . $oldUrl . '%'
            ));

            if ($metaCount > 0) {
                $result['found_in'][] = 'custom_fields';
                $result['occurrences']['custom_fields'] = $metaCount;
                $result['total_occurrences'] += $metaCount;

                $result['details'][] = [
                    'type' => 'custom_fields',
                    'old_url' => $oldUrl,
                    'new_url' => $newUrl,
                    'occurrences' => $metaCount
                ];
            }
        }

        // Check menus
        if (in_array('menus', $options['content_types'] ?? [])) {
            $menuCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->posts}
                 WHERE post_type = 'nav_menu_item' AND (post_content LIKE %s OR post_excerpt LIKE %s)",
                '%' . $oldUrl . '%', '%' . $oldUrl . '%'
            ));

            $menuMetaCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->postmeta} pm
                 JOIN {$this->wpdb->posts} p ON pm.post_id = p.ID
                 WHERE p.post_type = 'nav_menu_item' AND pm.meta_value LIKE %s",
                '%' . $oldUrl . '%'
            ));

            $totalMenuCount = $menuCount + $menuMetaCount;
            if ($totalMenuCount > 0) {
                $result['found_in'][] = 'menus';
                $result['occurrences']['menus'] = $totalMenuCount;
                $result['total_occurrences'] += $totalMenuCount;

                $result['details'][] = [
                    'type' => 'navigation_menus',
                    'old_url' => $oldUrl,
                    'new_url' => $newUrl,
                    'occurrences' => $totalMenuCount
                ];
            }
        }

        // Check widgets
        if (in_array('widgets', $options['content_types'] ?? [])) {
            $widgetCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->options}
                 WHERE (option_name LIKE 'widget_%' OR option_name LIKE 'theme_mods_%')
                 AND option_value LIKE %s",
                '%' . $oldUrl . '%'
            ));

            if ($widgetCount > 0) {
                $result['found_in'][] = 'widgets';
                $result['occurrences']['widgets'] = $widgetCount;
                $result['total_occurrences'] += $widgetCount;

                $result['details'][] = [
                    'type' => 'widgets_customizer',
                    'old_url' => $oldUrl,
                    'new_url' => $newUrl,
                    'occurrences' => $widgetCount
                ];
            }
        }

        return $result;
    }

    /**
     * Update CSV job progress
     */
    private function updateCsvJobProgress(string $jobId, int $processed, int $total, string $message): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId, []);
        $jobData['progress']['current'] = $processed;
        $jobData['progress']['total'] = $total;
        $jobData['progress']['message'] = $message;
        $jobData['progress']['percentage'] = round(($processed / $total) * 100, 1);
        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);
    }

    /**
     * Finalize CSV dry run with results
     */
    private function finalizeCsvDryRun(string $jobId, array $urlAnalysis, array $affectedContent, array $detailedReport, array $urlMap): void
    {
        $jobData = get_option(self::OPTION_PREFIX . 'job_' . $jobId);

        $totalChanges = array_sum($affectedContent);
        $affectedUrls = count($urlAnalysis);

        $summary = [
            'total_url_mappings' => count($urlMap),
            'urls_with_matches' => $affectedUrls,
            'urls_without_matches' => count($urlMap) - $affectedUrls,
            'total_potential_changes' => $totalChanges,
            'content_breakdown' => $affectedContent
        ];

        $jobData['status'] = 'completed';
        $jobData['completed_at'] = current_time('mysql');
        $jobData['progress']['current_step'] = 'Analysis completed';
        $jobData['progress']['message'] = sprintf('Analyzed %d URLs, found %d with matches', count($urlMap), $affectedUrls);
        $jobData['result'] = [
            'type' => 'dry_run',
            'summary' => $summary,
            'affected_content' => $affectedContent,
            'total_changes' => $totalChanges,
            'url_analysis' => $urlAnalysis,
            'detailed_report' => $detailedReport,
            'recommendations' => $this->generateDryRunRecommendations($urlAnalysis, $affectedContent)
        ];

        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData, false);
    }
}