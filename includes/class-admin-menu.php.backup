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
        add_action( 'admin_init', array( $instance, 'handle_export' ) );
        add_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_admin_styles' ) );
        add_action( 'wp_ajax_amfm_get_post_type_taxonomies', array( $instance, 'ajax_get_post_type_taxonomies' ) );
        add_action( 'wp_ajax_amfm_export_data', array( $instance, 'ajax_export_data' ) );
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
        
        wp_enqueue_script(
            'amfm-admin-script',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin-script.js',
            array('jquery'),
            rand(1,99),
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('amfm-admin-script', 'amfmAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'exportNonce' => wp_create_nonce('amfm_export_nonce'),
        ));
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
     * AJAX handler to get taxonomies for a post type
     */
    public function ajax_get_post_type_taxonomies() {
        // Check nonce
        if (!check_ajax_referer('amfm_export_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check if post type is provided
        if (empty($_POST['post_type'])) {
            wp_send_json_error('No post type provided');
            return;
        }

        // Get and sanitize the post type
        $post_type = sanitize_key($_POST['post_type']);

        // Get taxonomies for this post type
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        
        if (empty($taxonomies)) {
            wp_send_json_error('No taxonomies found');
            return;
        }

        // Format taxonomies for response
        $formatted_taxonomies = array();
        foreach ($taxonomies as $taxonomy) {
            $formatted_taxonomies[] = array(
                'name' => $taxonomy->name,
                'label' => $taxonomy->label
            );
        }

        wp_send_json_success($formatted_taxonomies);
    }

    /**
     * AJAX handler for export functionality
     */
    public function ajax_export_data() {
        // Verify nonce and user capabilities first
        if (!check_ajax_referer('amfm_export_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        if (empty($_POST['export_post_type'])) {
            wp_send_json_error('Please select a post type to export.');
            return;
        }

        // Sanitize and validate post type
        $post_type = sanitize_key(wp_unslash($_POST['export_post_type']));
        if (!post_type_exists($post_type)) {
            wp_send_json_error('Invalid post type selected.');
            return;
        }

        // Sanitize export options
        $export_options = isset($_POST['export_options']) ? 
            array_map('sanitize_key', wp_unslash($_POST['export_options'])) : 
            array();
        
        // Validate and sanitize taxonomy selection
        $taxonomy_selection = isset($_POST['taxonomy_selection']) ? 
            sanitize_key(wp_unslash($_POST['taxonomy_selection'])) : 
            'all';
        if (!in_array($taxonomy_selection, array('all', 'selected'), true)) {
            $taxonomy_selection = 'all';
        }

        // Sanitize specific taxonomies
        $specific_taxonomies = isset($_POST['specific_taxonomies']) ? 
            array_map('sanitize_key', wp_unslash($_POST['specific_taxonomies'])) : 
            array();
        
        // Validate and sanitize ACF selection
        $acf_selection = isset($_POST['acf_selection']) ? 
            sanitize_key(wp_unslash($_POST['acf_selection'])) : 
            'all';
        if (!in_array($acf_selection, array('all', 'selected'), true)) {
            $acf_selection = 'all';
        }

        // Sanitize specific ACF groups
        $specific_acf_groups = isset($_POST['specific_acf_groups']) ? 
            array_map('sanitize_key', wp_unslash($_POST['specific_acf_groups'])) : 
            array();
        
        // Sanitize selected post columns
        $selected_post_columns = isset($_POST['post_columns']) ? 
            array_map('sanitize_key', wp_unslash($_POST['post_columns'])) : 
            array('id', 'title'); // Default to ID and Title if none selected

        // Get all posts of the selected type
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));

        if (empty($posts)) {
            wp_send_json_error(sprintf(
                'No posts found for the post type: %s',
                esc_html($post_type)
            ));
            return;
        }

        // Get taxonomies based on selection
        $taxonomies = array();
        if (in_array('taxonomies', $export_options, true)) {
            if ($taxonomy_selection === 'all') {
                $taxonomies = get_object_taxonomies($post_type, 'objects');
            } else {
                // Only include specifically selected taxonomies
                foreach ($specific_taxonomies as $tax_name) {
                    if (taxonomy_exists($tax_name)) {
                        $taxonomy = get_taxonomy($tax_name);
                        if ($taxonomy) {
                            $taxonomies[$tax_name] = $taxonomy;
                        }
                    }
                }
            }
        }

        // Initialize ACF fields array based on selection
        $acf_fields = array();
        if (in_array('acf_fields', $export_options, true) && function_exists('acf_get_field_groups')) {
            if ($acf_selection === 'all') {
                // Get all field groups and their fields
                $field_groups = acf_get_field_groups();
                foreach ($field_groups as $field_group) {
                    $fields = acf_get_fields($field_group);
                    if ($fields) {
                        foreach ($fields as $field) {
                            $acf_fields[$field['name']] = $field['label'];
                        }
                    }
                }
            } else {
                // Only include fields from specifically selected field groups
                foreach ($specific_acf_groups as $group_key) {
                    $field_group = acf_get_field_group($group_key);
                    if ($field_group) {
                        $fields = acf_get_fields($field_group);
                        if ($fields) {
                            foreach ($fields as $field) {
                                $acf_fields[$field['name']] = $field['label'];
                            }
                        }
                    }
                }
            }
        }

        // Prepare headers based on selected post columns
        $headers = array();
        $post_column_mappings = array(
            'id' => 'ID',
            'title' => 'Post Title',
            'content' => 'Post Content',
            'excerpt' => 'Post Excerpt',
            'status' => 'Post Status',
            'date' => 'Post Date',
            'modified' => 'Post Modified',
            'url' => 'Post URL',
            'slug' => 'Post Slug',
            'author' => 'Post Author'
        );

        // Only include selected post columns
        if (in_array('post_columns', $export_options, true)) {
            foreach ($selected_post_columns as $column) {
                if (isset($post_column_mappings[$column])) {
                    $headers[] = $post_column_mappings[$column];
                }
            }
        }

        // Add taxonomy headers only for selected taxonomies
        if (in_array('taxonomies', $export_options, true) && !empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $headers[] = $taxonomy->label;
            }
        }

        // Add ACF field headers only for selected fields
        if (in_array('acf_fields', $export_options, true) && !empty($acf_fields)) {
            foreach ($acf_fields as $field_name => $field_label) {
                $headers[] = $field_label;
            }
        }

        // Add featured image header if selected
        if (in_array('featured_image', $export_options, true)) {
            $headers[] = 'Featured Image URL';
        }

        // Prepare CSV data
        $csv_data = array();
        $csv_data[] = $headers;

        // Process each post
        foreach ($posts as $post) {
            $row = array();

            // Add post columns based on selection
            if (in_array('post_columns', $export_options, true)) {
                foreach ($selected_post_columns as $column) {
                    switch ($column) {
                        case 'id':
                            $row[] = $post->ID;
                            break;
                        case 'title':
                            $row[] = $post->post_title;
                            break;
                        case 'content':
                            $row[] = $post->post_content;
                            break;
                        case 'excerpt':
                            $row[] = $post->post_excerpt;
                            break;
                        case 'status':
                            $row[] = $post->post_status;
                            break;
                        case 'date':
                            $row[] = $post->post_date;
                            break;
                        case 'modified':
                            $row[] = $post->post_modified;
                            break;
                        case 'url':
                            $row[] = get_permalink($post->ID);
                            break;
                        case 'slug':
                            $row[] = $post->post_name;
                            break;
                        case 'author':
                            $author = get_userdata($post->post_author);
                            $row[] = $author ? $author->display_name : '';
                            break;
                        default:
                            $row[] = '';
                            break;
                    }
                }
            }

            // Add taxonomy values with proper escaping
            if (in_array('taxonomies', $export_options, true)) {
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($post->ID, $taxonomy->name, array('fields' => 'names'));
                    $row[] = !is_wp_error($terms) ? 
                        implode(', ', $terms) : 
                        '';
                }
            }

            // Add ACF field values with proper escaping
            if (in_array('acf_fields', $export_options, true)) {
                foreach ($acf_fields as $field_name => $field_label) {
                    $value = get_field($field_name, $post->ID);
                    if (is_array($value)) {
                        $value = wp_json_encode($value);
                    }
                    $row[] = $value;
                }
            }

            // Add featured image with proper URL escaping
            if (in_array('featured_image', $export_options, true)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'full');
                $row[] = $image_url ? $image_url : '';
            }

            $csv_data[] = $row;
        }

        // Generate filename
        $filename = sanitize_file_name($post_type) . '-export-' . gmdate('Y-m-d') . '.csv';

        wp_send_json_success(array(
            'data' => $csv_data,
            'filename' => $filename,
            'total' => count($posts)
        ));
    }

    /**
     * Handle export functionality
     */
    public function handle_export() {
        // Verify nonce and user capabilities first
        if (!isset($_POST['amfm_export']) || 
            !check_admin_referer('amfm_export_nonce', 'amfm_export_nonce') || 
            !current_user_can('manage_options')) {
            return;
        }

        if (empty($_POST['export_post_type'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Please select a post type to export.', 'amfm-tools') . '</p></div>';
            });
            return;
        }

        // Sanitize and validate post type
        $post_type = sanitize_key(wp_unslash($_POST['export_post_type']));
        if (!post_type_exists($post_type)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid post type selected.', 'amfm-tools') . '</p></div>';
            });
            return;
        }

        // Sanitize export options
        $export_options = isset($_POST['export_options']) ? 
            array_map('sanitize_key', wp_unslash($_POST['export_options'])) : 
            array();
        
        // Validate and sanitize taxonomy selection
        $taxonomy_selection = isset($_POST['taxonomy_selection']) ? 
            sanitize_key(wp_unslash($_POST['taxonomy_selection'])) : 
            'all';
        if (!in_array($taxonomy_selection, array('all', 'selected'), true)) {
            $taxonomy_selection = 'all';
        }

        // Sanitize specific taxonomies
        $specific_taxonomies = isset($_POST['specific_taxonomies']) ? 
            array_map('sanitize_key', wp_unslash($_POST['specific_taxonomies'])) : 
            array();
        
        // Validate and sanitize ACF selection
        $acf_selection = isset($_POST['acf_selection']) ? 
            sanitize_key(wp_unslash($_POST['acf_selection'])) : 
            'all';
        if (!in_array($acf_selection, array('all', 'selected'), true)) {
            $acf_selection = 'all';
        }

        // Sanitize specific ACF groups
        $specific_acf_groups = isset($_POST['specific_acf_groups']) ? 
            array_map('sanitize_key', wp_unslash($_POST['specific_acf_groups'])) : 
            array();

        // Get all posts of the selected type
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));

        if (empty($posts)) {
            add_action('admin_notices', function() use ($post_type) {
                echo '<div class="notice notice-error"><p>' . sprintf(
                    esc_html__('No posts found for the post type: %s', 'amfm-tools'),
                    esc_html($post_type)
                ) . '</p></div>';
            });
            return;
        }

        // Get taxonomies based on selection
        $taxonomies = array();
        if (in_array('taxonomies', $export_options, true)) {
            if ($taxonomy_selection === 'all') {
                $taxonomies = get_object_taxonomies($post_type, 'objects');
            } else {
                // Only include specifically selected taxonomies
                foreach ($specific_taxonomies as $tax_name) {
                    if (taxonomy_exists($tax_name)) {
                        $taxonomy = get_taxonomy($tax_name);
                        if ($taxonomy) {
                            $taxonomies[$tax_name] = $taxonomy;
                        }
                    }
                }
            }
        }

        // Initialize ACF fields array based on selection
        $acf_fields = array();
        if (in_array('acf_fields', $export_options, true) && function_exists('acf_get_field_groups')) {
            if ($acf_selection === 'all') {
                // Get all field groups and their fields
                $field_groups = acf_get_field_groups();
                foreach ($field_groups as $field_group) {
                    $fields = acf_get_fields($field_group);
                    if ($fields) {
                        foreach ($fields as $field) {
                            $acf_fields[$field['name']] = $field['label'];
                        }
                    }
                }
            } else {
                // Only include fields from specifically selected field groups
                foreach ($specific_acf_groups as $group_key) {
                    $field_group = acf_get_field_group($group_key);
                    if ($field_group) {
                        $fields = acf_get_fields($field_group);
                        if ($fields) {
                            foreach ($fields as $field) {
                                $acf_fields[$field['name']] = $field['label'];
                            }
                        }
                    }
                }
            }
        }

        // Prepare headers with proper escaping
        $headers = array(
            esc_html__('ID', 'amfm-tools'),
            esc_html__('Post Title', 'amfm-tools'),
            esc_html__('Post Content', 'amfm-tools'),
            esc_html__('Post Excerpt', 'amfm-tools'),
            esc_html__('Post Status', 'amfm-tools'),
            esc_html__('Post Date', 'amfm-tools'),
            esc_html__('Post Modified', 'amfm-tools'),
            esc_html__('Post URL', 'amfm-tools')
        );

        // Add taxonomy headers only for selected taxonomies
        if (in_array('taxonomies', $export_options, true) && !empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $headers[] = esc_html($taxonomy->label);
            }
        }

        // Add ACF field headers only for selected fields
        if (in_array('acf_fields', $export_options, true) && !empty($acf_fields)) {
            foreach ($acf_fields as $field_name => $field_label) {
                $headers[] = esc_html($field_label);
            }
        }

        // Add featured image header if selected
        if (in_array('featured_image', $export_options, true)) {
            $headers[] = esc_html__('Featured Image URL', 'amfm-tools');
        }

        // Use WordPress filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        // Create temporary file with proper permissions
        $temp_file = wp_tempnam('amfm-export');
        if (!$temp_file) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error creating temporary file for export.', 'amfm-tools') . '</p></div>';
            });
            return;
        }

        // Add BOM for Excel
        $wp_filesystem->put_contents($temp_file, "\xEF\xBB\xBF");

        // Prepare CSV content with proper escaping
        $csv_content = $this->array_to_csv($headers);

        // Process each post
        foreach ($posts as $post) {
            $row = array(
                absint($post->ID),
                sanitize_text_field($post->post_title),
                wp_kses_post($post->post_content),
                sanitize_textarea_field($post->post_excerpt),
                sanitize_key($post->post_status),
                sanitize_text_field($post->post_date),
                sanitize_text_field($post->post_modified),
                esc_url_raw(get_permalink($post->ID))
            );

            // Add taxonomy values with proper escaping
            if (in_array('taxonomies', $export_options, true)) {
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($post->ID, $taxonomy->name, array('fields' => 'names'));
                    $row[] = !is_wp_error($terms) ? 
                        implode(', ', array_map('sanitize_text_field', $terms)) : 
                        '';
                }
            }

            // Add ACF field values with proper escaping
            if (in_array('acf_fields', $export_options, true)) {
                foreach ($acf_fields as $field_name => $field_label) {
                    $value = get_field($field_name, $post->ID);
                    if (is_array($value)) {
                        $value = wp_json_encode($value);
                    }
                    $row[] = sanitize_text_field($value);
                }
            }

            // Add featured image with proper URL escaping
            if (in_array('featured_image', $export_options, true)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'full');
                $row[] = $image_url ? esc_url_raw($image_url) : '';
            }

            $csv_content .= $this->array_to_csv($row);
        }

        // Write content to file with proper permissions
        if (false === $wp_filesystem->put_contents($temp_file, $csv_content, FS_CHMOD_FILE)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error writing export data to file.', 'amfm-tools') . '</p></div>';
            });
            return;
        }

        // Set proper headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header(sprintf(
            'Content-Disposition: attachment; filename="%s-export-%s.csv"',
            sanitize_file_name($post_type),
            sanitize_file_name(gmdate('Y-m-d'))
        ));
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output the file contents and delete temp file
        if ($wp_filesystem->exists($temp_file)) {
            // Use wp_kses_post for the final output to ensure it's safe
            echo wp_kses_post($wp_filesystem->get_contents($temp_file));
            $wp_filesystem->delete($temp_file);
        }
        exit;
    }

    /**
     * Convert array to CSV line
     *
     * @param array $fields Array of fields to convert to CSV
     * @return string CSV formatted line with proper escaping
     */
    private function array_to_csv($fields) {
        global $wp_filesystem;
        
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        // Create a temporary file
        $temp_file = wp_tempnam('csv-line');
        if (!$temp_file) {
            return '';
        }

        // Format the fields as CSV
        $output = array();
        foreach ($fields as $field) {
            $output[] = '"' . str_replace('"', '""', $field) . '"';
        }
        $csv_line = implode(',', $output) . "\n";

        // Write to temp file and read back
        $wp_filesystem->put_contents($temp_file, $csv_line);
        $result = $wp_filesystem->get_contents($temp_file);
        
        // Clean up
        $wp_filesystem->delete($temp_file);
        
        return $result;
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
                                    <div class="amfm-feature-icon">📊</div>
                                    <h3>Import/Export Tools</h3>
                                    <p>Comprehensive data management tools for importing keywords, categories and exporting posts with ACF fields, taxonomies, and metadata to CSV.</p>
                                    <a href="<?php echo admin_url( 'admin.php?page=amfm-tools&tab=import-export' ); ?>" class="amfm-feature-link">
                                        Go to Import/Export →
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

                <?php elseif ( $active_tab === 'import-export' ) : ?>
                    <!-- Import/Export Tab Content -->
                    <div class="amfm-tab-content">
                        <?php if ( $show_results || $show_category_results ) : ?>
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
                        <?php else : ?>
                            <?php
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
                                    </div>
                                </div>
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

                <?php endif; ?>
            </div>
        </div>

        <?php
    }
}
