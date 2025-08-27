<?php

namespace Tests\Unit\Controllers;

use Tests\Helpers\WordPressTestCase;
use App\Controllers\ACFFieldsController;

/**
 * Test suite for ACFFieldsController
 * 
 * Tests ACF field groups and post types registration through the controller.
 */
class ACFFieldsControllerTest extends WordPressTestCase
{
    private ACFFieldsController $controller;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ACFFieldsController();
        
        // Mock WordPress functions
        \Brain\Monkey\Functions\when('did_action')->justReturn(true);
        
        // Mock ACF functions
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_local_field_group')->justReturn(true);
        \Brain\Monkey\Functions\when('function_exists')->with('acf_add_local_field_group')->justReturn(true);
        \Brain\Monkey\Functions\when('acf_get_local_field_group')->justReturn(null);
        \Brain\Monkey\Functions\when('acf_add_local_field_group')->justReturn(true);
        
        // Mock post type functions
        \Brain\Monkey\Functions\when('post_type_exists')->justReturn(false);
        \Brain\Monkey\Functions\when('register_post_type')->justReturn(true);
    }

    public function testControllerInitialization(): void
    {
        $this->assertInstanceOf(ACFFieldsController::class, $this->controller);
        $this->assertArrayHasKey('init', $this->controller->actions);
        $this->assertEquals('registerPostTypes', $this->controller->actions['init']);
        $this->assertArrayHasKey('acf/init', $this->controller->actions);
        $this->assertEquals('registerFieldGroups', $this->controller->actions['acf/init']);
    }

    public function testRegisterPostTypesCallsService(): void
    {
        // Mock the service to verify it's called
        $reflection = new \ReflectionClass($this->controller);
        $serviceProperty = $reflection->getProperty('acfService');
        $serviceProperty->setAccessible(true);
        
        $mockService = $this->createMock(\App\Services\ACFService::class);
        $mockService->expects($this->once())->method('registerPostTypes');
        $serviceProperty->setValue($this->controller, $mockService);
        
        $this->controller->registerPostTypes();
    }

    public function testRegisterFieldGroupsCallsService(): void
    {
        // Mock the service to verify it's called
        $reflection = new \ReflectionClass($this->controller);
        $serviceProperty = $reflection->getProperty('acfService');
        $serviceProperty->setAccessible(true);
        
        $mockService = $this->createMock(\App\Services\ACFService::class);
        $mockService->expects($this->once())->method('registerFieldGroups');
        $serviceProperty->setValue($this->controller, $mockService);
        
        $this->controller->registerFieldGroups();
    }

    public function testBootstrapMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('bootstrap'));
        
        $method = $reflection->getMethod('bootstrap');
        $this->assertTrue($method->isProtected());
        
        // Test that bootstrap can be called without errors
        $method->setAccessible(true);
        $method->invoke($this->controller);
        
        // Should complete without throwing exception
        $this->assertTrue(true);
    }

    public function testControllerHasCorrectActions(): void
    {
        $expectedActions = [
            'init' => 'registerPostTypes',
            'acf/init' => 'registerFieldGroups'
        ];
        
        foreach ($expectedActions as $hook => $method) {
            $this->assertArrayHasKey($hook, $this->controller->actions);
            $this->assertEquals($method, $this->controller->actions[$hook]);
        }
    }

    public function testControllerHasEmptyFilters(): void
    {
        $this->assertArrayHasKey('filters', get_object_vars($this->controller));
        $this->assertEmpty($this->controller->filters);
    }
}