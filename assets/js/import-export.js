/**
 * AMFM Tools Import/Export JavaScript
 * Handles all import/export functionality including AJAX requests and UI interactions
 */

// Global variables and data
let importExportData = {};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeImportExport();
});

/**
 * Initialize import/export functionality
 */
function initializeImportExport() {
    // Generate import/export data for drawers
    generateImportExportData();
    
    // Handle card clicks to open drawers
    const tabCards = document.querySelectorAll('.amfm-tab-card');
    tabCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const toolType = this.getAttribute('data-tab');
            openImportExportDrawer(toolType);
        });
    });
    
    // Handle drawer close button
    const drawerCloseBtn = document.querySelector('.amfm-drawer-close');
    if (drawerCloseBtn) {
        drawerCloseBtn.addEventListener('click', closeImportExportDrawer);
    }
    
    // Handle ESC key to close drawer
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImportExportDrawer();
        }
    });
}

/**
 * Generate import/export data for drawers
 */
function generateImportExportData() {
    // Get data passed from PHP
    const postTypesOptions = window.amfmPostTypesOptions || '';
    const allFieldGroups = window.amfmFieldGroups || [];
    const acfFieldGroupsHtml = window.amfmAcfFieldGroupsHtml || '';
    const exportNonce = window.amfmExportNonce || '';
    const keywordsNonce = window.amfmKeywordsNonce || '';
    const categoriesNonce = window.amfmCategoriesNonce || '';
    
    importExportData = {
        'export': {
            name: 'Export Data',
            icon: '📤',
            content: generateExportForm(postTypesOptions, acfFieldGroupsHtml, exportNonce)
        },
        'keywords': {
            name: 'Import Keywords',
            icon: '📥',
            content: generateKeywordsForm(keywordsNonce)
        },
        'categories': {
            name: 'Import Categories',
            icon: '📂',
            content: generateCategoriesForm(categoriesNonce)
        }
    };
}

/**
 * Generate export form HTML
 */
function generateExportForm(postTypesOptions, acfFieldGroupsHtml, exportNonce) {
    return `
        <div class="amfm-drawer-section">
            <h3>Export Posts with ACF Fields</h3>
            <p class="amfm-drawer-description">Export your posts with Advanced Custom Fields data to CSV format for backup, migration, or analysis purposes.</p>
            
            <form method="post" action="" id="amfm_export_form" class="amfm-modern-form">
                ${exportNonce}
                
                <div class="amfm-form-group">
                    <label for="export_post_type">Select Post Type to Export</label>
                    <select name="export_post_type" id="export_post_type" required>
                        <option value="">Choose a post type...</option>
                        ${postTypesOptions}
                    </select>
                </div>

                <div class="export-options" style="display: none;">
                    <!-- Post Columns Section -->
                    <div class="amfm-form-group">
                        <label>
                            <input type="checkbox" name="export_options[]" value="post_columns" id="post-columns-toggle" checked>
                            <strong>Post Columns</strong>
                        </label>
                        <div class="post-columns-section" id="post-columns-section">
                            <div class="amfm-checkbox-grid" style="margin-top: 15px;">
                                <label><input type="checkbox" name="post_columns[]" value="id" checked> <span>Post ID</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="title" checked> <span>Title</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="content"> <span>Content</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="excerpt"> <span>Excerpt</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="status"> <span>Status</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="date"> <span>Date</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="modified"> <span>Modified</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="url"> <span>URL</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="slug"> <span>Slug</span></label>
                                <label><input type="checkbox" name="post_columns[]" value="author"> <span>Author</span></label>
                            </div>
                        </div>
                    </div>

                    <!-- Taxonomies Section -->
                    <div class="amfm-form-group">
                        <label>
                            <input type="checkbox" name="export_options[]" value="taxonomies" id="taxonomies-toggle">
                            <strong>Taxonomies</strong>
                        </label>
                        <div class="taxonomy-section" id="taxonomy-section" style="display: none;">
                            <div style="margin: 15px 0;">
                                <label><input type="radio" name="taxonomy_selection" value="all" checked> Export All Taxonomies</label><br>
                                <label><input type="radio" name="taxonomy_selection" value="selected"> Select Specific Taxonomies</label>
                            </div>
                            <div id="taxonomy-checkboxes" class="amfm-checkbox-grid" style="display: none;">
                                <!-- Dynamic content will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- ACF Fields Section -->
                    <div class="amfm-form-group">
                        <label>
                            <input type="checkbox" name="export_options[]" value="acf_fields" id="acf-fields-toggle">
                            <strong>ACF Fields</strong>
                        </label>
                        <div class="acf-section" id="acf-section" style="display: none;">
                            <div style="margin: 15px 0;">
                                <label><input type="radio" name="acf_selection" value="all" checked> Export All ACF Fields</label><br>
                                <label><input type="radio" name="acf_selection" value="selected"> Select Specific Field Groups</label>
                            </div>
                            <div id="acf-groups-checkboxes" class="amfm-checkbox-grid" style="display: none;">
                                ${acfFieldGroupsHtml}
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image Section -->
                    <div class="amfm-form-group">
                        <label>
                            <input type="checkbox" name="export_options[]" value="featured_image" id="featured-image-toggle">
                            <strong>Featured Image</strong>
                        </label>
                        <p style="margin: 10px 0 0 25px; font-size: 13px; color: #666;">Export the URL of each post's featured image.</p>
                    </div>

                    <div class="amfm-form-actions">
                        <button type="submit" name="amfm_export" class="button button-primary amfm-primary-btn">
                            Export to CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>
    `;
}

