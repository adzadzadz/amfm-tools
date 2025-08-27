<?php

namespace Tests\Unit\Services;

use Tests\Helpers\WordPressTestCase;
use App\Services\SettingsService;

/**
 * Test suite for SettingsService
 * 
 * Tests settings management functionality including component settings,
 * Elementor widgets, and excluded keywords.
 */
class SettingsServiceTest extends WordPressTestCase
{
    private SettingsService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingsService();
        
        // Mock WordPress functions
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\when('get_option')->justReturn([]);
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_send_json_success')->justReturn(true);
        \Brain\Monkey\Functions\when('wp_send_json_error')->justReturn(true);
        
        // Mock framework config
        $mockConfig = $this->createMock('\AdzWP\Core\Config');
        $mockConfig->method('set')->willReturn(true);
        
        if (!class_exists('\Adz')) {
            eval('class Adz { public static function config() { return null; } }');
        }
    }

    public function testUpdateComponentSettings(): void
    {
        \Brain\Monkey\Functions\when('update_option')
            ->with('amfm_enabled_components', ['acf_helper', 'import_export'])
            ->justReturn(true);
        
        $result = $this->service->updateComponentSettings(['acf_helper', 'import_export', 'acf_helper']); // Test deduplication
        $this->assertTrue($result);
    }

    public function testUpdateComponentSettingsFails(): void
    {
        \Brain\Monkey\Functions\when('update_option')
            ->with('amfm_enabled_components', ['acf_helper'])
            ->justReturn(false);
        
        $result = $this->service->updateComponentSettings(['acf_helper']);
        $this->assertFalse($result);
    }

    public function testUpdateElementorWidgets(): void
    {
        \Brain\Monkey\Functions\when('update_option')
            ->with('amfm_elementor_enabled_widgets', ['amfm_related_posts'])
            ->justReturn(true);
        
        $result = $this->service->updateElementorWidgets(['amfm_related_posts']);
        $this->assertTrue($result);
    }

    public function testUpdateExcludedKeywords(): void
    {
        $keywordsText = "keyword1\nkeyword2\n\nkeyword3";
        $expectedArray = ['keyword1', 'keyword2', 'keyword3']; // Empty lines filtered out
        
        \Brain\Monkey\Functions\when('update_option')
            ->with('amfm_excluded_keywords', $expectedArray)
            ->justReturn(true);
        
        $result = $this->service->updateExcludedKeywords($keywordsText);
        $this->assertTrue($result);
    }

    public function testUpdateExcludedKeywordsWithDifferentLineEndings(): void
    {
        $keywordsText = "keyword1\r\nkeyword2\rkeyword3";
        $expectedArray = ['keyword1', 'keyword2', 'keyword3'];
        
        \Brain\Monkey\Functions\when('update_option')
            ->with('amfm_excluded_keywords', $expectedArray)
            ->justReturn(true);
        
        $result = $this->service->updateExcludedKeywords($keywordsText);
        $this->assertTrue($result);
    }

    public function testGetEnabledComponentsWithDefaults(): void
    {
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_enabled_components')
            ->justReturn(false); // Option doesn't exist
        
        \Brain\Monkey\Functions\when('update_option')
            ->with('amfm_enabled_components', $this->anything())
            ->justReturn(true);
        
        $result = $this->service->getEnabledComponents();
        
        $this->assertIsArray($result);
        $this->assertContains('acf_helper', $result);
        $this->assertContains('import_export', $result);
    }

    public function testGetEnabledComponentsWithExistingData(): void
    {
        $existingComponents = ['text_utilities', 'optimization'];
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_enabled_components')
            ->justReturn($existingComponents);
        
        $result = $this->service->getEnabledComponents();
        
        // Should include existing components plus core components
        $this->assertContains('text_utilities', $result);
        $this->assertContains('optimization', $result);
        $this->assertContains('acf_helper', $result); // Core component
        $this->assertContains('import_export', $result); // Core component
    }

    public function testGetEnabledElementorWidgets(): void
    {
        $widgets = ['amfm_related_posts'];
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_elementor_enabled_widgets', [])
            ->justReturn($widgets);
        
        $result = $this->service->getEnabledElementorWidgets();
        $this->assertEquals($widgets, $result);
    }

    public function testGetExcludedKeywords(): void
    {
        $keywords = ['keyword1', 'keyword2'];
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_excluded_keywords', [])
            ->justReturn($keywords);
        
        $result = $this->service->getExcludedKeywords();
        $this->assertEquals($keywords, $result);
    }

    public function testGetToggleableComponents(): void
    {
        $result = $this->service->getToggleableComponents();
        
        $this->assertIsArray($result);
        $this->assertNotContains('acf_helper', $result); // Core component should be excluded
        $this->assertNotContains('import_export', $result); // Core component should be excluded
        $this->assertContains('text_utilities', $result);
        $this->assertContains('optimization', $result);
    }

    public function testIsComponentEnabled(): void
    {
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_enabled_components')
            ->justReturn(['acf_helper', 'text_utilities']);
        
        $this->assertTrue($this->service->isComponentEnabled('text_utilities'));
        $this->assertTrue($this->service->isComponentEnabled('acf_helper')); // Core component always enabled
        $this->assertFalse($this->service->isComponentEnabled('optimization'));
    }

    public function testIsElementorWidgetEnabled(): void
    {
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_elementor_enabled_widgets', [])
            ->justReturn(['amfm_related_posts']);
        
        $this->assertTrue($this->service->isElementorWidgetEnabled('amfm_related_posts'));
        $this->assertFalse($this->service->isElementorWidgetEnabled('amfm_other_widget'));
    }

    public function testHandleExcludedKeywordsUpdateWithInvalidNonce(): void
    {
        $_POST['amfm_excluded_keywords_nonce'] = 'invalid';
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(false);
        
        // Should return early without processing
        $this->service->handleExcludedKeywordsUpdate();
        
        // No exception should be thrown - method should return early
        $this->assertTrue(true);
    }

    public function testHandleExcludedKeywordsUpdateSuccess(): void
    {
        $_POST['amfm_excluded_keywords_nonce'] = 'valid';
        $_POST['excluded_keywords'] = 'keyword1\nkeyword2';
        
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        
        // Mock admin notices
        \Brain\Monkey\Functions\when('add_action')->with('admin_notices', $this->anything())->justReturn(true);
        
        $this->service->handleExcludedKeywordsUpdate();
        
        // Should complete without throwing exception
        $this->assertTrue(true);
    }

    public function testHandleDkvConfigUpdate(): void
    {
        $_POST['amfm_dkv_config_nonce'] = 'valid';
        $_POST['dkv_excluded_keywords'] = 'keyword1';
        $_POST['dkv_default_fallback'] = 'Default text';
        $_POST['dkv_cache_duration'] = '48';
        
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\when('add_action')->with('admin_notices', $this->anything())->justReturn(true);
        
        $this->service->handleDkvConfigUpdate();
        
        // Should complete without throwing exception
        $this->assertTrue(true);
    }

    public function testHandleElementorWidgetsUpdate(): void
    {
        $_POST['amfm_elementor_widgets_nonce'] = 'valid';
        $_POST['enabled_widgets'] = ['amfm_related_posts'];
        
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\when('add_action')->with('admin_notices', $this->anything())->justReturn(true);
        
        $this->service->handleElementorWidgetsUpdate();
        
        // Should complete without throwing exception
        $this->assertTrue(true);
    }

    public function testGetDkvDefaultFallback(): void
    {
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_dkv_default_fallback', '')
            ->justReturn('Default fallback text');
        
        $result = $this->service->getDkvDefaultFallback();
        $this->assertEquals('Default fallback text', $result);
    }

    public function testGetDkvCacheDuration(): void
    {
        \Brain\Monkey\Functions\when('get_option')
            ->with('amfm_dkv_cache_duration', 24)
            ->justReturn(48);
        
        $result = $this->service->getDkvCacheDuration();
        $this->assertEquals(48, $result);
    }

    public function testAjaxDkvConfigUpdateWithInvalidNonce(): void
    {
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(false);
        \Brain\Monkey\Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => 'Security check failed.'], 403);
        
        $this->service->ajaxDkvConfigUpdate();
    }

    public function testAjaxDkvConfigUpdateSuccess(): void
    {
        $_POST['dkv_excluded_keywords'] = 'keyword1';
        $_POST['dkv_default_fallback'] = 'Default text';
        $_POST['dkv_cache_duration'] = '24';
        
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with(['message' => 'DKV configuration updated successfully!']);
        
        $this->service->ajaxDkvConfigUpdate();
    }

    public function testAjaxElementorWidgetsUpdateWithInvalidPermissions(): void
    {
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('current_user_can')->with('manage_options')->justReturn(false);
        \Brain\Monkey\Functions\expect('wp_send_json_error')
            ->once()
            ->with('Permission denied');
        
        $this->service->ajaxElementorWidgetsUpdate();
    }

    public function testAjaxElementorWidgetsUpdateSuccess(): void
    {
        $_POST['enabled_widgets'] = ['amfm_related_posts'];
        
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with('Elementor widget settings updated successfully');
        
        $this->service->ajaxElementorWidgetsUpdate();
    }

    public function testAjaxToggleComponentWithInvalidComponent(): void
    {
        $_POST['component'] = '';
        
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\expect('wp_send_json_error')
            ->once()
            ->with('Invalid component');
        
        $this->service->ajaxToggleComponent();
    }

    public function testAjaxToggleElementorWidgetSuccess(): void
    {
        $_POST['widget'] = 'amfm_related_posts';
        $_POST['enabled'] = 'true';
        
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('get_option')->justReturn([]);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with('Widget status updated');
        
        $this->service->ajaxToggleElementorWidget();
    }

    public function testHandleComponentSettingsUpdate(): void
    {
        $_POST['amfm_component_settings_nonce'] = 'valid';
        $_POST['enabled_components'] = ['text_utilities', 'optimization'];
        
        \Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\when('add_action')->with('admin_notices', $this->anything())->justReturn(true);
        
        $this->service->handleComponentSettingsUpdate();
        
        // Should complete without throwing exception
        $this->assertTrue(true);
    }

    public function testAjaxComponentSettingsUpdateSuccess(): void
    {
        $_POST['enabled_components'] = ['text_utilities'];
        
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(true);
        \Brain\Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with('Component settings updated successfully');
        
        $this->service->ajaxComponentSettingsUpdate();
    }

    public function testAjaxComponentSettingsUpdateFailure(): void
    {
        $_POST['enabled_components'] = ['text_utilities'];
        
        \Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        \Brain\Monkey\Functions\when('update_option')->justReturn(false);
        \Brain\Monkey\Functions\expect('wp_send_json_error')
            ->once()
            ->with('Failed to update component settings');
        
        $this->service->ajaxComponentSettingsUpdate();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up $_POST data
        $_POST = [];
    }
}