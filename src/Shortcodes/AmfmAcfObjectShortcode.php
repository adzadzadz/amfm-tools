<?php

namespace App\Shortcodes;

/**
 * AMFM ACF Object Shortcode
 * 
 * Displays properties from ACF object fields
 * Migrated from amfm-bylines plugin
 */
class AmfmAcfObjectShortcode
{
    /**
     * Render amfm_acf_object shortcode
     * Usage: [amfm_acf_object field="field_name" property="property_name" post_id="123" size="full"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Object property value or error message
     */
    public function render($atts = [])
    {
        // Default attributes
        $defaults = [
            'field' => '',
            'property' => '',
            'post_id' => null, // Default to current post
            'size' => 'full'  // Default image size
        ];
        
        // Parse attributes
        $atts = \shortcode_atts($defaults, $atts, 'amfm_acf_object');
        
        // Validate required parameters
        if (empty($atts['field']) || empty($atts['property'])) {
            return 'Missing field or property';
        }

        // Check if ACF is available
        if (!\function_exists('get_field')) {
            return 'ACF plugin not available';
        }

        // Get post ID
        $post_id = $atts['post_id'] ? intval($atts['post_id']) : \get_the_ID();
        if (!$post_id) {
            return 'No valid post ID';
        }

        // Get the object field
        $object = \get_field(\sanitize_text_field($atts['field']), $post_id);

        if (!\is_object($object)) {
            return 'Invalid object or field';
        }

        return $this->getObjectProperty($object, $atts['property'], $atts['size']);
    }

    /**
     * Get property value from object
     * 
     * @param object $object The ACF object
     * @param string $property Property name to retrieve
     * @param string $size Image size for thumbnail property
     * @return string Property value or error message
     */
    private function getObjectProperty($object, $property, $size)
    {
        $property = \sanitize_text_field($property);
        
        // Handle special case for thumbnails
        if ($property === 'thumbnail') {
            if (isset($object->ID)) {
                $thumbnail_url = \get_the_post_thumbnail_url($object->ID, \sanitize_text_field($size));
                if ($thumbnail_url) {
                    return sprintf(
                        '<img src="%s" alt="Thumbnail">',
                        \esc_url($thumbnail_url)
                    );
                }
            }
            return 'No image';
        }

        // Handle other properties
        if (isset($object->{$property})) {
            return \esc_html($object->{$property});
        }

        return 'Invalid object or property';
    }
}