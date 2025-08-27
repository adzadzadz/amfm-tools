<?php

namespace Tests\Unit\Shortcodes;

use Tests\Helpers\WordPressTestCase;
use App\Shortcodes\LimitWordsShortcode;

/**
 * Test suite for LimitWordsShortcode
 * 
 * Tests the limit_words shortcode functionality including
 * word limiting, ellipsis handling, and strip tags options.
 */
class LimitWordsShortcodeTest extends WordPressTestCase
{
    private LimitWordsShortcode $shortcode;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcode = new LimitWordsShortcode();
        
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
        
        $this->assertEquals('limit_words', $tagProperty->getValue($this->shortcode));
    }

    public function testRenderWithDefaultLimit(): void
    {
        $content = 'This is a test sentence with more than ten words to test the limiting functionality.';
        $result = $this->shortcode->render([], $content);
        
        // Default limit is 10 words, so should be truncated with ellipsis
        $words = explode(' ', trim(str_replace('...', '', $result)));
        $this->assertLessThanOrEqual(10, count($words));
        $this->assertStringEndsWith('...', $result);
    }

    public function testRenderWithCustomLimit(): void
    {
        $content = 'One two three four five six seven eight nine ten eleven twelve words.';
        $result = $this->shortcode->render(['limit' => '5'], $content);
        
        $words = explode(' ', trim(str_replace('...', '', $result)));
        $this->assertLessThanOrEqual(5, count($words));
        $this->assertStringEndsWith('...', $result);
    }

    public function testRenderWithoutEllipsis(): void
    {
        $content = 'This is a longer text that should be truncated without ellipsis when specified.';
        $result = $this->shortcode->render(['limit' => '5', 'ellipsis' => 'false'], $content);
        
        $words = explode(' ', $result);
        $this->assertEquals(5, count($words));
        $this->assertStringEndsNotWith('...', $result);
    }

    public function testRenderWithCustomEllipsis(): void
    {
        $content = 'This text will be truncated with custom ellipsis.';
        $result = $this->shortcode->render(['limit' => '3', 'ellipsis' => ' [more]'], $content);
        
        $this->assertStringEndsWith(' [more]', $result);
    }

    public function testRenderWithStripTags(): void
    {
        $content = '<p>This is <strong>HTML</strong> content with <em>tags</em>.</p>';
        
        \Brain\Monkey\Functions\when('wp_strip_all_tags')->with($content)->justReturn('This is HTML content with tags.');
        
        $result = $this->shortcode->render(['limit' => '10', 'strip_tags' => 'true'], $content);
        
        // Should not contain HTML tags
        $this->assertStringNotContains('<p>', $result);
        $this->assertStringNotContains('<strong>', $result);
        $this->assertStringNotContains('<em>', $result);
    }

    public function testRenderWithoutStripTags(): void
    {
        $content = '<p>This is <strong>HTML</strong> content.</p>';
        
        $result = $this->shortcode->render(['limit' => '10', 'strip_tags' => 'false'], $content);
        
        // Should preserve HTML tags (assuming no stripping occurs)
        $this->assertStringContains($content, $result);
    }

    public function testRenderWithShortContent(): void
    {
        $content = 'Short text.';
        $result = $this->shortcode->render(['limit' => '10'], $content);
        
        // Should not add ellipsis for content shorter than limit
        $this->assertEquals($content, $result);
        $this->assertStringEndsNotWith('...', $result);
    }

    public function testRenderWithZeroLimit(): void
    {
        $content = 'This content should be completely removed.';
        $result = $this->shortcode->render(['limit' => '0'], $content);
        
        $this->assertEquals('', $result);
    }

    public function testRenderWithNegativeLimit(): void
    {
        $content = 'This content should use default limit.';
        $result = $this->shortcode->render(['limit' => '-5'], $content);
        
        // Negative limit should default to 10
        $words = explode(' ', trim(str_replace('...', '', $result)));
        $this->assertLessThanOrEqual(10, count($words));
    }

    public function testRenderWithInvalidLimit(): void
    {
        $content = 'This content should use default limit with invalid input.';
        $result = $this->shortcode->render(['limit' => 'invalid'], $content);
        
        // Invalid limit should default to 10
        $words = explode(' ', trim(str_replace('...', '', $result)));
        $this->assertLessThanOrEqual(10, count($words));
    }

    public function testRenderWithEmptyContent(): void
    {
        $result = $this->shortcode->render(['limit' => '10'], '');
        
        $this->assertEquals('', $result);
    }

    public function testRenderWithWhitespaceOnlyContent(): void
    {
        $result = $this->shortcode->render(['limit' => '10'], '   ');
        
        $this->assertEquals('', $result);
    }

    public function testProcessAttributesWithDefaults(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('processAttributes');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, []);
        
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals('...', $result['ellipsis']);
        $this->assertTrue($result['strip_tags']);
    }

    public function testProcessAttributesWithCustomValues(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('processAttributes');
        $method->setAccessible(true);
        
        $attrs = [
            'limit' => '20',
            'ellipsis' => ' [read more]',
            'strip_tags' => 'false'
        ];
        
        $result = $method->invoke($this->shortcode, $attrs);
        
        $this->assertEquals(20, $result['limit']);
        $this->assertEquals(' [read more]', $result['ellipsis']);
        $this->assertFalse($result['strip_tags']);
    }

    public function testLimitWordsWithExactLimit(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('limitWords');
        $method->setAccessible(true);
        
        $text = 'One two three four five six seven eight nine ten';
        $result = $method->invoke($this->shortcode, $text, 10, '...');
        
        $this->assertEquals($text, $result); // No ellipsis for exact match
    }

    public function testLimitWordsWithExceedingLimit(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('limitWords');
        $method->setAccessible(true);
        
        $text = 'One two three four five six seven eight nine ten eleven twelve';
        $result = $method->invoke($this->shortcode, $text, 5, '...');
        
        $this->assertEquals('One two three four five...', $result);
    }

    public function testBooleanConversion(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('toBool');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->shortcode, 'true'));
        $this->assertTrue($method->invoke($this->shortcode, 'TRUE'));
        $this->assertTrue($method->invoke($this->shortcode, '1'));
        $this->assertTrue($method->invoke($this->shortcode, 'yes'));
        $this->assertTrue($method->invoke($this->shortcode, 'on'));
        
        $this->assertFalse($method->invoke($this->shortcode, 'false'));
        $this->assertFalse($method->invoke($this->shortcode, 'FALSE'));
        $this->assertFalse($method->invoke($this->shortcode, '0'));
        $this->assertFalse($method->invoke($this->shortcode, 'no'));
        $this->assertFalse($method->invoke($this->shortcode, 'off'));
        $this->assertFalse($method->invoke($this->shortcode, ''));
        $this->assertFalse($method->invoke($this->shortcode, 'invalid'));
    }
}