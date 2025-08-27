<?php

namespace Tests\Unit\Services;

use Tests\Helpers\WordPressTestCase;
use App\Services\CsvImportService;

/**
 * Test suite for CsvImportService
 * 
 * Tests CSV import functionality including validation, batch processing,
 * and various import scenarios.
 */
class CsvImportServiceTest extends WordPressTestCase
{
    private CsvImportService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CsvImportService();
        
        // Mock WordPress functions specific to CSV import
        \Brain\Monkey\Functions\when('wp_check_filetype')->justReturn(['ext' => 'csv']);
        \Brain\Monkey\Functions\when('get_post')->justReturn(null);
        \Brain\Monkey\Functions\when('wp_update_post')->justReturn(1);
        \Brain\Monkey\Functions\when('wp_set_post_terms')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_get_post_terms')->justReturn([]);
        \Brain\Monkey\Functions\when('get_term_by')->justReturn(false);
        \Brain\Monkey\Functions\when('wp_insert_term')->justReturn(['term_id' => 1]);
        \Brain\Monkey\Functions\when('get_taxonomy')->justReturn((object) ['name' => 'category', 'label' => 'Category']);
        \Brain\Monkey\Functions\when('taxonomy_exists')->justReturn(true);
        \Brain\Monkey\Functions\when('get_taxonomies')->justReturn([]);
        \Brain\Monkey\Functions\when('attachment_url_to_postid')->justReturn(0);
        \Brain\Monkey\Functions\when('set_post_thumbnail')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_redirect')->justReturn(true);
        \Brain\Monkey\Functions\when('set_transient')->justReturn(true);
        
        // Mock ACF functions
        \Brain\Monkey\Functions\when('function_exists')->with('acf_get_field_groups')->justReturn(true);
        \Brain\Monkey\Functions\when('function_exists')->with('update_field')->justReturn(true);
        \Brain\Monkey\Functions\when('acf_get_field_groups')->justReturn([]);
        \Brain\Monkey\Functions\when('acf_get_fields')->justReturn([]);
        \Brain\Monkey\Functions\when('get_field')->justReturn('');
        \Brain\Monkey\Functions\when('update_field')->justReturn(true);
        
