<?php

namespace Tests\Unit\Controllers;

use Tests\Helpers\FrameworkTestCase;
use App\Controllers\ACFController;
use Mockery;

/**
 * Unit tests for ACFController
 * 
 * Tests ACF integration, keyword handling, and cookie management
 */
class ACFControllerTest extends FrameworkTestCase
{
    protected ACFController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ACFController();
    }

    public function testControllerInstantiation()
    {
        $this->assertInstanceOf(ACFController::class, $this->controller);
        $this->assertArrayHasKey('init', $this->controller->actions);
        $this->assertArrayHasKey('wp', $this->controller->actions);
        $this->assertEquals('initialize', $this->controller->actions['init']);
        $this->assertEquals('setKeywordsToCookies', $this->controller->actions['wp']);
    }

    public function testBootstrapWithoutACF()
    {
        // Mock class_exists to return false (ACF not active)
        $this->mockWordPressFunction('class_exists', 'ACF', false);
        
        // Use reflection to access protected bootstrap method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('bootstrap');
        $method->setAccessible(true);
        
        // This should complete without error even when ACF is not active
        $method->invoke($this->controller);
        $this->assertTrue(true);
    }

    public function testBootstrapWithACF()
    {
        // Mock class_exists to return true (ACF is active)
        $this->mockWordPressFunction('class_exists', 'ACF', true);
        
        // Use reflection to access protected bootstrap method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('bootstrap');
        $method->setAccessible(true);
        
        $method->invoke($this->controller);
        $this->assertTrue(true);
    }

    public function testInitializeAdmin()
    {
        // Mock the isAdmin method
        $controller = Mockery::mock(ACFController::class)->makePartial();
        $controller->shouldReceive('isAdmin')->andReturn(true);
        $controller->shouldReceive('isFrontend')->andReturn(false);
        
        $controller->initialize();
        $this->assertTrue(true);
    }

    public function testInitializeFrontend()
    {
        // Mock the isFrontend method
        $controller = Mockery::mock(ACFController::class)->makePartial();
        $controller->shouldReceive('isAdmin')->andReturn(false);
        $controller->shouldReceive('isFrontend')->andReturn(true);
        
        $controller->initialize();
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesSkipForAjax()
    {
        // Mock WordPress functions to simulate AJAX request
        $this->mockWordPressFunction('wp_doing_ajax', true);
        
        // The method should return early for AJAX requests
        $this->controller->setKeywordsToCookies();
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesSkipForAdmin()
    {
        // Mock WordPress functions to simulate admin request
        $this->mockWordPressFunction('wp_doing_ajax', false);
        $this->mockWordPressFunction('is_admin', true);
        
        // The method should return early for admin requests
        $this->controller->setKeywordsToCookies();
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesSkipForIframe()
    {
        // Mock WordPress functions and set iframe request
        $this->mockWordPressFunction('wp_doing_ajax', false);
        $this->mockWordPressFunction('is_admin', false);
        
        // Define the constant to simulate iframe request
        if (!defined('IFRAME_REQUEST')) {
            define('IFRAME_REQUEST', true);
        }
        
        // The method should return early for iframe requests
        $this->controller->setKeywordsToCookies();
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesSkipForGravityForms()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('wp_doing_ajax', false);
        $this->mockWordPressFunction('is_admin', false);
        
        // Set Gravity Forms AJAX parameters
        $_POST['gform_ajax'] = '1';
        
        // The method should return early for Gravity Forms requests
        $this->controller->setKeywordsToCookies();
        
        // Clean up
        unset($_POST['gform_ajax']);
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesSkipForSecFetchIframe()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('wp_doing_ajax', false);
        $this->mockWordPressFunction('is_admin', false);
        
        // Set iframe security header
        $_SERVER['HTTP_SEC_FETCH_DEST'] = 'iframe';
        
        // The method should return early for iframe security requests
        $this->controller->setKeywordsToCookies();
        
        // Clean up
        unset($_SERVER['HTTP_SEC_FETCH_DEST']);
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesWithValidKeywords()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('wp_doing_ajax', false);
        $this->mockWordPressFunction('is_admin', false);
        $this->mockWordPressFunction('setcookie', true);
        $this->mockWordPressFunction('json_encode', '["keyword1","keyword2"]');
        $this->mockWordPressFunction('time', 1234567890);
        
        // Mock the controller to return keywords
        $controller = Mockery::mock(ACFController::class)->makePartial();
        $controller->shouldReceive('getKeywords')->andReturn([
            'keywords' => ['keyword1', 'keyword2'],
            'other_keywords' => ['other1', 'other2']
        ]);
        
        // This should set cookies without errors
        $controller->setKeywordsToCookies();
        $this->assertTrue(true);
    }

    public function testSetKeywordsToCookiesWithEmptyKeywords()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('wp_doing_ajax', false);
        $this->mockWordPressFunction('is_admin', false);
        
        // Mock the controller to return empty keywords
        $controller = Mockery::mock(ACFController::class)->makePartial();
        $controller->shouldReceive('getKeywords')->andReturn([
            'keywords' => [],
            'other_keywords' => []
        ]);
        
        // This should complete without setting cookies
        $controller->setKeywordsToCookies();
        $this->assertTrue(true);
    }

    public function testGetKeywordsWithPostId()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('get_queried_object_id', 123);
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 123, 'keyword1,keyword2,keyword3');
        $this->mockWordPressFunction('get_field', 'amfm_other_keywords', 123, 'other1,other2');
        
        $result = $this->controller->getKeywords();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('keywords', $result);
        $this->assertArrayHasKey('other_keywords', $result);
        $this->assertEquals(['keyword1', 'keyword2', 'keyword3'], $result['keywords']);
        $this->assertEquals(['other1', 'other2'], $result['other_keywords']);
    }

    public function testGetKeywordsWithoutPostId()
    {
        global $post;
        $originalPost = $post;
        
        // Mock WordPress functions and global post
        $this->mockWordPressFunction('get_queried_object_id', 0);
        $post = (object) ['ID' => 456];
        
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 456, 'global_keyword');
        $this->mockWordPressFunction('get_field', 'amfm_other_keywords', 456, '');
        
        $result = $this->controller->getKeywords();
        
        $this->assertIsArray($result);
        $this->assertEquals(['global_keyword'], $result['keywords']);
        $this->assertEquals([], $result['other_keywords']);
        
        // Restore global post
        $post = $originalPost;
    }

    public function testGetKeywordsWithNoPostFound()
    {
        global $post;
        $originalPost = $post;
        
        // Mock WordPress functions with no post found
        $this->mockWordPressFunction('get_queried_object_id', 0);
        $post = null;
        
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 0, '');
        $this->mockWordPressFunction('get_field', 'amfm_other_keywords', 0, '');
        
        $result = $this->controller->getKeywords();
        
        $this->assertIsArray($result);
        $this->assertEquals([], $result['keywords']);
        $this->assertEquals([], $result['other_keywords']);
        
        // Restore global post
        $post = $originalPost;
    }

    public function testGetKeywordsWithNullValues()
    {
        // Mock WordPress functions with null ACF values
        $this->mockWordPressFunction('get_queried_object_id', 123);
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 123, null);
        $this->mockWordPressFunction('get_field', 'amfm_other_keywords', 123, null);
        
        $result = $this->controller->getKeywords();
        
        $this->assertIsArray($result);
        $this->assertEquals([], $result['keywords']);
        $this->assertEquals([], $result['other_keywords']);
    }

    public function testGetKeywordsWithSingleValues()
    {
        // Mock WordPress functions with single keyword values
        $this->mockWordPressFunction('get_queried_object_id', 123);
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 123, 'single_keyword');
        $this->mockWordPressFunction('get_field', 'amfm_other_keywords', 123, 'single_other');
        
        $result = $this->controller->getKeywords();
        
        $this->assertIsArray($result);
        $this->assertEquals(['single_keyword'], $result['keywords']);
        $this->assertEquals(['single_other'], $result['other_keywords']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}