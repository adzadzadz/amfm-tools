<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'import-export';
$post_types_options = $post_types_options ?? '';
$acf_field_groups = $acf_field_groups ?? '';
$all_taxonomies = $all_taxonomies ?? '';
$has_acf = $has_acf ?? false;
$export_nonce = $export_nonce ?? '';
$import_nonce = $import_nonce ?? '';
$show_results = $show_results ?? false;
$results = $results ?? null;
?>

<style>
/* Import/Export single column layout */
.amfm-import-export-single-column {
    display: flex;
    flex-direction: column;
    gap: 30px;
    margin: 40px 0;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.amfm-import-export-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #e1e5e9;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.amfm-import-export-card:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.amfm-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
}

.amfm-card-icon {
    font-size: 2.5rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.amfm-card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #2c3e50;
}

.amfm-card-body {
    margin-top: 20px;
}

.amfm-card-description {
    font-size: 1rem;
    line-height: 1.6;
    color: #5a6c7d;
    margin: 0 0 30px 0;
}

/* Form styles */
.amfm-form {
    padding: 0;
}

.amfm-form-group {
    margin-bottom: 25px;
}

.amfm-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #2c3e50;
}

.amfm-form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
}

.amfm-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.amfm-checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    cursor: pointer;
}

.amfm-radio-group {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.amfm-radio-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.amfm-form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    text-align: center;
}

.button-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    padding: 12px 30px !important;
    font-size: 1rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    min-width: 150px !important;
    color: white !important;
    transition: all 0.3s ease !important;
}

.button-primary:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%) !important;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4) !important;
    transform: translateY(-1px) !important;
}

/* File upload styling */
.amfm-file-upload-wrapper {
    position: relative;
    display: block;
}

.amfm-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
}

.amfm-file-upload-display {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    background: #f9fafb;
    transition: all 0.3s ease;
    cursor: pointer;
    min-height: 80px;
    width: 100%;
    box-sizing: border-box;
}

.amfm-file-upload-display:hover {
    border-color: #667eea;
    background: #f0f9ff;
}

.amfm-file-upload-wrapper.dragover .amfm-file-upload-display {
    border-color: #667eea;
    background: #e0f2fe;
    transform: scale(1.02);
}

.amfm-file-upload-wrapper.file-selected .amfm-file-upload-display {
    border-color: #10b981;
    background: #ecfdf5;
}

.amfm-file-upload-icon {
    font-size: 2rem;
    color: #667eea;
    flex-shrink: 0;
}

.amfm-file-upload-text {
    flex-grow: 1;
}

.amfm-file-placeholder {
    display: block;
    color: #6b7280;
    font-size: 1rem;
    font-weight: 500;
}

.amfm-file-selected {
    display: block;
    color: #059669;
    font-weight: 600;
    margin-top: 5px;
}

/* File selection status styling */
.amfm-file-selection-status {
    margin-top: 15px;
    padding: 0;
}

.amfm-selected-file-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f0f9ff;
    border: 2px solid #10b981;
    border-radius: 8px;
    position: relative;
}

.amfm-file-icon {
    font-size: 1.5rem;
    color: #10b981;
    flex-shrink: 0;
}

.amfm-file-details {
    flex-grow: 1;
}

.amfm-file-name {
    font-weight: 600;
    color: #065f46;
    font-size: 1rem;
    margin-bottom: 2px;
}

.amfm-file-size {
    font-size: 0.875rem;
    color: #6b7280;
}

.amfm-remove-file {
    background: none;
    border: none;
    color: #dc2626;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.amfm-remove-file:hover {
    background: #fee2e2;
    color: #991b1b;
}

/* Import results styling */
.amfm-import-results {
    margin-top: 30px;
    padding: 25px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.amfm-import-results h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 1.25rem;
    font-weight: 600;
}

.amfm-import-success {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}

.amfm-import-error {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

.amfm-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #6b7280;
}

