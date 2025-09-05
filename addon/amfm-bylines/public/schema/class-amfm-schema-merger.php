<?php

/**
 * The schema merger class.
 *
 * Handles merging of custom schema with existing byline module schema.
 *
 * @link       https://adzjo.online/adz
 * @since      3.1.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public/schema
 */

class Amfm_Schema_Merger {

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
     * Initialize the class and set its properties.
     *
     * @since    3.1.0
     * @param    string    $plugin_name    The plugin name.
     * @param    string    $version        The plugin version.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Merge custom schema with byline module schema.
     *
     * @since    3.1.0
     * @param    array    $custom_schema    The parsed custom schema.
     * @return   array|false               The merged schema or false on error.
     */
    public function merge_with_byline_schema($custom_schema) {
        if (empty($custom_schema)) {
            return false;
        }
        
        $byline_schema = $this->get_byline_module_schema();
        
        if (empty($byline_schema) && empty($custom_schema)) {
            return false;
        }
        
        return $this->merge_schemas($byline_schema, $custom_schema);
    }

    /**
     * Get existing byline module schema.
     *
     * @since    3.1.0
     * @return   array    The byline module schema.
     */
    private function get_byline_module_schema() {
        global $post;
        
        if (!$post) {
            return array();
        }

        ob_start();
        
        do_action('amfm_bylines_output_schema');
        
        $byline_output = ob_get_clean();
        
        if (empty($byline_output)) {
            return $this->get_basic_page_schema();
        }
        
        $pattern = '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s';
        preg_match_all($pattern, $byline_output, $matches);
        
        $merged_byline_schema = array();
        
        foreach ($matches[1] as $json_content) {
            $schema_data = json_decode(trim($json_content), true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($schema_data['@graph']) && is_array($schema_data['@graph'])) {
                    $merged_byline_schema = array_merge($merged_byline_schema, $schema_data['@graph']);
                } elseif (isset($schema_data['@type'])) {
                    $merged_byline_schema[] = $schema_data;
                }
            }
        }
        
