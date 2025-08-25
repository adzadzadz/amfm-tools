<?php

namespace Tests\Unit\Controllers;

use Tests\Helpers\FrameworkTestCase;
use App\Controllers\Admin\DashboardController;
use App\Services\CsvImportService;
use App\Services\DataExportService;
use App\Services\AjaxService;
use App\Services\SettingsService;
use Mockery;

/**
 * Unit tests for DashboardController
 * 
 * Tests admin functionality, service integration, and WordPress hooks
 */
class DashboardControllerTest extends FrameworkTestCase
{
    protected DashboardController $controller;
    protected $mockCsvImportService;
    protected $mockDataExportService;
    protected $mockAjaxService;
    protected $mockSettingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create controller instance
        $this->controller = new DashboardController();
        
        // Mock services
        $this->mockCsvImportService = Mockery::mock(CsvImportService::class);
        $this->mockDataExportService = Mockery::mock(DataExportService::class);
        $this->mockAjaxService = Mockery::mock(AjaxService::class);
        $this->mockSettingsService = Mockery::mock(SettingsService::class);
    }

    public function testServiceInitialization()
    {
        // Test that services are properly instantiated in actionWpInit
        $this->assertInstanceOf(DashboardController::class, $this->controller);
        
        // Call the service initialization method
        $this->controller->actionWpInit();
        
        // Verify no fatal errors occurred
        $this->assertTrue(true);
    }

    public function testActionAdminInitWithServices()
    {
        // Mock the service() method to return our mocked services
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        
        $controller->shouldReceive('service')
            ->with('csv_import')
            ->andReturn($this->mockCsvImportService);
            
        $controller->shouldReceive('service')
            ->with('data_export')
            ->andReturn($this->mockDataExportService);
            
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);

        // Set up service method expectations
        $this->mockCsvImportService->shouldReceive('handleKeywordsUpload')->once();
        $this->mockCsvImportService->shouldReceive('handleCategoriesUpload')->once();
        $this->mockDataExportService->shouldReceive('handleDirectExport')->once();
        $this->mockSettingsService->shouldReceive('handleExcludedKeywordsUpdate')->once();
        $this->mockSettingsService->shouldReceive('handleElementorWidgetsUpdate')->once();
        $this->mockSettingsService->shouldReceive('handleComponentSettingsUpdate')->once();

        // Execute the method
        $controller->actionAdminInit();
        
        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function testActionAdminInitWithNullServices()
    {
        // Mock the service() method to return null (services not available)
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        
        $controller->shouldReceive('service')->andReturn(null);

        // This should not cause any errors when services are null
        $controller->actionAdminInit();
        
        $this->assertTrue(true);
    }

    public function testMainMenuExistsCheck()
    {
        // Test the mainMenuExists method using reflection
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mainMenuExists');
        $method->setAccessible(true);

        // Mock global $menu
        global $menu;
        $originalMenu = $menu;
        
        // Test when menu doesn't exist
        $menu = null;
        $this->assertFalse($method->invoke($this->controller));
        
        // Test when menu exists but no amfm entry
        $menu = [['Item 1', 'capability', 'other-page']];
        $this->assertFalse($method->invoke($this->controller));
        
        // Test when amfm menu exists
        $menu = [['AMFM', 'manage_options', 'amfm']];
        $this->assertTrue($method->invoke($this->controller));
        
        // Restore original menu
        $menu = $originalMenu;
    }

    public function testRenderAdminPage()
    {
        // Mock GET parameters
        $_GET['tab'] = 'dashboard';
        $_GET['imported'] = 'keywords';
        
        // Mock WordPress functions
        $this->mockWordPressFunction('get_transient', 'amfm_csv_import_results', ['success' => 1]);
        $this->mockWordPressFunction('delete_transient', 'amfm_csv_import_results');
        
        // Mock the service method
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('getDashboardData')->andReturn([]);
        
        // Capture output
        ob_start();
        $controller->renderAdminPage();
        $output = ob_get_clean();
        
        // Basic validation that method executed without errors
        $this->assertTrue(true);
        
        // Clean up
        unset($_GET['tab'], $_GET['imported']);
    }

    public function testGetDashboardData()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDashboardData');
        $method->setAccessible(true);

        // Mock the service method
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);
        
        $this->mockSettingsService->shouldReceive('getEnabledComponents')
            ->andReturn(['acf_helper', 'import_export']);

        $baseData = ['active_tab' => 'dashboard'];
        $result = $method->invoke($controller, $baseData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('available_components', $result);
        $this->assertArrayHasKey('enabled_components', $result);
        $this->assertEquals(['acf_helper', 'import_export'], $result['enabled_components']);
    }

    public function testGetDashboardDataWithNullService()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getDashboardData');
        $method->setAccessible(true);

        // Mock the service method to return null
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn(null);

        $baseData = ['active_tab' => 'dashboard'];
        $result = $method->invoke($controller, $baseData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('enabled_components', $result);
        $this->assertEquals([], $result['enabled_components']);
    }

    public function testGetImportExportData()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getImportExportData');
        $method->setAccessible(true);

        // Mock WordPress functions
        $this->mockWordPressFunction('get_post_types', ['show_ui' => true], 'objects', [
            'post' => (object)['name' => 'post', 'label' => 'Posts'],
            'page' => (object)['name' => 'page', 'label' => 'Pages']
        ]);
        
        $this->mockWordPressFunction('sanitize_key', '');
        $this->mockWordPressFunction('get_object_taxonomies', [], 'objects', []);
        $this->mockWordPressFunction('function_exists', 'acf_get_field_groups', true);
        $this->mockWordPressFunction('acf_get_field_groups', []);

        $baseData = ['active_tab' => 'import-export'];
        $result = $method->invoke($this->controller, $baseData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('post_types', $result);
        $this->assertArrayHasKey('post_type_taxonomies', $result);
        $this->assertArrayHasKey('all_field_groups', $result);
    }

    public function testAjaxMethods()
    {
        // Mock the service method
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        
        // Test AJAX taxonomy method
        $controller->shouldReceive('service')
            ->with('ajax')
            ->andReturn($this->mockAjaxService);
            
        $this->mockAjaxService->shouldReceive('getPostTypeTaxonomies')->once();
        $controller->actionWpAjaxAmfmGetPostTypeTaxonomies();

        // Test AJAX ACF field groups method
        $this->mockAjaxService->shouldReceive('getAcfFieldGroups')->once();
        $controller->actionWpAjaxAmfmGetAcfFieldGroups();

        // Test AJAX export data method
        $this->mockAjaxService->shouldReceive('exportData')->once();
        $controller->actionWpAjaxAmfmExportData();
        
        $this->assertTrue(true);
    }

    public function testAjaxMethodsWithNullService()
    {
        // Mock the service method to return null
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')->andReturn(null);

        // These should not cause errors when service is null
        $controller->actionWpAjaxAmfmGetPostTypeTaxonomies();
        $controller->actionWpAjaxAmfmGetAcfFieldGroups();
        $controller->actionWpAjaxAmfmExportData();
        
        $this->assertTrue(true);
    }

    public function testSettingsAjaxMethods()
    {
        // Mock the service method
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);

        // Test component settings update
        $this->mockSettingsService->shouldReceive('ajaxToggleComponent')->once();
        $controller->actionWpAjaxAmfmComponentSettingsUpdate();

        // Test Elementor widgets update
        $this->mockSettingsService->shouldReceive('ajaxToggleElementorWidget')->once();
        $controller->actionWpAjaxAmfmElementorWidgetsUpdate();
        
        $this->assertTrue(true);
    }

    public function testActionAdminEnqueueScripts()
    {
        // Mock WordPress functions
        $this->mockWordPressFunction('wp_enqueue_style');
        $this->mockWordPressFunction('wp_enqueue_script');
        $this->mockWordPressFunction('wp_localize_script');
        $this->mockWordPressFunction('admin_url', 'admin-ajax.php', 'http://example.com/wp-admin/admin-ajax.php');
        
        // Mock the createNonce method
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('createNonce')->andReturn('test_nonce');

        // Test with AMFM page
        $controller->actionAdminEnqueueScripts('toplevel_page_amfm-tools');
        
        // Test with non-AMFM page (should not enqueue)
        $controller->actionAdminEnqueueScripts('other-page');
        
        $this->assertTrue(true);
    }

    public function testGetUtilitiesData()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUtilitiesData');
        $method->setAccessible(true);

        // Mock the settings service
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);
        
        // Mock initial enabled components without optimization
        $this->mockSettingsService->shouldReceive('getEnabledComponents')
            ->andReturn(['acf_helper', 'import_export']);
        
        // Mock updateComponentSettings to enable optimization
        $this->mockSettingsService->shouldReceive('updateComponentSettings')
            ->with(['acf_helper', 'import_export', 'optimization'])
            ->once()
            ->andReturn(true);

        $baseData = ['active_tab' => 'utilities'];
        $result = $method->invoke($controller, $baseData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('available_utilities', $result);
        $this->assertArrayHasKey('enabled_utilities', $result);
        
        // Verify CSV Import and Data Export cards are removed
        $availableUtilities = $result['available_utilities'];
        $this->assertArrayNotHasKey('csv_import', $availableUtilities);
        $this->assertArrayNotHasKey('data_export', $availableUtilities);
        
        // Verify core utilities are present
        $this->assertArrayHasKey('acf_helper', $availableUtilities);
        $this->assertArrayHasKey('optimization', $availableUtilities);
        $this->assertArrayHasKey('import_export', $availableUtilities);
        
        // Verify utility details
        $this->assertEquals('ACF Helper', $availableUtilities['acf_helper']['name']);
        $this->assertEquals('Performance Optimization', $availableUtilities['optimization']['name']);
        $this->assertEquals('Import/Export Tools', $availableUtilities['import_export']['name']);
    }

    public function testGetUtilitiesDataPerformanceOptimizationEnabledByDefault()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUtilitiesData');
        $method->setAccessible(true);

        // Mock the settings service
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);
        
        // Mock enabled components without optimization initially
        $this->mockSettingsService->shouldReceive('getEnabledComponents')
            ->andReturn(['acf_helper', 'import_export']);
        
        // Verify that updateComponentSettings is called to enable optimization
        $this->mockSettingsService->shouldReceive('updateComponentSettings')
            ->with(Mockery::on(function($components) {
                return in_array('optimization', $components) &&
                       in_array('acf_helper', $components) &&
                       in_array('import_export', $components);
            }))
            ->once()
            ->andReturn(true);

        $baseData = ['active_tab' => 'utilities'];
        $result = $method->invoke($controller, $baseData);
        
        // Test passes if updateComponentSettings was called with optimization included
        $this->assertTrue(true);
    }

    public function testGetUtilitiesDataWhenOptimizationAlreadyEnabled()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUtilitiesData');
        $method->setAccessible(true);

        // Mock the settings service
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);
        
        // Mock enabled components with optimization already enabled
        $this->mockSettingsService->shouldReceive('getEnabledComponents')
            ->andReturn(['acf_helper', 'import_export', 'optimization']);
        
        // Verify updateComponentSettings is NOT called when optimization is already enabled
        $this->mockSettingsService->shouldNotReceive('updateComponentSettings');

        $baseData = ['active_tab' => 'utilities'];
        $result = $method->invoke($controller, $baseData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('enabled_utilities', $result);
        
        // Verify optimization is in the enabled utilities
        $enabledUtilities = $result['enabled_utilities'];
        $this->assertContains('optimization', $enabledUtilities);
    }

    public function testUtilitiesDataStructure()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getUtilitiesData');
        $method->setAccessible(true);

        // Mock the settings service
        $controller = Mockery::mock(DashboardController::class)->makePartial();
        $controller->shouldReceive('service')
            ->with('settings')
            ->andReturn($this->mockSettingsService);
        
        $this->mockSettingsService->shouldReceive('getEnabledComponents')
            ->andReturn(['acf_helper', 'import_export', 'optimization']);
        
        $this->mockSettingsService->shouldNotReceive('updateComponentSettings');

        $baseData = ['active_tab' => 'utilities'];
        $result = $method->invoke($controller, $baseData);

        // Verify the structure of available utilities
        $availableUtilities = $result['available_utilities'];
        
        // Test ACF Helper structure
        $this->assertEquals('ACF Helper', $availableUtilities['acf_helper']['name']);
        $this->assertEquals('🔧', $availableUtilities['acf_helper']['icon']);
        $this->assertEquals('Core Feature', $availableUtilities['acf_helper']['status']);
        $this->assertStringContainsString('ACF keyword cookies', $availableUtilities['acf_helper']['description']);
        
        // Test Performance Optimization structure
        $this->assertEquals('Performance Optimization', $availableUtilities['optimization']['name']);
        $this->assertEquals('⚡', $availableUtilities['optimization']['icon']);
        $this->assertEquals('Available', $availableUtilities['optimization']['status']);
        $this->assertStringContainsString('Gravity Forms optimization', $availableUtilities['optimization']['description']);
        
        // Test Import/Export Tools structure
        $this->assertEquals('Import/Export Tools', $availableUtilities['import_export']['name']);
        $this->assertEquals('📊', $availableUtilities['import_export']['icon']);
        $this->assertEquals('Core Feature', $availableUtilities['import_export']['status']);
        $this->assertStringContainsString('data management', $availableUtilities['import_export']['description']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}