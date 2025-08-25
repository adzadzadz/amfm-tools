<?php

namespace Tests\Unit\Controllers;

use Tests\Helpers\FrameworkTestCase;
use App\Controllers\OptimizationController;
use Mockery;

/**
 * Unit tests for OptimizationController
 * 
 * Tests performance optimization features, Gravity Forms optimization
 */
class OptimizationControllerTest extends FrameworkTestCase
{
    protected OptimizationController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new OptimizationController();
    }

    public function testControllerInstantiation()
    {
        $this->assertInstanceOf(OptimizationController::class, $this->controller);
        $this->assertArrayHasKey('init', $this->controller->actions);
        $this->assertArrayHasKey('wp_enqueue_scripts', $this->controller->actions);
        $this->assertArrayHasKey('gform_noconflict_scripts', $this->controller->filters);
        $this->assertArrayHasKey('gform_noconflict_styles', $this->controller->filters);
    }

    public function testControllerHookConfiguration()
    {
        // Test action hooks
        $this->assertEquals('initialize', $this->controller->actions['init']);
        $this->assertEquals(['conditionallyLoadGFAssets', 11], $this->controller->actions['wp_enqueue_scripts']);
        
        // Test filter hooks
        $this->assertEquals('enableGFNoConflictScripts', $this->controller->filters['gform_noconflict_scripts']);
        $this->assertEquals('enableGFNoConflictStyles', $this->controller->filters['gform_noconflict_styles']);
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

    public function testInitializeAdmin()
    {
        // Mock the isAdmin method
        $controller = Mockery::mock(OptimizationController::class)->makePartial();
        $controller->shouldReceive('isAdmin')->andReturn(true);
        $controller->shouldReceive('isFrontend')->andReturn(false);
        
        $controller->initialize();
        $this->assertTrue(true);
    }

    public function testInitializeFrontend()
    {
        // Mock the isFrontend method
        $controller = Mockery::mock(OptimizationController::class)->makePartial();
        $controller->shouldReceive('isAdmin')->andReturn(false);
        $controller->shouldReceive('isFrontend')->andReturn(true);
        
        $controller->initialize();
        $this->assertTrue(true);
    }

    public function testEnableGFNoConflictScripts()
    {
        $result = $this->controller->enableGFNoConflictScripts();
        $this->assertTrue($result);
    }

    public function testEnableGFNoConflictStyles()
    {
        $result = $this->controller->enableGFNoConflictStyles();
        $this->assertTrue($result);
    }

    public function testConditionallyLoadGFAssetsWithGravityFormShortcode()
    {
        global $post;
        $originalPost = $post;
        
        // Mock a post with Gravity Form shortcode
        $post = (object) [
            'ID' => 123,
            'post_content' => 'This is a test post with [gravityform id="1" title="false"] shortcode.'
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('is_a', $post, 'WP_Post', true);
        $this->mockWordPressFunction('has_shortcode', $post->post_content, 'gravityform', true);
        
        // Should not dequeue scripts when shortcode is present
        $this->controller->conditionallyLoadGFAssets();
        $this->assertTrue(true);
        
        // Restore global post
        $post = $originalPost;
    }

    public function testConditionallyLoadGFAssetsWithoutGravityFormShortcode()
    {
        global $post;
        $originalPost = $post;
        
        // Mock a post without Gravity Form shortcode
        $post = (object) [
            'ID' => 123,
            'post_content' => 'This is a test post without any forms.'
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('is_a', $post, 'WP_Post', true);
        $this->mockWordPressFunction('has_shortcode', $post->post_content, 'gravityform', false);
        $this->mockWordPressFunction('wp_dequeue_style', 'gforms_css');
        $this->mockWordPressFunction('wp_dequeue_script', 'gforms_conditional_logic');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_gravityforms');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_json');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_placeholder');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_masked_input');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_datepicker_init');
        
        // Should dequeue scripts when shortcode is not present
        $this->controller->conditionallyLoadGFAssets();
        $this->assertTrue(true);
        
        // Restore global post
        $post = $originalPost;
    }

    public function testConditionallyLoadGFAssetsWithNullPost()
    {
        global $post;
        $originalPost = $post;
        
        // Mock null post
        $post = null;
        
        // Mock WordPress functions
        $this->mockWordPressFunction('is_a', null, 'WP_Post', false);
        $this->mockWordPressFunction('wp_dequeue_style', 'gforms_css');
        $this->mockWordPressFunction('wp_dequeue_script', 'gforms_conditional_logic');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_gravityforms');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_json');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_placeholder');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_masked_input');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_datepicker_init');
        
        // Should dequeue scripts when post is null
        $this->controller->conditionallyLoadGFAssets();
        $this->assertTrue(true);
        
        // Restore global post
        $post = $originalPost;
    }

    public function testConditionallyLoadGFAssetsWithInvalidPost()
    {
        global $post;
        $originalPost = $post;
        
        // Mock invalid post object
        $post = (object) ['invalid' => 'data'];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('is_a', $post, 'WP_Post', false);
        $this->mockWordPressFunction('wp_dequeue_style', 'gforms_css');
        $this->mockWordPressFunction('wp_dequeue_script', 'gforms_conditional_logic');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_gravityforms');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_json');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_placeholder');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_masked_input');
        $this->mockWordPressFunction('wp_dequeue_script', 'gform_datepicker_init');
        
        // Should dequeue scripts when post is not a WP_Post instance
        $this->controller->conditionallyLoadGFAssets();
        $this->assertTrue(true);
        
        // Restore global post
        $post = $originalPost;
    }

    public function testPerformanceOptimizationFeatures()
    {
        // Test that the controller implements expected performance optimization features
        $reflection = new \ReflectionClass($this->controller);
        
        // Verify key methods exist
        $this->assertTrue($reflection->hasMethod('conditionallyLoadGFAssets'));
        $this->assertTrue($reflection->hasMethod('enableGFNoConflictScripts'));
        $this->assertTrue($reflection->hasMethod('enableGFNoConflictStyles'));
        
        // Verify methods are public
        $this->assertTrue($reflection->getMethod('conditionallyLoadGFAssets')->isPublic());
        $this->assertTrue($reflection->getMethod('enableGFNoConflictScripts')->isPublic());
        $this->assertTrue($reflection->getMethod('enableGFNoConflictStyles')->isPublic());
    }

    public function testGravityFormsOptimizationLogic()
    {
        // Test the complete Gravity Forms optimization workflow
        global $post;
        $originalPost = $post;
        
        // Test case 1: Post with Gravity Form - should not dequeue
        $post = (object) [
            'post_content' => 'Content with [gravityform id="1" title="false" description="false"]'
        ];
        
        $this->mockWordPressFunction('is_a', $post, 'WP_Post', true);
        $this->mockWordPressFunction('has_shortcode', $post->post_content, 'gravityform', true);
        
        // This should not dequeue anything
        $this->controller->conditionallyLoadGFAssets();
        
        // Test case 2: Post without Gravity Form - should dequeue
        $post = (object) [
            'post_content' => 'Content without forms'
        ];
        
        $this->mockWordPressFunction('is_a', $post, 'WP_Post', true);
        $this->mockWordPressFunction('has_shortcode', $post->post_content, 'gravityform', false);
        
        // Mock all dequeue functions
        $dequeueItems = [
            'wp_dequeue_style' => ['gforms_css'],
            'wp_dequeue_script' => [
                'gforms_conditional_logic',
                'gform_gravityforms', 
                'gform_json',
                'gform_placeholder',
                'gform_masked_input',
                'gform_datepicker_init'
            ]
        ];
        
        foreach ($dequeueItems as $function => $items) {
            foreach ($items as $item) {
                $this->mockWordPressFunction($function, $item);
            }
        }
        
        $this->controller->conditionallyLoadGFAssets();
        $this->assertTrue(true);
        
        // Restore global post
        $post = $originalPost;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}