        return $merged_byline_schema;
    }

    /**
     * Get basic page schema when no byline schema exists.
     *
     * @since    3.1.0
     * @return   array    Basic page schema.
     */
    private function get_basic_page_schema() {
        global $post;
        
        if (!$post) {
            return array();
        }
        
        $schema = array();
        
        if ($post->post_type === 'page') {
            $schema[] = array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID) . '#webpage',
                'url' => get_permalink($post->ID),
                'name' => get_the_title($post->ID),
                'description' => $this->get_page_description($post),
                'inLanguage' => get_bloginfo('language'),
                'isPartOf' => array(
                    '@id' => get_home_url() . '/#website'
                )
            );
        } elseif ($post->post_type === 'post') {
            $schema[] = array(
                '@type' => 'Article',
                '@id' => get_permalink($post->ID) . '#article',
                'url' => get_permalink($post->ID),
                'headline' => get_the_title($post->ID),
                'description' => $this->get_page_description($post),
                'datePublished' => get_the_date('c', $post->ID),
                'dateModified' => get_the_modified_date('c', $post->ID),
                'inLanguage' => get_bloginfo('language'),
                'isPartOf' => array(
                    '@id' => get_home_url() . '/#website'
                )
            );
        }
        
        $schema[] = array(
            '@type' => 'WebSite',
            '@id' => get_home_url() . '/#website',
            'url' => get_home_url() . '/',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'inLanguage' => get_bloginfo('language')
        );
        
        return $schema;
    }

    /**
     * Get page description from various sources.
     *
     * @since    3.1.0
     * @param    WP_Post    $post    The post object.
     * @return   string             The page description.
     */
    private function get_page_description($post) {
        $description = '';
        
        if (function_exists('get_field')) {
            $meta_description = get_field('meta_description', $post->ID);
            if (!empty($meta_description)) {
                return $meta_description;
            }
        }
        
        if (has_excerpt($post->ID)) {
            $description = get_the_excerpt($post->ID);
        } else {
            $content = strip_tags($post->post_content);
            $description = wp_trim_words($content, 20, '...');
        }
        
        return $description;
    }

    /**
     * Merge byline and custom schemas.
     *
     * @since    3.1.0
     * @param    array    $byline_schema    The byline module schema.
     * @param    array    $custom_schema    The custom schema.
     * @return   array                      The merged schema.
     */
    private function merge_schemas($byline_schema, $custom_schema) {
        $merged = array(
            '@context' => 'https://schema.org',
            '@graph' => array()
        );
        
        if (!empty($byline_schema)) {
            $merged['@graph'] = array_merge($merged['@graph'], $byline_schema);
        }
        
        if (isset($custom_schema['@graph']) && is_array($custom_schema['@graph'])) {
            $merged['@graph'] = array_merge($merged['@graph'], $custom_schema['@graph']);
        } elseif (isset($custom_schema['@type'])) {
            $merged['@graph'][] = $custom_schema;
        } else {
            foreach ($custom_schema as $item) {
                if (isset($item['@type'])) {
                    $merged['@graph'][] = $item;
                }
            }
        }
        
        $merged['@graph'] = $this->remove_duplicate_schemas($merged['@graph']);
        
        $merged['@graph'] = $this->sort_schema_items($merged['@graph']);
        
        return $merged;
    }

    /**
     * Remove duplicate schema items.
     *
     * @since    3.1.0
     * @param    array    $schema_items    Array of schema items.
     * @return   array                     Deduplicated schema items.
     */
    private function remove_duplicate_schemas($schema_items) {
        $seen_ids = array();
        $unique_items = array();
        
        foreach ($schema_items as $item) {
            $identifier = $this->get_schema_identifier($item);
            
            if (!in_array($identifier, $seen_ids)) {
                $seen_ids[] = $identifier;
                $unique_items[] = $item;
            }
        }
        
        return $unique_items;
    }

    /**
     * Get schema identifier for deduplication.
     *
     * @since    3.1.0
     * @param    array    $schema_item    A schema item.
     * @return   string                   The identifier.
     */
    private function get_schema_identifier($schema_item) {
        if (isset($schema_item['@id'])) {
            return $schema_item['@id'];
        }
        
        if (isset($schema_item['@type'])) {
            $type = $schema_item['@type'];
            
            if (isset($schema_item['url'])) {
                return $type . ':' . $schema_item['url'];
            }
            
            if (isset($schema_item['name'])) {
                return $type . ':' . $schema_item['name'];
            }
            
            return $type . ':' . md5(serialize($schema_item));
        }
        
        return 'unknown:' . md5(serialize($schema_item));
    }

    /**
     * Sort schema items by priority.
     *
     * @since    3.1.0
     * @param    array    $schema_items    Array of schema items.
     * @return   array                     Sorted schema items.
     */
    private function sort_schema_items($schema_items) {
        $priority_order = array(
            'WebSite' => 1,
            'Organization' => 2,
            'MedicalOrganization' => 2,
            'WebPage' => 3,
            'Article' => 3,
            'MedicalCondition' => 4,
            'MedicalTest' => 5,
            'MedicalTherapy' => 5,
            'Drug' => 5,
            'Offer' => 6,
            'Service' => 6,
            'FAQPage' => 7,
            'Question' => 7,
            'BreadcrumbList' => 8,
            'Person' => 9
        );
        
        usort($schema_items, function($a, $b) use ($priority_order) {
            $type_a = isset($a['@type']) ? $a['@type'] : 'Unknown';
            $type_b = isset($b['@type']) ? $b['@type'] : 'Unknown';
            
            $priority_a = isset($priority_order[$type_a]) ? $priority_order[$type_a] : 999;
            $priority_b = isset($priority_order[$type_b]) ? $priority_order[$type_b] : 999;
            
            return $priority_a - $priority_b;
        });
        
        return $schema_items;
    }

    /**
     * Validate merged schema.
     *
     * @since    3.1.0
     * @param    array    $merged_schema    The merged schema.
     * @return   array                      Validation result with 'valid' boolean and 'errors' array.
     */
    public function validate_merged_schema($merged_schema) {
        $errors = array();
        $valid = true;
        
        if (!isset($merged_schema['@context'])) {
            $errors[] = 'Missing @context';
            $valid = false;
        }
        
        if (!isset($merged_schema['@graph']) || !is_array($merged_schema['@graph'])) {
            $errors[] = 'Missing or invalid @graph';
            $valid = false;
        } else {
            foreach ($merged_schema['@graph'] as $index => $item) {
                if (!isset($item['@type'])) {
                    $errors[] = "Item at index {$index} missing @type";
                    $valid = false;
                }
            }
        }
        
        $json_encoded = wp_json_encode($merged_schema);
        if ($json_encoded === false) {
            $errors[] = 'Schema cannot be JSON encoded';
            $valid = false;
        }
        
        return array(
            'valid' => $valid,
            'errors' => $errors
        );
    }

    /**
     * Get schema merge statistics.
     *
     * @since    3.1.0
     * @param    array    $merged_schema    The merged schema.
     * @return   array                      Merge statistics.
     */
    public function get_merge_statistics($merged_schema) {
        if (!isset($merged_schema['@graph']) || !is_array($merged_schema['@graph'])) {
            return array('total_items' => 0, 'types' => array());
        }
        
        $types = array();
        
        foreach ($merged_schema['@graph'] as $item) {
            if (isset($item['@type'])) {
                $type = $item['@type'];
                $types[$type] = isset($types[$type]) ? $types[$type] + 1 : 1;
            }
        }
        
        return array(
            'total_items' => count($merged_schema['@graph']),
            'types' => $types
        );
    }
}