<?php

namespace Tests\Unit\Services;

use Tests\Helpers\WordPressTestCase;
use App\Services\ACFService;

/**
 * Test suite for ACFService
 * 
 * Tests ACF field groups and custom post types registration,
 * configuration management, and validation.
 */
class ACFServiceTest extends WordPressTestCase
{
    private ACFService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ACFService();
        
        // Mock WordPress functions
        \Brain\Monkey\Functions\when('post_type_exists')->justReturn(false);
        \Brain\Monkey\Functions\when('register_post_type')->justReturn(true);
        
        // Mock ACF functions
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_local_field_group')->justReturn(true);
        \Brain\Monkey\Functions\when('function_exists')->with('acf_add_local_field_group')->justReturn(true);
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_local_field_groups')->justReturn(true);
        \Brain\Monkey\Functions\when('acf_get_local_field_group')->justReturn(null);
        \Brain\Monkey\Functions\when('acf_add_local_field_group')->justReturn(true);
        \Brain\Monkey\Functions\when('acf_get_local_field_groups')->justReturn([]);
    }

    public function testFieldGroupExistsReturnsFalseWhenAcfNotAvailable(): void
    {
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_local_field_group')->justReturn(false);
        
        $result = $this->service->fieldGroupExists('group_test');
        $this->assertFalse($result);
    }

    public function testFieldGroupExistsReturnsFalseWhenGroupDoesNotExist(): void
    {
        \Brain\Monkey\Functions\when('acf_get_local_field_group')->with('group_test')->justReturn(null);
        
        $result = $this->service->fieldGroupExists('group_test');
        $this->assertFalse($result);
    }

    public function testFieldGroupExistsReturnsTrueWhenGroupExists(): void
    {
        \Brain\Monkey\Functions\when('acf_get_local_field_group')->with('group_test')->justReturn(['key' => 'group_test']);
        
        $result = $this->service->fieldGroupExists('group_test');
        $this->assertTrue($result);
    }

    public function testRegisterFieldGroupsSkipsWhenAcfNotAvailable(): void
    {
        \Brain\Monkey\Functions\when('function_exists')->with('acf_add_local_field_group')->justReturn(false);
        
        // Should not call acf_add_local_field_group
        \Brain\Monkey\Functions\expect('acf_add_local_field_group')->never();
        
        $this->service->registerFieldGroups();
    }

    public function testRegisterFieldGroupsSkipsExistingGroups(): void
    {
        // Mock that field group exists
        \Brain\Monkey\Functions\when('acf_get_local_field_group')->justReturn(['key' => 'group_67edb6e5589ea']);
        
        // Should not call acf_add_local_field_group for existing groups
        \Brain\Monkey\Functions\expect('acf_add_local_field_group')->never();
        
        $this->service->registerFieldGroups();
    }

    public function testRegisterFieldGroupsRegistersNonExistentGroups(): void
    {
        // Mock that field group doesn't exist
        \Brain\Monkey\Functions\when('acf_get_local_field_group')->justReturn(null);
        
        // Should call acf_add_local_field_group for each field group
        \Brain\Monkey\Functions\expect('acf_add_local_field_group')->atLeast()->once();
        
        $this->service->registerFieldGroups();
    }

    public function testRegisterPostTypesSkipsExistingTypes(): void
    {
        \Brain\Monkey\Functions\when('post_type_exists')->with('ceu')->justReturn(true);
        \Brain\Monkey\Functions\when('post_type_exists')->with('staff')->justReturn(true);
        
        // Should not call register_post_type for existing post types
        \Brain\Monkey\Functions\expect('register_post_type')->never();
        
        $this->service->registerPostTypes();
    }

    public function testRegisterPostTypesRegistersNonExistentTypes(): void
    {
        \Brain\Monkey\Functions\when('post_type_exists')->justReturn(false);
        
        // Should call register_post_type for each post type
        \Brain\Monkey\Functions\expect('register_post_type')->times(2);
        
        $this->service->registerPostTypes();
    }

    public function testGetFieldGroupsReturnsConfiguredGroups(): void
    {
        $fieldGroups = $this->service->getFieldGroups();
        
        $this->assertIsArray($fieldGroups);
        $this->assertArrayHasKey('group_67edb6e5589ea', $fieldGroups);
        $this->assertArrayHasKey('group_6785868418204', $fieldGroups);
        $this->assertArrayHasKey('group_675375a800734', $fieldGroups);
        $this->assertArrayHasKey('group_68500b028842d', $fieldGroups);
        
        // Test CEU field group structure
        $ceuGroup = $fieldGroups['group_67edb6e5589ea'];
        $this->assertEquals('CEU', $ceuGroup['title']);
        $this->assertArrayHasKey('fields', $ceuGroup);
        $this->assertIsArray($ceuGroup['fields']);
        $this->assertNotEmpty($ceuGroup['fields']);
    }

    public function testGetPostTypesReturnsConfiguredTypes(): void
    {
        $postTypes = $this->service->getPostTypes();
        
        $this->assertIsArray($postTypes);
        $this->assertArrayHasKey('ceu', $postTypes);
        $this->assertArrayHasKey('staff', $postTypes);
        
        // Test CEU post type structure
        $ceuType = $postTypes['ceu'];
        $this->assertArrayHasKey('labels', $ceuType);
        $this->assertEquals('CEUs', $ceuType['labels']['name']);
        $this->assertTrue($ceuType['public']);
        $this->assertEquals('dashicons-calendar-alt', $ceuType['menu_icon']);
        
        // Test Staff post type structure
        $staffType = $postTypes['staff'];
        $this->assertEquals('Staff', $staffType['labels']['name']);
        $this->assertTrue($staffType['hierarchical']);
        $this->assertEquals('dashicons-groups', $staffType['menu_icon']);
    }

    public function testGetActiveFieldGroupsReturnsEmptyWhenAcfNotAvailable(): void
    {
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_local_field_groups')->justReturn(false);
        
        $result = $this->service->getActiveFieldGroups();
        $this->assertEmpty($result);
    }

    public function testGetActiveFieldGroupsReturnsAcfGroups(): void
    {
        $mockGroups = [
            ['key' => 'group_1', 'title' => 'Group 1'],
            ['key' => 'group_2', 'title' => 'Group 2']
        ];
        
        \Brain\Monkey\Functions\when('acf_get_local_field_groups')->justReturn($mockGroups);
        
        $result = $this->service->getActiveFieldGroups();
        $this->assertEquals($mockGroups, $result);
    }

    public function testExpandFieldGroupConfigAddsDefaults(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('expandFieldGroupConfig');
        $method->setAccessible(true);
        
        $config = [
            'key' => 'group_test',
            'title' => 'Test Group',
            'fields' => []
        ];
        
        $result = $method->invoke($this->service, $config);
        
        $this->assertEquals('group_test', $result['key']);
        $this->assertEquals('Test Group', $result['title']);
        $this->assertEquals(0, $result['menu_order']);
        $this->assertEquals('normal', $result['position']);
        $this->assertEquals('default', $result['style']);
        $this->assertTrue($result['active']);
    }

    public function testExpandFieldConfigAddsDefaults(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('expandFieldConfig');
        $method->setAccessible(true);
        
        $field = [
            'key' => 'field_test',
            'label' => 'Test Field',
            'name' => 'test_field',
            'type' => 'text'
        ];
        
        $result = $method->invoke($this->service, $field);
        
        $this->assertEquals('field_test', $result['key']);
        $this->assertEquals('Test Field', $result['label']);
        $this->assertEquals('test_field', $result['name']);
        $this->assertEquals('text', $result['type']);
        $this->assertEquals(0, $result['required']);
        $this->assertEquals(0, $result['conditional_logic']);
        $this->assertArrayHasKey('wrapper', $result);
        $this->assertIsArray($result['wrapper']);
    }

    public function testCeuFieldGroupConfiguration(): void
    {
        $fieldGroups = $this->service->getFieldGroups();
        $ceuGroup = $fieldGroups['group_67edb6e5589ea'];
        
        $this->assertEquals('CEU', $ceuGroup['title']);
        $this->assertTrue($ceuGroup['active']);
        $this->assertEquals('normal', $ceuGroup['position']);
        
        // Check location rules
        $this->assertArrayHasKey('location', $ceuGroup);
        $this->assertEquals('post_type', $ceuGroup['location'][0][0]['param']);
        $this->assertEquals('ceu', $ceuGroup['location'][0][0]['value']);
        
        // Check required fields exist
        $fieldNames = array_column($ceuGroup['fields'], 'name');
        $this->assertContains('subtitle', $fieldNames);
        $this->assertContains('date', $fieldNames);
        $this->assertContains('time', $fieldNames);
        $this->assertContains('registration_link', $fieldNames);
        $this->assertContains('description', $fieldNames);
        $this->assertContains('learning_objectives', $fieldNames);
        $this->assertContains('author', $fieldNames);
    }

    public function testStaffFieldGroupConfiguration(): void
    {
        $fieldGroups = $this->service->getFieldGroups();
        $staffGroup = $fieldGroups['group_675375a800734'];
        
        $this->assertEquals('Staff', $staffGroup['title']);
        $this->assertEquals('acf_after_title', $staffGroup['position']);
        $this->assertEquals('Staff Data', $staffGroup['description']);
        
        // Check location rules
        $this->assertEquals('staff', $staffGroup['location'][0][0]['value']);
        
        // Check required fields exist
        $fieldNames = array_column($staffGroup['fields'], 'name');
        $this->assertContains('title', $fieldNames);
        $this->assertContains('region', $fieldNames);
        $this->assertContains('description', $fieldNames);
        $this->assertContains('email', $fieldNames);
        $this->assertContains('linkedin_url', $fieldNames);
    }

    public function testSeoFieldGroupConfiguration(): void
    {
        $fieldGroups = $this->service->getFieldGroups();
        $seoGroup = $fieldGroups['group_68500b028842d'];
        
        $this->assertEquals('SEO', $seoGroup['title']);
        $this->assertEquals('side', $seoGroup['position']);
        $this->assertEquals(1, $seoGroup['menu_order']);
        
        // Check multiple location rules (post and page)
        $this->assertCount(2, $seoGroup['location']);
        $this->assertEquals('post', $seoGroup['location'][0][0]['value']);
        $this->assertEquals('page', $seoGroup['location'][1][0]['value']);
        
        // Check SEO fields
        $fieldNames = array_column($seoGroup['fields'], 'name');
        $this->assertContains('amfm_keywords', $fieldNames);
        $this->assertContains('amfm_other_keywords', $fieldNames);
    }

    public function testPostTypeConfiguration(): void
    {
        $postTypes = $this->service->getPostTypes();
        
        // Test CEU post type
        $ceuType = $postTypes['ceu'];
        $this->assertTrue($ceuType['public']);
        $this->assertTrue($ceuType['show_in_rest']);
        $this->assertEquals(4, $ceuType['menu_position']);
        $this->assertContains('title', $ceuType['supports']);
        $this->assertContains('thumbnail', $ceuType['supports']);
        $this->assertEquals('ceu', $ceuType['has_archive']);
        
        // Test Staff post type
        $staffType = $postTypes['staff'];
        $this->assertTrue($staffType['hierarchical']);
        $this->assertEquals(3, $staffType['menu_position']);
        $this->assertEquals('Profile Image', $staffType['labels']['featured_image']);
        $this->assertEquals('staff', $staffType['has_archive']);
    }
}