<?php

namespace App\Services;

class AjaxHandler {
    
    /**
     * AJAX handler to get taxonomies for a post type
     */
    public function getPostTypeTaxonomies() {
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
     * AJAX handler to get ACF field groups
     */
    public function getACFFieldGroups() {
        // Check nonce
        if (!check_ajax_referer('amfm_export_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Check if ACF is available
        if (!function_exists('acf_get_field_groups')) {
            wp_send_json_error('ACF not available');
            return;
        }

        // Get all ACF field groups
        $field_groups = acf_get_field_groups();
        
        if (empty($field_groups)) {
            wp_send_json_error('No ACF field groups found');
            return;
        }

        // Format field groups for response
        $formatted_groups = array();
        foreach ($field_groups as $group) {
            $formatted_groups[] = array(
                'key' => $group['key'],
                'title' => $group['title']
            );
        }

        wp_send_json_success($formatted_groups);
    }

    /**
     * AJAX handler for export functionality
     */
    public function exportData() {
        // Ensure this is an AJAX request
        if (!wp_doing_ajax()) {
            wp_send_json_error('Not an AJAX request');
            return;
        }
        
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
     * AJAX handler for component settings update
     */
    public function updateComponentSettings() {
        // Check nonce and capabilities
        if (!check_ajax_referer('amfm_component_settings_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $components = isset($_POST['components']) ? 
            array_map('sanitize_text_field', $_POST['components']) : [];
        
        // Always ensure core components are included
        $core_components = ['acf_helper', 'import_export'];
        $components = array_merge($components, $core_components);
        $components = array_unique($components);
        
        update_option('amfm_enabled_components', $components);
        
        wp_send_json_success('Component settings updated successfully');
    }

    /**
     * AJAX handler for Elementor widgets update
     */
    public function updateElementorWidgets() {
        // Check nonce and capabilities
        if (!check_ajax_referer('amfm_elementor_widgets_nonce', 'nonce', false) || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $widgets = isset($_POST['widgets']) ? 
            array_map('sanitize_text_field', $_POST['widgets']) : [];
        
        update_option('amfm_elementor_enabled_widgets', $widgets);
        
        wp_send_json_success('Widget settings updated successfully');
    }
}