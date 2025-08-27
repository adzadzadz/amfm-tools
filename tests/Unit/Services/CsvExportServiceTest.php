<?php

namespace Tests\Unit\Services;

use Tests\Helpers\WordPressTestCase;
use App\Services\CsvExportService;

/**
 * Test suite for CsvExportService
 * 
 * Tests CSV export functionality including validation, data processing,
 * and error handling scenarios.
 */
class CsvExportServiceTest extends WordPressTestCase
{
    private CsvExportService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CsvExportService();
        
        // Mock WordPress functions specific to CSV export
        \Brain\Monkey\Functions\when('post_type_exists')->justReturn(true);
        \Brain\Monkey\Functions\when('get_posts')->justReturn([]);
        \Brain\Monkey\Functions\when('get_object_taxonomies')->justReturn([]);
        \Brain\Monkey\Functions\when('taxonomy_exists')->justReturn(true);
        \Brain\Monkey\Functions\when('get_taxonomy')->justReturn((object) ['name' => 'test_taxonomy', 'label' => 'Test Taxonomy']);
        \Brain\Monkey\Functions\when('wp_get_post_terms')->justReturn([]);
        \Brain\Monkey\Functions\when('get_field')->justReturn('');
        \Brain\Monkey\Functions\when('get_the_post_thumbnail_url')->justReturn('');
        \Brain\Monkey\Functions\when('get_permalink')->justReturn('http://example.com/post');
        \Brain\Monkey\Functions\when('get_userdata')->justReturn((object) ['display_name' => 'Test Author']);
        
