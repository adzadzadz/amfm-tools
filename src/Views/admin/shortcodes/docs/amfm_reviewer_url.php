<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[amfm_reviewer_url]</code>
        </div>
        <p>Returns the medical reviewer's Staff CPT page URL without protocol for the current post. Only works on posts tagged as medical webpages.</p>
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
                <code>[amfm_reviewer_url]</code>
            </div>
            <div class="amfm-example-result">
                → "staff/dr-medical-reviewer/" (only on medical webpages)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>&lt;a href="https://yoursite.com/[amfm_reviewer_url]"&gt;Reviewer Credentials&lt;/a&gt;</code>
            </div>
            <div class="amfm-example-result">
                → Creates a link to the medical reviewer's profile
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>Medically reviewed by [amfm_info type="reviewedBy" data="name"] - [amfm_reviewer_url]</code>
            </div>
            <div class="amfm-example-result">
                → "Medically reviewed by Dr. Smith - staff/dr-medical-reviewer/"
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Only functions on posts tagged with 'medicalwebpage'</li>
            <li>Looks for post tags starting with 'medically-reviewed-by'</li>
            <li>Finds the corresponding Staff CPT post with the same tag</li>
            <li>Returns the Staff post's permalink without the protocol (http/https)</li>
            <li>Returns "No byline found" if conditions aren't met</li>
            <li>Part of medical content verification system</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Essential for medical content credibility and E-A-T</li>
            <li>Use in medical article bylines and disclaimers</li>
            <li>Perfect for healthcare content compliance</li>
            <li>Combine with reviewer credentials for full attribution</li>
            <li>Important for search engine trust signals</li>
            <li>Only appears on properly tagged medical content</li>
        </ul>
    </div>
</div>