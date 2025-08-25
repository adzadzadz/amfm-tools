<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * CSV Export Service - handles CSV data export functionality
 * 
 * Provides clean, reusable export logic with proper validation and security
 */
class CsvExportService extends Service
{
    /**
     * Export data based on provided options
     */
    public function exportData(array $options): array
    {
        // Validate required parameters
        $postType = $this->validatePostType($options['export_post_type'] ?? '');
        
        // Get posts
        $posts = $this->getPosts($postType);
        if (empty($posts)) {
            throw new \Exception(sprintf('No posts found for post type: %s', esc_html($postType)));
        }

        // Build export data
        $csvData = $this->buildCsvData($posts, $postType, $options);
        $filename = $this->generateFilename($postType);

        return [
            'data' => $csvData,
            'filename' => $filename,
            'total' => count($posts)
        ];
    }

    /**
     * Handle direct export from form submission
     */
    public function handleExport(): void
    {
        // Verify nonce and user capabilities first
        if (!isset($_POST['amfm_export']) ||
            !check_admin_referer('amfm_export_nonce', 'amfm_export_nonce') ||
            !current_user_can('manage_options')) {
            return;
        }

        try {
            $this->processExport();
        } catch (\Exception $e) {
            $this->addNotice('Export failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Process the export with full validation
     */
    private function processExport(): void
    {
        // Validate form data
        if (empty($_POST['export_post_type'])) {
            throw new \Exception('Please select a post type to export.');
        }

        // Sanitize and validate post type
        $postType = sanitize_key(wp_unslash($_POST['export_post_type']));
        if (!post_type_exists($postType)) {
            throw new \Exception('Invalid post type selected.');
        }

        // Process export options
        $exportOptions = $this->processExportOptions();
        
        // Get posts
        $posts = get_posts([
            'post_type' => $postType,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        if (empty($posts)) {
            throw new \Exception(sprintf('No posts found for the post type: %s', esc_html($postType)));
        }

        // Build export data
        $headers = $this->buildExportHeaders($postType, $exportOptions);
        $csvData = $this->buildExportData($posts, $postType, $exportOptions, $headers);
        
        // Output CSV
        $this->outputDirectCsv($csvData, $postType);
    }

    /**
     * Process and validate export options
     */
    private function processExportOptions(): array
    {
        $exportOptions = isset($_POST['export_options']) ?
            array_map('sanitize_key', wp_unslash($_POST['export_options'])) :
            [];

        $taxonomySelection = isset($_POST['taxonomy_selection']) ?
            sanitize_key(wp_unslash($_POST['taxonomy_selection'])) :
            'all';
        if (!in_array($taxonomySelection, ['all', 'selected'], true)) {
            $taxonomySelection = 'all';
        }

        $specificTaxonomies = isset($_POST['specific_taxonomies']) ?
            array_map('sanitize_key', wp_unslash($_POST['specific_taxonomies'])) :
            [];

        $acfSelection = isset($_POST['acf_selection']) ?
            sanitize_key(wp_unslash($_POST['acf_selection'])) :
            'all';
        if (!in_array($acfSelection, ['all', 'selected'], true)) {
            $acfSelection = 'all';
        }

        $specificAcfGroups = isset($_POST['specific_acf_groups']) ?
            array_map('sanitize_key', wp_unslash($_POST['specific_acf_groups'])) :
            [];

        // Handle post data selection
        $postDataSelection = isset($_POST['post_data_selection']) ?
            sanitize_key(wp_unslash($_POST['post_data_selection'])) :
            'all';
        if (!in_array($postDataSelection, ['all', 'selected'], true)) {
            $postDataSelection = 'all';
        }

        $specificPostColumns = isset($_POST['specific_post_columns']) ?
            array_map('sanitize_key', wp_unslash($_POST['specific_post_columns'])) :
            [];

        return [
            'export_options' => $exportOptions,
            'taxonomy_selection' => $taxonomySelection,
            'specific_taxonomies' => $specificTaxonomies,
            'acf_selection' => $acfSelection,
            'specific_acf_groups' => $specificAcfGroups,
            'post_data_selection' => $postDataSelection,
            'specific_post_columns' => $specificPostColumns
        ];
    }

    /**
     * Build export headers based on options
     */
    private function buildExportHeaders(string $postType, array $options): array
    {
        $headers = [];

        // Add post data headers only if post_data option is selected
        if (in_array('post_data', $options['export_options'], true)) {
            $postColumns = $this->getSelectedPostColumnsForExport($options);
            $headers = array_merge($headers, $this->getPostColumnHeaders($postColumns));
        }

        // Add taxonomy headers
        if (in_array('taxonomies', $options['export_options'], true)) {
            $taxonomies = $this->getTaxonomiesForExport($postType, $options);
            foreach ($taxonomies as $taxonomy) {
                $headers[] = esc_html($taxonomy->label);
            }
        }

        // Add ACF field headers
        if (in_array('acf_fields', $options['export_options'], true)) {
            $acfFields = $this->getAcfFieldsForExport($options);
            foreach ($acfFields as $fieldName => $fieldLabel) {
                $headers[] = esc_html($fieldLabel);
            }
        }

        // Add featured image header
        if (in_array('featured_image', $options['export_options'], true)) {
            $headers[] = esc_html__('Featured Image URL', 'amfm-tools');
        }

        return $headers;
    }

    /**
     * Build export data for all posts
     */
    private function buildExportData(array $posts, string $postType, array $options, array $headers): array
    {
        $csvData = [$headers];

        foreach ($posts as $post) {
            $row = [];

            // Add post data only if post_data option is selected
            if (in_array('post_data', $options['export_options'], true)) {
                $postColumns = $this->getSelectedPostColumnsForExport($options);
                $row = array_merge($row, $this->getPostColumnDataForExport($post, $postColumns));
            }

            // Add taxonomy values
            if (in_array('taxonomies', $options['export_options'], true)) {
                $taxonomies = $this->getTaxonomiesForExport($postType, $options);
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($post->ID, $taxonomy->name, ['fields' => 'names']);
                    $row[] = !is_wp_error($terms) ?
                        implode(', ', array_map('sanitize_text_field', $terms)) :
                        '';
                }
            }

            // Add ACF field values
            if (in_array('acf_fields', $options['export_options'], true)) {
                $acfFields = $this->getAcfFieldsForExport($options);
                foreach ($acfFields as $fieldName => $fieldLabel) {
                    $value = get_field($fieldName, $post->ID);
                    if (is_array($value)) {
                        $value = wp_json_encode($value);
                    }
                    $row[] = sanitize_text_field($value);
                }
            }

            // Add featured image
            if (in_array('featured_image', $options['export_options'], true)) {
                $imageUrl = get_the_post_thumbnail_url($post->ID, 'full');
                $row[] = $imageUrl ? esc_url_raw($imageUrl) : '';
            }

            $csvData[] = $row;
        }

        return $csvData;
    }

    /**
     * Output CSV directly to browser
     */
    private function outputDirectCsv(array $data, string $postType): void
    {
        // Set proper headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header(sprintf(
            'Content-Disposition: attachment; filename="%s-export-%s.csv"',
            sanitize_file_name($postType),
            sanitize_file_name(gmdate('Y-m-d'))
        ));
        header('Pragma: no-cache');
        header('Expires: 0');

        // Add BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        // Output CSV data
        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Validate and sanitize post type
     */
    private function validatePostType(string $postType): string
    {
        if (empty($postType)) {
            throw new \Exception('Please select a post type to export.');
        }

        $postType = sanitize_key($postType);
        if (!post_type_exists($postType)) {
            throw new \Exception('Invalid post type selected.');
        }

        return $postType;
    }


    /**
     * Get posts for export
     */
    private function getPosts(string $postType): array
    {
        return get_posts([
            'post_type' => $postType,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
    }

    /**
     * Build CSV data structure
     */
    private function buildCsvData(array $posts, string $postType, array $options): array
    {
        $headers = $this->buildHeaders($postType, $options);
        $csvData = [$headers];

        foreach ($posts as $post) {
            $row = $this->buildPostRow($post, $postType, $options);
            $csvData[] = $row;
        }

        return $csvData;
    }

    /**
     * Build CSV headers
     */
    private function buildHeaders(string $postType, array $options): array
    {
        $headers = [];
        $exportOptions = $options['export_options'] ?? [];

        // Post columns
        if (in_array('post_columns', $exportOptions, true)) {
            $selectedColumns = $this->getSelectedPostColumns($options);
            $headers = array_merge($headers, $this->getPostColumnHeaders($selectedColumns));
        }

        // Taxonomies
        if (in_array('taxonomies', $exportOptions, true)) {
            $taxonomies = $this->getTaxonomiesForExport($postType, $options);
            foreach ($taxonomies as $taxonomy) {
                $headers[] = $taxonomy->label;
            }
        }

        // ACF Fields
        if (in_array('acf_fields', $exportOptions, true)) {
            $acfFields = $this->getAcfFieldsForExport($options);
            foreach ($acfFields as $fieldLabel) {
                $headers[] = $fieldLabel;
            }
        }

        // Featured image
        if (in_array('featured_image', $exportOptions, true)) {
            $headers[] = 'Featured Image URL';
        }

        return $headers;
    }

    /**
     * Build row data for a single post
     */
    private function buildPostRow(\WP_Post $post, string $postType, array $options): array
    {
        $row = [];
        $exportOptions = $options['export_options'] ?? [];

        // Post columns
        if (in_array('post_columns', $exportOptions, true)) {
            $selectedColumns = $this->getSelectedPostColumns($options);
            $row = array_merge($row, $this->getPostColumnData($post, $selectedColumns));
        }

        // Taxonomies
        if (in_array('taxonomies', $exportOptions, true)) {
            $taxonomies = $this->getTaxonomiesForExport($postType, $options);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post->ID, $taxonomy->name, ['fields' => 'names']);
                $row[] = !is_wp_error($terms) ? implode(', ', $terms) : '';
            }
        }

        // ACF Fields
        if (in_array('acf_fields', $exportOptions, true)) {
            $acfFields = $this->getAcfFieldsForExport($options);
            foreach (array_keys($acfFields) as $fieldName) {
                $value = get_field($fieldName, $post->ID);
                if (is_array($value)) {
                    $value = wp_json_encode($value);
                }
                $row[] = $value ?: '';
            }
        }

        // Featured image
        if (in_array('featured_image', $exportOptions, true)) {
            $imageUrl = get_the_post_thumbnail_url($post->ID, 'full');
            $row[] = $imageUrl ?: '';
        }

        return $row;
    }

    /**
     * Get selected post columns for export based on options
     */
    private function getSelectedPostColumnsForExport(array $options): array
    {
        $postDataSelection = $options['post_data_selection'] ?? 'all';
        
        if ($postDataSelection === 'all') {
            // Return all available post columns including ID
            return ['id', 'post_title', 'post_content', 'post_excerpt', 'post_status', 
                    'post_date', 'post_modified', 'post_author', 'post_name', 
                    'menu_order', 'comment_status', 'ping_status', 'post_parent'];
        }
        
        // Always include ID even if not selected, as it's required for imports
        $selected = $options['specific_post_columns'] ?? [];
        $selected = is_array($selected) ? array_map('sanitize_key', $selected) : [];
        
        // Ensure ID is always first
        if (!in_array('id', $selected)) {
            array_unshift($selected, 'id');
        }
        
        return $selected;
    }

    /**
     * Get selected post columns with defaults (legacy method)
     */
    private function getSelectedPostColumns(array $options): array
    {
        $selected = $options['post_columns'] ?? ['id', 'title'];
        return is_array($selected) ? array_map('sanitize_key', $selected) : ['id', 'title'];
    }

    /**
     * Get post column headers
     */
    private function getPostColumnHeaders(array $columns): array
    {
        $mappings = [
            'id' => esc_html__('ID', 'amfm-tools'),
            'post_title' => esc_html__('Post Title', 'amfm-tools'),
            'post_content' => esc_html__('Post Content', 'amfm-tools'),
            'post_excerpt' => esc_html__('Post Excerpt', 'amfm-tools'),
            'post_status' => esc_html__('Post Status', 'amfm-tools'),
            'post_date' => esc_html__('Post Date', 'amfm-tools'),
            'post_modified' => esc_html__('Post Modified', 'amfm-tools'),
            'post_author' => esc_html__('Post Author', 'amfm-tools'),
            'post_name' => esc_html__('Post Slug', 'amfm-tools'),
            'menu_order' => esc_html__('Menu Order', 'amfm-tools'),
            'comment_status' => esc_html__('Comment Status', 'amfm-tools'),
            'ping_status' => esc_html__('Ping Status', 'amfm-tools'),
            'post_parent' => esc_html__('Post Parent', 'amfm-tools'),
            // Legacy mappings
            'title' => esc_html__('Post Title', 'amfm-tools'),
            'content' => esc_html__('Post Content', 'amfm-tools'),
            'excerpt' => esc_html__('Post Excerpt', 'amfm-tools'),
            'status' => esc_html__('Post Status', 'amfm-tools'),
            'date' => esc_html__('Post Date', 'amfm-tools'),
            'modified' => esc_html__('Post Modified', 'amfm-tools'),
            'url' => esc_html__('Post URL', 'amfm-tools'),
            'slug' => esc_html__('Post Slug', 'amfm-tools'),
            'author' => esc_html__('Post Author', 'amfm-tools')
        ];

        $headers = [];
        foreach ($columns as $column) {
            if (isset($mappings[$column])) {
                $headers[] = $mappings[$column];
            }
        }

        return $headers;
    }

    /**
     * Get post column data for export
     */
    private function getPostColumnDataForExport(\WP_Post $post, array $columns): array
    {
        $data = [];
        
        foreach ($columns as $column) {
            switch ($column) {
                case 'id':
                    $data[] = absint($post->ID);
                    break;
                case 'post_title':
                case 'title':
                    $data[] = sanitize_text_field($post->post_title);
                    break;
                case 'post_content':
                case 'content':
                    $data[] = wp_kses_post($post->post_content);
                    break;
                case 'post_excerpt':
                case 'excerpt':
                    $data[] = sanitize_textarea_field($post->post_excerpt);
                    break;
                case 'post_status':
                case 'status':
                    $data[] = sanitize_key($post->post_status);
                    break;
                case 'post_date':
                case 'date':
                    $data[] = sanitize_text_field($post->post_date);
                    break;
                case 'post_modified':
                case 'modified':
                    $data[] = sanitize_text_field($post->post_modified);
                    break;
                case 'post_author':
                case 'author':
                    $author = get_userdata($post->post_author);
                    $data[] = $author ? sanitize_text_field($author->display_name) : '';
                    break;
                case 'post_name':
                case 'slug':
                    $data[] = sanitize_title($post->post_name);
                    break;
                case 'menu_order':
                    $data[] = absint($post->menu_order);
                    break;
                case 'comment_status':
                    $data[] = sanitize_key($post->comment_status);
                    break;
                case 'ping_status':
                    $data[] = sanitize_key($post->ping_status);
                    break;
                case 'post_parent':
                    $data[] = absint($post->post_parent);
                    break;
                case 'url':
                    $data[] = esc_url_raw(get_permalink($post->ID));
                    break;
                default:
                    $data[] = '';
                    break;
            }
        }

        return $data;
    }

    /**
     * Get post column data (legacy method)
     */
    private function getPostColumnData(\WP_Post $post, array $columns): array
    {
        $data = [];
        
        foreach ($columns as $column) {
            switch ($column) {
                case 'id':
                    $data[] = $post->ID;
                    break;
                case 'title':
                    $data[] = $post->post_title;
                    break;
                case 'content':
                    $data[] = $post->post_content;
                    break;
                case 'excerpt':
                    $data[] = $post->post_excerpt;
                    break;
                case 'status':
                    $data[] = $post->post_status;
                    break;
                case 'date':
                    $data[] = $post->post_date;
                    break;
                case 'modified':
                    $data[] = $post->post_modified;
                    break;
                case 'url':
                    $data[] = get_permalink($post->ID);
                    break;
                case 'slug':
                    $data[] = $post->post_name;
                    break;
                case 'author':
                    $author = get_userdata($post->post_author);
                    $data[] = $author ? $author->display_name : '';
                    break;
                default:
                    $data[] = '';
                    break;
            }
        }

        return $data;
    }

    /**
     * Get taxonomies for export based on selection
     */
    private function getTaxonomiesForExport(string $postType, array $options): array
    {
        $taxonomySelection = sanitize_key($options['taxonomy_selection'] ?? 'all');
        
        if ($taxonomySelection === 'all') {
            return get_object_taxonomies($postType, 'objects');
        }
        
        // Get specific taxonomies
        $specificTaxonomies = $options['specific_taxonomies'] ?? [];
        $specificTaxonomies = is_array($specificTaxonomies) ? array_map('sanitize_key', $specificTaxonomies) : [];
        
        $taxonomies = [];
        foreach ($specificTaxonomies as $taxName) {
            if (taxonomy_exists($taxName)) {
                $taxonomy = get_taxonomy($taxName);
                if ($taxonomy) {
                    $taxonomies[$taxName] = $taxonomy;
                }
            }
        }

        return $taxonomies;
    }

    /**
     * Get ACF fields for export based on selection
     */
    private function getAcfFieldsForExport(array $options): array
    {
        if (!function_exists('acf_get_field_groups')) {
            return [];
        }

        $acfSelection = sanitize_key($options['acf_selection'] ?? 'all');
        $acfFields = [];
        
        if ($acfSelection === 'all') {
            // Get all field groups and their fields
            $fieldGroups = acf_get_field_groups();
            foreach ($fieldGroups as $fieldGroup) {
                $fields = acf_get_fields($fieldGroup);
                if ($fields) {
                    foreach ($fields as $field) {
                        $acfFields[$field['name']] = $field['label'];
                    }
                }
            }
        } else {
            // Get specific field groups
            $specificGroups = $options['specific_acf_groups'] ?? [];
            $specificGroups = is_array($specificGroups) ? array_map('sanitize_key', $specificGroups) : [];
            
            foreach ($specificGroups as $groupKey) {
                $fieldGroup = acf_get_field_group($groupKey);
                if ($fieldGroup) {
                    $fields = acf_get_fields($fieldGroup);
                    if ($fields) {
                        foreach ($fields as $field) {
                            $acfFields[$field['name']] = $field['label'];
                        }
                    }
                }
            }
        }

        return $acfFields;
    }

    /**
     * Generate export filename
     */
    private function generateFilename(string $postType): string
    {
        return sanitize_file_name($postType) . '-export-' . gmdate('Y-m-d') . '.csv';
    }

    /**
     * Output CSV file for direct download
     */
    private function outputCsv(array $data, string $filename): void
    {
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

    /**
     * Verify nonce
     */
    private function verifyNonce(string $nonceAction): bool
    {
        return isset($_POST[$nonceAction]) && wp_verify_nonce($_POST[$nonceAction], $nonceAction);
    }

    /**
     * Add admin notice
     */
    private function addNotice(string $message, string $type = 'info'): void
    {
        add_action('admin_notices', function() use ($message, $type) {
            $class = $type === 'error' ? 'notice-error' : 'notice-success';
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
    }
}