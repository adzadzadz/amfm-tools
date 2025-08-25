<?php

namespace Tests\Unit\Controllers;

use Tests\Helpers\FrameworkTestCase;
use App\Controllers\ShortcodeController;
use Mockery;

/**
 * Unit tests for ShortcodeController
 * 
 * Tests shortcode registration, DKV functionality, keyword filtering and processing
 */
class ShortcodeControllerTest extends FrameworkTestCase
{
    protected ShortcodeController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ShortcodeController();
        
        // Clear cookies for clean testing
        $_COOKIE = [];
    }

    public function testControllerInstantiation()
    {
        $this->assertInstanceOf(ShortcodeController::class, $this->controller);
        $this->assertArrayHasKey('init', $this->controller->actions);
        $this->assertEquals('registerShortcodes', $this->controller->actions['init']);
        $this->assertIsArray($this->controller->filters);
    }

    public function testBootstrap()
    {
        // Use reflection to access protected bootstrap method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('bootstrap');
        $method->setAccessible(true);
        
        // Bootstrap method is currently empty but should not cause errors
        $method->invoke($this->controller);
        $this->assertTrue(true);
    }

    public function testRegisterShortcodes()
    {
        // Mock WordPress add_shortcode function
        $this->mockWordPressFunction('add_shortcode', 'dkv', [$this->controller, 'renderDkvShortcode']);
        
        $this->controller->registerShortcodes();
        $this->assertTrue(true);
    }

    public function testRenderDkvShortcodeWithDefaults()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts');
        $this->mockWordPressFunction('wp_kses_post', '', '');
        $this->mockWordPressFunction('sanitize_text_field', '', '');
        
        // Mock the controller's internal methods
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getRandomKeyword')
            ->andReturn('test keyword');
        $controller->shouldReceive('stripCategoryPrefix')
            ->with('test keyword')
            ->andReturn('test keyword');
        $controller->shouldReceive('applyTextTransform')
            ->with('test keyword', '')
            ->andReturn('test keyword');

        $result = $controller->renderDkvShortcode();
        $this->assertEquals('test keyword', $result);
    }

    public function testRenderDkvShortcodeWithCustomAttributes()
    {
        $atts = [
            'pre' => 'Before: ',
            'post' => ' :After',
            'fallback' => 'No keywords',
            'other_keywords' => 'true',
            'text' => 'uppercase'
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [], $atts, 'dkv', $atts);
        $this->mockWordPressFunction('wp_kses_post', 'Before: ', 'Before: ');
        $this->mockWordPressFunction('wp_kses_post', ' :After', ' :After');
        $this->mockWordPressFunction('sanitize_text_field', 'No keywords', 'No keywords');
        $this->mockWordPressFunction('sanitize_text_field', 'true', 'true');
        $this->mockWordPressFunction('sanitize_text_field', '', '');
        $this->mockWordPressFunction('sanitize_text_field', 'uppercase', 'uppercase');
        
        // Mock the controller's internal methods
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getRandomKeyword')
            ->with(true, '', '')
            ->andReturn('test keyword');
        $controller->shouldReceive('stripCategoryPrefix')
            ->with('test keyword')
            ->andReturn('test keyword');
        $controller->shouldReceive('applyTextTransform')
            ->with('test keyword', 'uppercase')
            ->andReturn('TEST KEYWORD');

        $result = $controller->renderDkvShortcode($atts);
        $this->assertEquals('Before: TEST KEYWORD :After', $result);
    }

    public function testRenderDkvShortcodeFallback()
    {
        $atts = ['fallback' => 'No keywords available'];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts');
        $this->mockWordPressFunction('wp_kses_post');
        $this->mockWordPressFunction('sanitize_text_field', 'No keywords available', 'No keywords available');
        
        // Mock the controller to return empty keyword
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getRandomKeyword')->andReturn('');

        $result = $controller->renderDkvShortcode($atts);
        $this->assertEquals('No keywords available', $result);
    }

    public function testGetRandomKeywordWithACFData()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRandomKeyword');
        $method->setAccessible(true);

        // Mock the controller's internal methods
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getKeywordsFromSource')
            ->with(false)
            ->andReturn(['keyword1', 'keyword2', 'keyword3']);
        $controller->shouldReceive('filterExcludedKeywords')
            ->andReturn(['keyword1', 'keyword2', 'keyword3']);
        $controller->shouldReceive('filterByCategory')
            ->andReturn(['keyword1', 'keyword2', 'keyword3']);

        $result = $method->invoke($controller, false, '', '');
        $this->assertContains($result, ['keyword1', 'keyword2', 'keyword3']);
    }

    public function testGetRandomKeywordWithNoKeywords()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRandomKeyword');
        $method->setAccessible(true);

        // Mock the controller to return empty keywords
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getKeywordsFromSource')->andReturn([]);
        $controller->shouldReceive('filterExcludedKeywords')->andReturn([]);
        $controller->shouldReceive('filterByCategory')->andReturn([]);

        $result = $method->invoke($controller, false, '', '');
        $this->assertEquals('', $result);
    }

    public function testGetKeywordsFromSourceWithACF()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getKeywordsFromSource');
        $method->setAccessible(true);

        // Mock WordPress functions
        $this->mockWordPressFunction('get_queried_object_id', 123);
        $this->mockWordPressFunction('function_exists', 'get_field', true);
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 123, 'keyword1,keyword2,keyword3');

        $result = $method->invoke($this->controller, false);
        $this->assertEquals(['keyword1', 'keyword2', 'keyword3'], $result);
    }

    public function testGetKeywordsFromSourceWithCookies()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getKeywordsFromSource');
        $method->setAccessible(true);

        // Mock WordPress functions for no ACF data
        $this->mockWordPressFunction('get_queried_object_id', 123);
        $this->mockWordPressFunction('function_exists', 'get_field', true);
        $this->mockWordPressFunction('get_field', 'amfm_keywords', 123, '');

        // Set cookie data
        $_COOKIE['amfm_keywords'] = json_encode(['cookie1', 'cookie2']);

        $result = $method->invoke($this->controller, false);
        $this->assertEquals(['cookie1', 'cookie2'], $result);
    }

    public function testGetKeywordsFromSourceWithNoData()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getKeywordsFromSource');
        $method->setAccessible(true);

        // Mock WordPress functions for no data
        $this->mockWordPressFunction('get_queried_object_id', 0);
        $this->mockWordPressFunction('function_exists', 'get_field', false);

        global $post;
        $post = null;

        $result = $method->invoke($this->controller, false);
        $this->assertEquals([], $result);
    }

    public function testFilterExcludedKeywords()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('filterExcludedKeywords');
        $method->setAccessible(true);

        // Mock the controller to return excluded keywords
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getExcludedKeywords')
            ->andReturn(['excluded1', 'excluded2']);

        $keywords = ['keyword1', 'excluded1', 'keyword2', 'EXCLUDED2', 'keyword3'];
        $result = $method->invoke($controller, $keywords);
        
        $this->assertEquals(['keyword1', 'keyword2', 'keyword3'], $result);
    }

    public function testGetExcludedKeywordsExisting()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getExcludedKeywords');
        $method->setAccessible(true);

        // Mock get_option to return existing excluded keywords
        $this->mockWordPressFunction('get_option', 'amfm_excluded_keywords', null, ['custom1', 'custom2']);

        $result = $method->invoke($this->controller);
        $this->assertEquals(['custom1', 'custom2'], $result);
    }

    public function testGetExcludedKeywordsDefaults()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getExcludedKeywords');
        $method->setAccessible(true);

        // Mock WordPress functions for defaults
        $this->mockWordPressFunction('get_option', 'amfm_excluded_keywords', null, null);
        $this->mockWordPressFunction('update_option');
        
        // Mock the controller to return default excluded keywords
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('getDefaultExcludedKeywords')
            ->andReturn(['co-occurring', 'comorbid']);

        $result = $method->invoke($controller);
        $this->assertEquals(['co-occurring', 'comorbid'], $result);
    }

    public function testFilterByCategory()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('filterByCategory');
        $method->setAccessible(true);

        // Mock the controller's extractCategory method
        $controller = Mockery::mock(ShortcodeController::class)->makePartial();
        $controller->shouldReceive('extractCategory')
            ->andReturnUsing(function($keyword) {
                if (strpos($keyword, ':') !== false) {
                    return explode(':', $keyword)[0];
                }
                return '';
            });

        $keywords = ['cat1:keyword1', 'cat2:keyword2', 'cat1:keyword3', 'nokeyword'];
        
        // Test include filter
        $result = $method->invoke($controller, $keywords, 'cat1', '');
        $this->assertEquals(['cat1:keyword1', 'cat1:keyword3'], $result);
        
        // Test exclude filter
        $result = $method->invoke($controller, $keywords, '', 'cat2');
        $this->assertEquals(['cat1:keyword1', 'cat1:keyword3', 'nokeyword'], $result);
    }

    public function testExtractCategory()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('extractCategory');
        $method->setAccessible(true);

        // Test with category
        $result = $method->invoke($this->controller, 'category:keyword');
        $this->assertEquals('category', $result);
        
        // Test without category
        $result = $method->invoke($this->controller, 'keyword');
        $this->assertEquals('', $result);
        
        // Test with multiple colons
        $result = $method->invoke($this->controller, 'cat:sub:keyword');
        $this->assertEquals('cat', $result);
    }

    public function testStripCategoryPrefix()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('stripCategoryPrefix');
        $method->setAccessible(true);

        // Test with category prefix
        $result = $method->invoke($this->controller, 'category:keyword');
        $this->assertEquals('keyword', $result);
        
        // Test without category prefix
        $result = $method->invoke($this->controller, 'keyword');
        $this->assertEquals('keyword', $result);
        
        // Test with multiple colons
        $result = $method->invoke($this->controller, 'cat:sub:keyword');
        $this->assertEquals('sub:keyword', $result);
    }

    public function testApplyTextTransform()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('applyTextTransform');
        $method->setAccessible(true);

        // Test lowercase
        $result = $method->invoke($this->controller, 'Test Keyword', 'lowercase');
        $this->assertEquals('test keyword', $result);
        
        // Test uppercase
        $result = $method->invoke($this->controller, 'Test Keyword', 'uppercase');
        $this->assertEquals('TEST KEYWORD', $result);
        
        // Test capitalize
        $result = $method->invoke($this->controller, 'test keyword', 'capitalize');
        $this->assertEquals('Test Keyword', $result);
        
        // Test no transform
        $result = $method->invoke($this->controller, 'Test Keyword', 'invalid');
        $this->assertEquals('Test Keyword', $result);
        
        // Test empty transform
        $result = $method->invoke($this->controller, 'Test Keyword', '');
        $this->assertEquals('Test Keyword', $result);
    }

    public function testGetDefaultExcludedKeywords()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDefaultExcludedKeywords');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller);
        $this->assertIsArray($result);
        $this->assertContains('co-occurring', $result);
        $this->assertContains('comorbid', $result);
        $this->assertContains('co-morbid', $result);
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        Mockery::close();
        parent::tearDown();
    }
}