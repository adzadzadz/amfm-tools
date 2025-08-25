<?php

namespace App\Controllers;

use AdzWP\Core\Controller;
use App\Services\SettingsService;

class ShortcodeController extends Controller
{
    public $actions = [
        'init' => 'initializeShortcodes'
    ];

    public $filters = [];

    private $settingsService;
    private $registeredShortcodes = [];

    protected function bootstrap()
    {
        $this->settingsService = new SettingsService();
        
        // Listen for component changes to re-register shortcodes
        \add_action('update_option_amfm_enabled_components', [$this, 'onComponentsChanged'], 10, 2);
        
        // Clean up on plugin deactivation
        \register_deactivation_hook(AMFM_TOOLS_PATH . 'amfm-tools.php', [$this, 'onPluginDeactivation']);
    }

    /**
     * Initialize shortcode controllers based on enabled components
     */
    public function initializeShortcodes()
    {
        $this->registerShortcodes();
    }

    /**
     * Register shortcodes based on enabled components
     */
    public function registerShortcodes()
    {
        // Unregister all existing shortcodes first
        $this->unregisterAllShortcodes();

        // Get enabled components
        $enabledComponents = $this->settingsService->getEnabledComponents();

        // Register shortcodes based on enabled components
        if (in_array('shortcodes', $enabledComponents)) {
            $this->registerShortcode('dkv', \App\Shortcodes\DkvShortcode::class);
        }

        if (in_array('text_utilities', $enabledComponents)) {
            $this->registerShortcode('limit_words', \App\Shortcodes\LimitWordsShortcode::class);
            $this->registerShortcode('text_util', \App\Shortcodes\TextUtilShortcode::class);
        }
    }

    /**
     * Register a single shortcode
     */
    private function registerShortcode(string $tag, string $handlerClass)
    {
        if (class_exists($handlerClass)) {
            $handler = new $handlerClass();
            \add_shortcode($tag, [$handler, 'render']);
            $this->registeredShortcodes[$tag] = $handler;
        }
    }

    /**
     * Unregister all managed shortcodes
     */
    private function unregisterAllShortcodes()
    {
        foreach (array_keys($this->registeredShortcodes) as $tag) {
            \remove_shortcode($tag);
        }
        $this->registeredShortcodes = [];
    }

    /**
     * Handle component settings changes
     */
    public function onComponentsChanged($oldValue, $newValue)
    {
        // Re-register shortcodes when components change
        $this->registerShortcodes();
    }

    /**
     * Get list of all available shortcodes and their requirements
     */
    public function getAvailableShortcodes(): array
    {
        return [
            'dkv' => [
                'name' => 'Dynamic Keyword Value',
                'description' => 'Display random keywords with filtering options',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\DkvShortcode::class
            ],
            'limit_words' => [
                'name' => 'Limit Words',
                'description' => 'Limit text content to specified word count',
                'component' => 'text_utilities',
                'handler' => \App\Shortcodes\LimitWordsShortcode::class
            ],
            'text_util' => [
                'name' => 'Text Utilities',
                'description' => 'Various text transformation utilities',
                'component' => 'text_utilities',
                'handler' => \App\Shortcodes\TextUtilShortcode::class
            ]
        ];
    }

    /**
     * Get currently registered shortcodes
     */
    public function getRegisteredShortcodes(): array
    {
        return array_keys($this->registeredShortcodes);
    }

    /**
     * Check if a specific shortcode is currently registered
     */
    public function isShortcodeRegistered(string $tag): bool
    {
        return isset($this->registeredShortcodes[$tag]);
    }

    /**
     * Handle plugin deactivation - clean up all registered shortcodes
     */
    public function onPluginDeactivation()
    {
        $this->unregisterAllShortcodes();
    }

    /**
     * Get shortcode status for admin display
     */
    public function getShortcodeStatus(): array
    {
        $availableShortcodes = $this->getAvailableShortcodes();
        $enabledComponents = $this->settingsService->getEnabledComponents();
        $status = [];

        foreach ($availableShortcodes as $tag => $info) {
            $componentEnabled = in_array($info['component'], $enabledComponents);
            $shortcodeRegistered = $this->isShortcodeRegistered($tag);
            
            $status[$tag] = [
                'name' => $info['name'],
                'description' => $info['description'],
                'component' => $info['component'],
                'component_enabled' => $componentEnabled,
                'registered' => $shortcodeRegistered,
                'active' => $componentEnabled && $shortcodeRegistered
            ];
        }

        return $status;
    }
}