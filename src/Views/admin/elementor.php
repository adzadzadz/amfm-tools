<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'elementor';
$available_widgets = $available_widgets ?? [];
$enabled_widgets = $enabled_widgets ?? [];
?>

<!-- Modern Bootstrap 5 Elementor Widgets Management -->
<div class="container-fluid px-0">
    <div class="row g-3">
        <!-- Main Content -->
        <div class="col-12">
            <!-- Widgets Grid -->
            <form method="post" id="amfm-elementor-form">
                <?php wp_nonce_field('amfm_elementor_widgets_update', 'amfm_elementor_widgets_nonce'); ?>
                
                <div class="row g-3">
                    <?php if (empty($available_widgets)): ?>
                        <!-- No Widgets Available -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center py-5">
                                    <div class="text-muted mb-4">
                                        <i class="fas fa-puzzle-piece fs-1 opacity-25"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No Elementor Widgets Available</h5>
                                    <p class="text-muted small mb-0">No Elementor widgets are currently registered by this plugin.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($available_widgets as $widget_key => $widget_info): 
                            $is_enabled = in_array($widget_key, $enabled_widgets);
                        ?>
                            <div class="col-lg-6 col-xl-4">
                                <div class="card border-0 shadow-sm h-100 hover-lift">
                                    <div class="card-body p-4">
                                        <!-- Widget Header -->
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded bg-primary bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-puzzle-piece text-primary" style="font-size: 1.25rem;"></i>
                                                </div>
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark"><?php echo esc_html($widget_info['name']); ?></h5>
                                                </div>
                                            </div>
                                            <!-- Bootstrap 5 Form Switch -->
                                            <div class="form-check form-switch">
                                                <input class="form-check-input widget-toggle" 
                                                       type="checkbox" 
                                                       role="switch"
                                                       id="widget-<?php echo esc_attr($widget_key); ?>"
                                                       name="enabled_widgets[]" 
                                                       value="<?php echo esc_attr($widget_key); ?>"
                                                       <?php checked($is_enabled); ?>
                                                       style="cursor: pointer;">
                                            </div>
                                        </div>

                                        <!-- Widget Description -->
                                        <p class="text-muted mb-3 small"><?php echo esc_html($widget_info['description']); ?></p>

                                        <!-- Status Badge -->
                                        <div class="mb-3">
                                            <span class="badge <?php echo $is_enabled ? 'bg-success' : 'bg-secondary'; ?> bg-opacity-10 <?php echo $is_enabled ? 'text-success' : 'text-secondary'; ?> px-3 py-2">
                                                <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                <?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2 mt-auto">
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-sm flex-fill"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#amfm-widget-drawer"
                                                    onclick="loadWidgetContent('<?php echo esc_attr($widget_key); ?>', 'documentation')">
                                                <i class="fas fa-book me-1"></i>
                                                Docs
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm flex-fill"
                                                    data-bs-toggle="offcanvas"
                                                    data-bs-target="#amfm-widget-drawer"
                                                    onclick="loadWidgetContent('<?php echo esc_attr($widget_key); ?>', 'config')">
                                                <i class="fas fa-cog me-1"></i>
                                                Config
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

<!-- Widget Documentation/Config Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="amfm-widget-drawer" aria-labelledby="amfm-drawer-title">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="amfm-drawer-title">Widget Documentation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="amfm-drawer-body">
        <!-- Content will be loaded dynamically -->
    </div>
</div>

<script>
// Check if amfm_ajax is available from wp_localize_script
if (typeof amfm_ajax === 'undefined') {
    console.error('AMFM Tools: amfm_ajax is not defined. AJAX functionality will not work.');
}