.amfm-loading-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="amfm-import-export-single-column">
    <!-- Export Section -->
    <div class="amfm-import-export-card">
        <div class="amfm-card-header">
            <div class="amfm-card-icon">📤</div>
            <h2 class="amfm-card-title">Export Data</h2>
        </div>
        <div class="amfm-card-body">
            <p class="amfm-card-description">Export your posts, pages, and custom post types with their metadata to CSV format for backup or migration purposes.</p>
            
            <form method="post" action="" class="amfm-form" id="amfm-export-form">
                <input type="hidden" name="amfm_export_nonce" value="<?php echo esc_attr($export_nonce); ?>" />
                
                <div class="amfm-form-group">
                    <label for="export_post_type">Select Post Type:</label>
                    <select name="export_post_type" id="export_post_type" required>
                        <option value="">Select a post type</option>
                        <?php echo $post_types_options; ?>
                    </select>
                </div>

                <div class="amfm-form-group amfm-export-options" style="display:none;">
                    <label>Export Options:</label>
                    <div class="amfm-checkbox-grid">
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="post_data">
                            <span>Include Post Data</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="taxonomies">
                            <span>Include Taxonomies</span>
                        </label>
                        <?php if ($has_acf): ?>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="acf_fields">
                            <span>Include ACF Fields</span>
                        </label>
                        <?php endif; ?>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="export_options[]" value="featured_image">
                            <span>Include Featured Image URL</span>
                        </label>
                    </div>
                </div>

                <div class="amfm-form-group amfm-post-data-selection" style="display:none;">
                    <label>Post Data Selection:</label>
                    <div class="amfm-radio-group">
                        <label class="amfm-radio-item">
                            <input type="radio" name="post_data_selection" value="all">
                            <span>All Post Columns</span>
                        </label>
                        <label class="amfm-radio-item">
                            <input type="radio" name="post_data_selection" value="selected">
                            <span>Select Specific Columns</span>
                        </label>
                    </div>
                    <div class="amfm-specific-post-columns amfm-checkbox-grid" style="display:none;">
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_title">
                            <span>Post Title</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_content">
                            <span>Post Content</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_excerpt">
                            <span>Post Excerpt</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_status">
                            <span>Post Status</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_date">
                            <span>Post Date</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_modified">
                            <span>Post Modified Date</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_author">
                            <span>Post Author</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_name">
                            <span>Post Slug</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="menu_order">
                            <span>Menu Order</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="comment_status">
                            <span>Comment Status</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="ping_status">
                            <span>Ping Status</span>
                        </label>
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_parent">
                            <span>Post Parent</span>
                        </label>
                    </div>
                </div>

                <div class="amfm-form-group amfm-taxonomy-selection" style="display:none;">
                    <label>Taxonomy Selection:</label>
                    <div class="amfm-radio-group">
                        <label class="amfm-radio-item">
                            <input type="radio" name="taxonomy_selection" value="all">
                            <span>All Taxonomies</span>
                        </label>
                        <label class="amfm-radio-item">
                            <input type="radio" name="taxonomy_selection" value="selected">
                            <span>Select Specific Taxonomies</span>
                        </label>
                    </div>
                    <div class="amfm-specific-taxonomies amfm-checkbox-grid" style="display:none;">
                        <?php echo $all_taxonomies; ?>
                    </div>
                </div>

                <?php if ($has_acf): ?>
                <div class="amfm-form-group amfm-acf-selection" style="display:none;">
                    <label>ACF Field Selection:</label>
                    <div class="amfm-radio-group">
                        <label class="amfm-radio-item">
                            <input type="radio" name="acf_selection" value="all">
                            <span>All ACF Fields</span>
                        </label>
                        <label class="amfm-radio-item">
                            <input type="radio" name="acf_selection" value="selected">
                            <span>Select Specific Field Groups</span>
                        </label>
                    </div>
                    <div class="amfm-specific-acf-groups amfm-checkbox-grid" style="display:none;">
                        <?php echo $acf_field_groups; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="amfm-form-actions">
                    <button type="submit" name="amfm_export" value="1" class="button button-primary">
                        Export to CSV
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Section -->
    <div class="amfm-import-export-card">
        <div class="amfm-card-header">
            <div class="amfm-card-icon">📥</div>
            <h2 class="amfm-card-title">Import Data</h2>
        </div>
        <div class="amfm-card-body">
            <p class="amfm-card-description">Import data from CSV files to update posts with content, taxonomies, ACF fields, and other metadata seamlessly.</p>
            
            <form method="post" action="" enctype="multipart/form-data" class="amfm-form" id="amfm-import-form">
                <input type="hidden" name="amfm_csv_import_nonce" value="<?php echo esc_attr($import_nonce); ?>" />
                
                <div class="amfm-form-group">
                    <div class="amfm-file-upload-wrapper">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="amfm-file-input">
                        <label for="csv_file" class="amfm-file-upload-display">
                            <div class="amfm-file-upload-icon">📎</div>
                            <div class="amfm-file-upload-text">
                                <span class="amfm-file-placeholder">Choose CSV file or drag & drop here</span>
                            </div>
                        </label>
                    </div>
                    <!-- File selection status - appears below the upload box -->
                    <div class="amfm-file-selection-status" style="display:none;">
                        <div class="amfm-selected-file-info">
                            <span class="amfm-file-icon">📄</span>
                            <div class="amfm-file-details">
                                <div class="amfm-file-name"></div>
                                <div class="amfm-file-size"></div>
                            </div>
                            <button type="button" class="amfm-remove-file" title="Remove file">✖</button>
                        </div>
                    </div>
                </div>

                <div class="amfm-form-actions">
                    <button type="submit" class="button button-primary" id="amfm-import-submit">
                        Import Data
                    </button>
                </div>
            </form>
            
            <!-- Import Results Section -->
            <div id="amfm-import-results" class="amfm-import-results" style="display:none;">
                <h3>Import Results</h3>
                <div id="amfm-import-results-content"></div>
            </div>
        </div>
    </div>
