<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Admin_Menu {
    
    public static function init() {
        $instance = new self();
        add_action( 'admin_menu', array( $instance, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $instance, 'handle_csv_upload' ) );
        add_action( 'admin_init', array( $instance, 'handle_category_csv_upload' ) );
        add_action( 'admin_init', array( $instance, 'handle_excluded_keywords_update' ) );
        add_action( 'admin_init', array( $instance, 'handle_elementor_widgets_update' ) );
        add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_admin_styles' ) );
    }

    /**
     * Add admin menu under AMFM main menu
     */
    public function add_admin_menu() {
        // Check if main AMFM menu exists, if not create it
        if ( ! $this->main_menu_exists() ) {
            add_menu_page(
                __('AMFM', 'amfm-tools'), // Page title
                __('AMFM', 'amfm-tools'), // Menu title
                'manage_options', // Capability
                'amfm', // Menu slug
                array( $this, 'admin_page_callback' ), // Callback function
                'dashicons-admin-tools', // Icon
                2 // Position
            );
        }
        
        // Add Tools submenu
        add_submenu_page(
            'amfm',
            __('Tools', 'amfm-tools'),
            __('Tools', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            array( $this, 'admin_page_callback' )
        );
    }
    
    /**
     * Check if main AMFM menu already exists
     */
    private function main_menu_exists() {
        global $menu;
        if ( ! is_array( $menu ) ) {
            return false;
        }
        
        foreach ( $menu as $menu_item ) {
            if ( isset( $menu_item[2] ) && $menu_item[2] === 'amfm' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook ) {
        // Handle both main menu and submenu hooks
        if ( $hook !== 'toplevel_page_amfm' && $hook !== 'amfm_page_amfm-tools' ) {
            return;
        }
        
        wp_enqueue_style( 
            'amfm-admin-style', 
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin-style.css',
            array(),
            rand(1,99)
        );
    }

    /**
     * Handle CSV file upload and processing
     */
    public function handle_csv_upload() {
        if ( ! isset( $_POST['amfm_csv_import_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_csv_import_nonce'], 'amfm_csv_import' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>Error uploading file. Please try again.</p></div>';
            });
            return;
        }

        $file = $_FILES['csv_file'];
        $file_type = wp_check_filetype( $file['name'] );
        
        if ( $file_type['ext'] !== 'csv' ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>Please upload a valid CSV file.</p></div>';
            });
            return;
        }

        $results = $this->process_csv_file( $file['tmp_name'] );
        
        // Store results in transient for display
        set_transient( 'amfm_csv_import_results', $results, 300 );
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=seo&imported=1' ) );
        exit;
    }

    /**
     * Process the CSV file
     */
    private function process_csv_file( $file_path ) {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );

        if ( ! file_exists( $file_path ) ) {
            $results['details'][] = 'File does not exist';
            $results['errors']++;
            return $results;
        }

        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            $results['details'][] = 'Could not open file for reading';
            $results['errors']++;
            return $results;
        }

        $headers = fgetcsv( $handle );
        
        // Validate headers
        if ( ! $headers || ! in_array( 'ID', $headers ) || ! in_array( 'Keywords', $headers ) ) {
            $results['details'][] = 'Invalid CSV format. Required headers: ID, Keywords';
            $results['errors']++;
            fclose( $handle );
            return $results;
        }

        $id_index = array_search( 'ID', $headers );
        $keywords_index = array_search( 'Keywords', $headers );
        $row_number = 1; // Start at 1 since we already read headers

        while ( ( $row = fgetcsv( $handle ) ) !== FALSE ) {
            $row_number++;
            
            if ( count( $row ) <= max( $id_index, $keywords_index ) ) {
                $results['details'][] = "Row {$row_number}: Invalid row format";
                $results['errors']++;
                continue;
            }

            $post_id = intval( $row[$id_index] );
            $keywords = sanitize_text_field( $row[$keywords_index] );

            if ( ! $post_id ) {
                $results['details'][] = "Row {$row_number}: Invalid post ID";
                $results['errors']++;
                continue;
            }

            $post = get_post( $post_id );
            if ( ! $post ) {
                $results['details'][] = "Row {$row_number}: Post ID {$post_id} not found";
                $results['errors']++;
                continue;
            }

            // Update the amfm_keywords ACF field - force overwrite
            $existing_value = get_field( 'amfm_keywords', $post_id );
            $field_updated = update_field( 'amfm_keywords', $keywords, $post_id );
            
            // Check if the field was actually updated by comparing values
            $new_value = get_field( 'amfm_keywords', $post_id );
            
            if ( $new_value === $keywords ) {
                if ( $existing_value && $existing_value !== $keywords ) {
                    $results['details'][] = "Row {$row_number}: Overwritten post ID {$post_id} ('{$post->post_title}') amfm_keywords field from '{$existing_value}' to: {$keywords}";
                } else {
                    $results['details'][] = "Row {$row_number}: Updated post ID {$post_id} ('{$post->post_title}') amfm_keywords field with: {$keywords}";
                }
                $results['success']++;
            } else {
                $results['details'][] = "Row {$row_number}: Failed to update post ID {$post_id} - amfm_keywords ACF field not found or update failed";
                $results['errors']++;
            }
        }

        fclose( $handle );
        return $results;
    }

    /**
     * Handle CSV category file upload and processing
     */
    public function handle_category_csv_upload() {
        if ( ! isset( $_POST['amfm_category_csv_import_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_category_csv_import_nonce'], 'amfm_category_csv_import' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_FILES['category_csv_file'] ) || $_FILES['category_csv_file']['error'] !== UPLOAD_ERR_OK ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>Error uploading file. Please try again.</p></div>';
            });
            return;
        }

        $file = $_FILES['category_csv_file'];
        $file_type = wp_check_filetype( $file['name'] );
        
        if ( $file_type['ext'] !== 'csv' ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>Please upload a valid CSV file.</p></div>';
            });
            return;
        }

        $results = $this->process_category_csv_file( $file['tmp_name'] );
        
        // Store results in transient for display
        set_transient( 'amfm_category_csv_import_results', $results, 300 );
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=categories&imported=1' ) );
        exit;
    }

    /**
     * Process the category CSV file
     */
    private function process_category_csv_file( $file_path ) {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );

        if ( ! file_exists( $file_path ) ) {
            $results['details'][] = 'File does not exist';
            $results['errors']++;
            return $results;
        }

        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            $results['details'][] = 'Could not open file for reading';
            $results['errors']++;
            return $results;
        }

        $headers = fgetcsv( $handle );
        
        // Validate headers (case-insensitive)
        if ( ! $headers ) {
            $results['details'][] = 'Invalid CSV format. Could not read headers';
            $results['errors']++;
            fclose( $handle );
            return $results;
        }

        // Convert headers to lowercase for comparison
        $headers_lower = array_map( 'strtolower', $headers );
        $id_index = array_search( 'id', $headers_lower );
        $categories_index = array_search( 'categories', $headers_lower );

        if ( $id_index === false || $categories_index === false ) {
            $results['details'][] = 'Invalid CSV format. Required headers: id, Categories (case-insensitive)';
            $results['errors']++;
            fclose( $handle );
            return $results;
        }

        $row_number = 1; // Start at 1 since we already read headers

        while ( ( $row = fgetcsv( $handle ) ) !== FALSE ) {
            $row_number++;
            
            if ( count( $row ) <= max( $id_index, $categories_index ) ) {
                $results['details'][] = "Row {$row_number}: Invalid row format";
                $results['errors']++;
                continue;
            }

            $post_id = intval( $row[$id_index] );
            $category_name = trim( $row[$categories_index] );

            if ( ! $post_id ) {
                $results['details'][] = "Row {$row_number}: Invalid post ID";
                $results['errors']++;
                continue;
            }

            if ( empty( $category_name ) ) {
                $results['details'][] = "Row {$row_number}: Empty category name";
                $results['errors']++;
                continue;
            }

            $post = get_post( $post_id );
            if ( ! $post ) {
                $results['details'][] = "Row {$row_number}: Post ID {$post_id} not found";
                $results['errors']++;
                continue;
            }

            // Find or create the category
            $category = get_term_by( 'name', $category_name, 'category' );
            if ( ! $category ) {
                // Create new category
                $new_category = wp_insert_term( $category_name, 'category' );
                if ( is_wp_error( $new_category ) ) {
                    $results['details'][] = "Row {$row_number}: Failed to create category '{$category_name}': " . $new_category->get_error_message();
                    $results['errors']++;
                    continue;
                }
                $category_id = $new_category['term_id'];
                $results['details'][] = "Row {$row_number}: Created new category '{$category_name}' (ID: {$category_id})";
            } else {
                $category_id = $category->term_id;
            }

            // Assign category to post
            $result = wp_set_post_categories( $post_id, array( $category_id ), false );
            if ( is_wp_error( $result ) ) {
                $results['details'][] = "Row {$row_number}: Failed to assign category to post ID {$post_id}: " . $result->get_error_message();
                $results['errors']++;
                continue;
            }

            $results['details'][] = "Row {$row_number}: Successfully assigned category '{$category_name}' to post ID {$post_id} ('{$post->post_title}')";
            $results['success']++;
        }

        fclose( $handle );
        return $results;
    }

    /**
     * Handle excluded keywords update
     */
    public function handle_excluded_keywords_update() {
        if ( ! isset( $_POST['amfm_excluded_keywords_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_excluded_keywords_nonce'], 'amfm_excluded_keywords_update' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $excluded_keywords = isset( $_POST['excluded_keywords'] ) ? sanitize_textarea_field( $_POST['excluded_keywords'] ) : '';
        
        // Convert textarea input to array
        $keywords_array = array_filter( array_map( 'trim', explode( "\n", $excluded_keywords ) ) );
        
        // Update the option
        update_option( 'amfm_excluded_keywords', $keywords_array );
        
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>Excluded keywords updated successfully!</p></div>';
        });
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=shortcodes&updated=1' ) );
        exit;
    }

    /**
     * Handle Elementor widgets update
     */
    public function handle_elementor_widgets_update() {
        if ( ! isset( $_POST['amfm_elementor_widgets_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_elementor_widgets_nonce'], 'amfm_elementor_widgets_update' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $enabled_widgets = isset( $_POST['enabled_widgets'] ) ? array_map( 'sanitize_text_field', $_POST['enabled_widgets'] ) : array();
        
        // Update the option
        update_option( 'amfm_elementor_enabled_widgets', $enabled_widgets );
        
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>Elementor widget settings updated successfully!</p></div>';
        });
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=elementor&updated=1' ) );
        exit;
    }

    /**
     * Admin page callback
     */
    public function admin_page_callback() {
        $results = null;
        $category_results = null;
        $show_results = false;
        $show_category_results = false;
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

        if ( isset( $_GET['imported'] ) && $_GET['imported'] == '1' ) {
            if ( $active_tab === 'categories' ) {
                $category_results = get_transient( 'amfm_category_csv_import_results' );
                $show_category_results = true;
                delete_transient( 'amfm_category_csv_import_results' );
            } else {
                $results = get_transient( 'amfm_csv_import_results' );
                $show_results = true;
                $active_tab = 'seo'; // Switch to SEO tab when showing results
                delete_transient( 'amfm_csv_import_results' );
            }
        }

        ?>
        <div class="wrap amfm-admin-page">
            <h1 class="amfm-page-title">
                <span class="amfm-icon">🛠️</span>
                AMFM Tools
            </h1>
            
            <div class="amfm-container">
                <!-- Tabs Navigation -->
                <div class="amfm-tabs-nav">
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=general' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">🏠</span>
                        General
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=seo' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'seo' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">📊</span>
                        SEO
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=shortcodes' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">📄</span>
                        Shortcodes
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=categories' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'categories' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">📂</span>
                        Categories
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=elementor' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">🎨</span>
                        Elementor
                    </a>
                </div>

                <!-- Tab Content -->
                <?php if ( $active_tab === 'general' ) : ?>
                    <!-- General Tab Content -->
                    <div class="amfm-tab-content">
                        <div class="amfm-general-section">
                            <div class="amfm-welcome-box">
                                <h2>Welcome to AMFM Tools</h2>
                                <p>This plugin provides various tools and utilities for the AMFM website management.</p>
                            </div>

                            <div class="amfm-features-grid">
                                <div class="amfm-feature-card">
                                    <div class="amfm-feature-icon">📈</div>
                                    <h3>SEO Tools</h3>
                                    <p>Import and manage SEO keywords using CSV files. Update ACF fields in bulk for better search engine optimization.</p>
                                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=seo' ); ?>" class="amfm-feature-link">
                                        Go to SEO Tools →
                                    </a>
                                </div>

                                <div class="amfm-feature-card">
                                    <div class="amfm-feature-icon">📂</div>
                                    <h3>Category Import</h3>
                                    <p>Bulk assign categories to posts using CSV files. Automatically creates categories if they don't exist.</p>
                                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=categories' ); ?>" class="amfm-feature-link">
                                        Go to Category Import →
                                    </a>
                                </div>

                                <div class="amfm-feature-card">
                                    <div class="amfm-feature-icon">⚡</div>
                                    <h3>Performance Optimization</h3>
                                    <p>Various performance optimizations and enhancements are automatically applied to improve site speed.</p>
                                    <div class="amfm-feature-status">
                                        <span class="amfm-status-active">Active</span>
                                    </div>
                                </div>

                                <div class="amfm-feature-card">
                                    <div class="amfm-feature-icon">📝</div>
                                    <h3>Text Utilities</h3>
                                    <p>Enhanced text processing and formatting utilities for better content management.</p>
                                    <div class="amfm-feature-status">
                                        <span class="amfm-status-active">Active</span>
                                    </div>
                                </div>

                                <div class="amfm-feature-card">
                                    <div class="amfm-feature-icon">🔧</div>
                                    <h3>ACF Helpers</h3>
                                    <p>Advanced Custom Fields integration and helper functions for enhanced functionality.</p>
                                    <div class="amfm-feature-status">
                                        <span class="amfm-status-active">Active</span>
                                    </div>
                                </div>
                            </div>

                            <div class="amfm-info-section">
                                <h3>Plugin Information</h3>
                                <div class="amfm-info-grid">
                                    <div class="amfm-info-item">
                                        <strong>Version:</strong> 1.0.0
                                    </div>
                                    <div class="amfm-info-item">
                                        <strong>Author:</strong> Adrian T. Saycon
                                    </div>
                                    <div class="amfm-info-item">
                                        <strong>Website:</strong> <a href="https://adzbyte.com/" target="_blank">adzbyte.com</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ( $active_tab === 'seo' ) : ?>
                    <!-- SEO Tab Content -->
                    <div class="amfm-tab-content">
                        <?php if ( $show_results && $results ) : ?>
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
                                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=seo' ); ?>" class="button button-primary">
                                        Import Another File
                                    </a>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="amfm-upload-section">
                                <div class="amfm-seo-header">
                                    <h2>
                                        <span class="amfm-seo-icon">📊</span>
                                        CSV Keywords Import
                                    </h2>
                                    <p>Import keywords to update ACF fields in bulk for SEO optimization.</p>
                                </div>

                                <div class="amfm-info-box">
                                    <h3>CSV Import Instructions</h3>
                                    <p>Upload a CSV file with the following format:</p>
                                    <ul>
                                        <li><strong>ID</strong> - Post ID to update</li>
                                        <li><strong>Keywords</strong> - Keywords to add to the ACF field</li>
                                    </ul>
                                    <p><strong>Requirements:</strong></p>
                                    <ul>
                                        <li>CSV file must contain headers in the first row</li>
                                        <li>Post IDs must exist in your WordPress database</li>
                                        <li>ACF (Advanced Custom Fields) plugin must be active</li>
                                        <li>Keywords will be saved to the 'amfm_keywords' ACF field</li>
                                    </ul>
                                    <p><strong>Example CSV content:</strong></p>
                                    <pre style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 4px; margin: 10px 0;">ID,Keywords
1,"wordpress, cms, website"
2,"seo, optimization, performance"</pre>
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
                        <?php endif; ?>
                    </div>

                <?php elseif ( $active_tab === 'shortcodes' ) : ?>
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
                                                <h4>Available Attributes:</h4>
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
                                    </div>

                                    <!-- Right Column: Configuration -->
                                    <div class="amfm-shortcode-config-column">
                                        <div class="amfm-shortcode-card">
                                            <h3>Excluded Keywords Management</h3>
                                            <p>Keywords listed below will be automatically filtered out from the DKV shortcode output. You can add, remove, or modify any keywords including the defaults.</p>
                                            
                                            <?php
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ( $active_tab === 'categories' ) : ?>
                    <!-- Categories Tab Content -->
                    <div class="amfm-tab-content">
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
                                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=categories' ); ?>" class="button button-primary">
                                        Import Another File
                                    </a>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="amfm-upload-section">
                                <div class="amfm-seo-header">
                                    <h2>
                                        <span class="amfm-seo-icon">📂</span>
                                        CSV Category Import
                                    </h2>
                                    <p>Import categories to assign to posts in bulk using CSV files.</p>
                                </div>

                                <div class="amfm-info-box">
                                    <h3>CSV Import Instructions</h3>
                                    <p>Upload a CSV file with the following format:</p>
                                    <ul>
                                        <li><strong>id</strong> - Post ID to assign category to</li>
                                        <li><strong>Categories</strong> - Category name to assign to the post</li>
                                    </ul>
                                    <p><strong>Requirements:</strong></p>
                                    <ul>
                                        <li>CSV file must contain headers in the first row (case-insensitive)</li>
                                        <li>Post IDs must exist in your WordPress database</li>
                                        <li>Categories will be created automatically if they don't exist</li>
                                        <li>Each row assigns one category to one post</li>
                                        <li>Existing categories on posts will be preserved (categories are added, not replaced)</li>
                                    </ul>
                                    <p><strong>Example CSV content:</strong></p>
                                    <pre style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 4px; margin: 10px 0;">id,Categories
2518,"Bipolar Disorder & Mania"
2650,"News, Advocacy & Thought Leadership"
2708,"Bipolar Disorder & Mania"</pre>
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
                        <?php endif; ?>
                    </div>

                <?php elseif ( $active_tab === 'elementor' ) : ?>
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

                            <?php
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

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Handle widget toggle changes
                        const widgetCheckboxes = document.querySelectorAll('.amfm-widget-checkbox');
                        widgetCheckboxes.forEach(function(checkbox) {
                            checkbox.addEventListener('change', function() {
                                const card = this.closest('.amfm-widget-card');
                                const statusText = card.querySelector('.amfm-status-text');
                                
                                if (this.checked) {
                                    card.classList.remove('amfm-widget-disabled');
                                    card.classList.add('amfm-widget-enabled');
                                    statusText.textContent = 'Enabled';
                                } else {
                                    card.classList.remove('amfm-widget-enabled');
                                    card.classList.add('amfm-widget-disabled');
                                    statusText.textContent = 'Disabled';
                                }
                            });
                        });
                    });
                    </script>
                <?php endif; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle CSV file input (SEO tab)
            const fileInput = document.getElementById('csv_file');
            const fileInfo = document.querySelector('.amfm-file-info');
            const fileText = document.querySelector('.amfm-file-text');

            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        fileText.textContent = file.name;
                        fileInfo.innerHTML = `<small>File size: ${(file.size / 1024).toFixed(2)} KB</small>`;
                    } else {
                        fileText.textContent = 'Choose CSV File';
                        fileInfo.innerHTML = '';
                    }
                });
            }

            // Handle Category CSV file input (Categories tab)
            const categoryFileInput = document.getElementById('category_csv_file');
            const categoryFileInfo = document.querySelector('#category_csv_file').closest('.amfm-file-input-wrapper').querySelector('.amfm-file-info');
            const categoryFileText = document.querySelector('#category_csv_file').closest('.amfm-file-input-wrapper').querySelector('.amfm-file-text');

            if (categoryFileInput) {
                categoryFileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        categoryFileText.textContent = file.name;
                        categoryFileInfo.innerHTML = `<small>File size: ${(file.size / 1024).toFixed(2)} KB</small>`;
                    } else {
                        categoryFileText.textContent = 'Choose CSV File';
                        categoryFileInfo.innerHTML = '';
                    }
                });
            }
        });
        </script>
        <?php
    }
}
