<?php

use App\Controllers\PublicBylinesController;
use Tests\Helpers\WordPressTestCase;

/**
 * Integration tests to validate the PublicBylinesController migration
 * 
 * These tests validate that all core functionality from the original amfm-bylines
 * plugin has been properly migrated and works as expected
 */
class PublicBylinesValidationTest extends WordPressTestCase
{
    private $controller;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PublicBylinesController();
    }

    /**
     * Test that WordPress hooks are properly registered
     */
    public function testWordPressHooksRegistration()
    {
        // Test that the controller has the expected action methods
        $this->assertTrue(method_exists($this->controller, 'actionInit'));
        $this->assertTrue(method_exists($this->controller, 'actionWpEnqueueScripts'));
        $this->assertTrue(method_exists($this->controller, 'actionWpAjaxAmfmFetchRelatedPosts'));
        $this->assertTrue(method_exists($this->controller, 'actionWpAjaxNoprivAmfmFetchRelatedPosts'));
        
        // Test filter method exists
        $this->assertTrue(method_exists($this->controller, 'filterRankMathFrontendDescription'));
    }

    /**
     * Test schema management functionality
     */
    public function testSchemaManagement()
    {
        // Test that manageBylinesSchema method exists and returns array
        $this->assertTrue(method_exists($this->controller, 'manageBylinesSchema'));
        
        $sample_data = [
            [
                '@type' => 'Article',
                'headline' => 'Test Article'
            ]
        ];
        
        $result = $this->controller->manageBylinesSchema($sample_data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertEquals('Article', $result[0]['@type']);
    }

    /**
     * Test byline data retrieval from Staff CPT
     */
    public function testBylineDataRetrieval()
    {
        // Test getByline method exists and handles different types
        $this->assertTrue(method_exists($this->controller, 'getByline'));
        
        // Without WordPress context, should return false
        $this->assertFalse($this->controller->getByline('author'));
        $this->assertFalse($this->controller->getByline('editor'));
        $this->assertFalse($this->controller->getByline('reviewedBy'));
        $this->assertFalse($this->controller->getByline('inThePress'));
    }

    /**
     * Test byline URL generation
     */
    public function testBylineUrlGeneration()
    {
        // Test getBylineUrl method exists and returns appropriate message when no byline found
        $this->assertTrue(method_exists($this->controller, 'getBylineUrl'));
        
        $result = $this->controller->getBylineUrl('author');
        $this->assertEquals('No byline found', $result);
        
        $result = $this->controller->getBylineUrl('editor');
        $this->assertEquals('No byline found', $result);
        
        $result = $this->controller->getBylineUrl('reviewedBy');
        $this->assertEquals('No byline found', $result);
    }

    /**
     * Test tag checking functionality
     */
    public function testTagChecking()
    {
        // Use reflection to test private isTagged method
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('isTagged');
        $method->setAccessible(true);
        
        // Without WordPress context, should return false
        $this->assertFalse($method->invoke($this->controller, 'authored-by'));
        $this->assertFalse($method->invoke($this->controller, 'edited-by'));
        $this->assertFalse($method->invoke($this->controller, 'medically-reviewed-by'));
        $this->assertFalse($method->invoke($this->controller, 'medicalwebpage', true));
    }

    /**
     * Test staff post retrieval
     */
    public function testStaffPostRetrieval()
    {
        // Use reflection to test private getStaff method
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('getStaff');
        $method->setAccessible(true);
        
        // Without WordPress context, should return false
        $this->assertFalse($method->invoke($this->controller, 'authored-by-test'));
    }

    /**
     * Test LinkedIn URL functionality
     */
    public function testLinkedInUrlFunctionality()
    {
        // Use reflection to test private getLinkedinUrl method
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('getLinkedinUrl');
        $method->setAccessible(true);
        
        // Without WordPress context, should return false
        $this->assertFalse($method->invoke($this->controller));
    }

    /**
     * Test RankMath integration
     */
    public function testRankMathIntegration()
    {
        // Test that description filter method exists and works
        $original_description = 'Original description';
        $result = $this->controller->filterRankMathFrontendDescription($original_description);
        
        // Without staff context, should return original description
        $this->assertEquals($original_description, $result);
    }

    /**
     * Test AJAX related posts functionality
     */
    public function testAjaxRelatedPostsFunctionality()
    {
        // Use reflection to test private getRelatedPosts method
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRelatedPosts');
        $method->setAccessible(true);
        
        // Test with invalid post ID
        $result = $method->invoke($this->controller, 999999, 'all', 5, 1, 'post');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertEquals('<p>No related posts found.</p>', $result['content']);
    }

    /**
     * Test staff profile schema functionality
     */
    public function testStaffProfileSchema()
    {
        // Use reflection to test private addStaffProfileSchema method
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('addStaffProfileSchema');
        $method->setAccessible(true);
        
        $this->assertTrue($method->isPrivate());
        
        // Method exists and should be callable
        $this->assertTrue(is_callable([$this->controller, 'addStaffProfileSchema']));
    }

    /**
     * Test that all expected byline types are supported
     */
    public function testSupportedBylineTypes()
    {
        $supported_types = ['author', 'editor', 'reviewedBy', 'inThePress'];
        
        foreach ($supported_types as $type) {
            // Each type should return consistent response when no data available
            $result = $this->controller->getByline($type);
            $this->assertFalse($result);
            
            $url_result = $this->controller->getBylineUrl($type);
            $this->assertEquals('No byline found', $url_result);
        }
    }

    /**
     * Test medical webpage detection logic
     */
    public function testMedicalWebPageLogic()
    {
        // The reviewedBy functionality should only work for medical webpages
        // This is tested indirectly through getByline method behavior
        $result = $this->controller->getByline('reviewedBy');
        $this->assertFalse($result); // Should be false without medical context
    }

    /**
     * Test expected JavaScript localization data structure
     */
    public function testJavaScriptLocalizationData()
    {
        // Test that the controller methods used for JS localization exist and work
        $reflection = new ReflectionClass($this->controller);
        $isTagged = $reflection->getMethod('isTagged');
        $isTagged->setAccessible(true);
        
        $getLinkedinUrl = $reflection->getMethod('getLinkedinUrl');
        $getLinkedinUrl->setAccessible(true);
        
        // Expected data structure for amfmLocalize
        $expected_keys = [
            'author',
            'editor', 
            'reviewedBy',
            'author_page_url',
            'editor_page_url',
            'reviewer_page_url',
            'in_the_press_page_url',
            'has_social_linkedin'
        ];
        
        // All methods should be callable and return expected types
        $this->assertIsBool($isTagged->invoke($this->controller, 'authored-by'));
        $this->assertIsBool($isTagged->invoke($this->controller, 'edited-by'));
        $this->assertFalse($getLinkedinUrl->invoke($this->controller));
        
        // URL methods should return strings
        $this->assertIsString($this->controller->getBylineUrl('author'));
        $this->assertIsString($this->controller->getBylineUrl('editor'));
        $this->assertIsString($this->controller->getBylineUrl('reviewedBy'));
        $this->assertIsString($this->controller->getBylineUrl('inThePress'));
    }

    /**
     * Test that no database-specific functionality remains
     */
    public function testNoDatabaseFunctionality()
    {
        // Ensure old database methods were removed
        $this->assertFalse(method_exists($this->controller, 'manageBylinesSchemaDatabase'));
        $this->assertFalse(method_exists($this->controller, 'hasBylinesData'));
        
        // Ensure getByline no longer accepts $use_cpt parameter in the new version
        $reflection = new ReflectionMethod($this->controller, 'getByline');
        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters); // Only $type parameter should remain
        $this->assertEquals('type', $parameters[0]->getName());
    }
}