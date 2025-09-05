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
        
        // Listen for shortcode config changes
        \add_action('amfm_shortcodes_changed', [$this, 'registerShortcodes']);
        
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
     * Register shortcodes based on config
     */
    public function registerShortcodes()
    {
        // Unregister all existing shortcodes first
        $this->unregisterAllShortcodes();

        $config = \Adz::config();

        // Register shortcodes based on config
        if ($config->get('shortcodes.dkv', true)) {
            $this->registerShortcode('dkv', \App\Shortcodes\DkvShortcode::class);
        }

        if ($config->get('shortcodes.limit_words', true)) {
            $this->registerShortcode('limit_words', \App\Shortcodes\LimitWordsShortcode::class);
        }

        if ($config->get('shortcodes.text_util', true)) {
            $this->registerShortcode('text_util', \App\Shortcodes\TextUtilShortcode::class);
        }

        // Bylines shortcodes
        if ($config->get('shortcodes.amfm_info', true)) {
            $this->registerShortcode('amfm_info', \App\Shortcodes\AmfmInfoShortcode::class);
        }

        if ($config->get('shortcodes.amfm_author_url', true)) {
            $this->registerShortcode('amfm_author_url', \App\Shortcodes\AmfmAuthorUrlShortcode::class);
        }

        if ($config->get('shortcodes.amfm_editor_url', true)) {
            $this->registerShortcode('amfm_editor_url', \App\Shortcodes\AmfmEditorUrlShortcode::class);
        }

        if ($config->get('shortcodes.amfm_reviewer_url', true)) {
            $this->registerShortcode('amfm_reviewer_url', \App\Shortcodes\AmfmReviewerUrlShortcode::class);
        }

        if ($config->get('shortcodes.amfm_bylines_grid', true)) {
            $this->registerShortcode('amfm_bylines_grid', \App\Shortcodes\AmfmBylinesGridShortcode::class);
        }

        // ACF-dependent shortcodes
        if ($config->get('shortcodes.amfm_acf', true) && class_exists('ACF')) {
            $this->registerShortcode('amfm_acf', \App\Shortcodes\AmfmAcfShortcode::class);
        }

        if ($config->get('shortcodes.amfm_acf_object', true) && class_exists('ACF')) {
            $this->registerShortcode('amfm_acf_object', \App\Shortcodes\AmfmAcfObjectShortcode::class);
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
                'component' => 'dkv_shortcode',
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
            ],
            'amfm_info' => [
                'name' => 'Byline Info',
                'description' => 'Display author, editor, or reviewer information',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmInfoShortcode::class
            ],
            'amfm_author_url' => [
                'name' => 'Author URL',
                'description' => 'Display author page URL',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmAuthorUrlShortcode::class
            ],
            'amfm_editor_url' => [
                'name' => 'Editor URL',
                'description' => 'Display editor page URL',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmEditorUrlShortcode::class
            ],
            'amfm_reviewer_url' => [
                'name' => 'Reviewer URL',
                'description' => 'Display reviewer page URL',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmReviewerUrlShortcode::class
            ],
            'amfm_bylines_grid' => [
                'name' => 'Bylines Grid',
                'description' => 'Display grid of author, editor, and reviewer information',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmBylinesGridShortcode::class
            ],
            'amfm_acf' => [
                'name' => 'ACF Field',
                'description' => 'Display ACF field values',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmAcfShortcode::class
            ],
            'amfm_acf_object' => [
                'name' => 'ACF Object',
                'description' => 'Display ACF object field properties',
                'component' => 'shortcodes',
                'handler' => \App\Shortcodes\AmfmAcfObjectShortcode::class
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