// Widget documentation and configuration data
const widgetData = {
    'amfm_related_posts': {
        name: 'AMFM Related Posts',
        description: 'Display related posts based on ACF keywords with customizable layouts and styling options.',
        documentation: `
            <div class="documentation-content">
                <div class="alert alert-info border-0 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Overview</h6>
                            <p class="mb-0 small">The AMFM Related Posts widget provides powerful content discovery features for your Elementor pages. It automatically finds and displays related posts based on ACF (Advanced Custom Fields) keywords.</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-star text-warning me-2"></i>
                            Key Features
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-tags text-primary me-2 mt-1"></i>
                                    <div>
                                        <strong class="d-block">Keyword Matching</strong>
                                        <small class="text-muted">Automatically finds related posts based on ACF keywords</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-th-large text-success me-2 mt-1"></i>
                                    <div>
                                        <strong class="d-block">Multiple Layouts</strong>
                                        <small class="text-muted">Choose from grid, list, or carousel display options</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-palette text-warning me-2 mt-1"></i>
                                    <div>
                                        <strong class="d-block">Customizable Styling</strong>
                                        <small class="text-muted">Full control over typography, colors, and spacing</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-filter text-info me-2 mt-1"></i>
                                    <div>
                                        <strong class="d-block">Query Controls</strong>
                                        <small class="text-muted">Filter by post type, category, tags, and custom taxonomies</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-play-circle text-success me-2"></i>
                            How to Use
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-start border rounded p-3 bg-light">
                                    <span class="badge bg-primary me-3 mt-1">1</span>
                                    <div>
                                        <strong class="d-block mb-1">Add Widget</strong>
                                        <small class="text-muted">Open Elementor editor and search for "AMFM" in the widgets panel</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-start border rounded p-3 bg-light">
                                    <span class="badge bg-success me-3 mt-1">2</span>
                                    <div>
                                        <strong class="d-block mb-1">Configure Settings</strong>
                                        <small class="text-muted">Set keyword source, number of posts, layout options in the Content tab</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-start border rounded p-3 bg-light">
                                    <span class="badge bg-info me-3 mt-1">3</span>
                                    <div>
                                        <strong class="d-block mb-1">Style Widget</strong>
                                        <small class="text-muted">Customize appearance using the Style tab for colors, typography, and spacing</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning border-0">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-triangle fs-4 me-3 text-warning"></i>
                        <div>
                            <h6 class="alert-heading mb-2">Requirements</h6>
                            <ul class="mb-0 small">
                                <li>Elementor (Free or Pro) must be installed and active</li>
                                <li>Advanced Custom Fields (ACF) plugin must be active</li>
                                <li>Posts must have ACF keyword fields populated for matching</li>
                                <li>This widget must be enabled in the Elementor management section</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `,
        config: `
            <div class="documentation-content">
                <div class="alert alert-success border-0 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-cogs fs-4 me-3 text-success"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Widget Configuration</h6>
                            <p class="mb-0 small">Configure the AMFM Related Posts widget settings through the Elementor editor panel.</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-file-alt text-primary me-2"></i>
                            Content Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="border-start border-primary border-3 ps-3 py-2">
                                    <strong class="d-block">Keyword Source</strong>
                                    <small class="text-muted">Choose between 'AMFM Keywords', 'AMFM Other Keywords', or 'Both Fields'</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-start border-success border-3 ps-3 py-2">
                                    <strong class="d-block">Number of Posts</strong>
                                    <small class="text-muted">Set how many related posts to display (default: 3)</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-start border-info border-3 ps-3 py-2">
                                    <strong class="d-block">Post Types</strong>
                                    <small class="text-muted">Select which post types to include in results</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-start border-warning border-3 ps-3 py-2">
                                    <strong class="d-block">Exclude Current Post</strong>
                                    <small class="text-muted">Automatically exclude the current post from results</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-paint-brush text-success me-2"></i>
                            Style Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded bg-light">
                                    <i class="fas fa-font text-primary fs-3 mb-2"></i>
                                    <h6 class="mb-1">Typography</h6>
                                    <small class="text-muted">Font family, size, weight, and color for titles and content</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded bg-light">
                                    <i class="fas fa-expand-arrows-alt text-success fs-3 mb-2"></i>
                                    <h6 class="mb-1">Spacing</h6>
                                    <small class="text-muted">Control margins, padding, and gaps between elements</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded bg-light">
                                    <i class="fas fa-palette text-warning fs-3 mb-2"></i>
                                    <h6 class="mb-1">Colors</h6>
                                    <small class="text-muted">Customize background, text, and accent colors</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded bg-light">
                                    <i class="fas fa-mobile-alt text-info fs-3 mb-2"></i>
                                    <h6 class="mb-1">Responsive</h6>
                                    <small class="text-muted">Set different configurations for tablet and mobile breakpoints</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `
    }
};

