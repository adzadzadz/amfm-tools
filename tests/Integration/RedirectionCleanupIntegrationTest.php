<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\RedirectionCleanupService;
use App\Controllers\Admin\RedirectionCleanupController;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;

class RedirectionCleanupIntegrationTest extends TestCase
{
    private RedirectionCleanupService $service;
    private RedirectionCleanupController $controller;
    private $wpdbMock;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();

        // Define constants
        if (!defined('AMFM_TOOLS_URL')) {
            define('AMFM_TOOLS_URL', 'http://example.com/wp-content/plugins/amfm-tools/');
        }
        if (!defined('AMFM_TOOLS_VERSION')) {
            define('AMFM_TOOLS_VERSION', '1.0.0');
        }

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
        Functions\when('update_option')->justReturn(true);
        Functions\when('maybe_unserialize')->returnArg(1);
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });
        Functions\when('add_submenu_page')->justReturn('hook');
        Functions\when('wp_enqueue_script')->justReturn(true);
        Functions\when('wp_enqueue_style')->justReturn(true);
        Functions\when('wp_localize_script')->justReturn(true);
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/admin-ajax.php');
        Functions\when('wp_create_nonce')->justReturn('test-nonce');
        Functions\when('__')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('wp_send_json')->justReturn(null);
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

        $this->service = new RedirectionCleanupService();
        $this->controller = new RedirectionCleanupController();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test AJAX endpoints integration
     */
    public function testAjaxEndpointsIntegration()
    {
        $_POST['nonce'] = 'test-nonce';

        // Mock service data
        Functions\when('get_option')->alias(function($option, $default = null) {
            if ($option === 'amfm_redirection_cleanup_url_mappings') {
                return [
                    'http://example.com/test' => [
                        'final_url' => 'http://example.com/new-test',
                        'occurrences' => 1
                    ]
                ];
            }
            return $default;
        });

        // Mock database queries
        $this->wpdbMock->shouldReceive('esc_like')->andReturn('http://example.com/test');
        $this->wpdbMock->shouldReceive('prepare')->andReturn('SELECT COUNT query');
        $this->wpdbMock->shouldReceive('get_var')->andReturn(2);

        // Test analyze content endpoint
        $capturedData = null;
        Functions\when('wp_send_json')->alias(function($data) use (&$capturedData) {
            $capturedData = $data;
        });

        $this->controller->actionWpAjaxAnalyzeContent();

        $this->assertNotNull($capturedData);
        $this->assertTrue($capturedData['success']);
        $this->assertArrayHasKey('stats', $capturedData);
    }

    /**
     * Test error handling throughout the workflow
     */
    public function testErrorHandling()
    {
        // Test invalid CSV file
        $file = [
            'error' => UPLOAD_ERR_NO_FILE,
            'name' => '',
            'tmp_name' => ''
        ];

        $result = $this->service->processUploadedCsv($file);
        $this->assertFalse($result['success']);

        // Test analysis with no data
        Functions\when('get_option')->justReturn([]);
        $result = $this->service->analyzeContent();
        $this->assertFalse($result['success']);

        // Test processing with no data
        $result = $this->service->processReplacements();
        $this->assertFalse($result['success']);
    }

    /**
     * Test data persistence and retrieval
     */
    public function testDataPersistence()
    {
        $testData = [
            'csv_file' => 'test.csv',
            'stats' => ['unique_urls' => 5],
            'analysis' => ['posts' => 10],
            'last_import' => '2023-01-01 12:00:00',
            'last_analysis' => '2023-01-01 12:30:00',
            'url_mappings' => ['url1' => [], 'url2' => [], 'url3' => []]
        ];

        Functions\when('get_option')->alias(function($option, $default = null) use ($testData) {
            switch ($option) {
                case 'amfm_redirection_cleanup_current_csv':
                    return $testData['csv_file'];
                case 'amfm_redirection_cleanup_csv_stats':
                    return $testData['stats'];
                case 'amfm_redirection_cleanup_analysis':
                    return $testData['analysis'];
                case 'amfm_redirection_cleanup_last_import':
                    return $testData['last_import'];
                case 'amfm_redirection_cleanup_last_analysis':
                    return $testData['last_analysis'];
                case 'amfm_redirection_cleanup_url_mappings':
                    return $testData['url_mappings'];
                default:
                    return $default;
            }
        });

        Functions\when('count')->alias(function($array) {
            return count($array);
        });

        $currentData = $this->service->getCurrentData();

        $this->assertEquals($testData['csv_file'], $currentData['csv_file']);
        $this->assertEquals($testData['stats'], $currentData['stats']);
        $this->assertEquals($testData['analysis'], $currentData['analysis']);
        $this->assertEquals($testData['last_import'], $currentData['last_import']);
        $this->assertEquals($testData['last_analysis'], $currentData['last_analysis']);
        $this->assertEquals(3, $currentData['mappings_count']);
    }

    /**
     * Test complete service workflow
     */
    public function testCompleteServiceWorkflow()
    {
        // Test with valid mappings
        $mappings = [
            'http://example.com/old-page' => [
                'final_url' => 'http://example.com/new-page',
                'occurrences' => 1
            ],
            'http://example.com/another-old' => [
                'final_url' => 'http://example.com/another-new',
                'occurrences' => 1
            ]
        ];

        Functions\when('get_option')->alias(function($option, $default = null) use ($mappings) {
            if ($option === 'amfm_redirection_cleanup_url_mappings') {
                return $mappings;
            }
            return $default;
        });

        // Test content analysis
        $this->wpdbMock->shouldReceive('esc_like')->andReturnUsing(function($arg) {
            return addcslashes($arg, '%_\\');
        });
        $this->wpdbMock->shouldReceive('prepare')->andReturn('SELECT COUNT query');
        $this->wpdbMock->shouldReceive('get_var')->andReturn(3);

        $analysisResult = $this->service->analyzeContent();

        $this->assertTrue($analysisResult['success']);
        $this->assertArrayHasKey('stats', $analysisResult);
        $this->assertEquals(6, $analysisResult['stats']['posts']); // 3 posts * 2 URLs

        // Test URL replacement processing (dry run)
        $this->wpdbMock->shouldReceive('get_results')->andReturn([
            (object) [
                'ID' => 1,
                'post_content' => 'Check out this link: http://example.com/old-page for more info',
                'post_excerpt' => ''
            ],
            (object) [
                'ID' => 2,
                'post_content' => 'Visit http://example.com/another-old for details',
                'post_excerpt' => ''
            ]
        ]);

        $processingResult = $this->service->processReplacements([
            'dry_run' => true,
            'content_types' => ['posts'],
            'batch_size' => 50
        ]);

        $this->assertTrue($processingResult['success']);
        $this->assertTrue($processingResult['dry_run']);
        $this->assertArrayHasKey('results', $processingResult);
        $this->assertGreaterThan(0, $processingResult['results']['posts_updated']);
    }
}