<?php

namespace Tests\Unit\Shortcodes;

use Tests\Helpers\WordPressTestCase;
use App\Shortcodes\DkvShortcode;

/**
 * Test suite for DkvShortcode
 * 
 * Tests the DKV (Display Key Values) shortcode functionality including
 * attribute processing, keyword filtering, and output generation.
 */
class DkvShortcodeTest extends WordPressTestCase
{
    private DkvShortcode $shortcode;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcode = new DkvShortcode();
        
        // Mock WordPress functions
        \Brain\Monkey\Functions\when('get_field')->justReturn('');
        \Brain\Monkey\Functions\when('get_option')->justReturn([]);
        \Brain\Monkey\Functions\when('get_transient')->justReturn(false);
        \Brain\Monkey\Functions\when('set_transient')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_kses_post')->returnArg(1);
        \Brain\Monkey\Functions\when('esc_html')->returnArg(1);
        \Brain\Monkey\Functions\when('shortcode_atts')->returnArg(1);
    }

    public function testShortcodeRegistration(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $tagProperty = $reflection->getProperty('tag');
        $tagProperty->setAccessible(true);
        
        $this->assertEquals('dkv', $tagProperty->getValue($this->shortcode));
    }

    public function testRenderWithEmptyKeywords(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', null)->justReturn('');
        
        $result = $this->shortcode->render(['fallback' => 'No keywords']);
        $this->assertEquals('No keywords', $result);
    }

    public function testRenderWithKeywords(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', null)->justReturn('keyword1, keyword2, keyword3');
        \Brain\Monkey\Functions\when('get_option')->with('amfm_excluded_keywords', [])->justReturn([]);
        
        $result = $this->shortcode->render([]);
        $this->assertStringContains('keyword1', $result);
        $this->assertStringContains('keyword2', $result);
        $this->assertStringContains('keyword3', $result);
    }

    public function testRenderWithExcludedKeywords(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', null)->justReturn('keyword1, excluded, keyword2');
        \Brain\Monkey\Functions\when('get_option')->with('amfm_excluded_keywords', [])->justReturn(['excluded']);
        
        $result = $this->shortcode->render([]);
        $this->assertStringContains('keyword1', $result);
        $this->assertStringContains('keyword2', $result);
        $this->assertStringNotContains('excluded', $result);
    }

    public function testRenderWithCustomSeparator(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', null)->justReturn('keyword1, keyword2');
        \Brain\Monkey\Functions\when('get_option')->with('amfm_excluded_keywords', [])->justReturn([]);
        
        $result = $this->shortcode->render(['separator' => ' | ']);
        $this->assertStringContains('keyword1 | keyword2', $result);
    }

    public function testRenderWithCustomFallback(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', null)->justReturn('');
        
        $result = $this->shortcode->render(['fallback' => 'Custom fallback text']);
        $this->assertEquals('Custom fallback text', $result);
    }

    public function testRenderWithSpecificPostId(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', 123)->justReturn('post specific keywords');
        \Brain\Monkey\Functions\when('get_option')->with('amfm_excluded_keywords', [])->justReturn([]);
        
        $result = $this->shortcode->render(['post_id' => '123']);
        $this->assertStringContains('post specific keywords', $result);
    }

    public function testProcessAttributesWithDefaults(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('processAttributes');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, []);
        
        $this->assertArrayHasKey('post_id', $result);
        $this->assertArrayHasKey('separator', $result);
        $this->assertArrayHasKey('fallback', $result);
        $this->assertArrayHasKey('cache_duration', $result);
        $this->assertEquals(', ', $result['separator']);
        $this->assertEquals('', $result['fallback']);
    }

    public function testProcessAttributesWithCustomValues(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('processAttributes');
        $method->setAccessible(true);
        
        $attrs = [
            'post_id' => '456',
            'separator' => ' - ',
            'fallback' => 'No data',
            'cache_duration' => '48'
        ];
        
        $result = $method->invoke($this->shortcode, $attrs);
        
        $this->assertEquals(456, $result['post_id']);
        $this->assertEquals(' - ', $result['separator']);
        $this->assertEquals('No data', $result['fallback']);
        $this->assertEquals(48, $result['cache_duration']);
    }

    public function testGetKeywordsFromCache(): void
    {
        \Brain\Monkey\Functions\when('get_transient')->with('dkv_keywords_123')->justReturn('cached keywords');
        
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('getKeywords');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 123, 24);
        $this->assertEquals('cached keywords', $result);
    }

    public function testGetKeywordsFromDatabase(): void
    {
        \Brain\Monkey\Functions\when('get_transient')->justReturn(false);
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', 123)->justReturn('fresh keywords');
        \Brain\Monkey\Functions\when('set_transient')->with('dkv_keywords_123', 'fresh keywords', 86400)->justReturn(true);
        
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('getKeywords');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 123, 24);
        $this->assertEquals('fresh keywords', $result);
    }

    public function testFilterKeywordsWithExclusions(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('filterKeywords');
        $method->setAccessible(true);
        
        $keywords = ['keyword1', 'excluded', 'keyword2', 'another_excluded'];
        $excluded = ['excluded', 'another_excluded'];
        
        $result = $method->invoke($this->shortcode, $keywords, $excluded);
        
        $this->assertContains('keyword1', $result);
        $this->assertContains('keyword2', $result);
        $this->assertNotContains('excluded', $result);
        $this->assertNotContains('another_excluded', $result);
    }

    public function testFilterKeywordsWithEmptyExclusions(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('filterKeywords');
        $method->setAccessible(true);
        
        $keywords = ['keyword1', 'keyword2', 'keyword3'];
        $excluded = [];
        
        $result = $method->invoke($this->shortcode, $keywords, $excluded);
        
        $this->assertEquals($keywords, $result);
    }

    public function testFormatKeywordsOutput(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('formatKeywords');
        $method->setAccessible(true);
        
        $keywords = ['keyword1', 'keyword2', 'keyword3'];
        $separator = ' | ';
        
        $result = $method->invoke($this->shortcode, $keywords, $separator);
        
        $this->assertEquals('keyword1 | keyword2 | keyword3', $result);
    }

    public function testFormatKeywordsWithEmptyArray(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('formatKeywords');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, [], ', ');
        
        $this->assertEquals('', $result);
    }

    public function testGetCacheKey(): void
    {
        $reflection = new \ReflectionClass($this->shortcode);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->shortcode, 123);
        $this->assertEquals('dkv_keywords_123', $result);
    }

    public function testRenderWithCaching(): void
    {
        // First call - should cache the result
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', 123)->justReturn('test keywords');
        \Brain\Monkey\Functions\when('get_transient')->with('dkv_keywords_123')->justReturn(false);
        \Brain\Monkey\Functions\when('get_option')->with('amfm_excluded_keywords', [])->justReturn([]);
        
        \Brain\Monkey\Functions\expect('set_transient')
            ->once()
            ->with('dkv_keywords_123', 'test keywords', $this->anything());
        
        $result = $this->shortcode->render(['post_id' => '123', 'cache_duration' => '1']);
        $this->assertStringContains('test keywords', $result);
    }

    public function testRenderWithInvalidCacheDuration(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('amfm_keywords', null)->justReturn('keywords');
        \Brain\Monkey\Functions\when('get_option')->with('amfm_excluded_keywords', [])->justReturn([]);
        
        // Invalid cache duration should default to 24 hours
        $result = $this->shortcode->render(['cache_duration' => 'invalid']);
        $this->assertStringContains('keywords', $result);
    }
}