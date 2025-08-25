<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'import-export';
$show_results = $show_results ?? false;
$show_category_results = $show_category_results ?? false;
$results = $results ?? null;
$category_results = $category_results ?? null;
$post_types = $post_types ?? [];
$selected_post_type = $selected_post_type ?? '';
$post_type_taxonomies = $post_type_taxonomies ?? [];
$all_field_groups = $all_field_groups ?? [];

// Determine current sub-tab from URL
$current_subtab = $_GET['subtab'] ?? 'export';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Enhanced Header -->
        <div class="amfm-header">
            <div class="amfm-header-content">
                <div class="amfm-header-main">
                    <div class="amfm-header-logo">
                        <span class="amfm-icon">🛠️</span>
                    </div>
                    <div class="amfm-header-text">
                        <h1>AMFM Tools</h1>
                        <p class="amfm-subtitle">Advanced Features Management</p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($show_results || $show_category_results) : ?>
            <!-- Import Results Section -->
            <div class="amfm-results-section">
                <?php if ($show_results && $results) : ?>
                <h2>Import Results</h2>
                
                <div class="amfm-stats">
                    <div class="amfm-stat amfm-stat-success">
                        <div class="amfm-stat-number"><?php echo esc_html($results['success']); ?></div>
                        <div class="amfm-stat-label">Successful Updates</div>
                    </div>
                    <div class="amfm-stat amfm-stat-error">
                        <div class="amfm-stat-number"><?php echo esc_html($results['errors']); ?></div>
                        <div class="amfm-stat-label">Errors</div>
                    </div>
                </div>

                <?php if (!empty($results['details'])) : ?>
                    <div class="amfm-details">
                        <h3>Detailed Log</h3>
                        <div class="amfm-log">
                            <?php foreach ($results['details'] as $detail) : ?>
                                <div class="amfm-log-item">
                                    <?php echo esc_html($detail); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($show_category_results && $category_results) : ?>
                <h2>Category Import Results</h2>
                
                <div class="amfm-stats">
                    <div class="amfm-stat amfm-stat-success">
                        <div class="amfm-stat-number"><?php echo esc_html($category_results['success']); ?></div>
                        <div class="amfm-stat-label">Successful Assignments</div>
                    </div>
                    <div class="amfm-stat amfm-stat-error">
                        <div class="amfm-stat-number"><?php echo esc_html($category_results['errors']); ?></div>
                        <div class="amfm-stat-label">Errors</div>
                    </div>
                </div>

                <?php if (!empty($category_results['details'])) : ?>
                    <div class="amfm-details">
                        <h3>Detailed Log</h3>
                        <div class="amfm-log">
                            <?php foreach ($category_results['details'] as $detail) : ?>
                                <div class="amfm-log-item">
                                    <?php echo esc_html($detail); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="amfm-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export')); ?>" class="button button-primary">
                        Continue Import/Export
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Tabbed Interface -->
            <div class="amfm-tab-content">
                <!-- Tab Navigation -->
                <div class="amfm-subtabs-nav">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export&subtab=export')); ?>" 
                       class="amfm-subtab-link <?php echo $current_subtab === 'export' ? 'active' : ''; ?>">
                        <span class="amfm-subtab-icon">📤</span>
                        Export Data
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export&subtab=keywords')); ?>" 
                       class="amfm-subtab-link <?php echo $current_subtab === 'keywords' ? 'active' : ''; ?>">
                        <span class="amfm-subtab-icon">📥</span>
                        Import Keywords
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export&subtab=categories')); ?>" 
                       class="amfm-subtab-link <?php echo $current_subtab === 'categories' ? 'active' : ''; ?>">
                        <span class="amfm-subtab-icon">📋</span>
                        Import Categories
                    </a>
                </div>

                <!-- Tab Content -->
                <div class="amfm-subtab-content">
                    <?php if ($current_subtab === 'export') : ?>
                        <!-- Export Data Tab -->
                        <div class="amfm-import-export-section">
                            <div class="amfm-section-header">
                                <h2>
                                    <span class="amfm-section-icon">📤</span>
                                    Export Data
                                </h2>
                                <p>Export posts with ACF fields, taxonomies, and metadata to CSV format.</p>
                            </div>

                            <form method="post" action="" id="amfm_export_form">
                                <?php wp_nonce_field('amfm_export_nonce', 'amfm_export_nonce'); ?>
                                
                                <div class="export-section">
                                    <h3><?php esc_html_e('Select Post Type to Export', 'amfm-tools'); ?></h3>
                                    <select name="export_post_type" id="export_post_type" required style="width: 100%; padding: 8px; margin-bottom: 15px;">
                                        <option value=""><?php esc_html_e('Select a post type...', 'amfm-tools'); ?></option>
                                        <?php foreach ($post_types as $post_type): ?>
                                        <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($selected_post_type, $post_type->name); ?>>
                                            <?php echo esc_html($post_type->label); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div class="export-options" style="display: <?php echo $selected_post_type ? 'block' : 'none'; ?>;">
                                        <h3><?php esc_html_e('Export Options', 'amfm-tools'); ?></h3>
                                    
                                        <!-- Post Columns Options -->
                                        <div class="option-section" style="margin-bottom: 15px;">
                                            <h4><?php esc_html_e('Post Fields', 'amfm-tools'); ?></h4>
                                            <label><input type="checkbox" name="export_columns[]" value="ID" checked> ID</label><br>
                                            <label><input type="checkbox" name="export_columns[]" value="post_title" checked> Title</label><br>
                                            <label><input type="checkbox" name="export_columns[]" value="post_content"> Content</label><br>
                                            <label><input type="checkbox" name="export_columns[]" value="post_excerpt"> Excerpt</label><br>
                                            <label><input type="checkbox" name="export_columns[]" value="post_date" checked> Date</label><br>
                                            <label><input type="checkbox" name="export_columns[]" value="post_status" checked> Status</label><br>
                                            <label><input type="checkbox" name="export_columns[]" value="post_author"> Author</label><br>
                                        </div>

                                        <!-- Taxonomy Selection -->
                                        <div class="taxonomy-section" id="taxonomy-section" style="display: none; margin-bottom: 15px;">
                                            <h4><?php esc_html_e('Taxonomies', 'amfm-tools'); ?></h4>
                                            <div id="taxonomy-checkboxes">
                                                <!-- Dynamic content will be loaded here -->
                                            </div>
                                        </div>

                                        <!-- ACF Fields Selection -->
                                        <div class="acf-section" style="margin-bottom: 15px;">
                                            <h4><?php esc_html_e('ACF Fields', 'amfm-tools'); ?></h4>
                                            <div id="acf-checkboxes">
                                                <?php if (!empty($all_field_groups)) : ?>
                                                    <?php foreach ($all_field_groups as $group) : ?>
                                                        <div style="margin-bottom: 8px; font-weight: bold;"><?php echo esc_html($group['title']); ?></div>
                                                        <?php if (!empty($group['fields'])) : ?>
                                                            <?php foreach ($group['fields'] as $field) : ?>
                                                                <label style="margin-left: 20px;"><input type="checkbox" name="export_acf_fields[]" value="<?php echo esc_attr($field['name']); ?>"> <?php echo esc_html($field['label']); ?></label><br>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p><em>No ACF field groups found. Make sure ACF is active and has field groups configured.</em></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <button type="submit" name="amfm_export" class="button button-primary">
                                            <?php esc_html_e('Export to CSV', 'amfm-tools'); ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($current_subtab === 'keywords') : ?>
                        <!-- Import Keywords Tab -->
                        <div class="amfm-import-export-section">
                            <div class="amfm-section-header">
                                <h2>
                                    <span class="amfm-section-icon">📥</span>
                                    Import Keywords
                                </h2>
                                <p>Import keywords from CSV to assign to posts via ACF fields.</p>
                            </div>

                            <div class="import-instructions">
                                <h3>CSV Format Requirements</h3>
                                <p>Your CSV file should have the following columns:</p>
                                <ul>
                                    <li><strong>post_id</strong>: The ID of the post to update</li>
                                    <li><strong>keywords</strong>: Comma-separated keywords (for amfm_keywords field)</li>
                                    <li><strong>other_keywords</strong> (optional): Additional keywords (for amfm_other_keywords field)</li>
                                </ul>
                                <div class="csv-example">
                                    <h4>Example CSV content:</h4>
                                    <code>
                                        post_id,keywords,other_keywords<br>
                                        123,"keyword1,keyword2,keyword3","extra1,extra2"<br>
                                        456,"test,sample","additional"
                                    </code>
                                </div>
                            </div>

                            <form method="post" enctype="multipart/form-data" class="amfm-import-form">
                                <?php wp_nonce_field('amfm_keywords_import_nonce', 'amfm_keywords_import_nonce'); ?>
                                
                                <div class="upload-section">
                                    <h3>Upload CSV File</h3>
                                    <input type="file" name="keywords_csv_file" id="keywords_csv_file" accept=".csv" required>
                                    <p class="description">Maximum file size: 2MB</p>
                                </div>

                                <div class="import-options">
                                    <h3>Import Options</h3>
                                    <label>
                                        <input type="checkbox" name="overwrite_existing" value="1"> 
                                        Overwrite existing keywords
                                    </label>
                                    <p class="description">If unchecked, new keywords will be appended to existing ones.</p>
                                </div>

                                <button type="submit" name="amfm_import_keywords" class="button button-primary">
                                    Import Keywords
                                </button>
                            </form>
                        </div>

                    <?php elseif ($current_subtab === 'categories') : ?>
                        <!-- Import Categories Tab -->
                        <div class="amfm-import-export-section">
                            <div class="amfm-section-header">
                                <h2>
                                    <span class="amfm-section-icon">📋</span>
                                    Import Categories
                                </h2>
                                <p>Import category assignments from CSV to organize your content.</p>
                            </div>

                            <div class="import-instructions">
                                <h3>CSV Format Requirements</h3>
                                <p>Your CSV file should have the following columns:</p>
                                <ul>
                                    <li><strong>post_id</strong>: The ID of the post to update</li>
                                    <li><strong>categories</strong>: Comma-separated category names or IDs</li>
                                    <li><strong>taxonomy</strong> (optional): Taxonomy name (defaults to 'category')</li>
                                </ul>
                                <div class="csv-example">
                                    <h4>Example CSV content:</h4>
                                    <code>
                                        post_id,categories,taxonomy<br>
                                        123,"News,Technology,Updates",category<br>
                                        456,"Product Reviews","custom_taxonomy"
                                    </code>
                                </div>
                            </div>

                            <form method="post" enctype="multipart/form-data" class="amfm-import-form">
                                <?php wp_nonce_field('amfm_categories_import_nonce', 'amfm_categories_import_nonce'); ?>
                                
                                <div class="upload-section">
                                    <h3>Upload CSV File</h3>
                                    <input type="file" name="categories_csv_file" id="categories_csv_file" accept=".csv" required>
                                    <p class="description">Maximum file size: 2MB</p>
                                </div>

                                <div class="import-options">
                                    <h3>Import Options</h3>
                                    <label>
                                        <input type="checkbox" name="create_missing_categories" value="1" checked> 
                                        Create missing categories
                                    </label>
                                    <p class="description">If unchecked, missing categories will be skipped.</p>
                                    
                                    <label>
                                        <input type="checkbox" name="replace_categories" value="1"> 
                                        Replace existing categories
                                    </label>
                                    <p class="description">If unchecked, new categories will be added to existing ones.</p>
                                </div>

                                <button type="submit" name="amfm_import_categories" class="button button-primary">
                                    Import Categories
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Tab Navigation Styles */
.amfm-subtabs-nav {
    display: flex;
    border-bottom: 2px solid #e5e5e5;
    margin-bottom: 30px;
    background: #f9f9f9;
    border-radius: 6px 6px 0 0;
    overflow: hidden;
}