</div>

<?php if ($show_results && $results): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show import results if available
    const resultsDiv = document.getElementById('amfm-import-results');
    const resultsContent = document.getElementById('amfm-import-results-content');
    
    if (resultsDiv && resultsContent) {
        resultsDiv.style.display = 'block';
        resultsContent.innerHTML = '<pre><?php echo esc_js(json_encode($results, JSON_PRETTY_PRINT)); ?></pre>';
    }
});
</script>
<?php endif; ?>

<script>
// Inline initialization as fallback
document.addEventListener('DOMContentLoaded', function() {
    console.log('AMFM Export: Inline initialization');
    
    // Wait for jQuery to be available
    function initWhenReady() {
        if (window.jQuery) {
            const $ = window.jQuery;
            console.log('AMFM Export: jQuery available, setting up handlers');
            
            // Export post type change handler
            $('#export_post_type').on('change', function() {
                const postType = $(this).val();
                console.log('AMFM Export: Post type changed to:', postType);
                
                if (postType) {
                    $('.amfm-export-options').show();
                    console.log('AMFM Export: Showing export options');
                    
                    // Don't auto-check options - ensure sub-options are hidden
                    $('.amfm-post-data-selection').hide();
                    $('.amfm-taxonomy-selection').hide();
                    $('.amfm-acf-selection').hide();
                } else {
                    $('.amfm-export-options').hide();
                    $('.amfm-post-data-selection').hide();
                    $('.amfm-taxonomy-selection').hide();
                    $('.amfm-acf-selection').hide();
                    $('input[name="export_options[]"]').prop('checked', false);
                }
            });
            
            // Add handlers for export option checkboxes
            $('input[name="export_options[]"][value="post_data"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.amfm-post-data-selection').show();
                } else {
                    $('.amfm-post-data-selection').hide();
                }
            });
            
            $('input[name="export_options[]"][value="taxonomies"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.amfm-taxonomy-selection').show();
                } else {
                    $('.amfm-taxonomy-selection').hide();
                }
            });
            
            $('input[name="export_options[]"][value="acf_fields"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.amfm-acf-selection').show();
                } else {
                    $('.amfm-acf-selection').hide();
                }
            });
            
            // Add handlers for sub-selection radio buttons
            $('input[name="post_data_selection"]').on('change', function() {
                if ($(this).val() === 'selected') {
                    $('.amfm-specific-post-columns').show();
                } else {
                    $('.amfm-specific-post-columns').hide();
                }
            });
            
            $('input[name="taxonomy_selection"]').on('change', function() {
                if ($(this).val() === 'selected') {
                    $('.amfm-specific-taxonomies').show();
                } else {
                    $('.amfm-specific-taxonomies').hide();
                }
            });
            
            $('input[name="acf_selection"]').on('change', function() {
                if ($(this).val() === 'selected') {
                    $('.amfm-specific-acf-groups').show();
                } else {
                    $('.amfm-specific-acf-groups').hide();
                }
            });
            
            // Trigger change if post type already selected
            if ($('#export_post_type').val()) {
                $('#export_post_type').trigger('change');
            }
            
            // Import file handling
            const fileInput = $('#csv_file');
            const fileWrapper = $('.amfm-file-upload-wrapper');
            const filePlaceholder = $('.amfm-file-placeholder');
            const fileStatus = $('.amfm-file-selection-status');
            const fileName = $('.amfm-file-name');
            const fileSize = $('.amfm-file-size');
            
            // Format file size utility
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            // Handle file selection
            fileInput.on('change', function() {
                const file = this.files[0];
                console.log('File selected:', file ? file.name : 'none');
                if (file) {
                    // Update file display
                    filePlaceholder.text('File selected - choose a different file or drag & drop to replace');
                    fileWrapper.addClass('file-selected');
                    
                    // Show file details below
                    fileName.text(file.name);
                    fileSize.text(formatFileSize(file.size));
                    fileStatus.show();
                } else {
                    // Reset to initial state
                    filePlaceholder.text('Choose CSV file or drag & drop here');
                    fileWrapper.removeClass('file-selected');
                    fileStatus.hide();
                }
            });
            
            // Handle remove file button
            $('.amfm-remove-file').on('click', function() {
                fileInput.val('');
                fileInput.trigger('change');
            });
            
            // Inline display functions for CSV table and batch import
            function displayCsvTableInline(csvData, container) {
                const headers = csvData.headers;
                const rows = csvData.rows;
                
                let html = '<div class="amfm-csv-preview">';
                html += '<h3>CSV Preview (' + rows.length + ' rows)</h3>';
                html += '<div class="amfm-csv-table-wrapper" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;">';
                html += '<table class="amfm-csv-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">';
                
                // Headers
                html += '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">';
                html += '<tr>';
                html += '<th style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Status</th>';
                headers.forEach(header => {
                    html += '<th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">' + header + '</th>';
                });
                // Only add Post Title column if it doesn't already exist in headers
                if (!headers.includes('Post Title')) {
                    html += '<th style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Post Title</th>';
                }
                html += '</tr>';
                html += '</thead>';
                
                // Rows
                html += '<tbody>';
                rows.forEach(function(row) {
                    html += '<tr data-row-number="' + row.row_number + '" data-post-id="' + row.post_id + '">';
                    html += '<td class="status-cell" style="padding: 8px; border: 1px solid #ddd; width: 80px; text-align: center;">';
                    html += '<span class="status-badge status-pending" style="padding: 2px 8px; border-radius: 12px; font-size: 11px; background: #ffc107; color: #000;">Pending</span>';
                    html += '</td>';
                    
                    headers.forEach(header => {
                        const cellValue = row.data[header] || '';
                        html += '<td style="padding: 8px; border: 1px solid #ddd; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="' + cellValue + '">' + cellValue + '</td>';
                    });
                    
                    // Only add separate Post Title column if it doesn't already exist in headers
                    if (!headers.includes('Post Title')) {
                        html += '<td style="padding: 8px; border: 1px solid #ddd; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' + row.post_title + '</td>';
                    }
                    html += '</tr>';
                });
                html += '</tbody>';
                html += '</table>';
                html += '</div>';
                html += '</div>';
                
                container.html(html);
            }
            
            function startBatchImportInline(csvData, container) {
                const batchSize = 10;
                const rows = csvData.rows;
                const headers = csvData.headers;
                let currentBatch = 0;
                let totalBatches = Math.ceil(rows.length / batchSize);
                let processedRows = 0;
                let successCount = 0;
                let errorCount = 0;
                let skippedCount = 0;
                let importStopped = false;
                
                // Add progress summary above table
                const progressHtml = '<div class="amfm-import-progress" style="margin-bottom: 20px; padding: 15px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #2196F3;">' +
                    '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">' +
                    '<h4 style="margin: 0;">Import Progress</h4>' +
                    '<button id="amfm-stop-import" class="button button-secondary" style="background: #dc3545; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer;">Stop Import</button>' +
                    '</div>' +
                    '<div class="progress-stats">' +
                    '<span class="processed-count">Processed: <strong>0</strong> / <strong>' + rows.length + '</strong></span> | ' +
                    '<span class="success-count">Updated: <strong>0</strong></span> | ' +
                    '<span class="skipped-count">Skipped: <strong>0</strong></span> | ' +
                    '<span class="error-count">Errors: <strong>0</strong></span>' +
                    '</div>' +
                    '<div class="progress-bar-wrapper" style="margin-top: 10px; background: #e0e0e0; border-radius: 10px; height: 20px;">' +
                    '<div class="progress-bar" style="background: linear-gradient(90deg, #4CAF50, #2196F3); height: 100%; border-radius: 10px; width: 0%; transition: width 0.3s ease;"></div>' +
                    '</div>' +
                    '</div>';
                
                container.prepend(progressHtml);
                
                // Add stop button event handler
                container.find('#amfm-stop-import').on('click', function() {
                    importStopped = true;
                    $(this).prop('disabled', true).text('Stopping...');
                    
                    // Show stopped message
                    const stoppedHtml = '<div class="amfm-stopped-message" style="margin-top: 15px; padding: 12px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; color: #856404;">' +
                        '<strong>⏹️ Import Stopped</strong> - Import was stopped by user. Processed <strong>' + processedRows + '</strong> rows before stopping.' +
                        '</div>';
                    container.find('.amfm-import-progress').after(stoppedHtml);
                    
                    // Re-enable the import button
                    $('#amfm-import-submit').prop('disabled', false);
                });
                
                function processBatch() {
                    // Check if import was stopped
                    if (importStopped) {
                        return;
                    }
                    
                    const startIdx = currentBatch * batchSize;
                    const endIdx = Math.min(startIdx + batchSize, rows.length);
                    const batchRows = rows.slice(startIdx, endIdx);
                    
                    console.log('Processing batch', currentBatch + 1, 'of', totalBatches, '- rows', startIdx + 1, 'to', endIdx);
                    
                    // Update status to "Saving" for current batch
                    batchRows.forEach(row => {
                        const rowElement = container.find('tr[data-row-number="' + row.row_number + '"]');
                        const statusCell = rowElement.find('.status-cell .status-badge');
                        statusCell.removeClass('status-pending').addClass('status-saving')
                            .css({background: '#17a2b8', color: 'white'})
                            .text('Saving...');
                    });
                    
                    // Send batch to server
                    const ajaxUrlForBatch = window.amfmData?.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php';
                    $.ajax({
                        url: ajaxUrlForBatch,
                        type: 'POST',
                        data: {
                            action: 'amfm_csv_import_batch',
                            amfm_csv_import_nonce: $('input[name="amfm_csv_import_nonce"]').val(),
                            batch_data: JSON.stringify({
                                headers: headers,
                                rows: batchRows
                            })
                        },
                        timeout: 30000,
                        success: function(response) {
                            console.log('Batch response:', response);
                            if (response.success) {
                                const results = response.data;
                                
                                // Update status for each processed row
                                results.processed_rows.forEach(processedRow => {
                                    const rowElement = container.find('tr[data-row-number="' + processedRow.row_number + '"]');
                                    const statusCell = rowElement.find('.status-cell .status-badge');
                                    
                                    if (processedRow.status === 'completed') {
                                        statusCell.removeClass('status-saving').addClass('status-completed')
                                            .css({background: '#28a745', color: 'white'})
                                            .text('Updated');
                                        successCount++;
                                    } else if (processedRow.status === 'skipped') {
                                        statusCell.removeClass('status-saving').addClass('status-skipped')
                                            .css({background: '#6c757d', color: 'white'})
                                            .text('Skipped')
                                            .attr('title', processedRow.message);
                                        skippedCount++;
                                    } else {
                                        statusCell.removeClass('status-saving').addClass('status-error')
                                            .css({background: '#dc3545', color: 'white'})
                                            .text('Error')
                                            .attr('title', processedRow.message);
                                        errorCount++;
                                    }
                                    processedRows++;
                                });
                                
                                // Update progress
                                updateProgress();
                                
                                // Process next batch
                                currentBatch++;
                                if (currentBatch < totalBatches && !importStopped) {
                                    setTimeout(processBatch, 500);
                                } else {
                                    onImportComplete();
                                }
                                
                            } else {
                                console.error('Batch failed:', response.data);
                                // Mark all rows in this batch as error
                                batchRows.forEach(row => {
                                    const rowElement = container.find('tr[data-row-number="' + row.row_number + '"]');
                                    const statusCell = rowElement.find('.status-cell .status-badge');
                                    statusCell.removeClass('status-saving').addClass('status-error')
                                        .css({background: '#dc3545', color: 'white'})
                                        .text('Error')
                                        .attr('title', response.data || 'Batch processing failed');
                                    errorCount++;
                                    processedRows++;
                                });
                                
                                updateProgress();
                                currentBatch++;
                                if (currentBatch < totalBatches && !importStopped) {
                                    setTimeout(processBatch, 500);
                                } else {
                                    onImportComplete();
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Batch AJAX error:', {status, error, xhr});
                            // Mark all rows in this batch as error
                            batchRows.forEach(row => {
                                const rowElement = container.find('tr[data-row-number="' + row.row_number + '"]');
                                const statusCell = rowElement.find('.status-cell .status-badge');
                                statusCell.removeClass('status-saving').addClass('status-error')
                                    .css({background: '#dc3545', color: 'white'})
                                    .text('Error')
                                    .attr('title', 'Network error: ' + error);
                                errorCount++;
                                processedRows++;
                            });
                            
                            updateProgress();
                            currentBatch++;
                            if (currentBatch < totalBatches && !importStopped) {
                                setTimeout(processBatch, 500);
                            } else {
                                onImportComplete();
                            }
                        }
                    });
                }
                
                function updateProgress() {
                    const progressPercent = Math.round((processedRows / rows.length) * 100);
                    container.find('.processed-count strong').first().text(processedRows);
                    container.find('.success-count strong').text(successCount);
                    container.find('.skipped-count strong').text(skippedCount);
                    container.find('.error-count strong').text(errorCount);
                    container.find('.progress-bar').css('width', progressPercent + '%');
                }
                
                function onImportComplete() {
                    $('#amfm-import-submit').prop('disabled', false);
                    
                    // Hide stop button since import is done
                    container.find('#amfm-stop-import').hide();
                    
                    // Show completion message
                    let completionHtml;
                    if (importStopped) {
                        completionHtml = '<div class="amfm-completion-message" style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; color: #856404;">' +
                            '<h4 style="margin: 0 0 10px 0;">⏹️ Import Stopped</h4>' +
                            '<p style="margin: 0;">Import was stopped by user. Processed <strong>' + processedRows + '</strong> rows with <strong>' + successCount + '</strong> updates, <strong>' + skippedCount + '</strong> skipped (same values), and <strong>' + errorCount + '</strong> errors before stopping.</p>' +
                            '</div>';
                    } else {
                        completionHtml = '<div class="amfm-completion-message" style="margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">' +
                            '<h4 style="margin: 0 0 10px 0;">✅ Import Completed!</h4>' +
                            '<p style="margin: 0;">Successfully processed <strong>' + processedRows + '</strong> rows with <strong>' + successCount + '</strong> updates, <strong>' + skippedCount + '</strong> skipped (same values), and <strong>' + errorCount + '</strong> errors.</p>' +
                            '</div>';
                    }
                    
                    container.find('.amfm-csv-preview').after(completionHtml);
                }
                
                // Start processing
                setTimeout(processBatch, 1000);
            }
            
            // Import form submission
            $('#amfm-import-form').on('submit', function(e) {
                e.preventDefault();
                console.log('Import form submitted');
                
                const formData = new FormData(this);
                const submitButton = $('#amfm-import-submit');
                const resultsSection = $('#amfm-import-results');
                const resultsContent = $('#amfm-import-results-content');
                
                // Show loading state
                submitButton.prop('disabled', true).text('Loading CSV...');
                resultsSection.show();
                resultsContent.html('<div class="amfm-loading"><div class="amfm-loading-spinner"></div>Reading CSV file...</div>');
                
                // First, get CSV preview to show table
                formData.append('action', 'amfm_csv_preview');
                
                // Try to get ajax URL
                const ajaxUrl = window.amfmData?.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php';
                console.log('Using AJAX URL:', ajaxUrl);
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 30000,
                    success: function(response) {
                        console.log('Preview response:', response);
                        if (response.success) {
                            // Use inline display functions as fallback
                            displayCsvTableInline(response.data, resultsContent);
                            startBatchImportInline(response.data, resultsContent);
                            submitButton.text('Import Data');
                        } else {
                            resultsSection.removeClass('amfm-import-success').addClass('amfm-import-error');
                            resultsContent.html('<p><strong>Preview Failed:</strong> ' + (response.data || 'Unknown error occurred.') + '</p>');
                            submitButton.prop('disabled', false).text('Import Data');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', {status, error, xhr});
                        resultsSection.removeClass('amfm-import-success').addClass('amfm-import-error');
                        resultsContent.html('<p><strong>Preview Failed:</strong> ' + error + '</p>');
                        submitButton.prop('disabled', false).text('Import Data');
                    }
                });
            });
            
        } else {
            console.log('AMFM Export: jQuery not ready, retrying...');
            setTimeout(initWhenReady, 100);
        }
    }
    
    initWhenReady();
});
</script>