        // Mock nonce verification
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
    }

    public function testHandleKeywordsUploadWithInvalidNonce(): void
    {
        $_POST['amfm_csv_import_nonce'] = 'invalid';
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(false);
        
        // Should return early without processing
        $this->service->handleKeywordsUpload();
        
        // No exception should be thrown - method should return early
        $this->assertTrue(true);
    }

    public function testHandleKeywordsUploadWithInvalidCapabilities(): void
    {
        $_POST['amfm_csv_import_nonce'] = 'valid';
        \Brain\Monkey\Functions\when('current_user_can')->with('manage_options')->justReturn(false);
        
        // Should return early without processing
        $this->service->handleKeywordsUpload();
        
        // No exception should be thrown - method should return early
        $this->assertTrue(true);
    }

    public function testValidateUploadedFileForAjaxWithInvalidFile(): void
    {
        $_FILES['csv_file'] = ['error' => UPLOAD_ERR_NO_FILE];
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUploadedFileForAjax');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'csv_file');
        $this->assertNull($result);
    }

    public function testValidateUploadedFileForAjaxWithInvalidExtension(): void
    {
        $_FILES['csv_file'] = ['error' => UPLOAD_ERR_OK, 'name' => 'test.txt'];
        \Brain\Monkey\Functions\when('wp_check_filetype')->justReturn(['ext' => 'txt']);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUploadedFileForAjax');
        $method->setAccessible(true);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please upload a valid CSV file.');
        
        $method->invoke($this->service, 'csv_file');
    }

    public function testValidateUploadedFileForAjaxWithValidFile(): void
    {
        $_FILES['csv_file'] = [
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.csv',
            'tmp_name' => '/tmp/test.csv'
        ];
        \Brain\Monkey\Functions\when('wp_check_filetype')->justReturn(['ext' => 'csv']);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateUploadedFileForAjax');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'csv_file');
        $this->assertIsArray($result);
        $this->assertEquals('test.csv', $result['name']);
    }

    public function testGetPostTitleWithInvalidId(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPostTitle');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 0);
        $this->assertEquals('Invalid ID', $result);
    }

    public function testGetPostTitleWithValidId(): void
    {
        $mockPost = (object) ['post_title' => 'Test Post'];
        \Brain\Monkey\Functions\when('get_post')->with(1)->justReturn($mockPost);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPostTitle');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 1);
        $this->assertEquals('Test Post', $result);
    }

    public function testGetPostTitleWithNonExistentPost(): void
    {
        \Brain\Monkey\Functions\when('get_post')->with(999)->justReturn(null);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPostTitle');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 999);
        $this->assertEquals('Post not found', $result);
    }

    public function testProcessUnifiedRowWithInvalidPostId(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('processUnifiedRow');
        $method->setAccessible(true);
        
        $row = ['invalid_id'];
        $columnMap = ['ID' => 0];
        $headers = ['ID'];
        $results = ['success' => 0, 'errors' => 0, 'details' => []];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid post ID');
        
        $method->invoke($this->service, $row, $columnMap, $headers, 1, $results);
    }

    public function testProcessUnifiedRowWithNonExistentPost(): void
    {
        \Brain\Monkey\Functions\when('get_post')->with(999)->justReturn(null);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('processUnifiedRow');
        $method->setAccessible(true);
        
        $row = ['999'];
        $columnMap = ['ID' => 0];
        $headers = ['ID'];
        $results = ['success' => 0, 'errors' => 0, 'details' => []];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Post ID 999 not found');
        
        $method->invoke($this->service, $row, $columnMap, $headers, 1, $results);
    }

    public function testUpdatePostFieldWithPostTitle(): void
    {
        $mockPost = (object) ['ID' => 1, 'post_title' => 'Old Title'];
        \Brain\Monkey\Functions\when('wp_update_post')->with([
            'ID' => 1,
            'post_title' => 'New Title'
        ])->justReturn(1);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updatePostField');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, $mockPost, 'Post Title', 'New Title', $updated);
        
        $this->assertTrue($result);
        $this->assertContains('Title', $updated);
    }

    public function testUpdatePostFieldWithSameValue(): void
    {
        $mockPost = (object) ['ID' => 1, 'post_title' => 'Same Title'];
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updatePostField');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, $mockPost, 'Post Title', 'Same Title', $updated);
        
        $this->assertFalse($result); // Should return false for same value
        $this->assertEmpty($updated);
    }

    public function testUpdatePostTaxonomyWithNewTerms(): void
    {
        \Brain\Monkey\Functions\when('get_term_by')->with('name', 'New Category', 'category')->justReturn(false);
        \Brain\Monkey\Functions\when('wp_insert_term')->with('New Category', 'category')->justReturn(['term_id' => 2]);
        \Brain\Monkey\Functions\when('wp_get_post_terms')->with(1, 'category', ['fields' => 'ids'])->justReturn([]);
        \Brain\Monkey\Functions\when('wp_set_post_terms')->with(1, [2], 'category')->justReturn(true);
        \Brain\Monkey\Functions\when('get_taxonomy')->with('category')->justReturn((object) ['label' => 'Categories']);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updatePostTaxonomy');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, 1, 'category', 'New Category', $updated);
        
        $this->assertTrue($result);
        $this->assertContains('Categories', $updated);
    }

    public function testUpdatePostTaxonomyWithSameTerms(): void
    {
        $mockTerm = (object) ['term_id' => 1];
        \Brain\Monkey\Functions\when('get_term_by')->with('name', 'Existing Category', 'category')->justReturn($mockTerm);
        \Brain\Monkey\Functions\when('wp_get_post_terms')->with(1, 'category', ['fields' => 'ids'])->justReturn([1]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updatePostTaxonomy');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, 1, 'category', 'Existing Category', $updated);
        
        $this->assertFalse($result); // Should return false for same terms
        $this->assertEmpty($updated);
    }

    public function testUpdateAcfFieldWithSameValue(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('test_field', 1)->justReturn('same value');
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateAcfField');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, 1, 'test_field', 'same value', $updated);
        
        $this->assertFalse($result); // Should return false for same value
        $this->assertEmpty($updated);
    }

    public function testUpdateAcfFieldWithNewValue(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('test_field', 1)->justReturn('old value');
        \Brain\Monkey\Functions\when('update_field')->with('test_field', 'new value', 1)->justReturn(true);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateAcfField');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, 1, 'test_field', 'new value', $updated);
        
        $this->assertTrue($result);
        $this->assertContains('ACF: test_field', $updated);
    }

    public function testUpdateAcfFieldWithJsonValue(): void
    {
        \Brain\Monkey\Functions\when('get_field')->with('test_field', 1)->justReturn(['old' => 'data']);
        \Brain\Monkey\Functions\when('update_field')->with('test_field', ['new' => 'data'], 1)->justReturn(true);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateAcfField');
        $method->setAccessible(true);
        
        $updated = [];
        $result = $method->invoke($this->service, 1, 'test_field', '{"new":"data"}', $updated);
        
        $this->assertTrue($result);
        $this->assertContains('ACF: test_field', $updated);
    }

    public function testSetFeaturedImageFromUrlWithInvalidUrl(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('setFeaturedImageFromUrl');
        $method->setAccessible(true);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid image URL');
        
        $method->invoke($this->service, 1, 'not-a-url');
    }

    public function testSetFeaturedImageFromUrlWithValidUrl(): void
    {
        \Brain\Monkey\Functions\when('attachment_url_to_postid')->with('https://example.com/image.jpg')->justReturn(123);
        \Brain\Monkey\Functions\when('set_post_thumbnail')->with(1, 123)->justReturn(true);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('setFeaturedImageFromUrl');
        $method->setAccessible(true);
        
        // Should not throw exception
        $method->invoke($this->service, 1, 'https://example.com/image.jpg');
        $this->assertTrue(true);
    }

    public function testIsAcfFieldWithExistingField(): void
    {
        $mockFieldGroup = ['key' => 'group_123'];
        $mockField = ['name' => 'test_field', 'label' => 'Test Field'];
        
        \Brain\Monkey\Functions\when('acf_get_field_groups')->justReturn([$mockFieldGroup]);
        \Brain\Monkey\Functions\when('acf_get_fields')->with($mockFieldGroup)->justReturn([$mockField]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isAcfField');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'test_field');
        $this->assertTrue($result);
    }

    public function testIsAcfFieldWithNonExistentField(): void
    {
        $mockFieldGroup = ['key' => 'group_123'];
        
        \Brain\Monkey\Functions\when('acf_get_field_groups')->justReturn([$mockFieldGroup]);
        \Brain\Monkey\Functions\when('acf_get_fields')->with($mockFieldGroup)->justReturn([]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isAcfField');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'non_existent_field');
        $this->assertFalse($result);
    }

    public function testGetTaxonomyByLabel(): void
    {
        $mockTaxonomy = (object) ['name' => 'category', 'label' => 'Categories'];
        \Brain\Monkey\Functions\when('get_taxonomies')->with([], 'objects')->justReturn(['category' => $mockTaxonomy]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTaxonomyByLabel');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Categories');
        $this->assertIsObject($result);
        $this->assertEquals('category', $result->name);
    }

    public function testGetTaxonomyByLabelWithNonExistentLabel(): void
    {
        \Brain\Monkey\Functions\when('get_taxonomies')->with([], 'objects')->justReturn([]);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTaxonomyByLabel');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Non Existent');
        $this->assertNull($result);
    }

    public function testValidateKeywordsHeaders(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateKeywordsHeaders');
        $method->setAccessible(true);
        
        // Valid headers
        $this->assertTrue($method->invoke($this->service, ['ID', 'Keywords']));
        
        // Invalid headers - missing ID
        $this->assertFalse($method->invoke($this->service, ['Keywords']));
        
        // Invalid headers - missing Keywords
        $this->assertFalse($method->invoke($this->service, ['ID']));
        
        // Null headers
        $this->assertFalse($method->invoke($this->service, null));
    }

    public function testValidateCategoriesHeaders(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateCategoriesHeaders');
        $method->setAccessible(true);
        
        // Valid headers (case insensitive)
        $this->assertTrue($method->invoke($this->service, ['ID', 'Categories']));
        $this->assertTrue($method->invoke($this->service, ['id', 'categories']));
        
        // Invalid headers - missing ID
        $this->assertFalse($method->invoke($this->service, ['Categories']));
        
        // Invalid headers - missing Categories
        $this->assertFalse($method->invoke($this->service, ['ID']));
        
        // Null headers
        $this->assertFalse($method->invoke($this->service, null));
    }

    public function testVerifyNonce(): void
    {
        $_POST['test_nonce'] = 'valid_nonce';
        \Brain\Monkey\Functions\when('wp_verify_nonce')->with('valid_nonce', 'test_action')->justReturn(true);
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('verifyNonce');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'test_nonce', 'test_action');
        $this->assertTrue($result);
    }

    public function testProcessBatchWithValidData(): void
    {
        $mockPost = (object) ['ID' => 1, 'post_title' => 'Test Post'];
        \Brain\Monkey\Functions\when('get_post')->with(1)->justReturn($mockPost);
        
        $batchData = [
            'headers' => ['ID', 'Post Title'],
            'rows' => [
                [
                    'row_number' => 2,
                    'data' => ['ID' => '1', 'Post Title' => 'Updated Title']
                ]
            ]
        ];
        
        $results = $this->service->processBatch($batchData);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('errors', $results);
        $this->assertArrayHasKey('processed_rows', $results);
    }

    public function testProcessBatchWithInvalidHeaders(): void
    {
        $batchData = [
            'headers' => ['Post Title'], // Missing ID
            'rows' => []
        ];
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid headers - ID column required.');
        
        $this->service->processBatch($batchData);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up $_POST and $_FILES data
        $_POST = [];
        $_FILES = [];
    }
}