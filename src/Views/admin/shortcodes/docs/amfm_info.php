<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[amfm_info type="author" data="name"]</code>
        </div>
        <p>Displays specific byline information from Staff CPT posts based on post tags.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>type</strong> - Byline type: 'author', 'editor', 'reviewedBy' (required)
            </div>
            <div class="amfm-attribute">
                <strong>data</strong> - Data to display: 'name', 'credentials', 'job_title', 'page_url', 'img' (required)
            </div>
            <div class="amfm-attribute">
                <strong>before</strong> - Text to display before the information (optional)
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_info type="author" data="name"]</code>
            </div>
            <div class="amfm-example-result">
                → "Dr. John Smith" (displays author's full name)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_info type="author" data="credentials"]</code>
            </div>
            <div class="amfm-example-result">
                → "MD, PhD" (displays author's credentials)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_info type="reviewedBy" data="name" before="Reviewed by "]</code>
            </div>
            <div class="amfm-example-result">
                → "Reviewed by Dr. Jane Doe" (only shows if post has medical webpage tag)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_info type="editor" data="page_url"]</code>
            </div>
            <div class="amfm-example-result">
                → "staff/editor-name/" (displays editor's page URL without protocol)
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Looks for post tags with specific prefixes: 'authored-by', 'edited-by', 'medically-reviewed-by'</li>
            <li>Finds Staff CPT posts that have matching tags</li>
            <li>Extracts data from ACF fields on the Staff post</li>
            <li>Reviewer information only shows on posts tagged with 'medicalwebpage'</li>
            <li>Returns empty string if no matching byline is found</li>
            <li>Page URLs are returned without protocol (http/https) for cleaner display</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Use in post templates to automatically display byline information</li>
            <li>Combine multiple shortcodes to create comprehensive author bios</li>
            <li>The 'before' attribute is useful for adding labels or formatting</li>
            <li>Perfect for creating structured data and SEO-friendly author information</li>
            <li>Medical reviews only appear on medically-focused content</li>
            <li>Can be used in widgets, post content, or theme templates</li>
        </ul>
    </div>
</div>