<?php

namespace Tests\Unit\Controllers;

use Tests\Helpers\FrameworkTestCase;
use App\Controllers\TextController;
use Mockery;

/**
 * Unit tests for TextController
 * 
 * Tests text processing functionality, word limiting, and ACF integration
 */
class TextControllerTest extends FrameworkTestCase
{
    protected TextController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TextController();
    }

    public function testControllerInstantiation()
    {
        $this->assertInstanceOf(TextController::class, $this->controller);
        $this->assertArrayHasKey('init', $this->controller->actions);
        $this->assertEquals('initialize', $this->controller->actions['init']);
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

    public function testInitialize()
    {
        // Mock WordPress add_shortcode function
        $this->mockWordPressFunction('add_shortcode', 'limit_words', [$this->controller, 'limitWords']);
        
        // Mock the controller methods
        $controller = Mockery::mock(TextController::class)->makePartial();
        $controller->shouldReceive('isAdmin')->andReturn(false);
        $controller->shouldReceive('isFrontend')->andReturn(true);
        
        $controller->initialize();
        $this->assertTrue(true);
    }

    public function testInitializeAdmin()
    {
        // Mock WordPress add_shortcode function
        $this->mockWordPressFunction('add_shortcode', 'limit_words', [$this->controller, 'limitWords']);
        
        // Mock the controller methods for admin
        $controller = Mockery::mock(TextController::class)->makePartial();
        $controller->shouldReceive('isAdmin')->andReturn(true);
        $controller->shouldReceive('isFrontend')->andReturn(false);
        
        $controller->initialize();
        $this->assertTrue(true);
    }

    public function testLimitWordsWithACFField()
    {
        $atts = [
            'text' => 'my_field_name',
            'words' => 10
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $longText = 'This is a very long text that contains more than ten words and should be truncated by the shortcode functionality.';
        $this->mockWordPressFunction('get_field', 'my_field_name', $longText);
        
        $result = $this->controller->limitWords($atts);
        
        // Should truncate to 10 words plus ellipsis
        $expectedWords = explode(' ', $longText);
        $expectedResult = implode(' ', array_slice($expectedWords, 0, 10)) . '...';
        
        $this->assertEquals($expectedResult, $result);
    }

    public function testLimitWordsWithContent()
    {
        $atts = [
            'words' => 5
        ];
        $content = 'This is a test content with more than five words in it';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        $this->assertEquals('This is a test content...', $result);
    }

    public function testLimitWordsWithShortContent()
    {
        $atts = [
            'words' => 10
        ];
        $content = 'Short content here';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        // Should return content as-is since it's shorter than limit
        $this->assertEquals('Short content here', $result);
    }

    public function testLimitWordsWithEmptyACFField()
    {
        $atts = [
            'text' => 'empty_field',
            'words' => 10
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $this->mockWordPressFunction('get_field', 'empty_field', '');
        
        $result = $this->controller->limitWords($atts);
        
        $this->assertEquals('', $result);
    }

    public function testLimitWordsWithNullACFField()
    {
        $atts = [
            'text' => 'null_field',
            'words' => 10
        ];
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $this->mockWordPressFunction('get_field', 'null_field', null);
        
        $result = $this->controller->limitWords($atts);
        
        $this->assertEquals('', $result);
    }

    public function testLimitWordsWithDefaultAttributes()
    {
        $atts = [];
        $content = str_repeat('word ', 25); // 25 words
        
        // Mock WordPress functions with defaults
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', [
            'text' => '',
            'words' => 20,
        ]);
        
        $result = $this->controller->limitWords($atts, trim($content));
        
        // Should limit to default 20 words
        $expectedWords = str_repeat('word ', 20);
        $this->assertEquals(trim($expectedWords) . '...', $result);
    }

    public function testLimitWordsWithZeroWords()
    {
        $atts = [
            'words' => 0
        ];
        $content = 'Some content here';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        // Should return just ellipsis since 0 words requested
        $this->assertEquals('...', $result);
    }

    public function testLimitWordsWithNegativeWords()
    {
        $atts = [
            'words' => -5
        ];
        $content = 'Some content here';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        // Should return just ellipsis since negative words requested
        $this->assertEquals('...', $result);
    }

    public function testLimitWordsWithSingleWord()
    {
        $atts = [
            'words' => 1
        ];
        $content = 'Single word content here';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        $this->assertEquals('Single...', $result);
    }

    public function testLimitWordsWithExactWordCount()
    {
        $atts = [
            'words' => 4
        ];
        $content = 'Exactly four words here';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        // Should return content as-is since it matches the limit exactly
        $this->assertEquals('Exactly four words here', $result);
    }

    public function testLimitWordsWithSpecialCharacters()
    {
        $atts = [
            'words' => 3
        ];
        $content = 'Content with special-characters and punctuation! More content here.';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        $this->assertEquals('Content with special-characters...', $result);
    }

    public function testLimitWordsWithEmptyContent()
    {
        $atts = [
            'words' => 10
        ];
        $content = '';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        $this->assertEquals('', $result);
    }

    public function testLimitWordsWithWhitespaceContent()
    {
        $atts = [
            'words' => 10
        ];
        $content = '   ';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('shortcode_atts', [
            'text' => '',
            'words' => 20,
        ], $atts, 'limit_words', $atts);
        
        $result = $this->controller->limitWords($atts, $content);
        
        // explode on whitespace should create empty array elements, 
        // which when imploded back should result in empty or whitespace
        $this->assertTrue(empty(trim($result)));
    }

    public function testShortcodeFunctionality()
    {
        // Test that the controller properly handles shortcode functionality
        $reflection = new \ReflectionClass($this->controller);
        
        // Verify the limitWords method exists and is public
        $this->assertTrue($reflection->hasMethod('limitWords'));
        $this->assertTrue($reflection->getMethod('limitWords')->isPublic());
        
        // Verify the method has the correct number of parameters
        $method = $reflection->getMethod('limitWords');
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}