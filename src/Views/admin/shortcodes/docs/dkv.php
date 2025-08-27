<?php
if (!defined('ABSPATH')) exit;
?>

<div class="amfm-shortcode-docs">
    <div class="amfm-shortcode-usage">
        <h3>Basic Usage:</h3>
        <div class="amfm-code-block">
            <code>[dkv]</code>
        </div>
        <p>Returns a random keyword from the regular keywords.</p>
    </div>

    <div class="amfm-shortcode-attributes">
        <h3>Available Attributes:</h3>
        <div class="amfm-attributes-list">
            <div class="amfm-attribute">
                <strong>pre</strong> - Text to display before the keyword (default: empty)
            </div>
            <div class="amfm-attribute">
                <strong>post</strong> - Text to display after the keyword (default: empty)
            </div>
            <div class="amfm-attribute">
                <strong>fallback</strong> - Text to display if no keyword is available (default: empty)
            </div>
            <div class="amfm-attribute">
                <strong>other_keywords</strong> - Use other keywords instead of regular keywords (default: false)
            </div>
            <div class="amfm-attribute">
                <strong>include</strong> - Only show keywords from specified categories (comma-separated)
            </div>
            <div class="amfm-attribute">
                <strong>exclude</strong> - Hide keywords from specified categories (comma-separated)
            </div>
            <div class="amfm-attribute">
                <strong>text</strong> - Transform keyword case: lowercase, uppercase, capitalize
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-examples">
        <h3>Examples:</h3>
        
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[dkv pre="Best " post=" services"]</code>
            </div>
            <div class="amfm-example-result">
                → "Best web design services" (if "web design" is a keyword)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[dkv other_keywords="true" pre="Learn " post=" today"]</code>
            </div>
            <div class="amfm-example-result">
                → "Learn WordPress today" (using other keywords)
            </div>
        </div>

        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[dkv fallback="digital marketing"]</code>
            </div>
            <div class="amfm-example-result">
                → Shows a random keyword, or "digital marketing" if none available
            </div>
        </div>

        <h4>Category Filtering Examples:</h4>
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[dkv include="i"]</code>
            </div>
            <div class="amfm-example-result">
                → "BCBS" (only shows insurance keywords, strips "i:" prefix)
            </div>
        </div>
        <div class="amfm-example">
            <div class="amfm-example-code">
                <code>[dkv include="i,c,v" text="lowercase"]</code>
            </div>
            <div class="amfm-example-result">
                → "depression" (insurance, condition, or vendor keywords in lowercase)
            </div>
        </div>
    </div>

    <div class="amfm-shortcode-note">
        <h3>How It Works:</h3>
        <ul>
            <li>Keywords are stored in browser cookies when visiting pages with ACF keyword fields</li>
            <li>Regular keywords come from the "amfm_keywords" field</li>
            <li>Other keywords come from the "amfm_other_keywords" field</li>
            <li><strong>Category Format:</strong> Keywords can be categorized using "category:keyword" format (e.g., "i:BCBS", "c:Depression")</li>
            <li><strong>Category Filtering:</strong> Use include/exclude to filter by categories; prefixes are automatically stripped for display</li>
            <li><strong>Text Transformation:</strong> Apply CSS-like text transformations (lowercase, uppercase, capitalize)</li>
            <li>Keywords are automatically filtered using the global exclusion list</li>
            <li>A random keyword is selected each time the shortcode is displayed</li>
            <li>Spaces in pre/post attributes are preserved (e.g., pre=" " will add a space)</li>
            <li>If no keywords are available and no fallback is set, nothing is displayed</li>
        </ul>
    </div>

    <div class="amfm-usage-tips">
        <h3>Usage Tips:</h3>
        <ul>
            <li>Use the shortcode in posts, pages, widgets, and theme files</li>
            <li>Keywords are updated automatically when users visit pages</li>
            <li>Set meaningful fallback text for better user experience</li>
            <li>Use pre/post attributes to create natural sentences</li>
            <li>The other_keywords attribute gives you access to alternative keyword sets</li>
            <li><strong>Category Organization:</strong> Store keywords with prefixes like "i:Insurance" or "c:Condition" for better organization</li>
            <li><strong>Smart Filtering:</strong> Combine include/exclude with other attributes for targeted content</li>
            <li><strong>Case Consistency:</strong> Use text attribute to maintain consistent formatting across your site</li>
            <li>Keywords are automatically filtered using the exclusion list</li>
        </ul>
    </div>
</div>