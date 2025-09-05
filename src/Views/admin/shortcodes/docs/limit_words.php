<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[limit_words content="Your long text here" limit="10"]</code>
        </div>
        <p>Limits the specified content to a maximum number of words.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>content</strong> - The text content to limit (required)
            </div>
            <div class="amfm-attribute">
                <strong>limit</strong> - Maximum number of words to display (default: 20)
            </div>
            <div class="amfm-attribute">
                <strong>more</strong> - Text to append when content is truncated (default: "...")
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[limit_words content="This is a very long sentence that needs to be shortened." limit="5"]</code>
            </div>
            <div class="amfm-example-result">
                → "This is a very long..."
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[limit_words content="Short text" limit="10"]</code>
            </div>
            <div class="amfm-example-result">
                → "Short text" (no truncation needed)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[limit_words content="Custom truncation example" limit="2" more=" [read more]"]</code>
            </div>
            <div class="amfm-example-result">
                → "Custom truncation [read more]"
            </div>
        </div>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Perfect for excerpt creation and content previews</li>
            <li>Useful in loops and widget areas with space constraints</li>
            <li>Maintains word boundaries - never cuts words in half</li>
            <li>Customize the "more" indicator to match your site's style</li>
            <li>Great for creating consistent preview lengths across your site</li>
            <li>Can be used with dynamic content from other shortcodes</li>
        </ul>
    </div>
</div>