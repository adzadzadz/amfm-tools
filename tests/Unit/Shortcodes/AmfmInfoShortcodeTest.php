<?php

use App\Shortcodes\AmfmInfoShortcode;
use Tests\Helpers\TestCase;

class AmfmInfoShortcodeTest extends TestCase
{
    private $shortcode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcode = new AmfmInfoShortcode();
    }

    public function testShortcodeInstantiation()
    {
        $this->assertInstanceOf(AmfmInfoShortcode::class, $this->shortcode);
    }

    public function testInvalidTypeReturnsError()
    {
        $result = $this->shortcode->render(['type' => 'invalid', 'data' => 'name']);
        $this->assertEquals("Type must be either 'author', 'editor', 'reviewedBy'", $result);
    }

    public function testInvalidDataReturnsError()
    {
        $result = $this->shortcode->render(['type' => 'author', 'data' => 'invalid']);
        $this->assertEquals("Data must be either 'name', 'credentials', 'job_title', 'page_url', 'img'", $result);
    }

    public function testValidAttributesWithoutByline()
    {
        // Without proper WordPress context and byline data, should return "No byline found"
        $result = $this->shortcode->render(['type' => 'author', 'data' => 'name']);
        $this->assertEquals("No byline found", $result);
    }

    public function testDefaultAttributes()
    {
        // Test with default attributes (no parameters)
        $result = $this->shortcode->render([]);
        $this->assertEquals("No byline found", $result);
    }

    public function testFormatBylineDataMethodExists()
    {
        $reflection = new ReflectionClass($this->shortcode);
        $this->assertTrue($reflection->hasMethod('formatBylineData'));
        
        $method = $reflection->getMethod('formatBylineData');
        $this->assertFalse($method->isPublic());
    }
}