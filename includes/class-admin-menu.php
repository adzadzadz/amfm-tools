<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Admin_Menu {
    
    public static function init() {
        $instance = new self();
        add_action( 'admin_menu', array( $instance, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $instance, 'handle_csv_upload' ) );
        add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_admin_styles' ) );
    }

    /**
     * Add admin menu under Tools
     */
    public function add_admin_menu() {
        add_management_page(
            'AMFM CSV Import',
            'AMFM',
            'manage_options',
            'amfm-csv-import',
            array( $this, 'admin_page_callback' )
        );
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook ) {
        if ( $hook !== 'tools_page_amfm-csv-import' ) {
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
        
        wp_redirect( admin_url( 'tools.php?page=amfm-csv-import&tab=seo&imported=1' ) );
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
     * Admin page callback
     */
    public function admin_page_callback() {
        $results = null;
        $show_results = false;
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

        if ( isset( $_GET['imported'] ) && $_GET['imported'] == '1' ) {
            $results = get_transient( 'amfm_csv_import_results' );
            $show_results = true;
            $active_tab = 'seo'; // Switch to SEO tab when showing results
            delete_transient( 'amfm_csv_import_results' );
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
                    <a href="<?php echo admin_url( 'tools.php?page=amfm-csv-import&tab=general' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">🏠</span>
                        General
                    </a>
                    <a href="<?php echo admin_url( 'tools.php?page=amfm-csv-import&tab=seo' ); ?>" 
                       class="amfm-tab-link <?php echo $active_tab === 'seo' ? 'active' : ''; ?>">
                        <span class="amfm-tab-icon">�</span>
                        SEO
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
                                    <a href="<?php echo admin_url( 'tools.php?page=amfm-csv-import&tab=seo' ); ?>" class="amfm-feature-link">
                                        Go to SEO Tools →
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
                                    <a href="<?php echo admin_url( 'tools.php?page=amfm-csv-import&tab=seo' ); ?>" class="button button-primary">
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
                <?php endif; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
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
        });
        </script>
        <?php
    }
}
