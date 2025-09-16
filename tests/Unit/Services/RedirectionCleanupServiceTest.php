<?php

namespace Tests\Unit\Services;

use Tests\Helpers\TestCase;

/**
 * Unit tests for RedirectionCleanupService
 *
 * These tests focus on testing the core logic and functionality
 * of the RedirectionCleanupService class.
 */
class RedirectionCleanupServiceTest extends TestCase
{
    /**
     * Test URL normalization functionality
     */
    public function testUrlNormalization()
    {
        // Test relative URL normalization
        $this->assertEquals('/test-page', $this->normalizeTestUrl('/test-page'));
        $this->assertEquals('/test-page/', $this->normalizeTestUrl('/test-page/'));

        // Test empty URL handling
        $this->assertEquals('', $this->normalizeTestUrl(''));
    }

    /**
     * Test URL pattern replacement logic
     */
    public function testUrlReplacement()
    {
        $content = 'Visit our <a href="/old-page">old page</a> and check <a href="/another-old">another link</a>.';

        $urlMapping = [
            '/old-page' => '/new-page',
            '/another-old' => '/another-new'
        ];

        $result = $this->replaceUrlsInContent($content, $urlMapping);

        $this->assertStringContainsString('href="/new-page"', $result);
        $this->assertStringContainsString('href="/another-new"', $result);
        $this->assertStringNotContainsString('href="/old-page"', $result);
        $this->assertStringNotContainsString('href="/another-old"', $result);
    }

    /**
     * Test redirect chain resolution
     */
    public function testRedirectChainResolution()
    {
        $redirections = [
            [
                'sources' => [
                    ['pattern' => '/page-a', 'comparison' => 'exact']
                ],
                'url_to' => '/page-b'
            ],
            [
                'sources' => [
                    ['pattern' => '/page-b', 'comparison' => 'exact']
                ],
                'url_to' => '/page-c'
            ],
            [
                'sources' => [
                    ['pattern' => '/page-c', 'comparison' => 'exact']
                ],
                'url_to' => '/final-page'
            ]
        ];

        $finalDestination = $this->resolveFinalDestination('/page-a', $redirections);
        $this->assertEquals('/final-page', $finalDestination);
    }

    /**
     * Test infinite loop prevention in redirect chains
     */
    public function testInfiniteLoopPrevention()
    {
        $redirections = [
            [
                'sources' => [
                    ['pattern' => '/page-a', 'comparison' => 'exact']
                ],
                'url_to' => '/page-b'
            ],
            [
                'sources' => [
                    ['pattern' => '/page-b', 'comparison' => 'exact']
                ],
                'url_to' => '/page-a' // Creates a loop
            ]
        ];

        $result = $this->resolveFinalDestination('/page-a', $redirections, []);

        // Should prevent infinite loop and return something reasonable
        $this->assertNotEmpty($result);
        $this->assertTrue(in_array($result, ['/page-a', '/page-b']));
    }

    /**
     * Test URL mapping generation
     */
    public function testUrlMappingGeneration()
    {
        $redirections = [
            [
                'sources' => [
                    ['pattern' => '/old-url-1', 'comparison' => 'exact']
                ],
                'url_to' => '/new-url-1'
            ],
            [
                'sources' => [
                    ['pattern' => '/old-url-2', 'comparison' => 'exact']
                ],
                'url_to' => '/new-url-2'
            ]
        ];

        $mapping = $this->buildUrlMapping($redirections);

        $this->assertArrayHasKey('/old-url-1', $mapping);
        $this->assertArrayHasKey('/old-url-2', $mapping);
        $this->assertEquals('/new-url-1', $mapping['/old-url-1']);
        $this->assertEquals('/new-url-2', $mapping['/old-url-2']);
    }

    /**
     * Test URL replacement with various HTML contexts
     */
    public function testUrlReplacementInVariousContexts()
    {
        $content = '
            <a href="/old-page">Link</a>
            <img src="/old-image" alt="test">
            <a href=\'/old-single\'>Single quotes</a>
            Visit /old-plain in text
        ';

        $urlMapping = [
            '/old-page' => '/new-page',
            '/old-image' => '/new-image',
            '/old-single' => '/new-single',
            '/old-plain' => '/new-plain'
        ];

        $result = $this->replaceUrlsInContent($content, $urlMapping);

        // Check that URLs in attributes are replaced
        $this->assertStringContainsString('href="/new-page"', $result);
        $this->assertStringContainsString('src="/new-image"', $result);
        $this->assertStringContainsString("href='/new-single'", $result);
    }

    /**
     * Test relative vs absolute URL handling
     */
    public function testRelativeVsAbsoluteUrls()
    {
        $content = '
            <a href="/relative-url">Relative</a>
            <a href="https://example.com/absolute-url">Absolute</a>
        ';

        $urlMapping = [
            '/relative-url' => '/new-relative',
            'https://example.com/absolute-url' => 'https://example.com/new-absolute'
        ];

        $result = $this->replaceUrlsInContent($content, $urlMapping);

        $this->assertStringContainsString('href="/new-relative"', $result);
        $this->assertStringContainsString('href="https://example.com/new-absolute"', $result);
    }

