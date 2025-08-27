<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'utilities';
$available_utilities = $available_utilities ?? [];
$enabled_utilities = $enabled_utilities ?? [];
?>

<!-- Modern Bootstrap 5 Utility Management -->
<div class="container-fluid px-0">
    <div class="row g-3">
        <!-- Main Content -->
        <div class="col-12">
            <!-- Utilities Grid -->
            <form method="post" id="amfm-utility-form">
                <?php wp_nonce_field('amfm_component_settings_update', 'amfm_component_settings_nonce'); ?>
                
                <div class="row g-3">
                    <?php if (empty($available_utilities)): ?>
                        <!-- No Utilities Available -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center py-5">
                                    <div class="text-muted mb-4">
                                        <i class="fas fa-tools fs-1 opacity-25"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No Utilities Available</h5>
                                    <p class="text-muted small mb-0">No utilities are currently registered by this plugin.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($available_utilities as $utility_key => $utility_info): 
                            $is_core = $utility_info['status'] === 'Core Feature';
                            $is_enabled = in_array($utility_key, $enabled_utilities);
                        ?>
                            <div class="col-lg-6 col-xl-4">
                                <div class="card border-0 shadow-sm h-100 hover-lift">
                                    <div class="card-body p-4">
                                        <!-- Utility Header -->
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded bg-primary bg-opacity-10 p-2 me-3">
                                                    <i class="<?php echo esc_attr($utility_info['icon'] ?? 'fas fa-tools'); ?> text-primary" style="font-size: 1.25rem;"></i>
                                                </div>
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?php echo esc_html($utility_info['name']); ?></h5>
                                                </div>
                                            </div>
                                            
                                            <!-- Toggle Switch -->
                                            <?php if ($is_core): ?>
                                                <span class="badge bg-warning text-dark px-3 py-2">Core</span>
                                                <input type="hidden" name="enabled_components[]" value="<?php echo esc_attr($utility_key); ?>">
                                            <?php else: ?>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input utility-toggle" 
                                                           type="checkbox" 
                                                           role="switch"
                                                           id="utility-<?php echo esc_attr($utility_key); ?>"
                                                           name="enabled_components[]" 
                                                           value="<?php echo esc_attr($utility_key); ?>"
                                                           <?php checked($is_enabled); ?>
                                                           style="cursor: pointer;">
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Utility Description -->
                                        <p class="text-muted mb-3 small"><?php echo esc_html($utility_info['description']); ?></p>

                                        <!-- Status Badge -->
                                        <div class="mb-3">
                                            <?php if ($is_core): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                    Always Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge <?php echo $is_enabled ? 'bg-success' : 'bg-secondary'; ?> bg-opacity-10 <?php echo $is_enabled ? 'text-success' : 'text-secondary'; ?> px-3 py-2">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                    <?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2 mt-auto">
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm flex-fill"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#amfm-utility-drawer"
                                                    onclick="loadUtilityContent('<?php echo esc_attr($utility_key); ?>', 'documentation')">
                                                <i class="fas fa-book me-1"></i>
                                                Docs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Utility Documentation/Config Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="amfm-utility-drawer" aria-labelledby="amfm-drawer-title">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="amfm-drawer-title">Utility Documentation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="amfm-drawer-body">
        <!-- Content will be loaded dynamically -->
    </div>
</div>

<script>
// Localize AJAX data
const amfm_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    utility_nonce: '<?php echo wp_create_nonce('amfm_component_settings_nonce'); ?>'
};

