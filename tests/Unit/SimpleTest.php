<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Simple test to verify PHPUnit setup
 */
class SimpleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertIsString('hello');
    }

    public function testArrayOperations(): void
    {
        $array = ['a', 'b', 'c'];
        $this->assertCount(3, $array);
        $this->assertContains('b', $array);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('\App\Services\CsvExportService'));
        $this->assertTrue(class_exists('\App\Services\CsvImportService'));
        $this->assertTrue(class_exists('\App\Services\SettingsService'));
        $this->assertTrue(class_exists('\App\Services\ACFService'));
    }
}