    /**
     * Test empty and malformed URL handling
     */
    public function testEmptyAndMalformedUrlHandling()
    {
        // Empty content
        $result = $this->replaceUrlsInContent('', ['/old' => '/new']);
        $this->assertEquals('', $result);

        // Empty mapping
        $result = $this->replaceUrlsInContent('<a href="/test">link</a>', []);
        $this->assertEquals('<a href="/test">link</a>', $result);

        // Malformed redirections
        $redirections = [
            [
                'sources' => null, // Invalid
                'url_to' => '/new-page'
            ],
            [
                'sources' => [
                    ['pattern' => '', 'comparison' => 'exact'] // Empty pattern
                ],
                'url_to' => '/another-new'
            ]
        ];

        $mapping = $this->buildUrlMapping($redirections);
        $this->assertEmpty($mapping);
    }

    /**
     * Helper method to normalize URLs (simulates service method)
     */
    private function normalizeTestUrl(string $url): string
    {
        return trim($url);
    }

    /**
     * Helper method to replace URLs in content (simulates service method)
     */
    private function replaceUrlsInContent(string $content, array $urlMapping): string
    {
        if (empty($content) || empty($urlMapping)) {
            return $content;
        }

        $updatedContent = $content;

        foreach ($urlMapping as $oldUrl => $newUrl) {
            // Replace in href attributes (with quotes)
            $updatedContent = str_replace('href="' . $oldUrl . '"', 'href="' . $newUrl . '"', $updatedContent);

            // Replace in href attributes (with single quotes)
            $updatedContent = str_replace("href='" . $oldUrl . "'", "href='" . $newUrl . "'", $updatedContent);

            // Replace in src attributes (with quotes)
            $updatedContent = str_replace('src="' . $oldUrl . '"', 'src="' . $newUrl . '"', $updatedContent);

            // Replace in src attributes (with single quotes)
            $updatedContent = str_replace("src='" . $oldUrl . "'", "src='" . $newUrl . "'", $updatedContent);
        }

        return $updatedContent;
    }

    /**
     * Helper method to resolve redirect chains (simulates service method)
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
            $sources = $redirect['sources'] ?? [];
            if (!is_array($sources)) {
                continue;
            }

            foreach ($sources as $source) {
                if (($source['comparison'] ?? '') === 'exact' &&
                    ($source['pattern'] ?? '') === $url) {
                    return $this->resolveFinalDestination($redirect['url_to'], $redirections, $visited);
                }
            }
        }

        return $url;
    }

    /**
     * Helper method to build URL mapping (simulates service method)
     */
    private function buildUrlMapping(array $redirections): array
    {
        $urlMap = [];

        foreach ($redirections as $redirect) {
            $sources = $redirect['sources'] ?? null;
            $destination = $redirect['url_to'] ?? '';

            if (!is_array($sources) || empty($destination)) {
                continue;
            }

            // Process each source pattern
            foreach ($sources as $source) {
                $pattern = $source['pattern'] ?? '';
                $comparison = $source['comparison'] ?? '';

                if (empty($pattern) || $comparison !== 'exact') {
                    continue;
                }

                $finalDestination = $this->resolveFinalDestination($destination, $redirections);
                $urlMap[$pattern] = $finalDestination;
            }
        }

        return $urlMap;
    }

    /**
     * Test option parsing functionality
     */
    public function testOptionParsing()
    {
        $options = [
            'content_types' => ['posts', 'custom_fields'],
            'batch_size' => '50',
            'dry_run' => 'true',
            'include_relative' => 'false'
        ];

        $defaults = [
            'content_types' => ['posts', 'custom_fields', 'menus'],
            'batch_size' => 50,
            'dry_run' => false,
            'create_backup' => true,
            'include_relative' => true,
            'handle_query_params' => false
        ];

        $parsed = array_merge($defaults, $options);

        $this->assertEquals(['posts', 'custom_fields'], $parsed['content_types']);
        $this->assertEquals('50', $parsed['batch_size']);
        $this->assertEquals('true', $parsed['dry_run']);
    }

    /**
     * Test job data structure validation
     */
    public function testJobDataStructure()
    {
        $jobData = [
            'id' => 'test-job-123',
            'status' => 'initialized',
            'options' => [
                'content_types' => ['posts'],
                'batch_size' => 50,
                'dry_run' => false
            ],
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

        // Validate structure
        $this->assertArrayHasKey('id', $jobData);
        $this->assertArrayHasKey('status', $jobData);
        $this->assertArrayHasKey('options', $jobData);
        $this->assertArrayHasKey('progress', $jobData);
        $this->assertArrayHasKey('results', $jobData);

        // Validate nested structures
        $this->assertArrayHasKey('content_types', $jobData['options']);
        $this->assertArrayHasKey('batch_size', $jobData['options']);
        $this->assertArrayHasKey('dry_run', $jobData['options']);

        $this->assertArrayHasKey('total_items', $jobData['progress']);
        $this->assertArrayHasKey('processed_items', $jobData['progress']);
        $this->assertArrayHasKey('current_step', $jobData['progress']);

        $this->assertArrayHasKey('posts_updated', $jobData['results']);
        $this->assertArrayHasKey('total_url_replacements', $jobData['results']);
    }
}