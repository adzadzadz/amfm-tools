<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[amfm_acf field="field_name"]</code>
        </div>
        <p>Displays Advanced Custom Fields (ACF) field values with optional formatting and prefix text.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>field</strong> - The ACF field name to display (required)
            </div>
            <div class="amfm-attribute">
                <strong>before</strong> - Text to display before the field value (optional)
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
                <code>[amfm_acf field="custom_subtitle"]</code>
            </div>
            <div class="amfm-example-result">
                → "Your custom subtitle text"
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_acf field="author_bio" before="About: "]</code>
            </div>
            <div class="amfm-example-result">
                → "About: Author biography content"
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_acf field="staff_credentials" post_id="123"]</code>
            </div>
            <div class="amfm-example-result">
                → "MD, PhD" (from post ID 123)
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>Field Types Supported:</h3>
        <ul>
            <li><strong>Text Fields:</strong> Plain text, textarea, WYSIWYG</li>
            <li><strong>Number Fields:</strong> Numeric values</li>
            <li><strong>Choice Fields:</strong> Select, checkbox, radio buttons</li>
            <li><strong>Date Fields:</strong> Date picker values</li>
            <li><strong>URL Fields:</strong> Website links</li>
            <li><strong>Email Fields:</strong> Email addresses</li>
        </ul>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Uses ACF's get_field() function to retrieve values</li>
            <li>Works with the current post by default</li>
            <li>Can target specific posts with post_id parameter</li>
            <li>Returns empty string if field doesn't exist or is empty</li>
            <li>Safely handles various ACF field types</li>
            <li>Requires ACF plugin to be installed and active</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Perfect for displaying custom metadata in posts</li>
            <li>Use for author information, publication details, or custom content</li>
            <li>Great for creating dynamic content based on ACF fields</li>
            <li>Combine with other shortcodes for complex layouts</li>
            <li>Test field names carefully - they're case-sensitive</li>
            <li>Use 'before' attribute for labels and formatting</li>
        </ul>
    </div>
</div>