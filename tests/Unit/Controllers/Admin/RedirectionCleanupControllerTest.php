<?php

namespace Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\RedirectionCleanupController;
use App\Services\RedirectionCleanupService;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Mockery;

class RedirectionCleanupControllerTest extends TestCase
{
    private RedirectionCleanupController $controller;
    private $serviceMock;
    private $wpdbMock;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();

        // Define constants first
        if (!defined('AMFM_TOOLS_URL')) {
            define('AMFM_TOOLS_URL', 'http://example.com/wp-content/plugins/amfm-tools/');
        }
        if (!defined('AMFM_TOOLS_VERSION')) {
            define('AMFM_TOOLS_VERSION', '1.0.0');
        }

        // Mock global $wpdb
        $this->wpdbMock = Mockery::mock('\wpdb');
        global $wpdb;
        $wpdb = $this->wpdbMock;

        // Mock WordPress functions needed for service constructor
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/uploads'
        ]);
        Functions\when('wp_mkdir_p')->justReturn(true);
        Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Functions\when('wp_generate_uuid4')->justReturn('test-uuid-123');
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('maybe_unserialize')->returnArg(1);

        // Mock basic WordPress functions
        Functions\when('__')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_html_e')->returnArg(1);
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/admin-ajax.php');
        Functions\when('wp_create_nonce')->justReturn('test-nonce');

        // Create controller
        $this->controller = new RedirectionCleanupController();

        // Create service mock and inject
        $this->serviceMock = Mockery::mock(RedirectionCleanupService::class);
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('cleanupService');
        $property->setAccessible(true);
        $property->setValue($this->controller, $this->serviceMock);
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function testActionAdminMenuRegistersSubmenu()
    {
        Functions\expect('add_submenu_page')
            ->once()
            ->with(
                'amfm-tools',
                'Redirection Cleanup',
                'Redirection Cleanup',
                'manage_options',
                'amfm-tools-redirection-cleanup',
                Mockery::type('array')
            );

        $this->controller->actionAdminMenu();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionAdminEnqueueScriptsOnCorrectHook()
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'amfm-redirection-cleanup',
                AMFM_TOOLS_URL . 'assets/js/redirection-cleanup.js',
                ['jquery'],
                AMFM_TOOLS_VERSION,
                true
            );

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with(
                'amfm-redirection-cleanup',
                AMFM_TOOLS_URL . 'assets/css/redirection-cleanup.css',
                [],
                AMFM_TOOLS_VERSION
            );

        Functions\expect('wp_localize_script')
            ->once()
            ->with(
                'amfm-redirection-cleanup',
                'amfmRedirectionCleanup',
                Mockery::type('array')
            );

        $this->controller->actionAdminEnqueueScripts('amfm-tools_page_amfm-tools-redirection-cleanup');
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionAdminEnqueueScriptsSkipsOnWrongHook()
    {
        Functions\expect('wp_enqueue_script')->never();
        Functions\expect('wp_enqueue_style')->never();
        Functions\expect('wp_localize_script')->never();

        $this->controller->actionAdminEnqueueScripts('wrong-hook');
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionWpAjaxAnalyzeContentCallsService()
    {
        $_POST['nonce'] = 'test-nonce';

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $expectedResult = [
            'success' => true,
            'stats' => ['posts' => 10]
        ];

        $this->serviceMock->shouldReceive('analyzeContent')
            ->once()
            ->andReturn($expectedResult);

        Functions\expect('wp_send_json')
            ->once()
            ->with($expectedResult);

        $this->controller->actionWpAjaxAnalyzeContent();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionWpAjaxAnalyzeContentDeniesInsufficientPermissions()
    {
        $_POST['nonce'] = 'test-nonce';

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with('Insufficient permissions')
            ->andThrow(new \Exception('wp_die called'));

        $this->serviceMock->shouldNotReceive('analyzeContent');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wp_die called');

        $this->controller->actionWpAjaxAnalyzeContent();
    }

    public function testActionWpAjaxProcessReplacementsWithOptions()
    {
        $_POST['nonce'] = 'test-nonce';
        $_POST['dry_run'] = 'true';
        $_POST['content_types'] = ['posts', 'postmeta'];

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $expectedOptions = [
            'dry_run' => true,
            'content_types' => ['posts', 'postmeta'],
            'batch_size' => 50
        ];

        $expectedResult = [
            'success' => true,
            'results' => ['posts_updated' => 5]
        ];

        $this->serviceMock->shouldReceive('processReplacements')
            ->once()
            ->with($expectedOptions)
            ->andReturn($expectedResult);

        Functions\expect('wp_send_json')
            ->once()
            ->with($expectedResult);

        $this->controller->actionWpAjaxProcessReplacements();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionWpAjaxProcessReplacementsWithDefaults()
    {
        // Clear POST data from previous test
        unset($_POST['dry_run']);
        unset($_POST['content_types']);
        $_POST['nonce'] = 'test-nonce';

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $expectedOptions = [
            'dry_run' => false, // false because $_POST['dry_run'] is not set
            'content_types' => ['posts', 'postmeta'],
            'batch_size' => 50
        ];

        $expectedResult = [
            'success' => true,
            'results' => ['posts_updated' => 3]
        ];

        $this->serviceMock->shouldReceive('processReplacements')
            ->once()
            ->with($expectedOptions)
            ->andReturn($expectedResult);

        Functions\expect('wp_send_json')
            ->once()
            ->with($expectedResult);

        $this->controller->actionWpAjaxProcessReplacements();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionWpAjaxClearRedirectionData()
    {
        $_POST['nonce'] = 'test-nonce';

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $this->serviceMock->shouldReceive('clearAllData')
            ->once()
            ->andReturn(true);

        Functions\expect('wp_send_json')
            ->once()
            ->with([
                'success' => true,
                'message' => 'All data cleared successfully'
            ]);

        $this->controller->actionWpAjaxClearRedirectionData();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionWpAjaxClearRedirectionDataFailure()
    {
        $_POST['nonce'] = 'test-nonce';

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $this->serviceMock->shouldReceive('clearAllData')
            ->once()
            ->andReturn(false);

        Functions\expect('wp_send_json')
            ->once()
            ->with([
                'success' => false,
                'message' => 'Failed to clear data'
            ]);

        $this->controller->actionWpAjaxClearRedirectionData();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testActionWpAjaxGetCsvStats()
    {
        $_POST['nonce'] = 'test-nonce';

        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $expectedData = [
            'csv_file' => 'test.csv',
            'stats' => ['unique_urls' => 10],
            'mappings_count' => 5
        ];

        $this->serviceMock->shouldReceive('getCurrentData')
            ->once()
            ->andReturn($expectedData);

        Functions\expect('wp_send_json')
            ->once()
            ->with([
                'success' => true,
                'data' => $expectedData
            ]);

        $this->controller->actionWpAjaxGetCsvStats();
        $this->assertTrue(true); // Mock expectations verified
    }

    public function testRenderAdminPageWithNoData()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->serviceMock->shouldReceive('getCurrentData')
            ->once()
            ->andReturn([
                'csv_file' => null,
                'stats' => [],
                'analysis' => [],
                'last_import' => null,
                'last_analysis' => null,
                'mappings_count' => 0
            ]);

        $this->serviceMock->shouldReceive('getRecentJobs')
            ->once()
            ->andReturn([]);

        // Mock the static View::render method
        Mockery::mock('alias:\AdzWP\Core\View')
            ->shouldReceive('render')
            ->andReturn('<div>Test View</div>');

        ob_start();
        $this->controller->renderAdminPage();
        $output = ob_get_clean();

        $this->assertIsString($output);
    }

    public function testRenderAdminPageWithCsvUpload()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES['csv_file'] = [
            'name' => 'test.csv',
            'tmp_name' => '/tmp/test.csv',
            'error' => UPLOAD_ERR_OK
        ];
        $_POST['amfm_csv_nonce'] = 'test-nonce';

        Functions\when('check_admin_referer')->justReturn(true);

        $this->serviceMock->shouldReceive('processUploadedCsv')
            ->once()
            ->with($_FILES['csv_file'])
            ->andReturn([
                'success' => true,
                'message' => 'CSV uploaded successfully'
            ]);

        $this->serviceMock->shouldReceive('getCurrentData')
            ->once()
            ->andReturn([]);

        $this->serviceMock->shouldReceive('getRecentJobs')
            ->once()
            ->andReturn([]);

        Mockery::mock('alias:\AdzWP\Core\View')
            ->shouldReceive('render')
            ->with('admin/redirection-cleanup', Mockery::type('array'), true, 'layouts/main')
            ->andReturnUsing(function($template, $data) {
                return '<div>Test View</div>' . $data['notice'];
            });

        ob_start();
        $this->controller->renderAdminPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice-success', $output);
    }

    public function testRenderAdminPageWithCsvUploadError()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES['csv_file'] = [
            'name' => 'test.txt',
            'tmp_name' => '/tmp/test.txt',
            'error' => UPLOAD_ERR_OK
        ];
        $_POST['amfm_csv_nonce'] = 'test-nonce';

        Functions\when('check_admin_referer')->justReturn(true);

        $this->serviceMock->shouldReceive('processUploadedCsv')
            ->once()
            ->with($_FILES['csv_file'])
            ->andReturn([
                'success' => false,
                'message' => 'Invalid file type'
            ]);

        $this->serviceMock->shouldReceive('getCurrentData')
            ->once()
            ->andReturn([]);

        $this->serviceMock->shouldReceive('getRecentJobs')
            ->once()
            ->andReturn([]);

        Mockery::mock('alias:\AdzWP\Core\View')
            ->shouldReceive('render')
            ->with('admin/redirection-cleanup', Mockery::type('array'), true, 'layouts/main')
            ->andReturnUsing(function($template, $data) {
                return '<div>Test View</div>' . $data['notice'];
            });

        ob_start();
        $this->controller->renderAdminPage();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice-error', $output);
    }
}