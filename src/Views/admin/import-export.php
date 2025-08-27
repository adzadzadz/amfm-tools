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
                            <input type="radio" name="post_data_selection" value="all" checked>
                            <span>All Post Columns</span>
                        </label>
                        <label class="amfm-radio-item">
                            <input type="radio" name="post_data_selection" value="selected">
                            <span>Select Specific Columns</span>
                        </label>
                    </div>
                    <div class="amfm-specific-post-columns amfm-checkbox-grid" style="display:none;">
                        <label class="amfm-checkbox-item">
                            <input type="checkbox" name="specific_post_columns[]" value="post_title" checked>
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
                            <input type="radio" name="taxonomy_selection" value="all" checked>
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
                            <input type="radio" name="acf_selection" value="all" checked>
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
                                <span class="amfm-file-selected" style="display:none;"></span>
                            </div>
                        </label>
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