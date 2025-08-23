<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'shortcodes';
$excluded_keywords = $excluded_keywords ?? [];
$keywords_text = $keywords_text ?? '';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Header -->
        <div class="amfm-header">
            <h1><span class="amfm-icon">🛠️</span> AMFM Tools</h1>
            <p class="amfm-subtitle">Advanced Custom Field Management & Performance Optimization Tools</p>
        </div>

        <!-- Tabs Navigation -->
        <div class="amfm-tabs-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=dashboard')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎛️</span>
                Dashboard
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=import-export')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📊</span>
                Import/Export
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=shortcodes')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📄</span>
                Shortcodes
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=elementor')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎨</span>
                Elementor
            </a>
        </div>

        <!-- Shortcodes Tab Content -->
        <div class="amfm-tab-content">
            <div class="amfm-shortcodes-section">
                <div class="amfm-shortcodes-header">
                    <h2>
                        <span class="amfm-shortcodes-icon">📄</span>
                        Available Shortcodes
                    </h2>
                    <p>Use these shortcodes in your posts, pages, and widgets to display dynamic content from your keyword cookies.</p>
                </div>

                <div class="amfm-shortcode-docs">
                    <div class="amfm-shortcode-columns">
                        <!-- Left Column: Information -->
                        <div class="amfm-shortcode-info-column">
                            <div class="amfm-shortcode-card">
                                <h3>DKV Shortcode</h3>
                                <p>Displays a random keyword from your stored keywords with customizable formatting.</p>
                                
                                <div class="amfm-shortcode-usage">
                                    <h4>Basic Usage:</h4>
                                    <div class="amfm-code-block">
                                        <code>[dkv]</code>
                                    </div>
                                    <p>Returns a random keyword from the regular keywords.</p>
                                </div>

                                <div class="amfm-shortcode-attributes">
                                    <h4>Available Attributes: (Updated 2025-01-08)</h4>
                                    <ul>
                                        <li><strong>pre</strong> - Text to display before the keyword (default: empty)</li>
                                        <li><strong>post</strong> - Text to display after the keyword (default: empty)</li>
                                        <li><strong>fallback</strong> - Text to display if no keyword is available (default: empty)</li>
                                        <li><strong>other_keywords</strong> - Use other keywords instead of regular keywords (default: false)</li>
                                        <li><strong>include</strong> - Only show keywords from specified categories (comma-separated)</li>
                                        <li><strong>exclude</strong> - Hide keywords from specified categories (comma-separated)</li>
                                        <li><strong>text</strong> - Transform keyword case: lowercase, uppercase, capitalize</li>
                                    </ul>
                                </div>

                                <div class="amfm-shortcode-examples">
                                    <h4>Examples:</h4>
                                    
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

                                    <div class="amfm-example">
                                        <div class="amfm-example-code">
                                            <code>[dkv pre="Top " post=" company" other_keywords="true" fallback="SEO"]</code>
                                        </div>
                                        <div class="amfm-example-result">
                                            → "Top marketing company" (from other keywords) or "SEO" if none available
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
                                    <div class="amfm-example">
                                        <div class="amfm-example-code">
                                            <code>[dkv exclude="c" text="capitalize"]</code>
                                        </div>
                                        <div class="amfm-example-result">
                                            → "Web Design" (all keywords except conditions, in Title Case)
                                        </div>
                                    </div>
                                    <div class="amfm-example">
                                        <div class="amfm-example-code">
                                            <code>[dkv pre="Best " include="i" text="uppercase"]</code>
                                        </div>
                                        <div class="amfm-example-result">
                                            → "Best BCBS" (only insurance keywords in UPPERCASE)
                                        </div>
                                    </div>
                                </div>

                                <div class="amfm-shortcode-note">
                                    <h4>How It Works:</h4>
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
                            </div>

                            <div class="amfm-shortcode-card">
                                <h3>Usage Tips</h3>
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

                        <!-- Right Column: Configuration -->
                        <div class="amfm-shortcode-config-column">
                            <div class="amfm-shortcode-card">
                                <h3>Excluded Keywords Management</h3>
                                <p>Keywords listed below will be automatically filtered out from the DKV shortcode output. You can add, remove, or modify any keywords including the defaults.</p>
                                
                                <form method="post" class="amfm-excluded-keywords-form">
                                    <?php wp_nonce_field('amfm_excluded_keywords_update', 'amfm_excluded_keywords_nonce'); ?>
                                    
                                    <div class="amfm-form-row">
                                        <label for="excluded_keywords"><strong>Excluded Keywords (one per line):</strong></label>
                                        <textarea 
                                            id="excluded_keywords" 
                                            name="excluded_keywords" 
                                            rows="12" 
                                            cols="50"
                                            class="amfm-excluded-keywords-textarea"
                                            placeholder="Enter keywords to exclude, one per line..."
                                        ><?php echo esc_textarea($keywords_text); ?></textarea>
                                        <p class="amfm-form-description">
                                            Keywords are matched case-insensitively. Each keyword should be on a separate line.
                                            Clear this field completely to allow all keywords.
                                        </p>
                                    </div>
                                    
                                    <div class="amfm-form-actions">
                                        <button type="submit" class="button button-primary">
                                            Update Excluded Keywords
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>