// Individual widget toggle functionality using dedicated AJAX endpoint
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.widget-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function(e) {
            const widgetKey = this.value;
            const isEnabled = this.checked;
            const card = this.closest('.card');
            const statusBadge = card.querySelector('.badge');
            const nonceValue = document.querySelector('[name="amfm_elementor_widgets_nonce"]').value;
            
            console.log('Toggle changed:', widgetKey, 'to', isEnabled ? 'enabled' : 'disabled');
            console.log('Using nonce:', nonceValue);
            
            // Update status badge immediately for better UX
            if (isEnabled) {
                statusBadge.className = 'badge bg-success bg-opacity-10 text-success px-3 py-2';
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Enabled';
            } else {
                statusBadge.className = 'badge bg-secondary bg-opacity-10 text-secondary px-3 py-2';
                statusBadge.innerHTML = '<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Disabled';
            }
            
            // Prepare AJAX data for individual widget toggle
            const formData = new FormData();
            formData.append('action', 'amfm_toggle_elementor_widget');
            formData.append('widget', widgetKey);
            formData.append('enabled', isEnabled ? '1' : '0');
            formData.append('nonce', nonceValue);
            
            // Send AJAX request to toggle individual widget
            fetch(amfm_ajax.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Widget status updated successfully');
                    
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
                    
                    console.error('Failed to update widget status:', data.data || 'Unknown error');
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
    const form = document.getElementById('amfm-elementor-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
});

// Widget content functions
function loadWidgetContent(widgetKey, mode = 'documentation') {
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (widgetData[widgetKey]) {
        const data = widgetData[widgetKey];
        
        if (mode === 'documentation') {
            title.textContent = data.name + ' Documentation';
            body.innerHTML = data.documentation;
        } else if (mode === 'config') {
            title.textContent = data.name + ' Configuration';
            body.innerHTML = data.config;
        }
    }
}

// Initialize Bootstrap offcanvas
document.addEventListener('DOMContentLoaded', function() {
    const offcanvasElement = document.getElementById('amfm-widget-drawer');
    if (offcanvasElement && typeof bootstrap !== 'undefined') {
        new bootstrap.Offcanvas(offcanvasElement);
    }
});
</script>

<style>
/* Offcanvas Customizations */
#amfm-widget-drawer {
    width: 700px;
    top: 32px; /* Account for WordPress admin bar */
    height: calc(100vh - 32px);
}

/* Responsive admin bar height adjustments */
@media screen and (max-width: 782px) {
    #amfm-widget-drawer {
        top: 46px;
        height: calc(100vh - 46px);
    }
}

@media screen and (max-width: 600px) {
    #amfm-widget-drawer {
        top: 0;
        height: 100vh;
        width: 95%;
    }
}

/* Offcanvas header styling */
#amfm-widget-drawer .offcanvas-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

#amfm-widget-drawer .offcanvas-title {
    font-size: 18px;
    color: #333;
    margin: 0;
}

.documentation-content .card {
    border: 1px solid rgba(0,0,0,0.1) !important;
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

/* Additional responsive styles */
@media (max-width: 768px) {
    #amfm-widget-drawer {
        width: 90%;
    }
}
</style>