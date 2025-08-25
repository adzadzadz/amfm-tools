/**
 * Import/Export Page JavaScript
 */

// Import/Export drawer data
const importExportData = {
    'export': {
        title: 'Export Data',
        content: `
            <form method="post" action="" class="amfm-form" id="amfm-export-form">
                <input type="hidden" name="amfm_export_nonce" value="${window.amfmData?.exportNonce || ''}" />
                
                <div class="amfm-form-group">
                    <label for="export_post_type">Select Post Type:</label>
                    <select name="export_post_type" id="export_post_type" required>
                        <option value="">Select a post type</option>
                        ${window.amfmData?.postTypesOptions || ''}
                    </select>
                </div>

                <div class="amfm-form-group">
                    <label>Export Options:</label>
                    <div class="amfm-checkbox-grid">
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="taxonomies" checked>
                            <span>Include Taxonomies</span>
                        </label>
                        ${window.amfmData?.hasAcf ? `
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="acf_fields" checked>
                            <span>Include ACF Fields</span>
                        </label>
                        ` : ''}
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="featured_image">
                            <span>Include Featured Image URL</span>
                        </label>
                    </div>
                </div>

                <div class="amfm-form-group amfm-taxonomy-selection" style="display:none;">
                    <label>Taxonomy Selection:</label>
                    <div class="amfm-radio-group">
                        <label class="amfm-radio-item">
                            <input type="radio" name="taxonomy_selection" value="all" checked>
                            <span>All Taxonomies</span>
                        </label>
                        <label class="amfm-radio-item">
                            <input type="radio" name="taxonomy_selection" value="selected">
                            <span>Select Specific Taxonomies</span>
                        </label>
                    </div>
                    <div class="amfm-specific-taxonomies amfm-checkbox-grid" style="display:none;">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                ${window.amfmData?.hasAcf ? `
                <div class="amfm-form-group amfm-acf-selection" style="display:none;">
                    <label>ACF Field Selection:</label>
                    <div class="amfm-radio-group">
                        <label class="amfm-radio-item">
                            <input type="radio" name="acf_selection" value="all" checked>
                            <span>All ACF Fields</span>
                        </label>
                        <label class="amfm-radio-item">
                            <input type="radio" name="acf_selection" value="selected">
                            <span>Select Specific Field Groups</span>
                        </label>
                    </div>
                    <div class="amfm-specific-acf-groups amfm-checkbox-grid" style="display:none;">
                        ${window.amfmData?.acfFieldGroups || ''}
                    </div>
                </div>
                ` : ''}

                <div class="amfm-form-actions">
                    <button type="submit" name="amfm_export" value="1" class="button button-primary">
                        Export to CSV
                    </button>
                </div>
            </form>
        `
    },
    'import': {
        title: 'Import Data',
        content: `
            <form method="post" action="" enctype="multipart/form-data" class="amfm-form" id="amfm-import-form">
                <input type="hidden" name="amfm_csv_import_nonce" value="${window.amfmData?.importNonce || ''}" />
                
                <div class="amfm-info-box">
                    <h4>📋 Import Requirements</h4>
                    <p>Your CSV file should match the columns from the Export Data function. The system will automatically detect and import the following data:</p>
                    <ul class="amfm-requirements-list">
                        <li><strong>ID</strong> - Post ID (required)</li>
                        <li><strong>Post Title</strong> - Will update post title if provided</li>
                        <li><strong>Post Content</strong> - Will update post content if provided</li>
                        <li><strong>Post Excerpt</strong> - Will update post excerpt if provided</li>
                        <li><strong>Taxonomies</strong> - Will assign categories/tags based on column names</li>
                        <li><strong>ACF Fields</strong> - Will update ACF fields based on column names</li>
                        <li><strong>Featured Image URL</strong> - Will set featured image from URL</li>
                    </ul>
                </div>

                <div class="amfm-form-group">
                    <label for="csv_file">Select CSV File:</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="amfm-file-input">
                </div>

                <div class="amfm-info-box">
                    <h4>💡 Pro Tips</h4>
                    <ul class="amfm-requirements-list">
                        <li>Export first to see the exact column format</li>
                        <li>Keep the ID column - it's required to identify posts</li>
                        <li>Leave cells empty to skip updating that field</li>
                        <li>Use the same column names as the export</li>
                    </ul>
                </div>

                <div class="amfm-form-actions">
                    <button type="submit" class="button button-primary">
                        Import Data
                    </button>
                </div>
            </form>
        `
    }
};

// Drawer functionality
function openImportExportDrawer(type) {
    const drawer = document.getElementById('amfm-import-export-drawer');
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (importExportData[type]) {
        title.textContent = importExportData[type].title;
        body.innerHTML = importExportData[type].content;
        drawer.classList.add('amfm-drawer-open');
        document.body.style.overflow = 'hidden';
        
        // Initialize form handlers after content is loaded
        initializeFormHandlers();
    }
}

function closeImportExportDrawer() {
    const drawer = document.getElementById('amfm-import-export-drawer');
    drawer.classList.remove('amfm-drawer-open');
    document.body.style.overflow = '';
}

function initializeFormHandlers() {
    // Use vanilla JavaScript since we may not have jQuery
    const $ = window.jQuery;
    if (!$) {
        console.error('jQuery not available');
        return;
    }

    // Re-initialize handlers for the new form elements
    $(() => {
        // Toggle taxonomy selection
        $('input[name="export_options[]"][value="taxonomies"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-taxonomy-selection').show();
            } else {
                $('.amfm-taxonomy-selection').hide();
            }
        });

        // Toggle specific taxonomies
        $('input[name="taxonomy_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-taxonomies').show();
            } else {
                $('.amfm-specific-taxonomies').hide();
            }
        });

        // Toggle ACF selection
        $('input[name="export_options[]"][value="acf_fields"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-acf-selection').show();
            } else {
                $('.amfm-acf-selection').hide();
            }
        });

        // Toggle specific ACF groups
        $('input[name="acf_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-acf-groups').show();
            } else {
                $('.amfm-specific-acf-groups').hide();
            }
        });

        // Update taxonomies based on post type
        $('#export_post_type').off('change').on('change', function() {
            const postType = $(this).val();
            if (postType && window.ajaxurl && window.amfmData?.ajaxNonce) {
                $.ajax({
                    url: window.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'amfm_get_post_type_taxonomies',
                        post_type: postType,
                        nonce: window.amfmData.ajaxNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.amfm-specific-taxonomies').html(response.data);
                        }
                    },
                    error: function() {
                        console.error('Failed to load taxonomies');
                    }
                });
            }
        });
    });
}

// Close drawer with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportExportDrawer();
    }
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler for drawer overlay
    const overlay = document.querySelector('.amfm-drawer-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeImportExportDrawer);
    }
    
    // Make functions globally available
    window.openImportExportDrawer = openImportExportDrawer;
    window.closeImportExportDrawer = closeImportExportDrawer;
});