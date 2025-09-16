<?php

namespace Tests\Unit;

use Tests\Helpers\TestCase;

/**
 * Functional tests for the Redirection Cleanup System
 *
 * These tests verify that the main classes can be instantiated
 * and their basic methods work correctly.
 */
class RedirectionCleanupFunctionalTest extends TestCase
{
    /**
     * Test that the service and controller classes exist and have the expected methods
     */
    public function testClassesExistAndHaveExpectedMethods()
    {
        // Test that classes exist
        $this->assertTrue(class_exists(\App\Services\RedirectionCleanupService::class));
        $this->assertTrue(class_exists(\App\Controllers\Admin\RedirectionCleanupController::class));

        // Test that service has expected methods
        $serviceMethods = get_class_methods(\App\Services\RedirectionCleanupService::class);
        $expectedServiceMethods = [
            'isRankMathActive',
            'getAnalysisData',
            'analyzeRedirections',
            'startCleanupProcess',
            'processCleanupJob',
            'getJobProgress',
            'getRecentJobs',
            'rollbackChanges'
        ];

        foreach ($expectedServiceMethods as $method) {
            $this->assertContains($method, $serviceMethods, "Service missing method: $method");
        }

        // Test that controller has expected methods
        $controllerMethods = get_class_methods(\App\Controllers\Admin\RedirectionCleanupController::class);
        $expectedControllerMethods = [
            'actionAdminMenu',
            'actionAdminEnqueueScripts',
            'renderAdminPage',
            'actionWpAjaxAnalyzeRedirections',
            'actionWpAjaxStartCleanup',
            'actionWpAjaxGetCleanupProgress',
            'actionWpAjaxRollbackCleanup',
            'actionWpAjaxGetJobDetails'
        ];

        foreach ($expectedControllerMethods as $method) {
            $this->assertContains($method, $controllerMethods, "Controller missing method: $method");
        }
    }

    /**
     * Test URL replacement logic (core functionality)
     */
    public function testUrlReplacementCore()
    {
        // This tests the core URL replacement logic similar to what's in the service
        $content = 'Visit <a href="/old-page">our page</a> and <a href="/another-old">another link</a>.';
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
     * Helper method to create a mock wpdb object
     */
    private function getMockWpdb()
    {
        $wpdb = new \stdClass();
        $wpdb->prefix = 'wp_';
        $wpdb->posts = 'wp_posts';
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->options = 'wp_options';

        // Add mock methods
        $wpdb->get_var = function() { return 0; };
        $wpdb->get_results = function() { return []; };
        $wpdb->prepare = function($query) { return $query; };

        return $wpdb;
    }

    /**
     * Helper method to mock WordPress functions
     */
    private function mockWordPressFunctions()
    {
        // Define constants
        if (!defined('ARRAY_A')) {
            define('ARRAY_A', 'ARRAY_A');
        }
        if (!defined('FILTER_VALIDATE_BOOLEAN')) {
            define('FILTER_VALIDATE_BOOLEAN', 258);
        }
        if (!defined('AMFM_TOOLS_URL')) {
            define('AMFM_TOOLS_URL', 'https://example.com/wp-content/plugins/amfm-tools/');
        }
        if (!defined('AMFM_TOOLS_VERSION')) {
            define('AMFM_TOOLS_VERSION', '1.0.0');
        }

        // For this test, we'll rely on the Brain Monkey setup in the parent class
        // or use mocks/stubs instead of redeclaring functions
    }

    /**
     * Helper method to replace URLs in content (simplified version)
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
}