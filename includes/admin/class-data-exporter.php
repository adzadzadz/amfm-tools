<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Data_Exporter {
    
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
}