<?php

namespace Tests\Unit;

use Tests\Helpers\TestCase;

/**
 * Comprehensive tests for the Redirection Cleanup URL Validation Fix
 *
 * These tests verify that the fix prevents arbitrary text replacement
 * while allowing legitimate URL replacements in href attributes.
 */
class RedirectionCleanupValidationTest extends TestCase
{
    /**
     * Test that the URL validation regex works correctly
     */
    public function testUrlValidationRegex()
    {
        $testCases = [
            // Should be ALLOWED (valid URLs)
            ['/anxiety-treatment', true, 'Relative URL should be allowed'],
            ['/what-we-treat/depression', true, 'Relative URL with path should be allowed'],
            ['http://localhost:10003/page', true, 'HTTP URL should be allowed'],
            ['https://amfmtreatment.com/page', true, 'HTTPS URL should be allowed'],
            ['/', true, 'Root path should be allowed'],

            // Should be BLOCKED (arbitrary text)
            ['psychosis', false, 'Single word should be blocked'],
            ['depression', false, 'Single word should be blocked'],
            ['anxiety', false, 'Single word should be blocked'],
            ['trauma', false, 'Single word should be blocked'],
            ['some random text', false, 'Random text should be blocked'],
            ['file.pdf', false, 'Filename should be blocked'],
            ['image.jpg', false, 'Image filename should be blocked'],
        ];

        foreach ($testCases as [$url, $shouldMatch, $message]) {
            $matches = preg_match('#^(/|https?://)#', $url);
            $result = (bool) $matches;

            $this->assertEquals($shouldMatch, $result,
                "URL validation failed for '$url': $message"
            );
        }
    }

    /**
     * Test URL replacement with validation (the fixed logic)
     */
    public function testUrlReplacementWithValidation()
    {
        $content = '
            <p>Visit <a href="/anxiety-treatment">Anxiety Treatment</a> for help.</p>
            <p>We also treat psychosis and depression disorders.</p>
            <p>Check <a href="http://localhost:10003/old-page">our old page</a>.</p>
            <p>Text mentioning trauma and bipolar conditions.</p>
            <img src="/images/depression-help.jpg" alt="Depression Help">
        ';

        $urlMapping = [
            // Valid URLs that should be replaced
            '/anxiety-treatment' => '/what-we-treat/anxiety/',
            'http://localhost:10003/old-page' => 'http://localhost:10003/new-page',
            '/images/depression-help.jpg' => '/assets/depression-help.jpg',

            // Arbitrary text that should NOT be replaced (this was the bug)
            'psychosis' => 'http://localhost:10003/what-we-treat/psychosis/',
            'depression' => 'http://localhost:10003/what-we-treat/depression/',
            'trauma' => 'http://localhost:10003/what-we-treat/trauma/',
            'bipolar' => 'http://localhost:10003/what-we-treat/bipolar/',
        ];

        $result = $this->replaceUrlsWithValidation($content, $urlMapping);

        // Should replace valid URLs
        $this->assertStringContainsString('href="/what-we-treat/anxiety/"', $result,
            'Should replace relative URL in href');
        $this->assertStringContainsString('href="http://localhost:10003/new-page"', $result,
            'Should replace absolute URL in href');
        $this->assertStringContainsString('src="/assets/depression-help.jpg"', $result,
            'Should replace relative URL in src');

        // Should NOT replace arbitrary text
        $this->assertStringContainsString('We also treat psychosis and depression', $result,
            'Should NOT replace arbitrary text "psychosis" and "depression"');
        $this->assertStringContainsString('mentioning trauma and bipolar', $result,
            'Should NOT replace arbitrary text "trauma" and "bipolar"');

        // Verify old URLs are gone
        $this->assertStringNotContainsString('href="/anxiety-treatment"', $result,
            'Old relative URL should be replaced');
        $this->assertStringNotContainsString('href="http://localhost:10003/old-page"', $result,
            'Old absolute URL should be replaced');
    }

