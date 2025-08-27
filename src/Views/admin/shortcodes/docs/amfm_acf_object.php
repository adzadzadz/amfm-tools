<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[amfm_acf_object field="field_name" property="sub_property"]</code>
        </div>
        <p>Displays specific properties from ACF object fields like arrays, groups, or repeater fields.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>field</strong> - The ACF field name (required)
            </div>
            <div class="amfm-attribute">
                <strong>property</strong> - The object property or array key to display (required)
            </div>
            <div class="amfm-attribute">
                <strong>before</strong> - Text to display before the value (optional)
            </div>
            <div class="amfm-attribute">
                <strong>post_id</strong> - Post ID to get field from (defaults to current post)
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_acf_object field="contact_info" property="phone"]</code>
            </div>
            <div class="amfm-example-result">
                → "555-123-4567" (from contact_info group field)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_acf_object field="social_links" property="linkedin" before="LinkedIn: "]</code>
            </div>
            <div class="amfm-example-result">
                → "LinkedIn: https://linkedin.com/in/username"
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_acf_object field="author_details" property="bio" post_id="456"]</code>
            </div>
            <div class="amfm-example-result">
                → "Author biography from structured field" (from post 456)
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>Supported Field Types:</h3>
        <ul>
            <li><strong>Group Fields:</strong> Access sub-fields within groups</li>
            <li><strong>Arrays:</strong> Get specific array values by key</li>
            <li><strong>Objects:</strong> Access object properties</li>
            <li><strong>Serialized Data:</strong> Extract values from complex data structures</li>
        </ul>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Retrieves the ACF field value as an object or array</li>
            <li>Extracts the specified property or array key</li>
            <li>Handles nested data structures safely</li>
            <li>Returns empty string if field or property doesn't exist</li>
            <li>Works with current post or specified post ID</li>
            <li>Requires ACF plugin to be active</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Perfect for complex ACF field structures</li>
            <li>Great for accessing nested data without custom code</li>
            <li>Use for social media links, contact details, or structured metadata</li>
            <li>Test property names carefully - they're case-sensitive</li>
            <li>Ideal for group fields and repeater field data</li>
            <li>Combine with regular ACF shortcode for complete data display</li>
        </ul>
    </div>
</div>