.amfm-subtab-link {
    display: flex;
    align-items: center;
    padding: 15px 25px;
    color: #666;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    font-weight: 500;
    background: #f9f9f9;
}

.amfm-subtab-link:hover {
    color: #0073aa;
    background: #fff;
}

.amfm-subtab-link.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
    background: #fff;
}

.amfm-subtab-icon {
    margin-right: 8px;
    font-size: 16px;
}

/* Content Section Styles */
.amfm-import-export-section {
    background: #fff;
    padding: 30px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.amfm-section-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.amfm-section-header h2 {
    display: flex;
    align-items: center;
    margin: 0 0 10px 0;
    color: #333;
}

.amfm-section-icon {
    margin-right: 10px;
    font-size: 20px;
}

.amfm-section-header p {
    margin: 0;
    color: #666;
}

/* Import Instructions Styles */
.import-instructions {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 30px;
    border-left: 4px solid #0073aa;
}

.import-instructions h3 {
    margin-top: 0;
    color: #333;
}

.import-instructions ul {
    margin: 10px 0 20px 20px;
}

.csv-example {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.csv-example code {
    display: block;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #d73e48;
    line-height: 1.5;
}

/* Form Styles */
.export-section, .upload-section, .import-options {
    margin-bottom: 30px;
}

.export-section h3, .upload-section h3, .import-options h3 {
    color: #333;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
}

.option-section, .taxonomy-section, .acf-section {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.option-section label, .taxonomy-section label, .acf-section label {
    display: block;
    margin-bottom: 8px;
    cursor: pointer;
}

.option-section input, .taxonomy-section input, .acf-section input {
    margin-right: 8px;
}

/* Upload styles */
input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 2px dashed #ccc;
    border-radius: 4px;
    background: #fafafa;
    margin-bottom: 10px;
}

input[type="file"]:hover {
    border-color: #0073aa;
    background: #f0f8ff;
}

/* Results section styles */
.amfm-results-section {
    background: #fff;
    padding: 30px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.amfm-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.amfm-stat {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    text-align: center;
    min-width: 120px;
}

.amfm-stat-success {
    border-left: 4px solid #46b450;
}

.amfm-stat-error {
    border-left: 4px solid #dc3232;
}

.amfm-stat-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.amfm-stat-success .amfm-stat-number {
    color: #46b450;
}

.amfm-stat-error .amfm-stat-number {
    color: #dc3232;
}

.amfm-log {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
}

.amfm-log-item {
    margin-bottom: 5px;
    padding: 3px 0;
    border-bottom: 1px solid #eee;
}

/* Responsive design */
@media (max-width: 768px) {
    .amfm-subtabs-nav {
        flex-direction: column;
    }
    
    .amfm-subtab-link {
        border-bottom: 1px solid #e5e5e5;
        border-right: none;
    }
    
    .amfm-subtab-link.active {
        border-bottom-color: #e5e5e5;
        border-left: 3px solid #0073aa;
    }
    
    .amfm-stats {
        flex-direction: column;
    }
    
    .amfm-import-export-section {
        padding: 20px;
    }
}
</style>

<script>
// Handle post type selection for export
document.getElementById('export_post_type').addEventListener('change', function() {
    const postType = this.value;
    const exportOptions = document.querySelector('.export-options');
    const taxonomySection = document.getElementById('taxonomy-section');
    
    if (postType) {
        exportOptions.style.display = 'block';
        
        // Load taxonomies for selected post type
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'amfm_get_post_type_taxonomies',
                post_type: postType,
                nonce: '<?php echo wp_create_nonce('amfm_ajax_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const taxonomyCheckboxes = document.getElementById('taxonomy-checkboxes');
                taxonomyCheckboxes.innerHTML = '';
                
                if (data.data.length > 0) {
                    taxonomySection.style.display = 'block';
                    data.data.forEach(taxonomy => {
                        const label = document.createElement('label');
                        label.innerHTML = `<input type="checkbox" name="export_taxonomies[]" value="${taxonomy.name}"> ${taxonomy.label}`;
                        label.style.display = 'block';
                        label.style.marginBottom = '8px';
                        taxonomyCheckboxes.appendChild(label);
                    });
                } else {
                    taxonomySection.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error loading taxonomies:', error);
        });
    } else {
        exportOptions.style.display = 'none';
        taxonomySection.style.display = 'none';
    }
});
</script>