        // Mock ACF functions
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_field_groups')->justReturn(true);
        \Brain\Monkey\Functions\when('acf_get_field_groups')->justReturn([]);
        \Brain\Monkey\Functions\when('acf_get_fields')->justReturn([]);
        \Brain\Monkey\Functions\when('acf_get_field_group')->justReturn(null);
    }

    public function testExportDataValidatesPostType(): void
    {
        // Test with empty post type
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please select a post type to export.');
        
        $this->service->exportData([]);
    }

    public function testExportDataValidatesPostTypeExists(): void
    {
        \Brain\Monkey\Functions\when('post_type_exists')->with('invalid_type')->justReturn(false);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid post type selected.');
        
        $this->service->exportData(['export_post_type' => 'invalid_type']);
    }

    public function testExportDataThrowsExceptionWhenNoPostsFound(): void
    {
        \Brain\Monkey\Functions\when('get_posts')->justReturn([]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No posts found for post type: post');
        
        $this->service->exportData(['export_post_type' => 'post']);
    }

    public function testExportDataSuccessWithValidData(): void
    {
        // Mock post data
        $mockPost = (object) [
            'ID' => 1,
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_excerpt' => 'Test excerpt',
            'post_status' => 'publish',
            'post_date' => '2023-01-01 00:00:00',
            'post_modified' => '2023-01-01 00:00:00',
            'post_author' => 1,
            'post_name' => 'test-post',
            'menu_order' => 0,
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_parent' => 0
        ];
        
        \Brain\Monkey\Functions\when('get_posts')->justReturn([$mockPost]);
        
        $result = $this->service->exportData([
            'export_post_type' => 'post',
            'export_options' => ['post_data']
        ]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertStringContains('post-export-', $result['filename']);
    }

    public function testHandleExportWithInvalidNonce(): void
    {
        $_POST['amfm_export'] = '1';
        \Brain\Monkey\Functions\when('check_admin_referer')->justReturn(false);
        
        // Should return early without processing
        $this->service->handleExport();
        
        // No exception should be thrown - method should return early
        $this->assertTrue(true);
    }

    public function testHandleExportWithInvalidCapabilities(): void
    {
        $_POST['amfm_export'] = '1';
        \Brain\Monkey\Functions\when('check_admin_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('current_user_can')->with('manage_options')->justReturn(false);
        
        // Should return early without processing
        $this->service->handleExport();
        
        // No exception should be thrown - method should return early
        $this->assertTrue(true);
    }

    public function testGenerateFilenameFormat(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);
        
        $filename = $method->invoke($this->service, 'post');
        
        $this->assertIsString($filename);
        $this->assertStringStartsWith('post-export-', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    public function testValidatePostTypeWithValidType(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePostType');
        $method->setAccessible(true);
        
        \Brain\Monkey\Functions\when('post_type_exists')->with('post')->justReturn(true);
        
        $result = $method->invoke($this->service, 'post');
        $this->assertEquals('post', $result);
    }

    public function testGetPostsReturnsCorrectData(): void
    {
        $mockPosts = [
            (object) ['ID' => 1, 'post_title' => 'Post 1'],
            (object) ['ID' => 2, 'post_title' => 'Post 2']
        ];
        
        \Brain\Monkey\Functions\when('get_posts')
            ->with([
                'post_type' => 'post',
                'posts_per_page' => -1,
                'post_status' => 'any'
            ])
            ->justReturn($mockPosts);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPosts');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'post');
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->ID);
        $this->assertEquals(2, $result[1]->ID);
    }

    public function testBuildHeadersWithPostColumns(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);
        
        $options = [
            'export_options' => ['post_columns'],
            'post_columns' => ['id', 'title']
        ];
        
        $headers = $method->invoke($this->service, 'post', $options);
        
        $this->assertContains('ID', $headers);
        $this->assertContains('Post Title', $headers);
    }

    public function testBuildHeadersWithTaxonomies(): void
    {
        $mockTaxonomy = (object) ['name' => 'category', 'label' => 'Categories'];
        \Brain\Monkey\Functions\when('get_object_taxonomies')
            ->with('post', 'objects')
            ->justReturn(['category' => $mockTaxonomy]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);
        
        $options = [
            'export_options' => ['taxonomies'],
            'taxonomy_selection' => 'all'
        ];
        
        $headers = $method->invoke($this->service, 'post', $options);
        
        $this->assertContains('ID', $headers); // Always includes ID
        $this->assertContains('Categories', $headers);
    }

    public function testBuildHeadersWithACFFields(): void
    {
        $mockFieldGroup = ['key' => 'group_123'];
        $mockField = ['name' => 'test_field', 'label' => 'Test Field'];
        
        \Brain\Monkey\Functions\when('acf_get_field_groups')->justReturn([$mockFieldGroup]);
        \Brain\Monkey\Functions\when('acf_get_fields')->with($mockFieldGroup)->justReturn([$mockField]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);
        
        $options = [
            'export_options' => ['acf_fields'],
            'acf_selection' => 'all'
        ];
        
        $headers = $method->invoke($this->service, 'post', $options);
        
        $this->assertContains('ID', $headers); // Always includes ID
        $this->assertContains('test_field', $headers);
    }

    public function testBuildPostRowWithBasicData(): void
    {
        $mockPost = (object) [
            'ID' => 1,
            'post_title' => 'Test Post',
            'post_content' => 'Test content'
        ];
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPostRow');
        $method->setAccessible(true);
        
        $options = [
            'export_options' => ['post_columns'],
            'post_columns' => ['id', 'title']
        ];
        
        $row = $method->invoke($this->service, $mockPost, 'post', $options);
        
        $this->assertEquals(1, $row[0]); // ID
        $this->assertEquals('Test Post', $row[1]); // Title
    }

    public function testGetTaxonomiesForExportWithAllSelection(): void
    {
        $mockTaxonomy = (object) ['name' => 'category', 'label' => 'Categories'];
        \Brain\Monkey\Functions\when('get_object_taxonomies')
            ->with('post', 'objects')
            ->justReturn(['category' => $mockTaxonomy]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTaxonomiesForExport');
        $method->setAccessible(true);
        
        $options = ['taxonomy_selection' => 'all'];
        $result = $method->invoke($this->service, 'post', $options);
        
        $this->assertArrayHasKey('category', $result);
        $this->assertEquals('Categories', $result['category']->label);
    }

    public function testGetTaxonomiesForExportWithSpecificSelection(): void
    {
        $mockTaxonomy = (object) ['name' => 'category', 'label' => 'Categories'];
        
        \Brain\Monkey\Functions\when('taxonomy_exists')->with('category')->justReturn(true);
        \Brain\Monkey\Functions\when('get_taxonomy')->with('category')->justReturn($mockTaxonomy);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTaxonomiesForExport');
        $method->setAccessible(true);
        
        $options = [
            'taxonomy_selection' => 'selected',
            'specific_taxonomies' => ['category']
        ];
        
        $result = $method->invoke($this->service, 'post', $options);
        
        $this->assertArrayHasKey('category', $result);
        $this->assertEquals('Categories', $result['category']->label);
    }

    public function testGetACFFieldsForExportWithAllSelection(): void
    {
        $mockFieldGroup = ['key' => 'group_123'];
        $mockField = ['name' => 'test_field', 'label' => 'Test Field'];
        
        \Brain\Monkey\Functions\when('acf_get_field_groups')->justReturn([$mockFieldGroup]);
        \Brain\Monkey\Functions\when('acf_get_fields')->with($mockFieldGroup)->justReturn([$mockField]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAcfFieldsForExport');
        $method->setAccessible(true);
        
        $options = ['acf_selection' => 'all'];
        $result = $method->invoke($this->service, $options);
        
        $this->assertArrayHasKey('test_field', $result);
        $this->assertEquals('Test Field', $result['test_field']);
    }

    public function testGetACFFieldsForExportWithoutACF(): void
    {
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_field_groups')->justReturn(false);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAcfFieldsForExport');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, []);
        
        $this->assertEmpty($result);
    }

    public function testGetPostColumnHeaders(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPostColumnHeaders');
        $method->setAccessible(true);
        
        $columns = ['id', 'post_title', 'post_content'];
        $headers = $method->invoke($this->service, $columns);
        
        $this->assertContains('ID', $headers);
        $this->assertContains('Post Title', $headers);
        $this->assertContains('Post Content', $headers);
    }

    public function testGetPostColumnDataForExport(): void
    {
        $mockPost = (object) [
            'ID' => 1,
            'post_title' => 'Test Post',
            'post_content' => '<p>Test content</p>',
            'post_excerpt' => 'Test excerpt',
            'post_status' => 'publish',
            'post_date' => '2023-01-01 00:00:00',
            'post_author' => 1,
            'post_name' => 'test-post'
        ];
        
        \Brain\Monkey\Functions\when('get_userdata')->with(1)->justReturn((object) ['display_name' => 'Test Author']);
        \Brain\Monkey\Functions\when('get_permalink')->with(1)->justReturn('http://example.com/test-post');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPostColumnDataForExport');
        $method->setAccessible(true);
        
        $columns = ['id', 'post_title', 'post_author', 'url'];
        $data = $method->invoke($this->service, $mockPost, $columns);
        
        $this->assertEquals(1, $data[0]); // ID
        $this->assertEquals('Test Post', $data[1]); // Title
        $this->assertEquals('Test Author', $data[2]); // Author
        $this->assertEquals('http://example.com/test-post', $data[3]); // URL
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up $_POST data
        $_POST = [];
    }
}