<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[text_util action="uppercase" content="your text here"]</code>
        </div>
        <p>Applies various text transformations and utilities to content.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Actions:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>uppercase</strong> - Convert text to UPPERCASE
            </div>
            <div class="amfm-attribute">
                <strong>lowercase</strong> - Convert text to lowercase
            </div>
            <div class="amfm-attribute">
                <strong>capitalize</strong> - Convert Text To Title Case
            </div>
            <div class="amfm-attribute">
                <strong>trim</strong> - Remove leading and trailing whitespace
            </div>
            <div class="amfm-attribute">
                <strong>word_count</strong> - Return the number of words
            </div>
            <div class="amfm-attribute">
                <strong>char_count</strong> - Return the number of characters
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[text_util action="capitalize" content="hello world"]</code>
            </div>
            <div class="amfm-example-result">
                → "Hello World"
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[text_util action="word_count" content="This is a test sentence"]</code>
            </div>
            <div class="amfm-example-result">
                → "5"
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[text_util action="uppercase" content="make this loud"]</code>
            </div>
            <div class="amfm-example-result">
                → "MAKE THIS LOUD"
            </div>
        </div>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Chain multiple utilities together for complex transformations</li>
            <li>Useful for dynamic content formatting</li>
            <li>Great for creating consistent text styling across your site</li>
            <li>Word and character counts are useful for content analytics</li>
            <li>Perfect for normalizing user-generated content</li>
            <li>Can be combined with other shortcodes for advanced text processing</li>
        </ul>
    </div>
</div>