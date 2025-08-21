<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_CSV_Importer {
    
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
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=import-export&imported=keywords' ) );
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
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=import-export&imported=categories' ) );
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
}