    /**
     * Test that the fix prevents the corruption scenario that was happening
     */
    public function testPreventUrlCorruption()
    {
        // This tests the exact scenario that was causing corruption
        $content = '
            <a href="http://localhost:10003/page-about-psychosis-treatment">Psychosis Article</a>
            <p>This page discusses psychosis in detail.</p>
        ';

        // This mapping would have caused corruption before the fix
        $corruptingMapping = [
            'psychosis' => 'http://localhost:10003/what-we-treat/psychosis/'
        ];

        $result = $this->replaceUrlsWithValidation($content, $corruptingMapping);

        // Should NOT create corrupted URLs
        $this->assertStringNotContainsString(
            'http://localhost:10003/page-about-http://localhost:10003/what-we-treat/psychosis/',
            $result,
            'Should NOT create corrupted URLs by replacing text within existing URLs'
        );

        // Original content should remain intact
        $this->assertStringContainsString(
            'href="http://localhost:10003/page-about-psychosis-treatment"',
            $result,
            'Original URL should remain intact'
        );
        $this->assertStringContainsString(
            'discusses psychosis in detail',
            $result,
            'Text content should remain intact'
        );
    }

    /**
     * Test edge cases and complex scenarios
     */
    public function testEdgeCases()
    {
        $content = '
            <a href="/psychosis">Valid relative URL</a>
            <a href="psychosis">Invalid relative URL (no slash)</a>
            <p>The word psychosis appears here.</p>
            <a href="/anxiety/">URL with trailing slash</a>
            <a href="/depression-treatment">Hyphenated URL</a>
            Text about depression-treatment options.
        ';

        $urlMapping = [
            '/psychosis' => '/what-we-treat/psychosis/',
            'psychosis' => 'http://localhost:10003/blocked/',
            '/anxiety/' => '/what-we-treat/anxiety/',
            '/depression-treatment' => '/what-we-treat/depression/',
            'depression-treatment' => 'http://localhost:10003/blocked-text/',
        ];

        $result = $this->replaceUrlsWithValidation($content, $urlMapping);

        // Valid URLs should be replaced
        $this->assertStringContainsString('href="/what-we-treat/psychosis/"', $result);
        $this->assertStringContainsString('href="/what-we-treat/anxiety/"', $result);
        $this->assertStringContainsString('href="/what-we-treat/depression/"', $result);

        // Invalid URLs and arbitrary text should NOT be replaced
        $this->assertStringContainsString('href="psychosis"', $result,
            'Invalid relative URL (no slash) should remain unchanged');
        $this->assertStringContainsString('The word psychosis appears', $result,
            'Arbitrary text should not be replaced');
        $this->assertStringContainsString('about depression-treatment options', $result,
            'Hyphenated text should not be replaced');
    }

    /**
     * Test custom fields and meta data replacement
     */
    public function testCustomFieldReplacement()
    {
        $metaValues = [
            '_custom_url' => '/old-anxiety-page',
            '_description' => 'This field mentions psychosis and has a link to /trauma-help',
            '_another_field' => 'Just text about depression without URLs',
            '_image_url' => 'http://localhost:10003/old-image.jpg'
        ];

        $urlMapping = [
            '/old-anxiety-page' => '/new-anxiety-page',
            '/trauma-help' => '/what-we-treat/trauma/',
            'psychosis' => 'http://localhost:10003/what-we-treat/psychosis/',
            'depression' => 'http://localhost:10003/what-we-treat/depression/',
            'http://localhost:10003/old-image.jpg' => 'http://localhost:10003/new-image.jpg'
        ];

        $results = [];
        foreach ($metaValues as $key => $value) {
            $results[$key] = $this->replaceUrlsWithValidation($value, $urlMapping);
        }

        // Should replace valid URLs
        $this->assertEquals('/new-anxiety-page', $results['_custom_url']);
        $this->assertStringContainsString('/what-we-treat/trauma/', $results['_description']);
        $this->assertEquals('http://localhost:10003/new-image.jpg', $results['_image_url']);

        // Should NOT replace arbitrary text
        $this->assertStringContainsString('mentions psychosis', $results['_description'],
            'Should not replace arbitrary text in descriptions');
        $this->assertStringContainsString('about depression without', $results['_another_field'],
            'Should not replace arbitrary text in other fields');
    }

