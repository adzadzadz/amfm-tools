<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[amfm_author_url]</code>
        </div>
        <p>Returns the author's Staff CPT page URL without protocol for the current post.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>No attributes</strong> - This shortcode works automatically based on post tags
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_author_url]</code>
            </div>
            <div class="amfm-example-result">
                → "staff/dr-john-smith/" (returns clean URL path)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>&lt;a href="https://yoursite.com/[amfm_author_url]"&gt;Author Bio&lt;/a&gt;</code>
            </div>
            <div class="amfm-example-result">
                → Creates a link to the author's full bio page
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>Read more articles by [amfm_info type="author" data="name"] at [amfm_author_url]</code>
            </div>
            <div class="amfm-example-result">
                → "Read more articles by Dr. John Smith at staff/dr-john-smith/"
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Looks for post tags starting with 'authored-by'</li>
            <li>Finds the corresponding Staff CPT post with the same tag</li>
            <li>Returns the Staff post's permalink without the protocol (http/https)</li>
            <li>Returns "No byline found" if no author is tagged for the current post</li>
            <li>URL is clean and ready for use in links or canonical references</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Perfect for creating "About the Author" links</li>
            <li>Use in post footers or author bylines</li>
            <li>Combine with domain name to create full URLs</li>
            <li>Great for structured data and canonical author references</li>
            <li>Can be used in email signatures or author attribution</li>
            <li>Works automatically - just ensure posts have proper author tags</li>
        </ul>
    </div>
</div>