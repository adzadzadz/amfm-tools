<?php if (!defined('ABSPATH')) exit; ?>

<div class="amfm-tab-content">
    <div class="amfm-shortcodes">
        <h2>Available Shortcodes</h2>
        <p>Use these shortcodes in your posts, pages, and widgets.</p>
        
        <div class="amfm-shortcode-list">
            <div class="amfm-shortcode-item">
                <h3>[limit_words]</h3>
                <p>Limits the number of words displayed from an ACF field or content.</p>
                <div class="amfm-shortcode-example">
                    <strong>Example:</strong>
                    <code>[limit_words text="description" words="20"]</code>
                </div>
                <div class="amfm-shortcode-params">
                    <strong>Parameters:</strong>
                    <ul>
                        <li><code>text</code> - ACF field name to get content from</li>
                        <li><code>words</code> - Maximum number of words (default: 20)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>