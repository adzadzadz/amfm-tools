<?php

use App\Shortcodes\AmfmBylinesGridShortcode;
use Tests\Helpers\TestCase;

class AmfmBylinesGridShortcodeTest extends TestCase
{
    private $shortcode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcode = new AmfmBylinesGridShortcode();
    }

    public function testShortcodeInstantiation()
    {
        $this->assertInstanceOf(AmfmBylinesGridShortcode::class, $this->shortcode);
    }

    public function testRenderReturnsString()
    {
        $result = $this->shortcode->render([]);
        $this->assertIsString($result);
    }

    public function testRenderContainsContainerDiv()
    {
        $result = $this->shortcode->render([]);
        $this->assertStringContainsString('amfm-bylines-container', $result);
    }

    public function testRenderGridMethodExists()
    {
        $reflection = new ReflectionClass($this->shortcode);
        $this->assertTrue($reflection->hasMethod('renderGrid'));
        
        $method = $reflection->getMethod('renderGrid');
        $this->assertFalse($method->isPublic());
    }

    public function testRenderColumnMethodExists()
    {
        $reflection = new ReflectionClass($this->shortcode);
        $this->assertTrue($reflection->hasMethod('renderColumn'));
        
        $method = $reflection->getMethod('renderColumn');
        $this->assertFalse($method->isPublic());
    }
}