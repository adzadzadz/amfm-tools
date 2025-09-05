<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[amfm_bylines_grid]</code>
        </div>
        <p>Displays a responsive grid showing author, editor, and reviewer information for the current post.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>No attributes</strong> - Automatically detects and displays all available bylines
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[amfm_bylines_grid]</code>
            </div>
            <div class="amfm-example-result">
                → Displays a complete bylines grid with author, editor, and reviewer cards
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>&lt;div class="post-footer"&gt;[amfm_bylines_grid]&lt;/div&gt;</code>
            </div>
            <div class="amfm-example-result">
                → Places the bylines grid in a post footer section
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>What It Displays:</h3>
        <ul>
            <li><strong>Author Information:</strong> Photo, name, credentials, job title, and bio link</li>
            <li><strong>Editor Information:</strong> Editor details and profile link</li>
            <li><strong>Medical Reviewer:</strong> Only on medical webpages - reviewer credentials and profile</li>
            <li><strong>Responsive Design:</strong> Adapts to different screen sizes</li>
            <li><strong>Professional Layout:</strong> Clean, organized presentation</li>
            <li><strong>SEO Optimized:</strong> Proper schema markup for search engines</li>
        </ul>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Automatically detects which bylines are available for the current post</li>
            <li>Pulls information from Staff CPT posts based on post tags</li>
            <li>Creates a responsive grid layout with Bootstrap classes</li>
            <li>Only shows sections that have data (e.g., no empty boxes)</li>
            <li>Medical reviewer only appears on posts tagged 'medicalwebpage'</li>
            <li>Includes structured data for enhanced SEO</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Perfect for post footers or "About the Authors" sections</li>
            <li>Great for building author authority and credibility</li>
            <li>Essential for medical content E-A-T (Expertise, Authority, Trust)</li>
            <li>Use on high-value content pages</li>
            <li>Automatically handles mobile responsiveness</li>
            <li>Works best when posts have complete byline tagging</li>
            <li>Enhances user engagement with author information</li>
        </ul>
    </div>
</div>