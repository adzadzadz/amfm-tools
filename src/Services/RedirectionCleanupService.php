<?php

namespace App\Services;

/**
 * Redirection Cleanup Service
 *
 * Handles CSV processing and URL replacement functionality
 */
class RedirectionCleanupService
{
    private const OPTION_PREFIX = 'amfm_redirection_cleanup_';

    private $wpdb;
    private string $uploadDir;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Set up upload directory
        $uploadDirInfo = wp_upload_dir();
        $this->uploadDir = $uploadDirInfo['basedir'] . '/amfm-tools/redirection-cleanup';

        // Ensure directory exists
        if (!file_exists($this->uploadDir)) {
            wp_mkdir_p($this->uploadDir);
        }
    }

    /**
     * Process uploaded CSV file
     */
    public function processUploadedCsv(array $file): array
    {
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Validate file type
        $fileName = $file['name'];
        if (!str_ends_with(strtolower($fileName), '.csv')) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Please upload a CSV file.'
            ];
        }

        // Move uploaded file
        $filename = 'crawl-report-' . date('Y-m-d-His') . '.csv';
        $destination = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'message' => 'Failed to save uploaded file.'
            ];
        }

        // Parse CSV and extract URL mappings
        $result = $this->parseCrawlReportCsv($destination);

        if (!$result['success']) {
            @unlink($destination);
            return $result;
        }

        // Store parsed data
        update_option(self::OPTION_PREFIX . 'current_csv', $filename);
        update_option(self::OPTION_PREFIX . 'url_mappings', $result['mappings']);
        update_option(self::OPTION_PREFIX . 'csv_stats', $result['stats']);
        update_option(self::OPTION_PREFIX . 'last_import', current_time('mysql'));

        return [
            'success' => true,
            'message' => sprintf(
                'CSV processed successfully. Found %d unique URL redirections with %d total occurrences.',
                $result['stats']['unique_urls'],
                $result['stats']['total_occurrences']
            )
        ];
    }

    /**
     * Parse crawl report CSV file
     */
    private function parseCrawlReportCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'CSV file not found.'
            ];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'message' => 'Unable to read CSV file.'
            ];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Invalid CSV format - no headers found.'
            ];
        }

        // Find required columns
        $redirectedUrlIndex = $this->findColumnIndex($headers, ['redirected url', 'redirected_url']);
        $finalUrlIndex = $this->findColumnIndex($headers, ['final url', 'final_url']);

        if ($redirectedUrlIndex === false || $finalUrlIndex === false) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Required columns not found. CSV must contain "Redirected URL" and "Final URL" columns.'
            ];
        }

        $mappings = [];
        $totalRows = 0;
        $totalOccurrences = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;

            if (count($row) <= max($redirectedUrlIndex, $finalUrlIndex)) {
                continue;
            }

            $redirectedUrl = trim($row[$redirectedUrlIndex]);
            $finalUrl = trim($row[$finalUrlIndex]);

            if (empty($redirectedUrl) || empty($finalUrl)) {
                continue;
            }

            // Validate URLs
            if (!filter_var($redirectedUrl, FILTER_VALIDATE_URL) || !filter_var($finalUrl, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Transform URLs to match current site domain
            $normalizedRedirectedUrl = $this->normalizeUrlToDomain($redirectedUrl);
            $normalizedFinalUrl = $this->normalizeUrlToDomain($finalUrl);

            if (!isset($mappings[$normalizedRedirectedUrl])) {
                $mappings[$normalizedRedirectedUrl] = [
                    'final_url' => $normalizedFinalUrl,
                    'original_redirected_url' => $redirectedUrl,
                    'original_final_url' => $finalUrl,
                    'occurrences' => 0
                ];
            }

            $mappings[$normalizedRedirectedUrl]['occurrences']++;
            $totalOccurrences++;
        }

        fclose($handle);

        $stats = [
            'total_rows' => $totalRows,
            'unique_urls' => count($mappings),
            'total_occurrences' => $totalOccurrences,
            'top_redirections' => $this->getTopRedirections($mappings, 10)
        ];

        return [
            'success' => true,
            'mappings' => $mappings,
            'stats' => $stats
        ];
    }

    /**
     * Find column index by possible names
     */
    private function findColumnIndex(array $headers, array $possibleNames): false|int
    {
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            foreach ($possibleNames as $name) {
                if (str_contains($normalizedHeader, $name)) {
                    return $index;
                }
            }
        }
        return false;
    }

    /**
     * Get top redirections for display
     */
    private function getTopRedirections(array $mappings, int $limit): array
    {
        $sorted = $mappings;
        uasort($sorted, fn($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        $top = [];
        $count = 0;

        foreach ($sorted as $url => $data) {
            if ($count >= $limit) break;

            $top[] = [
                'url' => $url,
                'final_url' => $data['final_url'],
                'occurrences' => $data['occurrences']
            ];
            $count++;
        }

        return $top;
    }

    /**
     * Analyze content for URLs that need replacement
     */
    public function analyzeContent(): array
    {
        $mappings = get_option(self::OPTION_PREFIX . 'url_mappings', []);

        if (empty($mappings)) {
            return [
                'success' => false,
                'message' => 'No CSV data loaded. Please upload a CSV file first.'
            ];
        }

        $stats = ['posts' => 0, 'postmeta' => 0, 'total' => 0];

        foreach ($mappings as $originalUrl => $mapping) {
            // Count posts containing this URL
            $postCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->posts}
                WHERE post_status = 'publish'
                AND (post_content LIKE %s OR post_excerpt LIKE %s)",
                '%' . $this->wpdb->esc_like($originalUrl) . '%',
                '%' . $this->wpdb->esc_like($originalUrl) . '%'
            ));

            // Count meta fields containing this URL
            $metaCount = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->postmeta}
                WHERE meta_value LIKE %s",
                '%' . $this->wpdb->esc_like($originalUrl) . '%'
            ));

            $stats['posts'] += (int) $postCount;
            $stats['postmeta'] += (int) $metaCount;
        }

        $stats['total'] = $stats['posts'] + $stats['postmeta'];

        // Store analysis results
        update_option(self::OPTION_PREFIX . 'analysis', $stats);
        update_option(self::OPTION_PREFIX . 'last_analysis', current_time('mysql'));

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Process URL replacements
     */
    public function processReplacements(array $options = []): array
    {
        $mappings = get_option(self::OPTION_PREFIX . 'url_mappings', []);

        if (empty($mappings)) {
            return [
                'success' => false,
                'message' => 'No CSV data loaded. Please upload a CSV file first.'
            ];
        }

        $options = wp_parse_args($options, [
            'dry_run' => true,
            'content_types' => ['posts', 'postmeta', 'all_tables'],
            'batch_size' => 50,
            'batch_processing' => false,
            'batch_start' => 0,
            'batch_limit' => 10
        ]);

        $results = [
            'posts_updated' => 0,
            'meta_updated' => 0,
            'options_updated' => 0,
            'other_tables_updated' => 0,
            'urls_replaced' => 0
        ];

        // If batch processing, slice the mappings array
        if ($options['batch_processing']) {
            $totalMappings = count($mappings);
            $batchStart = $options['batch_start'];
            $batchLimit = $options['batch_limit'];

            // Add total count info for progress tracking
            $results['total_mappings'] = $totalMappings;
            $results['batch_start'] = $batchStart;
            $results['batch_limit'] = $batchLimit;
            $results['batch_end'] = min($batchStart + $batchLimit - 1, $totalMappings - 1);
            $results['is_complete'] = ($batchStart + $batchLimit) >= $totalMappings;

            // Get the batch slice
            $mappings = array_slice($mappings, $batchStart, $batchLimit, true);

            if (empty($mappings)) {
                $results['success'] = true;
                $results['message'] = 'Batch processing complete - no more mappings to process';
                return $results;
            }
        }

        // Process all tables if requested (comprehensive mode)
        if (in_array('all_tables', $options['content_types'])) {
            // For comprehensive mode, focus on key tables that commonly contain URLs
            $allTablesResults = $this->processKeyTables($mappings, $options);
            $results = array_merge($results, $allTablesResults);
        } else {
            // Process posts if requested
            if (in_array('posts', $options['content_types'])) {
                $postResults = $this->processPosts($mappings, $options);
                $results['posts_updated'] = $postResults['updated'];
                $results['urls_replaced'] += $postResults['replacements'];
            }

            // Process meta if requested
            if (in_array('postmeta', $options['content_types'])) {
                $metaResults = $this->processPostMeta($mappings, $options);
                $results['meta_updated'] = $metaResults['updated'];
                $results['urls_replaced'] += $metaResults['replacements'];
            }
        }

        // Store job record
        if (!$options['dry_run']) {
            $this->storeJobRecord($options, $results);
        }

        return [
            'success' => true,
            'dry_run' => $options['dry_run'],
            'results' => $results
        ];
    }

    /**
     * Process posts content
     */
    private function processPosts(array $mappings, array $options): array
    {
        $updated = 0;
        $replacements = 0;
        $batchSize = $options['batch_size'];
        $offset = 0;

        do {
            $posts = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT ID, post_content, post_excerpt FROM {$this->wpdb->posts}
                WHERE post_status = 'publish'
                ORDER BY ID LIMIT %d OFFSET %d",
                $batchSize,
                $offset
            ));

            foreach ($posts as $post) {
                $content = $post->post_content;
                $excerpt = $post->post_excerpt;
                $contentChanged = false;
                $excerptChanged = false;

                foreach ($mappings as $originalUrl => $mapping) {
                    $finalUrl = $mapping['final_url'];

                    // Create protocol variants (http/https) and slash variants for comprehensive matching
                    $urlVariants = [$originalUrl];

                    // Add protocol variants
                    if (strpos($originalUrl, 'http://') === 0) {
                        $urlVariants[] = str_replace('http://', 'https://', $originalUrl);
                    } elseif (strpos($originalUrl, 'https://') === 0) {
                        $urlVariants[] = str_replace('https://', 'http://', $originalUrl);
                    }

                    // Add slash variants for each protocol variant
                    $allVariants = [];
                    foreach ($urlVariants as $variant) {
                        $allVariants[] = $variant;

                        // Add version with trailing slash if it doesn't have one
                        if (substr($variant, -1) !== '/') {
                            $allVariants[] = $variant . '/';
                        }

                        // Add version without trailing slash if it has one
                        if (substr($variant, -1) === '/') {
                            $allVariants[] = rtrim($variant, '/');
                        }
                    }

                    $urlVariants = array_unique($allVariants);

                    foreach ($urlVariants as $urlToReplace) {
                        if (strpos($content, $urlToReplace) !== false) {
                            // Count occurrences to avoid infinite replacement loops
                            $originalCount = substr_count($content, $urlToReplace);

                            // Only replace if we haven't already replaced more than the original mappings suggested
                            if ($originalCount > 0) {
                                // Smart replacement: handle trailing slashes properly
                                $smartFinalUrl = $this->normalizeUrlSlashes($urlToReplace, $finalUrl);
                                $content = str_replace($urlToReplace, $smartFinalUrl, $content);
                                $contentChanged = true;
                                $replacements += $originalCount;
                            }
                        }

                        if (strpos($excerpt, $urlToReplace) !== false) {
                            $originalCount = substr_count($excerpt, $urlToReplace);
                            if ($originalCount > 0) {
                                // Smart replacement: handle trailing slashes properly
                                $smartFinalUrl = $this->normalizeUrlSlashes($urlToReplace, $finalUrl);
                                $excerpt = str_replace($urlToReplace, $smartFinalUrl, $excerpt);
                                $excerptChanged = true;
                                $replacements += $originalCount;
                            }
                        }
                    }

                    // FALLBACK: If mapping has original_redirected_url, also try that for backward compatibility
                    if (isset($mapping['original_redirected_url'])) {
                        $originalSourceUrl = $mapping['original_redirected_url'];
                        $urlVariantsOriginal = [$originalSourceUrl];

                        // Add protocol variants for original URL
                        if (strpos($originalSourceUrl, 'http://') === 0) {
                            $urlVariantsOriginal[] = str_replace('http://', 'https://', $originalSourceUrl);
                        } elseif (strpos($originalSourceUrl, 'https://') === 0) {
                            $urlVariantsOriginal[] = str_replace('https://', 'http://', $originalSourceUrl);
                        }

                        foreach ($urlVariantsOriginal as $fallbackUrl) {
                            if (strpos($content, $fallbackUrl) !== false) {
                                $originalCount = substr_count($content, $fallbackUrl);
                                if ($originalCount > 0) {
                                    $content = str_replace($fallbackUrl, $finalUrl, $content);
                                    $contentChanged = true;
                                    $replacements += $originalCount;
                                }
                            }

                            if (strpos($excerpt, $fallbackUrl) !== false) {
                                $originalCount = substr_count($excerpt, $fallbackUrl);
                                if ($originalCount > 0) {
                                    $excerpt = str_replace($fallbackUrl, $finalUrl, $excerpt);
                                    $excerptChanged = true;
                                    $replacements += $originalCount;
                                }
                            }
                        }
                    }
                }

                if (($contentChanged || $excerptChanged) && !$options['dry_run']) {
                    $this->wpdb->update(
                        $this->wpdb->posts,
                        [
                            'post_content' => $content,
                            'post_excerpt' => $excerpt
                        ],
                        ['ID' => $post->ID]
                    );
                    $updated++;
                } elseif ($contentChanged || $excerptChanged) {
                    $updated++; // Count for dry run
                }
            }

            $offset += $batchSize;

        } while (count($posts) === $batchSize);

        return ['updated' => $updated, 'replacements' => $replacements];
    }

    /**
     * Process post meta
     */
    private function processPostMeta(array $mappings, array $options): array
    {
        $updated = 0;
        $replacements = 0;
        $batchSize = $options['batch_size'];
        $offset = 0;

        do {
            $metas = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT meta_id, meta_value FROM {$this->wpdb->postmeta}
                ORDER BY meta_id LIMIT %d OFFSET %d",
                $batchSize,
                $offset
            ));

            foreach ($metas as $meta) {
                $value = $meta->meta_value;
                $valueChanged = false;

                foreach ($mappings as $originalUrl => $mapping) {
                    $finalUrl = $mapping['final_url'];

                    // Create protocol variants (http/https) and slash variants for comprehensive matching
                    $urlVariants = [$originalUrl];

                    // Add protocol variants
                    if (strpos($originalUrl, 'http://') === 0) {
                        $urlVariants[] = str_replace('http://', 'https://', $originalUrl);
                    } elseif (strpos($originalUrl, 'https://') === 0) {
                        $urlVariants[] = str_replace('https://', 'http://', $originalUrl);
                    }

                    // Add slash variants for each protocol variant
                    $allVariants = [];
                    foreach ($urlVariants as $variant) {
                        $allVariants[] = $variant;

                        // Add version with trailing slash if it doesn't have one
                        if (substr($variant, -1) !== '/') {
                            $allVariants[] = $variant . '/';
                        }

                        // Add version without trailing slash if it has one
                        if (substr($variant, -1) === '/') {
                            $allVariants[] = rtrim($variant, '/');
                        }
                    }

                    $urlVariants = array_unique($allVariants);

                    foreach ($urlVariants as $urlToReplace) {
                        if (strpos($value, $urlToReplace) !== false) {
                            $originalCount = substr_count($value, $urlToReplace);
                            if ($originalCount > 0) {
                                // Smart replacement: handle trailing slashes properly
                                $smartFinalUrl = $this->normalizeUrlSlashes($urlToReplace, $finalUrl);
                                $value = str_replace($urlToReplace, $smartFinalUrl, $value);
                                $valueChanged = true;
                                $replacements += $originalCount;
                            }
                        }
                    }

                    // FALLBACK: If mapping has original_redirected_url, also try that for backward compatibility
                    if (isset($mapping['original_redirected_url'])) {
                        $originalSourceUrl = $mapping['original_redirected_url'];
                        $urlVariantsOriginal = [$originalSourceUrl];

                        // Add protocol variants for original URL
                        if (strpos($originalSourceUrl, 'http://') === 0) {
                            $urlVariantsOriginal[] = str_replace('http://', 'https://', $originalSourceUrl);
                        } elseif (strpos($originalSourceUrl, 'https://') === 0) {
                            $urlVariantsOriginal[] = str_replace('https://', 'http://', $originalSourceUrl);
                        }

                        foreach ($urlVariantsOriginal as $fallbackUrl) {
                            if (strpos($value, $fallbackUrl) !== false) {
                                $originalCount = substr_count($value, $fallbackUrl);
                                if ($originalCount > 0) {
                                    $value = str_replace($fallbackUrl, $finalUrl, $value);
                                    $valueChanged = true;
                                    $replacements += $originalCount;
                                }
                            }
                        }
                    }
                }

                if ($valueChanged && !$options['dry_run']) {
                    $this->wpdb->update(
                        $this->wpdb->postmeta,
                        ['meta_value' => $value],
                        ['meta_id' => $meta->meta_id]
                    );
                    $updated++;
                } elseif ($valueChanged) {
                    $updated++; // Count for dry run
                }
            }

            $offset += $batchSize;

        } while (count($metas) === $batchSize);

        return ['updated' => $updated, 'replacements' => $replacements];
    }

    /**
     * Store job record for history
     */
    private function storeJobRecord(array $options, array $results): void
    {
        $jobId = wp_generate_uuid4();
        $jobData = [
            'timestamp' => current_time('mysql'),
            'options' => $options,
            'results' => $results
        ];

        update_option(self::OPTION_PREFIX . 'job_' . $jobId, $jobData);
    }

    /**
     * Get current data for display
     */
    public function getCurrentData(): array
    {
        return [
            'csv_file' => get_option(self::OPTION_PREFIX . 'current_csv'),
            'stats' => get_option(self::OPTION_PREFIX . 'csv_stats', []),
            'analysis' => get_option(self::OPTION_PREFIX . 'analysis', []),
            'last_import' => get_option(self::OPTION_PREFIX . 'last_import'),
            'last_analysis' => get_option(self::OPTION_PREFIX . 'last_analysis'),
            'mappings_count' => count(get_option(self::OPTION_PREFIX . 'url_mappings', []))
        ];
    }

    /**
     * Get recent jobs for history display
     */
    public function getRecentJobs(): array
    {
        $jobs = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT option_name, option_value FROM {$this->wpdb->options}
            WHERE option_name LIKE %s
            ORDER BY option_id DESC LIMIT 10",
            self::OPTION_PREFIX . 'job_%'
        ));

        $recentJobs = [];
        foreach ($jobs as $job) {
            $jobId = str_replace(self::OPTION_PREFIX . 'job_', '', $job->option_name);
            $jobData = maybe_unserialize($job->option_value);

            // Check if this is a new format job (with timestamp) or old format (without)
            if (isset($jobData['timestamp'])) {
                // New format
                $recentJobs[] = [
                    'id' => $jobId,
                    'timestamp' => $jobData['timestamp'],
                    'options' => $jobData['options'] ?? [],
                    'results' => $jobData['results'] ?? []
                ];
            } elseif (isset($jobData['status'])) {
                // Old format - skip these for now since they have different structure
                // Could be enhanced later to convert old format to new if needed
                continue;
            }
        }

        return $recentJobs;
    }

    /**
     * Fix malformed URLs created by faulty replacements
     */
    public function fixMalformedUrls(): array
    {
        // Pattern for URL-encoded malformed URLs
        $encodedPattern = '/http:\/\/localhost:10003\/what-we-treat\/http%3A%2F%2Flocalhost%3A10003%2Fwhat-we-treat%2F([^\/\s"\'<>%]+)%2F/';
        $encodedReplacement = 'http://localhost:10003/what-we-treat/$1/';

        // Pattern for regular malformed URLs
        $regularPattern = '/http:\/\/localhost:10003\/what-we-treat\/http:\/\/localhost:10003\/what-we-treat\/([^\/\s"\'<>]+)\//';
        $regularReplacement = 'http://localhost:10003/what-we-treat/$1/';

        $postsFixed = 0;
        $metaFixed = 0;
        $urlsFixed = 0;

        // Fix posts content and excerpt
        $posts = $this->wpdb->get_results(
            "SELECT ID, post_content, post_excerpt FROM {$this->wpdb->posts}
            WHERE post_status = 'publish'
            AND (post_content LIKE '%what-we-treat/http%' OR post_excerpt LIKE '%what-we-treat/http%')"
        );

        foreach ($posts as $post) {
            $originalContent = $post->post_content;
            $originalExcerpt = $post->post_excerpt;

            // Apply both patterns
            $fixedContent = preg_replace($encodedPattern, $encodedReplacement, $post->post_content);
            $fixedContent = preg_replace($regularPattern, $regularReplacement, $fixedContent);

            $fixedExcerpt = preg_replace($encodedPattern, $encodedReplacement, $post->post_excerpt);
            $fixedExcerpt = preg_replace($regularPattern, $regularReplacement, $fixedExcerpt);

            $contentChanged = $fixedContent !== $originalContent;
            $excerptChanged = $fixedExcerpt !== $originalExcerpt;

            if ($contentChanged || $excerptChanged) {
                $this->wpdb->update(
                    $this->wpdb->posts,
                    [
                        'post_content' => $fixedContent,
                        'post_excerpt' => $fixedExcerpt
                    ],
                    ['ID' => $post->ID]
                );
                $postsFixed++;

                // Count individual URL fixes in content
                $urlsFixed += preg_match_all($encodedPattern, $originalContent);
                $urlsFixed += preg_match_all($regularPattern, $originalContent);
                $urlsFixed += preg_match_all($encodedPattern, $originalExcerpt);
                $urlsFixed += preg_match_all($regularPattern, $originalExcerpt);
            }
        }

        // Fix postmeta
        $metas = $this->wpdb->get_results(
            "SELECT meta_id, meta_value FROM {$this->wpdb->postmeta}
            WHERE meta_value LIKE '%what-we-treat/http%'"
        );

        foreach ($metas as $meta) {
            $originalValue = $meta->meta_value;

            // Apply both patterns
            $fixedValue = preg_replace($encodedPattern, $encodedReplacement, $meta->meta_value);
            $fixedValue = preg_replace($regularPattern, $regularReplacement, $fixedValue);

            if ($fixedValue !== $originalValue) {
                $this->wpdb->update(
                    $this->wpdb->postmeta,
                    ['meta_value' => $fixedValue],
                    ['meta_id' => $meta->meta_id]
                );
                $metaFixed++;
                $urlsFixed += preg_match_all($encodedPattern, $originalValue);
                $urlsFixed += preg_match_all($regularPattern, $originalValue);
            }
        }

        // Store repair job record
        $this->storeJobRecord(
            ['repair' => 'malformed_urls'],
            [
                'posts_fixed' => $postsFixed,
                'meta_fixed' => $metaFixed,
                'urls_fixed' => $urlsFixed
            ]
        );

        return [
            'success' => true,
            'message' => sprintf(
                __('Fixed %d malformed URLs in %d posts and %d meta fields', 'amfm-tools'),
                $urlsFixed,
                $postsFixed,
                $metaFixed
            ),
            'results' => [
                'posts_fixed' => $postsFixed,
                'meta_fixed' => $metaFixed,
                'urls_fixed' => $urlsFixed
            ]
        ];
    }

    /**
     * Clear all data
     */
    public function clearAllData(): bool
    {
        $options = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT option_name FROM {$this->wpdb->options}
            WHERE option_name LIKE %s",
            self::OPTION_PREFIX . '%'
        ));

        foreach ($options as $option) {
            delete_option($option->option_name);
        }

        // Clean up uploaded files
        $files = glob($this->uploadDir . '/*.csv');
        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $error): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum upload size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];

        return $messages[$error] ?? 'Unknown upload error';
    }

    /**
     * Process key tables where URLs are commonly stored
     * Targets: posts, postmeta (includes Elementor), options (widgets), menus, etc.
     */
    private function processKeyTables(array $mappings, array $options): array
    {
        $results = [
            'posts_updated' => 0,
            'meta_updated' => 0,
            'options_updated' => 0,
            'other_tables_updated' => 0,
            'urls_replaced' => 0,
            'tables_processed' => []
        ];

        // Define key tables that typically contain URLs
        $keyTables = [
            $this->wpdb->posts => ['post_content', 'post_excerpt', 'guid'],
            $this->wpdb->postmeta => ['meta_value'], // Includes Elementor data, ACF, etc.
            $this->wpdb->options => ['option_value'], // Widgets, theme options, etc.
            $this->wpdb->termmeta => ['meta_value'], // Category/tag meta
            $this->wpdb->commentmeta => ['meta_value'], // Comment meta
        ];

        // Add any custom tables that might contain URLs
        $allTables = $this->wpdb->get_col("SHOW TABLES");
        foreach ($allTables as $table) {
            // Add Elementor tables
            if (strpos($table, 'elementor') !== false) {
                $keyTables[$table] = $this->getTextColumns($table);
            }
            // Add menu/navigation tables
            elseif (strpos($table, 'menu') !== false || strpos($table, 'nav') !== false) {
                $keyTables[$table] = $this->getTextColumns($table);
            }
            // Skip already added tables and excluded tables
            elseif (isset($keyTables[$table]) ||
                    strpos($table, 'rank_math') !== false ||
                    strpos($table, 'rankmath') !== false ||
                    strpos($table, '_cache') !== false ||
                    strpos($table, '_transient') !== false ||
                    strpos($table, '_log') !== false ||
                    strpos($table, 'actionscheduler') !== false) {
                continue;
            }
        }

        // Process each URL mapping on key tables only
        foreach ($mappings as $originalUrl => $mapping) {
            $finalUrl = $mapping['final_url'];

            // Generate URL variants
            $urlVariants = $this->generateUrlVariants($originalUrl);

            foreach ($urlVariants as $searchUrl) {
                $replaceUrl = $this->normalizeUrlSlashes($searchUrl, $finalUrl);

                // Process each key table
                foreach ($keyTables as $table => $columns) {
                    foreach ($columns as $column) {

                    if ($options['dry_run']) {
                        // Use simpler counting query for dry run
                        $query = "SELECT 1 FROM `$table` WHERE `$column` LIKE '%" . $this->wpdb->esc_like($searchUrl) . "%' LIMIT 1";
                        $hasMatch = $this->wpdb->get_var($query);

                        if ($hasMatch) {
                            // Count actual occurrences using string counting
                            $contentQuery = "SELECT `$column` FROM `$table` WHERE `$column` LIKE '%" . $this->wpdb->esc_like($searchUrl) . "%'";
                            $contents = $this->wpdb->get_col($contentQuery);

                            foreach ($contents as $content) {
                                $results['urls_replaced'] += substr_count($content, $searchUrl);
                            }

                            if (!in_array($table, $results['tables_processed'])) {
                                $results['tables_processed'][] = $table;
                            }
                        }
                    } else {
                        // Perform actual replacement
                        $updated = $this->wpdb->query($this->wpdb->prepare(
                            "UPDATE `$table` SET `$column` = REPLACE(`$column`, %s, %s) WHERE `$column` LIKE %s",
                            $searchUrl,
                            $replaceUrl,
                            '%' . $this->wpdb->esc_like($searchUrl) . '%'
                        ));

                        if ($updated > 0) {
                            $results['urls_replaced'] += $updated;

                            // Track which type of table was updated
                            if (strpos($table, 'posts') !== false) {
                                $results['posts_updated'] += $updated;
                            } elseif (strpos($table, 'postmeta') !== false) {
                                $results['meta_updated'] += $updated;
                            } elseif (strpos($table, 'options') !== false) {
                                $results['options_updated'] += $updated;
                            } else {
                                $results['other_tables_updated'] += $updated;
                            }

                            if (!in_array($table, $results['tables_processed'])) {
                                $results['tables_processed'][] = $table;
                            }
                        }
                    }
                    } // Close foreach ($columns as $column)
                } // Close foreach ($keyTables as $table => $columns)
            } // Close foreach ($urlVariants as $searchUrl)
        } // Close foreach ($mappings as $originalUrl => $mapping)

        return $results;
    }

    /**
     * Get text columns from a table
     */
    private function getTextColumns(string $table): array
    {
        $columns = [];
        $tableColumns = $this->wpdb->get_results("SHOW COLUMNS FROM `$table`");

        foreach ($tableColumns as $column) {
            if (preg_match('/text|varchar/i', $column->Type)) {
                $columns[] = $column->Field;
            }
        }

        return $columns;
    }

    /**
     * Generate all URL variants (protocol and slash combinations)
     */
    private function generateUrlVariants(string $url): array
    {
        $variants = [$url];

        // Add protocol variants
        if (strpos($url, 'http://') === 0) {
            $variants[] = str_replace('http://', 'https://', $url);
        } elseif (strpos($url, 'https://') === 0) {
            $variants[] = str_replace('https://', 'http://', $url);
        }

        // Add slash variants for each protocol variant
        $allVariants = [];
        foreach ($variants as $variant) {
            $allVariants[] = $variant;

            // Add version with trailing slash if it doesn't have one
            if (substr($variant, -1) !== '/') {
                $allVariants[] = $variant . '/';
            }

            // Add version without trailing slash if it has one
            if (substr($variant, -1) === '/') {
                $allVariants[] = rtrim($variant, '/');
            }
        }

        return array_unique($allVariants);
    }

    /**
     * Normalize URL slashes to prevent double slashes when replacing URLs
     *
     * @param string $originalUrl The URL being replaced
     * @param string $finalUrl The replacement URL
     * @return string The normalized final URL
     */
    private function normalizeUrlSlashes(string $originalUrl, string $finalUrl): string
    {
        // If the original URL ends with a slash and the final URL also ends with a slash,
        // remove the trailing slash from the final URL to prevent double slashes
        if (substr($originalUrl, -1) === '/' && substr($finalUrl, -1) === '/') {
            return rtrim($finalUrl, '/');
        }

        // If the original URL doesn't end with a slash but the final URL does,
        // keep the final URL as is
        return $finalUrl;
    }

    /**
     * Normalize URL to current site domain
     *
     * @param string $url The URL to normalize
     * @return string The normalized URL with current site domain
     */
    private function normalizeUrlToDomain(string $url): string
    {
        $parsedUrl = parse_url($url);
        $currentSiteUrl = parse_url(home_url());

        if (!$parsedUrl || !isset($parsedUrl['path'])) {
            return $url;
        }

        // Build normalized URL with current site domain
        $normalizedUrl = $currentSiteUrl['scheme'] . '://';

        if (isset($currentSiteUrl['host'])) {
            $normalizedUrl .= $currentSiteUrl['host'];
        }

        if (isset($currentSiteUrl['port'])) {
            $normalizedUrl .= ':' . $currentSiteUrl['port'];
        }

        // Add the path from original URL
        $normalizedUrl .= $parsedUrl['path'];

        // Add query string if present
        if (isset($parsedUrl['query'])) {
            $normalizedUrl .= '?' . $parsedUrl['query'];
        }

        // Add fragment if present
        if (isset($parsedUrl['fragment'])) {
            $normalizedUrl .= '#' . $parsedUrl['fragment'];
        }

        return $normalizedUrl;
    }
}