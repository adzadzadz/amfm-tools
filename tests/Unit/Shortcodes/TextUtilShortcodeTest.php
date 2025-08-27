<?php

namespace Tests\Unit\Shortcodes;

use Tests\Helpers\WordPressTestCase;
use App\Shortcodes\TextUtilShortcode;

/**
 * Test suite for TextUtilShortcode
 * 
 * Tests the text utility shortcode functionality including
 * various text transformations and formatting options.
 */
class TextUtilShortcodeTest extends WordPressTestCase
{
    private TextUtilShortcode $shortcode;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcode = new TextUtilShortcode();
        
        // Mock WordPress functions
        \Brain\Monkey\Functions\when('shortcode_atts')->returnArg(1);
        \Brain\Monkey\Functions\when('wp_strip_all_tags')->returnArg(1);
        \Brain\Monkey\Functions\when('esc_html')->returnArg(1);
    }

    public function testShortcodeRegistration(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $tagProperty = $reflection->getProperty('tag');
        $tagProperty->setAccessible(true);
        
        $this->assertEquals('text_util', $tagProperty->getValue($this->shortcode));
    }

    public function testRenderWithUppercaseTransform(): void
    {
        $content = 'hello world';
        $result = $this->shortcode->render(['transform' => 'uppercase'], $content);
        
        $this->assertEquals('HELLO WORLD', $result);
    }

    public function testRenderWithLowercaseTransform(): void
    {
        $content = 'HELLO WORLD';
        $result = $this->shortcode->render(['transform' => 'lowercase'], $content);
        
        $this->assertEquals('hello world', $result);
    }

    public function testRenderWithCapitalizeTransform(): void
    {
        $content = 'hello world test';
        $result = $this->shortcode->render(['transform' => 'capitalize'], $content);
        
        $this->assertEquals('Hello World Test', $result);
    }

    public function testRenderWithTitleCaseTransform(): void
    {
        $content = 'hello world from php';
        $result = $this->shortcode->render(['transform' => 'title'], $content);
        
        $this->assertEquals('Hello World From Php', $result);
    }

    public function testRenderWithSentenceCaseTransform(): void
    {
        $content = 'HELLO WORLD. THIS IS A TEST.';
        $result = $this->shortcode->render(['transform' => 'sentence'], $content);
        
        $this->assertEquals('Hello world. This is a test.', $result);
    }

    public function testRenderWithTrimTransform(): void
    {
        $content = '  hello world  ';
        $result = $this->shortcode->render(['transform' => 'trim'], $content);
        
        $this->assertEquals('hello world', $result);
    }

    public function testRenderWithReverseTransform(): void
    {
        $content = 'hello';
        $result = $this->shortcode->render(['transform' => 'reverse'], $content);
        
        $this->assertEquals('olleh', $result);
    }

    public function testRenderWithCountWordsTransform(): void
    {
        $content = 'hello world test';
        $result = $this->shortcode->render(['transform' => 'count_words'], $content);
        
        $this->assertEquals('3', $result);
    }

    public function testRenderWithCountCharsTransform(): void
    {
        $content = 'hello';
        $result = $this->shortcode->render(['transform' => 'count_chars'], $content);
        
        $this->assertEquals('5', $result);
    }

    public function testRenderWithStripTagsTransform(): void
    {
        $content = '<p>Hello <strong>world</strong></p>';
        
        \Brain\Monkey\Functions\when('wp_strip_all_tags')->with($content)->justReturn('Hello world');
        
        $result = $this->shortcode->render(['transform' => 'strip_tags'], $content);
        
        $this->assertEquals('Hello world', $result);
    }

    public function testRenderWithInvalidTransform(): void
    {
        $content = 'hello world';
        $result = $this->shortcode->render(['transform' => 'invalid_transform'], $content);
        
        // Should return original content for invalid transform
        $this->assertEquals($content, $result);
    }

    public function testRenderWithNoTransform(): void
    {
        $content = 'hello world';
        $result = $this->shortcode->render([], $content);
        
        // Should return original content when no transform specified
        $this->assertEquals($content, $result);
    }

    public function testRenderWithEmptyContent(): void
    {
        $result = $this->shortcode->render(['transform' => 'uppercase'], '');
        
        $this->assertEquals('', $result);
    }

    public function testProcessAttributesWithDefaults(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('processAttributes');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, []);
        
        $this->assertEquals('', $result['transform']);
    }

    public function testProcessAttributesWithCustomTransform(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('processAttributes');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, ['transform' => 'uppercase']);
        
        $this->assertEquals('uppercase', $result['transform']);
    }

    public function testApplyTransformUppercase(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello world', 'uppercase');
        $this->assertEquals('HELLO WORLD', $result);
    }

    public function testApplyTransformLowercase(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'HELLO WORLD', 'lowercase');
        $this->assertEquals('hello world', $result);
    }

    public function testApplyTransformCapitalize(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello world', 'capitalize');
        $this->assertEquals('Hello World', $result);
    }

    public function testApplyTransformSentence(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello world. this is test.', 'sentence');
        $this->assertEquals('Hello world. This is test.', $result);
    }

    public function testApplyTransformTrim(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, '  hello world  ', 'trim');
        $this->assertEquals('hello world', $result);
    }

    public function testApplyTransformReverse(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello', 'reverse');
        $this->assertEquals('olleh', $result);
    }

    public function testApplyTransformCountWords(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello world test', 'count_words');
        $this->assertEquals(3, $result);
    }

    public function testApplyTransformCountChars(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello', 'count_chars');
        $this->assertEquals(5, $result);
    }

    public function testApplyTransformStripTags(): void
    {
        \Brain\Monkey\Functions\when('wp_strip_all_tags')->with('<p>Hello</p>')->justReturn('Hello');
        
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, '<p>Hello</p>', 'strip_tags');
        $this->assertEquals('Hello', $result);
    }

    public function testApplyTransformInvalid(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('applyTransform');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 'hello world', 'invalid');
        $this->assertEquals('hello world', $result);
    }

    public function testMultipleTransformsInSequence(): void
    {
        // Test applying multiple transforms
        $content = '  HELLO WORLD  ';
        
        // First trim, then lowercase
        $result1 = $this->shortcode->render(['transform' => 'trim'], $content);
        $result2 = $this->shortcode->render(['transform' => 'lowercase'], $result1);
        
        $this->assertEquals('hello world', $result2);
    }

    public function testSentenceCaseWithMultipleSentences(): void
    {
        $content = 'FIRST SENTENCE. SECOND SENTENCE! THIRD QUESTION?';
        $result = $this->shortcode->render(['transform' => 'sentence'], $content);
        
        $this->assertEquals('First sentence. Second sentence! Third question?', $result);
    }

    public function testCountWordsWithSpecialCharacters(): void
    {
        $content = 'hello, world! how are you?';
        $result = $this->shortcode->render(['transform' => 'count_words'], $content);
        
        $this->assertEquals('5', $result);
    }

    public function testCountCharsWithSpaces(): void
    {
        $content = 'hello world';
        $result = $this->shortcode->render(['transform' => 'count_chars'], $content);
        
        $this->assertEquals('11', $result); // Including the space
    }
}