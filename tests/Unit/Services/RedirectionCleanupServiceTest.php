<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\RedirectionCleanupService;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Mockery;

class RedirectionCleanupServiceTest extends TestCase
{
    private RedirectionCleanupService $service;
    private $wpdbMock;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();

        // Mock global $wpdb
        $this->wpdbMock = Mockery::mock('\wpdb');
        $this->wpdbMock->posts = 'wp_posts';
        $this->wpdbMock->postmeta = 'wp_postmeta';
        $this->wpdbMock->options = 'wp_options';

        global $wpdb;
        $wpdb = $this->wpdbMock;

        // Mock WordPress functions
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/uploads'
        ]);
        Functions\when('wp_mkdir_p')->justReturn(true);
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('wp_generate_uuid4')->justReturn('test-uuid-123');
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
        Functions\when('maybe_unserialize')->returnArg(1);
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });
        Functions\when('filter_var')->alias(function($value, $filter) {
            if ($filter === FILTER_VALIDATE_URL) {
                return filter_var($value, FILTER_VALIDATE_URL);
            }
            return $value;
        });
        Functions\when('str_ends_with')->alias(function($haystack, $needle) {
            return str_ends_with($haystack, $needle);
        });
        Functions\when('str_contains')->alias(function($haystack, $needle) {
            return str_contains($haystack, $needle);
        });
        Functions\when('move_uploaded_file')->justReturn(true);
        Functions\when('file_exists')->alias(function($file) {
            return file_exists($file);
        });
        Functions\when('glob')->justReturn([]);

        $this->service = new RedirectionCleanupService();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function testProcessUploadedCsvWithInvalidFile()
    {
        $file = [
            'error' => UPLOAD_ERR_NO_FILE,
            'name' => '',
            'tmp_name' => ''
        ];

        $result = $this->service->processUploadedCsv($file);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No file was uploaded', $result['message']);
    }

    public function testProcessUploadedCsvWithInvalidExtension()
    {
        $file = [
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.txt',
            'tmp_name' => '/tmp/test.txt'
        ];

        $result = $this->service->processUploadedCsv($file);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid file type', $result['message']);
    }

    public function testAnalyzeContentWithNoData()
    {
        Functions\when('get_option')->justReturn([]);

        $result = $this->service->analyzeContent();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No CSV data loaded', $result['message']);
    }

    public function testAnalyzeContentWithValidData()
    {
        $mappings = [
            'http://example.com/old' => [
                'final_url' => 'http://example.com/new',
                'occurrences' => 1
            ]
        ];

        Functions\when('get_option')->alias(function($option, $default = null) use ($mappings) {
            if ($option === 'amfm_redirection_cleanup_url_mappings') {
                return $mappings;
            }
            return $default;
        });

        $this->wpdbMock->shouldReceive('esc_like')->andReturn('http://example.com/old');
        $this->wpdbMock->shouldReceive('prepare')->andReturn('SELECT COUNT query');
        $this->wpdbMock->shouldReceive('get_var')->andReturn(5);

        $result = $this->service->analyzeContent();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('stats', $result);
        $this->assertIsArray($result['stats']);
    }

    public function testProcessReplacementsWithNoData()
    {
        Functions\when('get_option')->justReturn([]);

        $result = $this->service->processReplacements();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No CSV data loaded', $result['message']);
    }

    public function testProcessReplacementsWithValidData()
    {
        $mappings = [
            'http://example.com/old' => [
                'final_url' => 'http://example.com/new',
                'occurrences' => 1
            ]
        ];

        Functions\when('get_option')->alias(function($option, $default = null) use ($mappings) {
            if ($option === 'amfm_redirection_cleanup_url_mappings') {
                return $mappings;
            }
            return $default;
        });

        // Mock database queries for processing
        $this->wpdbMock->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdbMock->shouldReceive('get_results')->andReturn([
            (object) [
                'ID' => 1,
                'post_content' => 'Content with http://example.com/old link',
                'post_excerpt' => ''
            ]
        ], [
            (object) [
                'meta_id' => 1,
                'meta_value' => 'Value with http://example.com/old'
            ]
        ]);

        $result = $this->service->processReplacements(['dry_run' => true]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('results', $result);
        $this->assertTrue($result['dry_run']);
    }

    public function testGetCurrentDataReturnsExpectedStructure()
    {
        Functions\when('get_option')->alias(function($option, $default = null) {
            switch ($option) {
                case 'amfm_redirection_cleanup_current_csv':
                    return 'test.csv';
                case 'amfm_redirection_cleanup_csv_stats':
                    return ['unique_urls' => 10];
                case 'amfm_redirection_cleanup_analysis':
                    return ['posts' => 5];
                case 'amfm_redirection_cleanup_last_import':
                    return '2023-01-01 12:00:00';
                case 'amfm_redirection_cleanup_last_analysis':
                    return '2023-01-01 12:30:00';
                case 'amfm_redirection_cleanup_url_mappings':
                    return ['url1' => [], 'url2' => []];
                default:
                    return $default;
            }
        });

        Functions\when('count')->alias(function($array) {
            return count($array);
        });

        $result = $this->service->getCurrentData();

        $this->assertArrayHasKey('csv_file', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('last_import', $result);
        $this->assertArrayHasKey('last_analysis', $result);
        $this->assertArrayHasKey('mappings_count', $result);
        $this->assertEquals('test.csv', $result['csv_file']);
        $this->assertEquals(2, $result['mappings_count']);
    }

    public function testGetRecentJobsReturnsFormattedData()
    {
        $jobData = [
            'timestamp' => '2023-01-01 12:00:00',
            'options' => ['dry_run' => true],
            'results' => ['posts_updated' => 5]
        ];

        Functions\when('maybe_unserialize')->alias(function($data) use ($jobData) {
            return $jobData;
        });

        $this->wpdbMock->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdbMock->shouldReceive('get_results')->andReturn([
            (object) [
                'option_name' => 'amfm_redirection_cleanup_job_123',
                'option_value' => serialize($jobData)
            ]
        ]);

        $result = $this->service->getRecentJobs();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('timestamp', $result[0]);
        $this->assertEquals('123', $result[0]['id']);
    }

    public function testClearAllDataExecutesCleanup()
    {
        $this->wpdbMock->shouldReceive('prepare')->andReturn('SELECT query');
        $this->wpdbMock->shouldReceive('get_results')->andReturn([
            (object) ['option_name' => 'amfm_redirection_cleanup_test']
        ]);

        Functions\when('glob')->justReturn(['/tmp/test1.csv', '/tmp/test2.csv']);

        $result = $this->service->clearAllData();

        $this->assertTrue($result);
    }

    public function testProcessUploadedCsvWithValidFile()
    {
        $file = [
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.csv',
            'tmp_name' => '/tmp/test.csv',
            'size' => 1024
        ];

        // Create a test CSV content
        $csvContent = "Type,Source,Redirected URL,Final URL,Alt Text\n";
        $csvContent .= "Hyperlink,http://example.com/page1,http://example.com/old1,http://example.com/new1,Link 1\n";
        $csvContent .= "Hyperlink,http://example.com/page2,http://example.com/old2,http://example.com/new2,Link 2\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($tempFile, $csvContent);

        Functions\when('move_uploaded_file')->justReturn(true);
        Functions\when('file_exists')->justReturn(true);

        // Mock the file reading functions to read our test file
        Functions\when('fopen')->alias(function($file, $mode) use ($tempFile) {
            return fopen($tempFile, $mode);
        });

        $result = $this->service->processUploadedCsv($file);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('2 unique URL redirections', $result['message']);

        unlink($tempFile);
    }

    public function testProcessUploadedCsvWithMissingColumns()
    {
        $file = [
            'error' => UPLOAD_ERR_OK,
            'name' => 'test.csv',
            'tmp_name' => '/tmp/test.csv',
            'size' => 1024
        ];

        // Create a CSV with missing required columns
        $csvContent = "Type,Source,Invalid Column,Alt Text\n";
        $csvContent .= "Hyperlink,http://example.com/page1,http://example.com/old1,Link 1\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($tempFile, $csvContent);

        Functions\when('move_uploaded_file')->justReturn(true);
        Functions\when('file_exists')->justReturn(true);

        Functions\when('fopen')->alias(function($file, $mode) use ($tempFile) {
            return fopen($tempFile, $mode);
        });

        $result = $this->service->processUploadedCsv($file);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Required columns not found', $result['message']);

        unlink($tempFile);
    }
}