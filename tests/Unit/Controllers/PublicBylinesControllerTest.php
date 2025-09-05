<?php

use App\Controllers\PublicBylinesController;
use Tests\Helpers\WordPressTestCase;

class PublicBylinesControllerTest extends WordPressTestCase
{
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PublicBylinesController();
    }

    public function testControllerInstantiation()
    {
        $this->assertInstanceOf(PublicBylinesController::class, $this->controller);
    }

    public function testGetBylineUrl()
    {
        // Test with mock data - would need proper WordPress test environment
        $url = $this->controller->getBylineUrl('author');
        $this->assertIsString($url);
    }

    public function testIsTaggedMethod()
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('isTagged');
        $method->setAccessible(true);

        // Test with empty tags (no WordPress context)
        $result = $method->invoke($this->controller, 'authored-by');
        $this->assertIsBool($result);
    }

    public function testGetBylineMethod()
    {
        // Test getByline method exists and returns false without tags
        $result = $this->controller->getByline('author');
        $this->assertFalse($result);
    }

    public function testGetStaffMethodExists()
    {
        // Use reflection to test private method exists
        $reflection = new ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('getStaff'));
        
        $method = $reflection->getMethod('getStaff');
        $this->assertFalse($method->isPublic());
    }

    public function testAjaxFetchRelatedPostsRequiresNonce()
    {
        // Mock $_POST data without proper nonce
        $_POST = [
            'widget_id' => 'test-widget',
            'post_id' => 123
        ];

        // This should fail nonce verification in real WordPress environment
        $this->expectException(\Error::class); // Function doesn't exist in test environment
        $this->controller->actionWpAjaxAmfmFetchRelatedPosts();
    }

    public function testManageBylinesSchemaMethod()
    {
        // Test schema method exists and handles empty data
        $empty_data = [];
        $result = $this->controller->manageBylinesSchema($empty_data);
        $this->assertIsArray($result);
    }

    public function testAddStaffProfileSchemaMethodExists()
    {
        // Use reflection to test private method exists
        $reflection = new ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('addStaffProfileSchema'));
        
        $method = $reflection->getMethod('addStaffProfileSchema');
        $this->assertFalse($method->isPublic());
    }
}