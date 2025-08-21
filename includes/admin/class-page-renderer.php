<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Page_Renderer {
    
    /**
     * Admin page callback
     */
    public function render_admin_page() {
        $results = null;
        $category_results = null;
        $show_results = false;
        $show_category_results = false;
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'dashboard';

        if ( isset( $_GET['imported'] ) ) {
            if ( $_GET['imported'] === 'categories' ) {
                $category_results = get_transient( 'amfm_category_csv_import_results' );
                $show_category_results = true;
                delete_transient( 'amfm_category_csv_import_results' );
            } elseif ( $_GET['imported'] === 'keywords' ) {
                $results = get_transient( 'amfm_csv_import_results' );
                $show_results = true;
                delete_transient( 'amfm_csv_import_results' );
            }
        }

        ?>
        <div class="wrap amfm-admin-page">
            <div class="amfm-container">
                <!-- Tabs Navigation -->
                <div class="amfm-tabs-nav">
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=dashboard' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">🎛️</span>
                        Dashboard
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=import-export' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">📊</span>
                        Import/Export
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=shortcodes' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">📄</span>
                        Shortcodes
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=elementor' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">🎨</span>
                        Elementor
                    </a>
                </div>

                <!-- Tab Content -->
                <?php if ( $active_tab === 'dashboard' ) : ?>
                    <?php $this->render_dashboard_tab(); ?>
                <?php elseif ( $active_tab === 'import-export' ) : ?>
                    <?php $this->render_import_export_tab( $show_results, $show_category_results, $results, $category_results ); ?>
                <?php elseif ( $active_tab === 'shortcodes' ) : ?>
                    <?php $this->render_shortcodes_tab(); ?>
                <?php elseif ( $active_tab === 'elementor' ) : ?>
                    <?php $this->render_elementor_tab(); ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
    }

    /**
     * Render Dashboard tab content
     */
    private function render_dashboard_tab() {
        // Get available components
        $available_components = array(
            'acf_helper' => array(
                'name' => 'ACF Helper',
                'description' => 'Manages ACF keyword cookies and enhances ACF functionality for dynamic content delivery.',
                'icon' => '🔧',
                'status' => 'Core Feature'
            ),
            'text_utilities' => array(
                'name' => 'Text Utilities',
                'description' => 'Provides text processing shortcodes like [limit_words] for content formatting.',
                'icon' => '📝',
                'status' => 'Available'
            ),
            'optimization' => array(
                'name' => 'Performance Optimization',
                'description' => 'Gravity Forms optimization and performance enhancements for faster page loading.',
                'icon' => '⚡',
                'status' => 'Available'
            ),
            'shortcodes' => array(
                'name' => 'Shortcode System',
                'description' => 'DKV shortcode and other dynamic content shortcodes with advanced filtering options.',
                'icon' => '📄',
                'status' => 'Available'
            ),
            'elementor_widgets' => array(
                'name' => 'Elementor Widgets',
                'description' => 'Custom Elementor widgets including Related Posts widget with keyword-based matching.',
                'icon' => '🎨',
                'status' => 'Available'
            ),
            'import_export' => array(
                'name' => 'Import/Export Tools',
                'description' => 'Comprehensive data management for importing keywords, categories, and exporting posts with ACF fields.',
                'icon' => '📊',
                'status' => 'Core Feature'
            )
        );
        
        // Get currently enabled components (default to all enabled)
        $enabled_components = get_option( 'amfm_enabled_components', array_keys( $available_components ) );
        ?>
        <!-- Dashboard Tab Content -->
        <div class="amfm-tab-content">
            <div class="amfm-dashboard-section">
                <div class="amfm-dashboard-header">
                    <h2>
                        <span class="amfm-dashboard-icon">🎛️</span>
                        Component Management Dashboard
                    </h2>
                    <p>Enable or disable individual plugin components. Disabled components will not be loaded, improving performance and reducing resource usage.</p>
                </div>

                <form method="post" class="amfm-component-settings-form">
                    <?php wp_nonce_field( 'amfm_component_settings_update', 'amfm_component_settings_nonce' ); ?>
                    
                    <div class="amfm-components-grid">
                        <?php foreach ( $available_components as $component_key => $component_info ) : ?>
                            <?php $is_core = $component_info['status'] === 'Core Feature'; ?>
                            <div class="amfm-component-card <?php echo in_array( $component_key, $enabled_components ) ? 'amfm-component-enabled' : 'amfm-component-disabled'; ?> <?php echo $is_core ? 'amfm-component-core' : ''; ?>">
                                <div class="amfm-component-header">
                                    <div class="amfm-component-icon"><?php echo esc_html( $component_info['icon'] ); ?></div>
                                    <div class="amfm-component-toggle">
                                        <?php if ( $is_core ) : ?>
                                            <span class="amfm-core-label">Core</span>
                                            <input type="hidden" name="enabled_components[]" value="<?php echo esc_attr( $component_key ); ?>">
                                        <?php else : ?>
                                            <label class="amfm-toggle-switch">
                                                <input type="checkbox" 
                                                       name="enabled_components[]" 
                                                       value="<?php echo esc_attr( $component_key ); ?>"
                                                       <?php checked( in_array( $component_key, $enabled_components ) ); ?>
                                                       class="amfm-component-checkbox">
                                                <span class="amfm-toggle-slider"></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="amfm-component-body">
                                    <h3 class="amfm-component-title"><?php echo esc_html( $component_info['name'] ); ?></h3>
                                    <p class="amfm-component-description"><?php echo esc_html( $component_info['description'] ); ?></p>
                                    <div class="amfm-component-status">
                                        <span class="amfm-status-indicator"></span>
                                        <span class="amfm-status-text">
                                            <?php if ( $is_core ) : ?>
                                                Always Active
                                            <?php else : ?>
                                                <?php echo in_array( $component_key, $enabled_components ) ? 'Enabled' : 'Disabled'; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </form>

                <div class="amfm-dashboard-info">
                    <div class="amfm-info-grid">
                        <div class="amfm-info-card">
                            <h3>💡 Component Management Tips</h3>
                            <ul>
                                <li>Core features cannot be disabled as they're essential for plugin functionality</li>
                                <li>Disabling unused components can improve site performance</li>
                                <li>Changes take effect immediately after saving</li>
                                <li>Re-enabling components restores full functionality without data loss</li>
                            </ul>
                        </div>
                        <div class="amfm-info-card">
                            <h3>📋 Plugin Information</h3>
                            <div class="amfm-plugin-details">
                                <div class="amfm-detail-item">
                                    <strong>Version:</strong> <?php echo esc_html( AMFM_Admin::get_version() ); ?>
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Author:</strong> Adrian T. Saycon
                                </div>
                                <div class="amfm-detail-item">
                                    <strong>Website:</strong> <a href="https://adzbyte.com/" target="_blank">adzbyte.com</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Import/Export tab content
     */
    private function render_import_export_tab( $show_results, $show_category_results, $results, $category_results ) {
        ?>
        <!-- Import/Export Tab Content -->
        <div class="amfm-tab-content">
            <?php if ( $show_results || $show_category_results ) : ?>
                <?php $this->render_import_results( $results, $category_results, $show_results, $show_category_results ); ?>
            <?php else : ?>
                <?php $this->render_import_export_forms(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render import results
     */
    private function render_import_results( $results, $category_results, $show_results, $show_category_results ) {
        ?>
        <div class="amfm-results-section">
            <h2>Import Results</h2>
            
            <div class="amfm-stats">
                <div class="amfm-stat amfm-stat-success">
                    <div class="amfm-stat-number"><?php echo esc_html( $results['success'] ); ?></div>
                    <div class="amfm-stat-label">Successful Updates</div>
                </div>
                <div class="amfm-stat amfm-stat-error">
                    <div class="amfm-stat-number"><?php echo esc_html( $results['errors'] ); ?></div>
                    <div class="amfm-stat-label">Errors</div>
                </div>
            </div>

            <?php if ( ! empty( $results['details'] ) ) : ?>
                <div class="amfm-details">
                    <h3>Detailed Log</h3>
                    <div class="amfm-log">
                        <?php foreach ( $results['details'] as $detail ) : ?>
                            <div class="amfm-log-item">
                                <?php echo esc_html( $detail ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="amfm-actions">
                <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=import-export' ); ?>" class="button button-primary">
                    Import Another File
                </a>
            </div>
        </div>
        
        <?php if ( $show_category_results && $category_results ) : ?>
        <div class="amfm-results-section">
            <h2>Category Import Results</h2>
            
            <div class="amfm-stats">
                <div class="amfm-stat amfm-stat-success">
                    <div class="amfm-stat-number"><?php echo esc_html( $category_results['success'] ); ?></div>
                    <div class="amfm-stat-label">Successful Assignments</div>
                </div>
                <div class="amfm-stat amfm-stat-error">
                    <div class="amfm-stat-number"><?php echo esc_html( $category_results['errors'] ); ?></div>
                    <div class="amfm-stat-label">Errors</div>
                </div>
            </div>

            <?php if ( ! empty( $category_results['details'] ) ) : ?>
                <div class="amfm-details">
                    <h3>Detailed Log</h3>
                    <div class="amfm-log">
                        <?php foreach ( $category_results['details'] as $detail ) : ?>
                            <div class="amfm-log-item">
                                <?php echo esc_html( $detail ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="amfm-actions">
                <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=import-export' ); ?>" class="button button-primary">
                    Import Another File
                </a>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render import/export forms
     */
    private function render_import_export_forms() {
        // Get all post types including built-in ones except revisions and menus
        $post_types = get_post_types(array(
            'show_ui' => true
        ), 'objects');
        
        // Remove unwanted post types
        unset($post_types['revision']);
        unset($post_types['nav_menu_item']);
        unset($post_types['custom_css']);
        unset($post_types['customize_changeset']);
        unset($post_types['acf-field-group']);
        unset($post_types['acf-field']);

        // Get selected post type if any
        $selected_post_type = isset($_POST['export_post_type']) ? sanitize_key($_POST['export_post_type']) : '';
        
        // Get taxonomies for selected post type
        $post_type_taxonomies = array();
        if ($selected_post_type) {
            $post_type_taxonomies = get_object_taxonomies($selected_post_type, 'objects');
        }
        
        // Get all ACF field groups
        $all_field_groups = array();
        if (function_exists('acf_get_field_groups')) {
            $all_field_groups = acf_get_field_groups();
        }
        ?>

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
                    <?php $this->render_export_form( $post_types, $selected_post_type, $post_type_taxonomies, $all_field_groups ); ?>
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
                    <?php $this->render_keywords_import_form(); ?>
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
                    <?php $this->render_categories_import_form(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render export form
     */
    private function render_export_form( $post_types, $selected_post_type, $post_type_taxonomies, $all_field_groups ) {
        ?>
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
                
                <!-- Taxonomy Options -->
                <div class="option-section" style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" name="export_options[]" value="taxonomies" class="toggle-section" data-section="taxonomy-options" checked>
                        <?php esc_html_e('Include Taxonomies', 'amfm-tools'); ?>
                    </label>
                    <div class="sub-options taxonomy-options" style="margin-left: 20px; margin-top: 10px;">
                        <label>
                            <input type="radio" name="taxonomy_selection" value="all" checked>
                            <?php esc_html_e('Export All Taxonomies', 'amfm-tools'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="taxonomy_selection" value="selected">
                            <?php esc_html_e('Select Specific Taxonomies', 'amfm-tools'); ?>
                        </label>
                        <div class="taxonomy-list" style="margin: 10px 0 10px 20px; display: none;">
                            <?php if (!empty($post_type_taxonomies)): ?>
                                <?php foreach ($post_type_taxonomies as $taxonomy): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="specific_taxonomies[]" value="<?php echo esc_attr($taxonomy->name); ?>">
                                    <?php echo esc_html($taxonomy->label); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php esc_html_e('No taxonomies found for this post type.', 'amfm-tools'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ACF Fields Options -->
                <div class="option-section" style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" name="export_options[]" value="acf_fields" class="toggle-section" data-section="acf-options" checked>
                        <?php esc_html_e('Include ACF Fields', 'amfm-tools'); ?>
                    </label>
                    <div class="sub-options acf-options" style="margin-left: 20px; margin-top: 10px;">
                        <label>
                            <input type="radio" name="acf_selection" value="all" checked>
                            <?php esc_html_e('Export All ACF Fields', 'amfm-tools'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="acf_selection" value="selected">
                            <?php esc_html_e('Select Specific Field Groups', 'amfm-tools'); ?>
                        </label>
                        <div class="acf-list" style="margin: 10px 0 10px 20px; display: none;">
                            <?php if (!empty($all_field_groups)): ?>
                                <?php foreach ($all_field_groups as $field_group): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="specific_acf_groups[]" value="<?php echo esc_attr($field_group['key']); ?>">
                                    <?php echo esc_html($field_group['title']); ?>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php esc_html_e('No ACF field groups found.', 'amfm-tools'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Post Columns Options -->
                <div class="option-section" style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" name="export_options[]" value="post_columns" class="toggle-section" data-section="post-columns-options" checked>
                        <?php esc_html_e('Select Post Columns', 'amfm-tools'); ?>
                    </label>
                    <div class="sub-options post-columns-options" style="margin-left: 20px; margin-top: 10px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="id" checked>
                                <?php esc_html_e('Post ID', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="title" checked>
                                <?php esc_html_e('Post Title', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="content">
                                <?php esc_html_e('Post Content', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="excerpt">
                                <?php esc_html_e('Post Excerpt', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="status">
                                <?php esc_html_e('Post Status', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="date">
                                <?php esc_html_e('Post Date', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="modified">
                                <?php esc_html_e('Post Modified', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="url">
                                <?php esc_html_e('Post URL', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="slug">
                                <?php esc_html_e('Post Slug', 'amfm-tools'); ?>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px;">
                                <input type="checkbox" name="post_columns[]" value="author">
                                <?php esc_html_e('Post Author', 'amfm-tools'); ?>
                            </label>
                        </div>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                            <button type="button" class="button-link post-columns-select-all" style="margin-right: 10px;">Select All</button>
                            <button type="button" class="button-link post-columns-select-none">Select None</button>
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
        <?php
    }

    /**
     * Render keywords import form
     */
    private function render_keywords_import_form() {
        ?>
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
                        <h4>Requirements</h4>
                        <ul>
                            <li>CSV file must contain headers in the first row</li>
                            <li>Post IDs must exist in your WordPress database</li>
                            <li>ACF (Advanced Custom Fields) plugin must be active</li>
                            <li>Keywords will be saved to the 'amfm_keywords' ACF field</li>
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
            <?php wp_nonce_field( 'amfm_csv_import', 'amfm_csv_import_nonce' ); ?>
        
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
        <?php
    }

    /**
     * Render categories import form
     */
    private function render_categories_import_form() {
        ?>
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
                        <h4>Requirements</h4>
                        <ul>
                            <li>CSV file must contain headers in the first row (case-insensitive)</li>
                            <li>Post IDs must exist in your WordPress database</li>
                            <li>Categories will be created automatically if they don't exist</li>
                            <li>Each row assigns one category to one post</li>
                            <li>Existing categories on posts will be preserved (categories are added, not replaced)</li>
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
            <?php wp_nonce_field( 'amfm_category_csv_import', 'amfm_category_csv_import_nonce' ); ?>
            
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
        <?php
    }

    /**
     * Render Shortcodes tab content
     */
    private function render_shortcodes_tab() {
        ?>
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
                            <?php $this->render_shortcode_documentation(); ?>
                        </div>

                        <!-- Right Column: Configuration -->
                        <div class="amfm-shortcode-config-column">
                            <?php $this->render_shortcode_configuration(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render shortcode documentation
     */
    private function render_shortcode_documentation() {
        ?>
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
        <?php
    }

    /**
     * Render shortcode configuration
     */
    private function render_shortcode_configuration() {
        // Get current excluded keywords
        $excluded_keywords = get_option( 'amfm_excluded_keywords', null );
        if ( $excluded_keywords === null ) {
            // Initialize with defaults if not set
            $excluded_keywords = array(
                'co-occurring',
                'life adjustment transition',
                'comorbidity',
                'comorbid',
                'co-morbidity',
                'co-morbid'
            );
            update_option( 'amfm_excluded_keywords', $excluded_keywords );
        }
        
        $keywords_text = is_array( $excluded_keywords ) ? implode( "\n", $excluded_keywords ) : '';
        ?>
        <div class="amfm-shortcode-card">
            <h3>Excluded Keywords Management</h3>
            <p>Keywords listed below will be automatically filtered out from the DKV shortcode output. You can add, remove, or modify any keywords including the defaults.</p>
            
            <form method="post" class="amfm-excluded-keywords-form">
                <?php wp_nonce_field( 'amfm_excluded_keywords_update', 'amfm_excluded_keywords_nonce' ); ?>
                
                <div class="amfm-form-row">
                    <label for="excluded_keywords"><strong>Excluded Keywords (one per line):</strong></label>
                    <textarea 
                        id="excluded_keywords" 
                        name="excluded_keywords" 
                        rows="12" 
                        cols="50"
                        class="amfm-excluded-keywords-textarea"
                        placeholder="Enter keywords to exclude, one per line..."
                    ><?php echo esc_textarea( $keywords_text ); ?></textarea>
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
        <?php
    }

    /**
     * Render Elementor tab content
     */
    private function render_elementor_tab() {
        // Get available widgets
        $available_widgets = array(
            'amfm_related_posts' => array(
                'name' => 'AMFM Related Posts',
                'description' => 'Display related posts based on ACF keywords with customizable layouts and styling options.',
                'icon' => '📰'
            )
        );
        
        // Get currently enabled widgets
        $enabled_widgets = get_option( 'amfm_elementor_enabled_widgets', array_keys( $available_widgets ) );
        ?>
        <!-- Elementor Tab Content -->
        <div class="amfm-tab-content">
            <div class="amfm-elementor-section">
                <div class="amfm-elementor-header">
                    <h2>
                        <span class="amfm-elementor-icon">🎨</span>
                        Elementor Widget Management
                    </h2>
                    <p>Enable or disable individual Elementor widgets provided by this plugin. Disabled widgets will not be loaded in the Elementor editor.</p>
                </div>

                <form method="post" class="amfm-elementor-widgets-form">
                    <?php wp_nonce_field( 'amfm_elementor_widgets_update', 'amfm_elementor_widgets_nonce' ); ?>
                    
                    <div class="amfm-widgets-grid">
                        <?php foreach ( $available_widgets as $widget_key => $widget_info ) : ?>
                            <div class="amfm-widget-card <?php echo in_array( $widget_key, $enabled_widgets ) ? 'amfm-widget-enabled' : 'amfm-widget-disabled'; ?>">
                                <div class="amfm-widget-header">
                                    <div class="amfm-widget-icon"><?php echo esc_html( $widget_info['icon'] ); ?></div>
                                    <div class="amfm-widget-toggle">
                                        <label class="amfm-toggle-switch">
                                            <input type="checkbox" 
                                                   name="enabled_widgets[]" 
                                                   value="<?php echo esc_attr( $widget_key ); ?>"
                                                   <?php checked( in_array( $widget_key, $enabled_widgets ) ); ?>
                                                   class="amfm-widget-checkbox">
                                            <span class="amfm-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="amfm-widget-body">
                                    <h3 class="amfm-widget-title"><?php echo esc_html( $widget_info['name'] ); ?></h3>
                                    <p class="amfm-widget-description"><?php echo esc_html( $widget_info['description'] ); ?></p>
                                    <div class="amfm-widget-status">
                                        <span class="amfm-status-indicator"></span>
                                        <span class="amfm-status-text">
                                            <?php echo in_array( $widget_key, $enabled_widgets ) ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="amfm-form-actions">
                        <button type="submit" class="button button-primary amfm-save-widgets">
                            Save Widget Settings
                        </button>
                    </div>
                </form>

                <div class="amfm-elementor-info">
                    <h3>💡 Tips</h3>
                    <ul>
                        <li>Disabling widgets can improve Elementor editor performance by reducing loaded components</li>
                        <li>Disabled widgets will not appear in the Elementor widget panel</li>
                        <li>Changes take effect immediately after saving</li>
                        <li>Re-enabling a widget restores all its functionality without data loss</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}