// Utility documentation data
const utilityData = {
    'acf_helper': {
        name: 'ACF Helper',
        description: 'Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Overview:</h3>
                    <p>The ACF Helper utility is a core component that automatically manages keyword cookies based on ACF (Advanced Custom Fields) data. It enhances WordPress sites with dynamic content capabilities.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Key Features:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Automatic Cookie Management</strong> - Stores keywords from ACF fields in browser cookies for dynamic content
                        </div>
                        <div class="amfm-attribute">
                            <strong>Multi-field Support</strong> - Handles both 'amfm_keywords' and 'amfm_other_keywords' fields
                        </div>
                        <div class="amfm-attribute">
                            <strong>Category Processing</strong> - Processes categorized keywords with prefixes (e.g., "i:Insurance", "c:Condition")
                        </div>
                        <div class="amfm-attribute">
                            <strong>Cookie Expiration</strong> - Configurable cookie duration (default: 24 hours)
                        </div>
                        <div class="amfm-attribute">
                            <strong>Cross-page Persistence</strong> - Keywords remain available across different pages during user session
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>How It Works:</h3>
                    <ul>
                        <li>Automatically detects ACF keyword fields on page load</li>
                        <li>Extracts and processes keywords from ACF fields</li>
                        <li>Stores keywords in browser cookies with configurable expiration</li>
                        <li>Provides foundation for DKV shortcode and other dynamic content features</li>
                        <li>Filters keywords against global exclusion list</li>
                        <li>Handles both regular and categorized keywords</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Benefits:</h3>
                    <ul>
                        <li><strong>Performance:</strong> Reduces database queries for keyword retrieval</li>
                        <li><strong>Dynamic Content:</strong> Enables personalized content based on page context</li>
                        <li><strong>SEO Enhancement:</strong> Supports keyword-based content optimization</li>
                        <li><strong>User Experience:</strong> Provides consistent keyword availability across sessions</li>
                        <li><strong>Developer Friendly:</strong> Simple integration with existing ACF workflows</li>
                    </ul>
                </div>
            </div>
        `
    },
    'optimization': {
        name: 'Performance Optimization',
        description: 'Gravity Forms optimization and performance enhancements for faster page loading.',
        content: `
            <div class="amfm-shortcode-docs">
                <div class="amfm-shortcode-usage">
                    <h3>Overview:</h3>
                    <p>The Performance Optimization utility provides comprehensive performance enhancements, particularly focusing on Gravity Forms optimization and general WordPress performance improvements.</p>
                </div>

                <div class="amfm-shortcode-attributes">
                    <h3>Optimization Features:</h3>
                    <div class="amfm-attributes-list">
                        <div class="amfm-attribute">
                            <strong>Gravity Forms Optimization</strong> - Reduces form loading times and improves rendering performance
                        </div>
                        <div class="amfm-attribute">
                            <strong>Script Optimization</strong> - Minimizes unnecessary script loading and improves page speed
                        </div>
                        <div class="amfm-attribute">
                            <strong>Style Optimization</strong> - Optimizes CSS delivery and reduces render-blocking resources
                        </div>
                        <div class="amfm-attribute">
                            <strong>Resource Management</strong> - Intelligently loads resources only when needed
                        </div>
                        <div class="amfm-attribute">
                            <strong>Caching Integration</strong> - Works with existing caching solutions for maximum performance
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-examples">
                    <h3>Performance Benefits:</h3>
                    
                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Faster Page Load Times</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Reduced time to first contentful paint and improved Core Web Vitals scores
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Improved Form Performance</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Gravity Forms load faster with optimized script and style delivery
                        </div>
                    </div>

                    <div class="amfm-example">
                        <div class="amfm-example-code">
                            <strong>Reduced Server Load</strong>
                        </div>
                        <div class="amfm-example-result">
                            → Lower CPU usage and memory consumption through smart resource management
                        </div>
                    </div>
                </div>

                <div class="amfm-shortcode-note">
                    <h3>When to Enable:</h3>
                    <ul>
                        <li>Sites with multiple Gravity Forms</li>
                        <li>High traffic websites requiring optimal performance</li>
                        <li>Sites with performance issues or slow loading times</li>
                        <li>E-commerce sites with form-heavy checkout processes</li>
                        <li>Sites that need to improve Core Web Vitals scores</li>
                    </ul>
                </div>

                <div class="amfm-usage-tips">
                    <h3>Best Practices:</h3>
                    <ul>
                        <li><strong>Enable by Default:</strong> This utility is automatically enabled for optimal performance</li>
                        <li><strong>Monitor Performance:</strong> Use tools like Google PageSpeed Insights to measure improvements</li>
                        <li><strong>Test Forms:</strong> Verify all Gravity Forms functionality after enabling</li>
                        <li><strong>Cache Compatibility:</strong> Works well with popular caching plugins</li>
                        <li><strong>Regular Updates:</strong> Keep the plugin updated for latest optimizations</li>
                    </ul>
                </div>
            </div>
        `
    }
};

// Utility content functions
function loadUtilityContent(utilityKey, mode = 'documentation') {
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (utilityData[utilityKey]) {
        const data = utilityData[utilityKey];
        
        title.textContent = data.name + ' Documentation';
        body.innerHTML = data.content;
    }
}

// Individual utility toggle functionality using dedicated AJAX endpoint
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.utility-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function(e) {
            const utilityKey = this.value;
            const isEnabled = this.checked;
            const card = this.closest('.card');
            const statusBadge = card.querySelector('.badge');
            const nonceValue = amfm_ajax.utility_nonce;
            
            console.log('Toggle changed:', utilityKey, 'to', isEnabled ? 'enabled' : 'disabled');
            console.log('Using nonce:', nonceValue);
            
            // Update status badge immediately for better UX
            if (isEnabled) {
                statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
            } else {
                statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
            }
            
            // Prepare AJAX data for individual utility toggle
            const formData = new FormData();
            formData.append('action', 'amfm_toggle_utility');
            formData.append('component', utilityKey);
            formData.append('enabled', isEnabled ? '1' : '0');
            formData.append('nonce', nonceValue);
            
            // Send AJAX request to toggle individual utility
            fetch(amfm_ajax.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Utility status updated successfully');
                    
                    // Add brief success pulse effect
                    statusBadge.style.opacity = '0.7';
                    setTimeout(() => {
                        statusBadge.style.opacity = '1';
                    }, 200);
                } else {
                    // Error - revert toggle state
                    this.checked = !isEnabled;
                    
                    // Revert status badge
                    if (!isEnabled) {
                        statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                        statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
                    } else {
                        statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                        statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
                    }
                    
                    console.error('Failed to update utility status:', data.data || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                
                // Revert toggle state on error
                this.checked = !isEnabled;
                
                // Revert status badge
                if (!isEnabled) {
                    statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                    statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
                } else {
                    statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                    statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
                }
            });
        });
    });
    
    // Prevent form submission to avoid page redirects
    const form = document.getElementById('amfm-utility-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Initialize Bootstrap offcanvas
    const offcanvasElement = document.getElementById('amfm-utility-drawer');
    if (offcanvasElement && typeof bootstrap !== 'undefined') {
        new bootstrap.Offcanvas(offcanvasElement);
    }
});
</script>

<style>
/* Modern Utility Management Styles */
#amfm-utility-drawer {
    width: 600px;
    top: 32px; /* Account for WordPress admin bar */
    height: calc(100vh - 32px);
}

/* Responsive admin bar height adjustments */
@media screen and (max-width: 782px) {
    #amfm-utility-drawer {
        top: 46px;
        height: calc(100vh - 46px);
    }
}

@media screen and (max-width: 600px) {
    #amfm-utility-drawer {
        top: 0;
        height: 100vh;
        width: 90%;
    }
}

/* Offcanvas header styling */
#amfm-utility-drawer .offcanvas-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#amfm-utility-drawer .offcanvas-title {
    font-size: 18px;
    color: #333;
    margin: 0;
}

/* Card Animations */
.hover-lift {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
}

/* Enhanced Bootstrap 5 Form Switch */
.form-check.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    margin-left: -3.5em;
    background-color: #6c757d;
    border-color: #6c757d;
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
    background-position: left center !important;
    background-repeat: no-repeat !important;
    background-size: contain !important;
    border-radius: 3em !important;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.form-check.form-switch .form-check-input:checked {
    background-position: right center !important;
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
}

.form-check.form-switch .form-check-input:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-check.form-switch .form-check-input:focus:not(:checked) {
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
}

.form-check.form-switch .form-check-input:checked:focus {
    background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'><circle r='3' fill='rgba(255,255,255,1.0)'/></svg>") !important;
}

/* Remove WordPress/Admin checkmark */
.form-check.form-switch .form-check-input::before,
.form-check.form-switch .form-check-input:checked::before {
    content: none !important;
    display: none !important;
}

.documentation-content .card {
    border: 1px solid rgba(0,0,0,0.1) !important;
}

/* Component Actions */
.amfm-component-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 8px;
}

.amfm-info-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
    text-decoration: none;
}

.amfm-info-button:hover {
    background: #005a87;
    color: white;
}

/* Documentation Styles */
.amfm-shortcode-docs h3 {
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 8px;
    margin-top: 30px;
    margin-bottom: 15px;
}

.amfm-shortcode-docs h4 {
    color: #555;
    margin-top: 25px;
    margin-bottom: 12px;
}

.amfm-code-block {
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 12px;
    margin: 10px 0;
    font-family: 'Courier New', monospace;
}

.amfm-code-block code {
    background: none;
    padding: 0;
    font-size: 14px;
    color: #d14;
}

.amfm-attributes-list {
    background: #f9f9f9;
    border-radius: 6px;
    padding: 15px;
}

.amfm-attribute {
    margin-bottom: 8px;
    padding: 8px;
    background: white;
    border-radius: 4px;
    border-left: 3px solid #0073aa;
}

.amfm-example {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 4px solid #46b450;
}

.amfm-example-code {
    margin-bottom: 8px;
}

.amfm-example-result {
    color: #666;
    font-style: italic;
}

.amfm-shortcode-note ul,
.amfm-usage-tips ul {
    margin-left: 20px;
}

.amfm-shortcode-note li,
.amfm-usage-tips li {
    margin-bottom: 8px;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .amfm-component-actions {
        flex-direction: column;
    }
    
    .amfm-info-button {
        justify-content: center;
    }
}
</style>