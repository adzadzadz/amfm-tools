<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.9
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public/partials
 */


//  create a shortcode to display an acf field
add_shortcode('amfm_acf', function ($atts) {
    // Set defaults and sanitize attributes
    $atts = shortcode_atts([
        'field' => '',
        'before' => '',
    ], $atts);

    // get the field name
    $field = $atts['field'];

    // get the before text
    $before = $atts['before'];

    // get the post id
    $post_id = get_the_ID();

    // get the field value
    $value = get_field($field, $post_id);

    // if there is no value, return nothing if has value return with before text
    if (!$value) {
        return '';
    }

    // if there is a before text, add it to the value
    if ($before) {
        $value = $before . " " . $value;
    }

    // return the value
    return $value;
});

add_shortcode('amfm_acf_object', function ($atts) {
    $atts = shortcode_atts([
        'field' => '',
        'property' => '',
        'post_id' => null, // Default to current post
        'size' => 'full',  // Default image size
    ], $atts);

    if (empty($atts['field']) || empty($atts['property'])) {
        return 'Missing field or property';
    }

    $post_id = $atts['post_id'] ? $atts['post_id'] : get_the_ID();
    $object = get_field($atts['field'], $post_id);

    if (is_object($object)) {
        // Handle Featured Image
        if ($atts['property'] === 'thumbnail') {
            $thumbnail_url = get_the_post_thumbnail_url($object->ID, $atts['size']);
            return $thumbnail_url ? '<img src="' . esc_url($thumbnail_url) . '" alt="Thumbnail">' : 'No image';
        }

        // Handle other properties
        if (isset($object->{$atts['property']})) {
            return esc_html($object->{$atts['property']});
        }
    }

    return 'Invalid object or property';
});