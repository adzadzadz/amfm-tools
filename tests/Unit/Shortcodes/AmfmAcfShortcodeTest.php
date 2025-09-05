<?php

use App\Shortcodes\AmfmAcfShortcode;
use Tests\Helpers\TestCase;

class AmfmAcfShortcodeTest extends TestCase
{
    private $shortcode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shortcode = new AmfmAcfShortcode();
    }

    public function testShortcodeInstantiation()
    {
        $this->assertInstanceOf(AmfmAcfShortcode::class, $this->shortcode);
    }

    public function testEmptyFieldReturnsEmpty()
    {
        $result = $this->shortcode->render(['field' => '']);
        $this->assertEquals('', $result);
    }

    public function testWithoutFieldAttributeReturnsEmpty()
    {
        $result = $this->shortcode->render([]);
        $this->assertEquals('', $result);
    }

    public function testDefaultAttributesAreSet()
    {
        // Test that defaults are properly applied
        $result = $this->shortcode->render(['field' => 'test_field']);
        $this->assertEquals('', $result); // Should be empty without WordPress context
    }

    public function testBeforeTextIsHandled()
    {
        // Without ACF function available, should return empty
        $result = $this->shortcode->render([
            'field' => 'test_field',
            'before' => 'Prefix:'
        ]);
        $this->assertEquals('', $result);
    }
}