/**
 * Generate keywords import form
 */
function generateKeywordsForm(keywordsNonce) {
    return `
        <div class="amfm-drawer-section">
            <h3>Import Keywords from CSV</h3>
            <p class="amfm-drawer-description">Import keywords from CSV files to assign to posts via ACF fields with batch processing support.</p>
            
            <div class="amfm-info-box">
                <h4>CSV Format Requirements</h4>
                <p>Your CSV file should include these columns:</p>
                <ul class="amfm-requirements-list">
                    <li><strong>post_id</strong> - The ID of the post to update</li>
                    <li><strong>keywords</strong> - Comma-separated keywords (for amfm_keywords field)</li>
                </ul>
            </div>
            
            <form method="post" enctype="multipart/form-data" class="amfm-modern-form">
                ${keywordsNonce}
                <div class="amfm-form-group">
                    <label for="keywords_csv_file">Choose CSV File</label>
                    <input type="file" id="keywords_csv_file" name="csv_file" accept=".csv" required>
                </div>
                <div class="amfm-form-actions">
                    <button type="submit" name="amfm_import_keywords" class="button button-primary amfm-primary-btn">
                        Import Keywords
                    </button>
                </div>
            </form>
        </div>
    `;
}

/**
 * Generate categories import form
 */
function generateCategoriesForm(categoriesNonce) {
    return `
        <div class="amfm-drawer-section">
            <h3>Import Categories from CSV</h3>
            <p class="amfm-drawer-description">Import categories from CSV files to assign to posts with automatic category creation support.</p>
            
            <div class="amfm-info-box">
                <h4>CSV Format Requirements</h4>
                <p>Your CSV file should include these columns:</p>
                <ul class="amfm-requirements-list">
                    <li><strong>id</strong> - Post ID to assign category to</li>
                    <li><strong>Categories</strong> - Category name to assign to the post</li>
                </ul>
            </div>
            
            <form method="post" enctype="multipart/form-data" class="amfm-modern-form">
                ${categoriesNonce}
                <div class="amfm-form-group">
                    <label for="categories_csv_file">Choose CSV File</label>
                    <input type="file" id="categories_csv_file" name="category_csv_file" accept=".csv" required>
                </div>
                <div class="amfm-form-actions">
                    <button type="submit" name="amfm_import_categories" class="button button-primary amfm-primary-btn">
                        Import Categories
                    </button>
                </div>
            </form>
        </div>
    `;
}

/**
 * Open import/export drawer
 */
function openImportExportDrawer(toolType) {
    const drawer = document.getElementById('amfm-import-export-drawer');
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (importExportData[toolType]) {
        const data = importExportData[toolType];
        
        title.innerHTML = data.icon + ' ' + data.name;
        body.innerHTML = data.content;
        
        drawer.classList.add('amfm-drawer-open');
        document.body.style.overflow = 'hidden';
        
        // Re-initialize export form if needed
        if (toolType === 'export') {
            initializeExportForm();
        }
    }
}

/**
 * Close import/export drawer
 */
function closeImportExportDrawer() {
    const drawer = document.getElementById('amfm-import-export-drawer');
    drawer.classList.remove('amfm-drawer-open');
    document.body.style.overflow = '';
}

/**
 * Initialize export form functionality
 */
function initializeExportForm() {
    const exportPostTypeSelect = document.getElementById('export_post_type');
    if (exportPostTypeSelect) {
        exportPostTypeSelect.addEventListener('change', handlePostTypeSelection);
    }
    
    // Handle form submission
    const exportForm = document.getElementById('amfm_export_form');
    if (exportForm) {
        exportForm.addEventListener('submit', handleExportFormSubmission);
    }
    
    // Handle export options checkboxes
    const exportOptionsCheckboxes = document.querySelectorAll('input[name="export_options[]"]');
    exportOptionsCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', handleExportOptionToggle);
    });
    
    // Handle taxonomy selection radio buttons
    const taxonomyRadios = document.querySelectorAll('input[name="taxonomy_selection"]');
    taxonomyRadios.forEach(radio => {
        radio.addEventListener('change', handleTaxonomySelectionToggle);
    });
    
    // Handle ACF selection radio buttons  
    const acfRadios = document.querySelectorAll('input[name="acf_selection"]');
    acfRadios.forEach(radio => {
        radio.addEventListener('change', handleAcfSelectionToggle);
    });
}