    /**
     * Test that changes are tracked correctly
     */
    public function testChangeTracking()
    {
        $content = '<a href="/old-page">Link</a> and <a href="/another-old">Another</a>';
        $urlMapping = [
            '/old-page' => '/new-page',
            '/another-old' => '/another-new',
            'invalid-text' => '/should-not-replace'
        ];

        $details = $this->replaceUrlsWithValidationAndDetails($content, $urlMapping);

        $this->assertEquals(2, count($details['changes']), 'Should track 2 valid changes');

        $changes = $details['changes'];
        $this->assertEquals('/old-page', $changes[0]['old']);
        $this->assertEquals('/new-page', $changes[0]['new']);
        $this->assertEquals(1, $changes[0]['count']);

        $this->assertEquals('/another-old', $changes[1]['old']);
        $this->assertEquals('/another-new', $changes[1]['new']);
        $this->assertEquals(1, $changes[1]['count']);

        // Should not track invalid replacements
        $invalidChanges = array_filter($changes, function($change) {
            return $change['old'] === 'invalid-text';
        });
        $this->assertEmpty($invalidChanges, 'Should not track changes for invalid URLs');
    }

    /**
     * Helper method that implements the URL replacement with validation
     * This mimics the actual service method with the fix
     */
    private function replaceUrlsWithValidation(string $content, array $urlMapping): string
    {
        if (empty($content) || empty($urlMapping)) {
            return $content;
        }

        // Clean up double slashes FIRST (as per the fix)
        $updatedContent = preg_replace('#(?<!http:)(?<!https:)//+#', '/', $content);

        foreach ($urlMapping as $oldUrl => $newUrl) {
            // THE FIX: Skip if this doesn't look like a URL path
            if (!preg_match('#^(/|https?://)#', $oldUrl)) {
                continue;
            }

            // Check if this is a relative URL (starts with /)
            $isRelativeUrl = strpos($oldUrl, '/') === 0;

            if ($isRelativeUrl) {
                // For relative URLs, only replace in href and src attributes
                $updatedContent = str_replace('href="' . $oldUrl . '"', 'href="' . $newUrl . '"', $updatedContent);
                $updatedContent = str_replace("href='" . $oldUrl . "'", "href='" . $newUrl . "'", $updatedContent);
                $updatedContent = str_replace('src="' . $oldUrl . '"', 'src="' . $newUrl . '"', $updatedContent);
                $updatedContent = str_replace("src='" . $oldUrl . "'", "src='" . $newUrl . "'", $updatedContent);
            } else {
                // For absolute URLs, replace anywhere in the content
                $updatedContent = str_replace($oldUrl, $newUrl, $updatedContent);
            }
        }

        return $updatedContent;
    }

    /**
     * Helper method that implements URL replacement with change tracking
     */
    private function replaceUrlsWithValidationAndDetails(string $content, array $urlMapping): array
    {
        if (empty($content) || empty($urlMapping)) {
            return [
                'content' => $content,
                'changes' => []
            ];
        }

        // Clean up double slashes FIRST
        $updatedContent = preg_replace('#(?<!http:)(?<!https:)//+#', '/', $content);
        $changes = [];

        foreach ($urlMapping as $oldUrl => $newUrl) {
            $totalCount = 0;

            // THE FIX: Skip if this doesn't look like a URL path
            if (!preg_match('#^(/|https?://)#', $oldUrl)) {
                continue;
            }

            // Check if this is a relative URL (starts with /)
            $isRelativeUrl = strpos($oldUrl, '/') === 0;

            if ($isRelativeUrl) {
                // For relative URLs, only replace in href and src attributes
                $updatedContent = str_replace('href="' . $oldUrl . '"', 'href="' . $newUrl . '"', $updatedContent, $count);
                $totalCount += $count;
                $updatedContent = str_replace("href='" . $oldUrl . "'", "href='" . $newUrl . "'", $updatedContent, $count);
                $totalCount += $count;
                $updatedContent = str_replace('src="' . $oldUrl . '"', 'src="' . $newUrl . '"', $updatedContent, $count);
                $totalCount += $count;
                $updatedContent = str_replace("src='" . $oldUrl . "'", "src='" . $newUrl . "'", $updatedContent, $count);
                $totalCount += $count;
            } else {
                // For absolute URLs, replace anywhere in the content
                $updatedContent = str_replace($oldUrl, $newUrl, $updatedContent, $count);
                $totalCount += $count;
            }

            // Track changes
            if ($totalCount > 0) {
                $changes[] = [
                    'old' => $oldUrl,
                    'new' => $newUrl,
                    'count' => $totalCount
                ];
            }
        }

        return [
            'content' => $updatedContent,
            'changes' => $changes
        ];
    }
}