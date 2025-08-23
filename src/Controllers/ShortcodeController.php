<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ShortcodeController extends Controller
{
    public $actions = [
        'init' => 'registerShortcodes'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Additional initialization if needed
    }

    /**
     * Register all shortcodes
     */
    public function registerShortcodes()
    {
        // Register DKV shortcode
        \add_shortcode('dkv', [$this, 'renderDkvShortcode']);
    }

    /**
     * Render DKV shortcode
     */
    public function renderDkvShortcode($atts = [], $content = null)
    {
        // Default attributes
        $defaults = [
            'pre' => '',
            'post' => '',
            'fallback' => '',
            'other_keywords' => 'false',
            'include' => '',
            'exclude' => '',
            'text' => ''
        ];
        
        // Parse attributes
        $atts = \shortcode_atts($defaults, $atts, 'dkv');
        
        // Sanitize attributes (preserve spaces in pre/post)
        $sanitized_atts = [
            'pre' => \wp_kses_post($atts['pre']), // Preserve spaces
            'post' => \wp_kses_post($atts['post']), // Preserve spaces
            'fallback' => \sanitize_text_field($atts['fallback']),
            'other_keywords' => \sanitize_text_field($atts['other_keywords']),
            'include' => \sanitize_text_field($atts['include']),
            'exclude' => \sanitize_text_field($atts['exclude']),
            'text' => \sanitize_text_field($atts['text'])
        ];
        
        // Determine which keywords to use
        $use_other_keywords = filter_var($sanitized_atts['other_keywords'], FILTER_VALIDATE_BOOLEAN);
        
        // Get a random keyword
        $keyword = $this->getRandomKeyword($use_other_keywords, $sanitized_atts['include'], $sanitized_atts['exclude']);
        
        // Return fallback if no keyword found
        if (empty($keyword)) {
            return $sanitized_atts['fallback'];
        }
        
        // Strip category prefix from keyword for display
        $display_keyword = $this->stripCategoryPrefix($keyword);
        
        // Apply text transformation
        $display_keyword = $this->applyTextTransform($display_keyword, $sanitized_atts['text']);
        
        // Return formatted keyword
        return $sanitized_atts['pre'] . $display_keyword . $sanitized_atts['post'];
    }
    
    /**
     * Get a random keyword based on filters
     */
    private function getRandomKeyword($use_other_keywords = false, $include = '', $exclude = '')
    {
        // Get keywords using hybrid approach (fresh ACF data first, then cookies fallback)
        $keywords = $this->getKeywordsFromSource($use_other_keywords);
        
        // Clean up keywords (remove empty values and trim whitespace)
        $keywords = array_filter(array_map('trim', $keywords));
        
        // Apply global keyword filters
        $keywords = $this->filterExcludedKeywords($keywords);
        
        // Apply category include/exclude filters
        $keywords = $this->filterByCategory($keywords, $include, $exclude);
        
        if (empty($keywords)) {
            return '';
        }
        
        // Return a random keyword
        return $keywords[array_rand($keywords)];
    }
    
    /**
     * Get keywords from ACF or cookies
     */
    private function getKeywordsFromSource($use_other_keywords = false)
    {
        // Try to get fresh data from ACF first (current page)
        $post_id = \get_queried_object_id();
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        if ($post_id && function_exists('get_field')) {
            $field_name = $use_other_keywords ? 'amfm_other_keywords' : 'amfm_keywords';
            $acf_keywords = \get_field($field_name, $post_id);
            
            if (!empty($acf_keywords)) {
                return explode(',', $acf_keywords);
            }
        }
        
        // Fall back to cookies if no ACF data (for non-ACF pages)
        $cookie_name = $use_other_keywords ? 'amfm_other_keywords' : 'amfm_keywords';
        if (isset($_COOKIE[$cookie_name])) {
            $cookie_keywords = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (is_array($cookie_keywords)) {
                return $cookie_keywords;
            }
        }
        
        return [];
    }
    
    /**
     * Filter out excluded keywords
     */
    private function filterExcludedKeywords($keywords)
    {
        // Get excluded keywords from option (includes defaults + custom)
        $excluded_keywords = $this->getExcludedKeywords();
        
        // Convert to lowercase for case-insensitive matching
        $excluded_keywords = array_map('strtolower', $excluded_keywords);
        
        // Filter out excluded keywords
        $filtered_keywords = [];
        foreach ($keywords as $keyword) {
            $keyword_lower = strtolower(trim($keyword));
            if (!in_array($keyword_lower, $excluded_keywords)) {
                $filtered_keywords[] = $keyword;
            }
        }
        
        return $filtered_keywords;
    }
    
    /**
     * Get excluded keywords from option
     */
    private function getExcludedKeywords()
    {
        // Get excluded keywords from option
        $excluded_keywords = \get_option('amfm_excluded_keywords', null);
        
        // If option doesn't exist, initialize with defaults
        if ($excluded_keywords === null) {
            $excluded_keywords = $this->getDefaultExcludedKeywords();
            \update_option('amfm_excluded_keywords', $excluded_keywords);
        }
        
        if (!is_array($excluded_keywords)) {
            $excluded_keywords = [];
        }
        
        return $excluded_keywords;
    }
    
    /**
     * Get default excluded keywords
     */
    private function getDefaultExcludedKeywords()
    {
        return [
            'co-occurring',
            'life adjustment transition',
            'comorbidity',
            'comorbid',
            'co-morbidity',
            'co-morbid'
        ];
    }
    
    /**
     * Filter keywords by category
     */
    private function filterByCategory($keywords, $include = '', $exclude = '')
    {
        // Convert include/exclude to arrays
        $include_categories = !empty($include) ? array_map('trim', explode(',', $include)) : [];
        $exclude_categories = !empty($exclude) ? array_map('trim', explode(',', $exclude)) : [];
        
        $filtered_keywords = [];
        
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            
            // Extract category from keyword (format: "category:keyword")
            $category = $this->extractCategory($keyword);
            
            // Apply include filter
            if (!empty($include_categories)) {
                if (!in_array($category, $include_categories)) {
                    continue; // Skip this keyword
                }
            }
            
            // Apply exclude filter
            if (!empty($exclude_categories)) {
                if (in_array($category, $exclude_categories)) {
                    continue; // Skip this keyword
                }
            }
            
            $filtered_keywords[] = $keyword;
        }
        
        return $filtered_keywords;
    }
    
    /**
     * Extract category from keyword
     */
    private function extractCategory($keyword)
    {
        // Extract category from "category:keyword" format
        if (strpos($keyword, ':') !== false) {
            $parts = explode(':', $keyword, 2);
            return trim($parts[0]);
        }
        return ''; // No category
    }
    
    /**
     * Strip category prefix from keyword
     */
    private function stripCategoryPrefix($keyword)
    {
        // Remove "category:" prefix from keyword for display
        if (strpos($keyword, ':') !== false) {
            $parts = explode(':', $keyword, 2);
            return trim($parts[1]);
        }
        return $keyword; // No prefix to strip
    }
    
    /**
     * Apply text transformation
     */
    private function applyTextTransform($text, $transform)
    {
        switch (strtolower($transform)) {
            case 'lowercase':
                return strtolower($text);
            case 'uppercase':
                return strtoupper($text);
            case 'capitalize':
                return ucwords(strtolower($text));
            default:
                return $text; // No transformation
        }
    }
}