/**
 * Handle post type selection change
 */
function handlePostTypeSelection() {
    const postType = this.value;
    const exportOptions = document.querySelector('.export-options');
    
    if (postType) {
        exportOptions.style.display = 'block';
        
        // Load taxonomies for selected post type
        fetch(amfmImportExport.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'amfm_get_post_type_taxonomies',
                post_type: postType,
                nonce: amfmImportExport.export_nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const taxonomyCheckboxes = document.getElementById('taxonomy-checkboxes');
                taxonomyCheckboxes.innerHTML = '';
                
                if (data.data.length > 0) {
                    data.data.forEach(taxonomy => {
                        const label = document.createElement('label');
                        label.innerHTML = `<input type="checkbox" name="specific_taxonomies[]" value="${taxonomy.name}"> <span>${taxonomy.label}</span>`;
                        taxonomyCheckboxes.appendChild(label);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading taxonomies:', error);
        });
    } else {
        exportOptions.style.display = 'none';
    }
}

/**
 * Handle export option toggle (show/hide sections)
 */
function handleExportOptionToggle() {
    const option = this.value;
    const isChecked = this.checked;
    
    switch(option) {
        case 'post_columns':
            const postColumnsSection = document.getElementById('post-columns-section');
            if (postColumnsSection) {
                postColumnsSection.style.display = isChecked ? 'block' : 'none';
            }
            break;
        case 'taxonomies':
            const taxonomySection = document.getElementById('taxonomy-section');
            if (taxonomySection) {
                taxonomySection.style.display = isChecked ? 'block' : 'none';
            }
            break;
        case 'acf_fields':
            const acfSection = document.getElementById('acf-section');
            if (acfSection) {
                acfSection.style.display = isChecked ? 'block' : 'none';
            }
            break;
    }
}

/**
 * Handle taxonomy selection toggle (all vs specific)
 */
function handleTaxonomySelectionToggle() {
    const taxonomyCheckboxes = document.getElementById('taxonomy-checkboxes');
    if (this.value === 'selected') {
        taxonomyCheckboxes.style.display = 'block';
    } else {
        taxonomyCheckboxes.style.display = 'none';
    }
}

/**
 * Handle ACF selection toggle (all vs specific)
 */
function handleAcfSelectionToggle() {
    const acfGroupsCheckboxes = document.getElementById('acf-groups-checkboxes');
    
    if (this.value === 'selected') {
        // Show field group checkboxes when "Select Specific Field Groups" is chosen
        acfGroupsCheckboxes.style.display = 'block';
    } else {
        // Hide field group checkboxes when "Export All ACF Fields" is chosen
        acfGroupsCheckboxes.style.display = 'none';
    }
}

/**
 * Handle export form submission
 */
function handleExportFormSubmission(e) {
    e.preventDefault();
    
    const exportBtn = e.target.querySelector('button[type="submit"]');
    if (!exportBtn) return;
    
    // Show loading state
    exportBtn.disabled = true;
    const originalText = exportBtn.textContent;
    exportBtn.textContent = 'Exporting...';
    
    // Collect form data
    const formData = new FormData(e.target);
    formData.append('action', 'amfm_export_data');
    formData.append('nonce', amfmImportExport.export_nonce);
    
    // Make AJAX request
    fetch(amfmImportExport.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create and download CSV file
            downloadCSV(data.data.data, data.data.filename);
            
            // Show success message
            showNotice('Export completed successfully! ' + data.data.total + ' posts exported.', 'success');
        } else {
            showNotice('Export failed: ' + (data.data || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Export error:', error);
        showNotice('Export failed due to a network error. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        exportBtn.disabled = false;
        exportBtn.textContent = originalText;
    });
}

/**
 * Function to convert array to CSV and download
 */
function downloadCSV(data, filename) {
    let csvContent = '';
    
    data.forEach(row => {
        const escapedRow = row.map(field => {
            // Convert to string and escape quotes
            let stringField = String(field || '');
            if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n')) {
                stringField = '"' + stringField.replace(/"/g, '""') + '"';
            }
            return stringField;
        });
        csvContent += escapedRow.join(',') + '\n';
    });
    
    // Create download link
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Function to show admin notices
 */
function showNotice(message, type) {
    const notice = document.createElement('div');
    notice.className = 'notice notice-' + type + ' is-dismissible';
    notice.innerHTML = '<p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
    
    const drawer = document.getElementById('amfm-import-export-drawer');
    const drawerBody = drawer ? drawer.querySelector('#amfm-drawer-body') : null;
    const container = drawerBody || document.querySelector('.amfm-container') || document.body;
    
    if (container) {
        container.insertBefore(notice, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (notice.parentNode) {
                notice.parentNode.removeChild(notice);
            }
        }, 5000);
        
        // Handle dismiss button
        const dismissBtn = notice.querySelector('.notice-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                notice.parentNode.removeChild(notice);
            });
        }
    }
}