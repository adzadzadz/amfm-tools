<?php

namespace App\Shortcodes;

/**
 * AMFM ACF Shortcode
 * 
 * Displays an ACF field value
 * Migrated from amfm-bylines plugin
 */
class AmfmAcfShortcode
{
    /**
     * Render amfm_acf shortcode
     * Usage: [amfm_acf field="field_name" before="text"]
     * 
     * @param array $atts Shortcode attributes
     * @return string ACF field value with optional before text
     */
    public function render($atts = [])
    {
        // Default attributes
        $defaults = [
            'field' => '',
            'before' => ''
        ];
        
        // Parse attributes
        $atts = \shortcode_atts($defaults, $atts, 'amfm_acf');
        
        // Sanitize attributes
        $field = \sanitize_text_field($atts['field']);
        $before = \wp_kses_post($atts['before']);
        
        if (empty($field)) {
            return '';
        }

        // Get post ID
        $post_id = \get_the_ID();
        if (!$post_id) {
            return '';
        }

        // Check if ACF is available
        if (!\function_exists('get_field')) {
            return '';
        }

        // Get field value
        $value = \get_field($field, $post_id);

        // Return empty if no value
        if (!$value) {
            return '';
        }

        // Add before text if provided
        if (!empty($before)) {
            $value = $before . " " . $value;
        }

        return $value;
    }
}