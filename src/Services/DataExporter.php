<?php

namespace App\Services;

class DataExporter {
    
    /**
     * Handle export functionality (only for non-AJAX requests)
     */
    public function handleExport() {
        // Skip if this is an AJAX request - AJAX is handled separately
        if (wp_doing_ajax()) {
            return;
        }
        
        // Check if export request was made
        if (!isset($_POST['amfm_export_nonce']) || 
            !wp_verify_nonce($_POST['amfm_export_nonce'], 'amfm_export_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Process export
        $this->processExport();
    }

    /**
     * Process the export
     */
    private function processExport() {
        if (empty($_POST['export_post_type'])) {
            return;
        }

        $post_type = sanitize_key($_POST['export_post_type']);
        $export_options = isset($_POST['export_options']) ? 
            array_map('sanitize_key', $_POST['export_options']) : [];

        // Get posts
        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        if (empty($posts)) {
            return;
        }

        // Build CSV data
        $csv_data = [];
        $headers = [];

        // Add post columns
        if (in_array('post_columns', $export_options)) {
            $selected_columns = isset($_POST['post_columns']) ? 
                array_map('sanitize_key', $_POST['post_columns']) : ['id', 'title'];
            
            foreach ($selected_columns as $column) {
                switch ($column) {
                    case 'id': $headers[] = 'ID'; break;
                    case 'title': $headers[] = 'Post Title'; break;
                    case 'content': $headers[] = 'Post Content'; break;
                    case 'excerpt': $headers[] = 'Post Excerpt'; break;
                    case 'status': $headers[] = 'Post Status'; break;
                    case 'date': $headers[] = 'Post Date'; break;
                    case 'modified': $headers[] = 'Post Modified'; break;
                    case 'url': $headers[] = 'Post URL'; break;
                    case 'slug': $headers[] = 'Post Slug'; break;
                    case 'author': $headers[] = 'Post Author'; break;
                }
            }
        }

        // Add taxonomy headers
        if (in_array('taxonomies', $export_options)) {
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $taxonomy) {
                $headers[] = $taxonomy->label;
            }
        }

        // Add ACF headers
        if (in_array('acf_fields', $export_options) && function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();
            foreach ($field_groups as $field_group) {
                $fields = acf_get_fields($field_group);
                if ($fields) {
                    foreach ($fields as $field) {
                        $headers[] = $field['label'];
                    }
                }
            }
        }

        // Add featured image header
        if (in_array('featured_image', $export_options)) {
            $headers[] = 'Featured Image URL';
        }

        $csv_data[] = $headers;

        // Process each post
        foreach ($posts as $post) {
            $row = [];

            // Add post data
            if (in_array('post_columns', $export_options)) {
                foreach ($selected_columns as $column) {
                    switch ($column) {
                        case 'id': $row[] = $post->ID; break;
                        case 'title': $row[] = $post->post_title; break;
                        case 'content': $row[] = $post->post_content; break;
                        case 'excerpt': $row[] = $post->post_excerpt; break;
                        case 'status': $row[] = $post->post_status; break;
                        case 'date': $row[] = $post->post_date; break;
                        case 'modified': $row[] = $post->post_modified; break;
                        case 'url': $row[] = get_permalink($post->ID); break;
                        case 'slug': $row[] = $post->post_name; break;
                        case 'author': 
                            $author = get_userdata($post->post_author);
                            $row[] = $author ? $author->display_name : '';
                            break;
                    }
                }
            }

            // Add taxonomy data
            if (in_array('taxonomies', $export_options)) {
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($post->ID, $taxonomy->name, ['fields' => 'names']);
                    $row[] = !is_wp_error($terms) ? implode(', ', $terms) : '';
                }
            }

            // Add ACF data
            if (in_array('acf_fields', $export_options) && function_exists('get_field')) {
                foreach ($field_groups as $field_group) {
                    $fields = acf_get_fields($field_group);
                    if ($fields) {
                        foreach ($fields as $field) {
                            $value = get_field($field['name'], $post->ID);
                            if (is_array($value)) {
                                $value = json_encode($value);
                            }
                            $row[] = $value;
                        }
                    }
                }
            }

            // Add featured image
            if (in_array('featured_image', $export_options)) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'full');
                $row[] = $image_url ?: '';
            }

            $csv_data[] = $row;
        }

        // Output CSV
        $this->outputCSV($csv_data, $post_type . '-export-' . date('Y-m-d') . '.csv');
    }

    /**
     * Output CSV file
     */
    private function outputCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}