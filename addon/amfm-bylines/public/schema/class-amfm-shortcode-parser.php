<?php

/**
 * The shortcode parser class.
 *
 * Handles parsing of shortcodes in custom schema JSON.
 *
 * @link       https://adzjo.online/adz
 * @since      3.1.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public/schema
 */

class Amfm_Shortcode_Parser {

    /**
     * The plugin name.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $plugin_name    The plugin name.
     */
    private $plugin_name;

    /**
     * The plugin version.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $version    The plugin version.
     */
    private $version;

    /**
     * The location handler.
     *
     * @since    3.1.0
     * @access   private
     * @var      Amfm_Location_Handler    $location_handler    The location handler.
     */
    private $location_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.1.0
     * @param    string                   $plugin_name        The plugin name.
     * @param    string                   $version            The plugin version.
     * @param    Amfm_Location_Handler    $location_handler   The location handler.
     */
    public function __construct($plugin_name, $version, $location_handler) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->location_handler = $location_handler;
    }

    /**
     * Parse shortcodes in schema JSON.
     *
     * @since    3.1.0
     * @param    string    $schema_json    The schema JSON with shortcodes.
     * @return   mixed                     The parsed schema array or false on error.
     */
    public function parse($schema_json) {
        if (empty($schema_json)) {
            return false;
        }

        $parsed_json = $this->parse_shortcodes($schema_json);
        
        $schema = json_decode($parsed_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AMFM Schema Parser: JSON decode error - ' . json_last_error_msg());
            error_log('AMFM Schema Parser: Original JSON (first 500): ' . substr($schema_json, 0, 500));
            error_log('AMFM Schema Parser: Parsed JSON (first 500): ' . substr($parsed_json, 0, 500));
            
            // Try to validate what went wrong
            $test_decode = json_decode($parsed_json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('AMFM Schema Parser: Still invalid after parsing: ' . json_last_error_msg());
            }
            
            return false;
        }
        
        return $schema;
    }

    /**
     * Parse shortcodes in schema JSON and return as JSON string.
     *
     * @since    3.1.0
     * @param    string    $schema_json    The schema JSON with shortcodes.
     * @return   string                    The parsed schema JSON string or empty string on error.
     */
    public function parse_to_json($schema_json) {
        if (empty($schema_json)) {
            return '';
        }

        return $this->parse_shortcodes($schema_json);
    }

    /**
     * Parse shortcodes in the JSON string.
     *
     * @since    3.1.0
     * @param    string    $json_string    The JSON string with shortcodes.
     * @return   string                    The JSON string with shortcodes replaced.
     */
    private function parse_shortcodes($json_string) {
        // Always try to fix JSON syntax first (adds missing commas around shortcodes)
        $fixed_json = $this->fix_json_syntax($json_string);
        
        // Try to decode the potentially fixed JSON
        $decoded = json_decode($fixed_json, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // If valid JSON, process shortcodes in the decoded structure
            $processed = $this->process_shortcodes_in_array($decoded);
            return wp_json_encode($processed, JSON_UNESCAPED_SLASHES);
        } else {
            // If still not valid JSON, try direct string replacement of shortcodes
            // This might make it valid JSON
            $pattern = '/{{([^}]+)}}/';
            $with_replacements = preg_replace_callback($pattern, array($this, 'process_shortcode'), $fixed_json);
            
            // Try to decode again after replacements
            $decoded = json_decode($with_replacements, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Success after replacement
                return $with_replacements;
            } else {
                // Last attempt: fix syntax again after replacements
                $final_fixed = $this->fix_json_syntax($with_replacements);
                
                // Log if still failing
                $test = json_decode($final_fixed, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('AMFM Schema Parser: Unable to create valid JSON even after fixes');
                }
                
                return $final_fixed;
            }
        }
    }
    
    /**
     * Fix common JSON syntax issues with shortcodes.
     *
     * @since    3.1.0
     * @param    string    $json_string    The JSON string to fix.
     * @return   string                    The fixed JSON string.
     */
    private function fix_json_syntax($json_string) {
        // Fix missing comma after shortcodes when followed by another element
        // Match {{...}} followed by optional whitespace/newlines and then { or [ or "
        $json_string = preg_replace('/(\{\{[^}]+\}\})(\s*)(?=[\{\[\"])/m', '$1,$2', $json_string);
        
        // Fix missing comma before shortcodes when preceded by another element
        // Match } or ] or " followed by optional whitespace/newlines and then {{
        $json_string = preg_replace('/([\}\]\"])(\s*)(?=\{\{)/m', '$1,$2', $json_string);
        
        // Fix double commas that might be introduced
        $json_string = preg_replace('/,(\s*),/m', ',$1', $json_string);
        
        // Fix trailing commas before closing brackets (invalid JSON)
        $json_string = preg_replace('/,(\s*)([\}\]])/m', '$1$2', $json_string);
        
        return $json_string;
    }
    
    /**
     * Recursively process shortcodes in array/object structure.
     *
     * @since    3.1.0
     * @param    mixed    $data    The data structure to process.
     * @return   mixed              The processed data structure.
     */
    private function process_shortcodes_in_array($data) {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                if (is_string($value) && preg_match('/^{{([^}]+)}}$/', $value, $matches)) {
                    // This is a shortcode placeholder, replace with actual data
                    $shortcode_content = trim($matches[1]);
                    $parts = $this->parse_shortcode_attributes($shortcode_content);
                    $result[$key] = $this->get_shortcode_data($parts['type'], $parts['attributes']);
                } else {
                    $result[$key] = $this->process_shortcodes_in_array($value);
                }
            }
            return $result;
        } elseif (is_string($data) && strpos($data, '{{') !== false) {
            // String contains shortcode, process it
            $pattern = '/{{([^}]+)}}/';
            return preg_replace_callback($pattern, array($this, 'process_shortcode'), $data);
        }
        return $data;
    }
    
    /**
     * Get shortcode data as array/object.
     *
     * @since    3.1.0
     * @param    string    $type          The shortcode type.
     * @param    array     $attributes    The shortcode attributes.
     * @return   mixed                    The shortcode data.
     */
    private function get_shortcode_data($type, $attributes) {
        switch ($type) {
            case 'location':
                return $this->get_location_data($attributes);
            
            case 'breadcrumbs':
                return $this->get_breadcrumbs_data($attributes);
            
            default:
                error_log('AMFM Schema Parser: Unknown shortcode type - ' . $type);
                return null;
        }
    }

    /**
     * Process individual shortcode.
     *
     * @since    3.1.0
     * @param    array    $matches    Regex matches.
     * @return   string               The replacement JSON.
     */
    private function process_shortcode($matches) {
        $shortcode_content = trim($matches[1]);
        
        $parts = $this->parse_shortcode_attributes($shortcode_content);
        
        $shortcode_type = $parts['type'];
        $attributes = $parts['attributes'];
        
        switch ($shortcode_type) {
            case 'location':
                return $this->process_location_shortcode($attributes);
            
            case 'breadcrumbs':
                return $this->process_breadcrumbs_shortcode($attributes);
            
            default:
                error_log('AMFM Schema Parser: Unknown shortcode type - ' . $shortcode_type);
                return '""';
        }
    }

    /**
     * Parse shortcode attributes.
     *
     * @since    3.1.0
     * @param    string    $shortcode_content    The shortcode content.
     * @return   array                           Array with 'type' and 'attributes'.
     */
    private function parse_shortcode_attributes($shortcode_content) {
        $parts = explode(' ', $shortcode_content, 2);
        $type = $parts[0];
        $attributes = array();
        
        if (isset($parts[1])) {
            $attr_string = $parts[1];
            
            preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attr_string, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }
        
        return array(
            'type' => $type,
            'attributes' => $attributes
        );
    }

    /**
     * Get location data as array.
     *
     * @since    3.1.0
     * @param    array    $attributes    Shortcode attributes.
     * @return   array                   The location schema data.
     */
    private function get_location_data($attributes) {
        $brand = isset($attributes['brand']) ? $attributes['brand'] : null;
        $region = isset($attributes['region']) ? $attributes['region'] : null;
        $state = isset($attributes['state']) ? $attributes['state'] : null;
        
        $filters = array();
        if ($brand) $filters['brand'] = $brand;
        if ($region) $filters['region'] = $region;
        if ($state) $filters['state'] = $state;
        
        $location_schema = $this->location_handler->generate_location_schema($filters);
        
        return $location_schema ? $location_schema : null;
    }
    
    /**
     * Get breadcrumbs data as array.
     *
     * @since    3.1.0
     * @param    array    $attributes    Shortcode attributes.
     * @return   array                   The breadcrumbs schema data.
     */
    private function get_breadcrumbs_data($attributes) {
        global $post;
        
        if (!$post) {
            return null;
        }
        
        $breadcrumbs = array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        // Add home
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => home_url()
        );
        
        // Add current page
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => get_the_title($post),
            'item' => get_permalink($post)
        );
        
        return $breadcrumbs;
    }
    
    /**
     * Process location shortcode (for string replacement).
     *
     * @since    3.1.0
     * @param    array    $attributes    Shortcode attributes.
     * @return   string                  The JSON for location schema.
     */
    private function process_location_shortcode($attributes) {
        $location_data = $this->get_location_data($attributes);
        
        if ($location_data) {
            return wp_json_encode($location_data, JSON_UNESCAPED_SLASHES);
        }
        
        return '""';
    }

    /**
     * Process breadcrumbs shortcode.
     *
     * @since    3.1.0
     * @param    array    $attributes    Shortcode attributes.
     * @return   string                  The JSON for breadcrumbs schema.
     */
    private function process_breadcrumbs_shortcode($attributes) {
        return $this->generate_breadcrumbs_schema();
    }

    /**
     * Generate breadcrumbs schema similar to byline module.
     *
     * @since    3.1.0
     * @return   string    The JSON for breadcrumbs schema.
     */
    private function generate_breadcrumbs_schema() {
        global $post;
        
        if (!$post) {
            return '""';
        }

        $breadcrumbs = array(
            '@type' => 'BreadcrumbList',
            '@id' => get_permalink($post->ID) . '#breadcrumbs',
            'itemListElement' => array()
        );

        $breadcrumb_items = array();
        
        $breadcrumb_items[] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => get_bloginfo('name'),
            'item' => home_url('/')
        );

        if ($post->post_type === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                $main_category = $categories[0];
                $breadcrumb_items[] = array(
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $main_category->name,
                    'item' => get_category_link($main_category->term_id)
                );
            }
        } elseif ($post->post_type === 'page') {
            if ($post->post_parent) {
                $parent_pages = array();
                $parent_id = $post->post_parent;
                
                while ($parent_id) {
                    $parent = get_post($parent_id);
                    if ($parent) {
                        array_unshift($parent_pages, $parent);
                        $parent_id = $parent->post_parent;
                    } else {
                        break;
                    }
                }
                
                $position = 2;
                foreach ($parent_pages as $parent_page) {
                    $breadcrumb_items[] = array(
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $parent_page->post_title,
                        'item' => get_permalink($parent_page->ID)
                    );
                }
            }
        }

        $final_position = count($breadcrumb_items) + 1;
        $breadcrumb_items[] = array(
            '@type' => 'ListItem',
            'position' => $final_position,
            'name' => get_the_title($post->ID),
            'item' => get_permalink($post->ID)
        );

        $breadcrumbs['itemListElement'] = $breadcrumb_items;
        
        return wp_json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get supported shortcode types.
     *
     * @since    3.1.0
     * @return   array    Array of supported shortcode types.
     */
    public function get_supported_shortcodes() {
        return array(
            'location' => array(
                'description' => 'Generates medical organization and clinic location schema',
                'attributes' => array(
                    'brand' => 'Filter by brand shortname (e.g., "mc")',
                    'region' => 'Filter by region (e.g., "northwest")',
                    'state' => 'Filter by state (e.g., "CA", "WA")'
                ),
                'examples' => array(
                    '{{location}}' => 'All locations',
                    '{{location brand="mc"}}' => 'All Mission Connection locations',
                    '{{location region="northwest"}}' => 'Northwest region locations',
                    '{{location state="CA"}}' => 'California locations',
                    '{{location brand="mc" state="WA"}}' => 'Mission Connection WA locations'
                )
            ),
            'breadcrumbs' => array(
                'description' => 'Generates breadcrumb navigation schema',
                'attributes' => array(),
                'examples' => array(
                    '{{breadcrumbs}}' => 'Current page breadcrumbs'
                )
            )
        );
    }
}