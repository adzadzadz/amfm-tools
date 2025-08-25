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
                        <p class="amfm-subtitle">Advanced Custom Field Management & Performance Optimization Tools</p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-header-stats">
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($post_types); ?></span>
                            <span class="amfm-header-stat-label">Post Types</span>
                        </div>
                        <div class="amfm-header-stat">
                            <span class="amfm-header-stat-number"><?php echo count($all_field_groups); ?></span>
                            <span class="amfm-header-stat-label">ACF Groups</span>
                        </div>
                    </div>
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
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

        <!-- Import/Export Tab Content -->
        <div class="amfm-tab-content">
            <?php if ($show_results || $show_category_results) : ?>
                <!-- Import Results -->
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
                        <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=import-export')); ?>" class="button button-primary">
                            Import Another File
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <!-- Accordion layout for all sections -->
                <div class="amfm-accordion-container" style="margin-top: 20px;">
                    
                    <!-- Export Section -->
                    <div class="amfm-accordion-section">
                        <div class="amfm-accordion-header" data-target="export-data">
                            <h2>
                                <span class="amfm-seo-icon">📤</span>
                                Export Data
                                <span class="amfm-accordion-toggle">▼</span>
                            </h2>
                            <p>Export posts with ACF fields, taxonomies, and more to CSV.</p>
                        </div>
                        <div class="amfm-accordion-content" id="export-data" style="display: none;">
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
                                            <label>
                                                <input type="checkbox" name="export_options[]" value="post_columns" class="toggle-section" data-section="post-columns-options" checked>
                                                <?php esc_html_e('Select Post Columns', 'amfm-tools'); ?>
                                            </label>
                                            <div class="sub-options post-columns-options" style="margin-left: 20px; margin-top: 10px;">
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                                                    <label><input type="checkbox" name="post_columns[]" value="id" checked> <?php esc_html_e('Post ID', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="title" checked> <?php esc_html_e('Post Title', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="content"> <?php esc_html_e('Post Content', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="excerpt"> <?php esc_html_e('Post Excerpt', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="status"> <?php esc_html_e('Post Status', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="date"> <?php esc_html_e('Post Date', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="modified"> <?php esc_html_e('Post Modified', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="url"> <?php esc_html_e('Post URL', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="slug"> <?php esc_html_e('Post Slug', 'amfm-tools'); ?></label>
                                                    <label><input type="checkbox" name="post_columns[]" value="author"> <?php esc_html_e('Post Author', 'amfm-tools'); ?></label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Taxonomy Options -->
                                        <div class="option-section" style="margin-bottom: 15px;">
                                            <label>
                                                <input type="checkbox" name="export_options[]" value="taxonomies" class="toggle-section" data-section="taxonomy-options" checked>
                                                <?php esc_html_e('Include Taxonomies', 'amfm-tools'); ?>
                                            </label>
                                            <div class="sub-options taxonomy-options" style="margin-left: 20px; margin-top: 10px; display: block;">
                                                <div style="margin-bottom: 10px;">
                                                    <label style="display: block; margin-bottom: 5px;">
                                                        <input type="radio" name="taxonomy_selection" value="all" checked>
                                                        <?php esc_html_e('Include all taxonomies', 'amfm-tools'); ?>
                                                    </label>
                                                    <label style="display: block;">
                                                        <input type="radio" name="taxonomy_selection" value="selected">
                                                        <?php esc_html_e('Select specific taxonomies', 'amfm-tools'); ?>
                                                    </label>
                                                </div>
                                                <div class="taxonomy-list" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                                                    <!-- Taxonomies will be loaded here dynamically -->
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ACF Fields Options -->
                                        <div class="option-section" style="margin-bottom: 15px;">
                                            <label>
                                                <input type="checkbox" name="export_options[]" value="acf_fields" class="toggle-section" data-section="acf-options" checked>
                                                <?php esc_html_e('Include ACF Fields', 'amfm-tools'); ?>
                                            </label>
                                            <div class="sub-options acf-options" style="margin-left: 20px; margin-top: 10px; display: block;">
                                                <div style="margin-bottom: 10px;">
                                                    <label style="display: block; margin-bottom: 5px;">
                                                        <input type="radio" name="acf_selection" value="all" checked>
                                                        <?php esc_html_e('Include all ACF fields', 'amfm-tools'); ?>
                                                    </label>
                                                    <label style="display: block;">
                                                        <input type="radio" name="acf_selection" value="selected">
                                                        <?php esc_html_e('Select specific field groups', 'amfm-tools'); ?>
                                                    </label>
                                                </div>
                                                <div class="acf-list" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                                                    <?php if (!empty($all_field_groups)) : ?>
                                                        <?php foreach ($all_field_groups as $group) : ?>
                                                        <label style="display: block; margin-bottom: 5px;">
                                                            <input type="checkbox" name="specific_acf_groups[]" value="<?php echo esc_attr($group['key']); ?>">
                                                            <?php echo esc_html($group['title']); ?>
                                                        </label>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <p><?php esc_html_e('No ACF field groups found.', 'amfm-tools'); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Featured Image Option -->
                                        <div class="option-section" style="margin-bottom: 15px;">
                                            <label>
                                                <input type="checkbox" name="export_options[]" value="featured_image" checked>
                                                <?php esc_html_e('Include Featured Image URL', 'amfm-tools'); ?>
                                            </label>
                                        </div>

                                        <p class="submit">
                                            <button type="submit" id="amfm_export_btn" class="button button-primary">
                                                <span class="export-text">Export to CSV</span>
                                                <span class="spinner" style="display: none; float: none; margin: 0 0 0 5px;"></span>
                                            </button>
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Keywords Import Section -->
                    <div class="amfm-accordion-section">
                        <div class="amfm-accordion-header" data-target="keywords-import">
                            <h2>
                                <span class="amfm-seo-icon">📥</span>
                                Import Keywords
                                <span class="amfm-accordion-toggle">▼</span>
                            </h2>
                            <p>Import keywords to update ACF fields in bulk for SEO optimization.</p>
                        </div>
                        <div class="amfm-accordion-content" id="keywords-import" style="display: none;">
                            <div class="amfm-import-section">
                                <!-- Collapsible Instructions -->
                                <div class="amfm-instructions-header" data-target="keywords-instructions">
                                    <button type="button" class="amfm-help-button">Need help?</button>
                                </div>
                                
                                <div class="amfm-instructions-content" id="keywords-instructions" style="display: none;">
                                    <div class="amfm-info-box">
                                        <div class="amfm-instructions-section">
                                            <h4>File Format</h4>
                                            <p>Upload a CSV file with the following columns:</p>
                                            <ul>
                                                <li><strong>ID</strong> - Post ID to update</li>
                                                <li><strong>Keywords</strong> - Keywords to add to the ACF field</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="amfm-instructions-section">
                                            <h4>Example CSV Content</h4>
                                            <div class="amfm-code-block">
                                                <pre>ID,Keywords
1,"wordpress, cms, website"
2,"seo, optimization, performance"</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" enctype="multipart/form-data" class="amfm-upload-form">
                                    <?php wp_nonce_field('amfm_csv_import', 'amfm_csv_import_nonce'); ?>
                                
                                    <div class="amfm-file-input-wrapper">
                                        <label for="csv_file" class="amfm-file-label">
                                            <span class="amfm-file-icon">📁</span>
                                            <span class="amfm-file-text">Choose CSV File</span>
                                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="amfm-file-input">
                                        </label>
                                        <div class="amfm-file-info"></div>
                                    </div>

                                    <div class="amfm-submit-wrapper">
                                        <button type="submit" class="button button-primary amfm-submit-btn">
                                            <span class="amfm-submit-icon">⬆️</span>
                                            Import CSV File
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Import Section -->
                    <div class="amfm-accordion-section">
                        <div class="amfm-accordion-header" data-target="categories-import">
                            <h2>
                                <span class="amfm-seo-icon">📂</span>
                                Import Categories
                                <span class="amfm-accordion-toggle">▼</span>
                            </h2>
                            <p>Import categories to assign to posts in bulk using CSV files.</p>
                        </div>
                        <div class="amfm-accordion-content" id="categories-import" style="display: none;">
                            <div class="amfm-import-section">
                                <!-- Collapsible Instructions -->
                                <div class="amfm-instructions-header" data-target="categories-instructions">
                                    <button type="button" class="amfm-help-button">Need help?</button>
                                </div>
                                
                                <div class="amfm-instructions-content" id="categories-instructions" style="display: none;">
                                    <div class="amfm-info-box">
                                        <div class="amfm-instructions-section">
                                            <h4>File Format</h4>
                                            <p>Upload a CSV file with the following columns:</p>
                                            <ul>
                                                <li><strong>id</strong> - Post ID to assign category to</li>
                                                <li><strong>Categories</strong> - Category name to assign to the post</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="amfm-instructions-section">
                                            <h4>Example CSV Content</h4>
                                            <div class="amfm-code-block">
                                                <pre>id,Categories
2518,"Bipolar Disorder & Mania"
2650,"News, Advocacy & Thought Leadership"
2708,"Bipolar Disorder & Mania"</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" enctype="multipart/form-data" class="amfm-upload-form">
                                    <?php wp_nonce_field('amfm_category_csv_import', 'amfm_category_csv_import_nonce'); ?>
                                    
                                    <div class="amfm-file-input-wrapper">
                                        <label for="category_csv_file" class="amfm-file-label">
                                            <span class="amfm-file-icon">📁</span>
                                            <span class="amfm-file-text">Choose CSV File</span>
                                            <input type="file" id="category_csv_file" name="category_csv_file" accept=".csv" required class="amfm-file-input">
                                        </label>
                                        <div class="amfm-file-info"></div>
                                    </div>

                                    <div class="amfm-submit-wrapper">
                                        <button type="submit" class="button button-primary amfm-submit-btn">
                                            <span class="amfm-submit-icon">⬆️</span>
                                            Import CSV File
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>