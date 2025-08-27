<?php

namespace App\Services;

use AdzWP\Core\Service;

/**
 * CSV Import Service - handles CSV file imports with validation
 * 
 * Provides secure and validated CSV import functionality for keywords and categories
 */
class CsvImportService extends Service
{
    /**
     * Handle keywords CSV upload
     */
    public function handleKeywordsUpload(): void
    {
        if (!$this->verifyNonce('amfm_csv_import_nonce', 'amfm_csv_import') || !current_user_can('manage_options')) {
            return;
        }

        $file = $this->validateUploadedFile('csv_file');
        if (!$file) {
            $this->addNotice('Error uploading file. Please try again.', 'error');
            return;
        }

        try {
            $results = $this->processKeywordsCsv($file['tmp_name']);
            set_transient('amfm_csv_import_results', $results, 300);
            wp_redirect(admin_url('admin.php?page=amfm-tools-import-export&imported=keywords'));
            exit;
        } catch (\Exception $e) {
            $this->addNotice('Import failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Handle categories CSV upload
     */
    public function handleCategoriesUpload(): void
    {
        if (!$this->verifyNonce('amfm_category_csv_import_nonce', 'amfm_category_csv_import') || !current_user_can('manage_options')) {
            return;
        }

        $file = $this->validateUploadedFile('category_csv_file');
        if (!$file) {
            $this->addNotice('Error uploading file. Please try again.', 'error');
            return;
        }

        try {
            $results = $this->processCategoriesCsv($file['tmp_name']);
            set_transient('amfm_category_csv_import_results', $results, 300);
            wp_redirect(admin_url('admin.php?page=amfm-tools-import-export&imported=categories'));
            exit;
        } catch (\Exception $e) {
            $this->addNotice('Import failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Handle unified CSV upload that matches export format
     */
    public function handleUnifiedCsvUpload(): void
    {
        if (!$this->verifyNonce('amfm_csv_import_nonce', 'amfm_csv_import') || !current_user_can('manage_options')) {
            return;
        }

        $file = $this->validateUploadedFile('csv_file');
        if (!$file) {
            $this->addNotice('Error uploading file. Please try again.', 'error');
            return;
        }

        try {
            $results = $this->processUnifiedCsv($file['tmp_name']);
            set_transient('amfm_unified_csv_import_results', $results, 300);
            wp_redirect(admin_url('admin.php?page=amfm-tools-import-export&imported=data'));
            exit;
        } catch (\Exception $e) {
            $this->addNotice('Import failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Handle unified CSV upload for AJAX requests
     * Returns data instead of redirecting
     */
    public function processUnifiedCsvForAjax(): array
    {
        // Direct file validation without framework dependencies
        $file = $this->validateUploadedFileForAjax('csv_file');
        if (!$file) {
            throw new \Exception('Error uploading file. Please try again.');
        }

        return $this->processUnifiedCsv($file['tmp_name']);
    }

    /**
     * Preview CSV file for AJAX requests
     */
    public function previewCsvForAjax(): array
    {
        $file = $this->validateUploadedFileForAjax('csv_file');
        if (!$file) {
            throw new \Exception('Error uploading file. Please try again.');
        }

        $handle = $this->openCsvFile($file['tmp_name']);
        $headers = fgetcsv($handle);
        
        // Clean headers and check for ID column
        if ($headers) {
            $headers = array_map('trim', $headers);
        }
        
        if (!$headers || !in_array('ID', $headers)) {
            fclose($handle);
            throw new \Exception('Invalid CSV format. ID column is required.');
        }

        $rows = [];
        $rowNumber = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Create row object with data and metadata
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = $row[$index] ?? '';
            }
            
            $rows[] = [
                'row_number' => $rowNumber,
                'post_id' => intval($rowData['ID'] ?? 0),
                'post_title' => $this->getPostTitle($rowData['ID'] ?? 0),
                'data' => $rowData,
                'status' => 'pending'
            ];
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows)
        ];
    }

    /**
     * Process a batch of CSV rows
     */
    public function processBatch(array $batchData): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'skipped' => 0,
            'processed_rows' => []
        ];

        $headers = $batchData['headers'] ?? [];
        $rows = $batchData['rows'] ?? [];

        // Clean headers before validation
        if (!empty($headers)) {
            $headers = array_map('trim', $headers);
        }
        
        if (empty($headers) || !in_array('ID', $headers)) {
            throw new \Exception('Invalid headers - ID column required.');
        }

        // Map headers to their indices
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $columnMap[$header] = $index;
        }

        foreach ($rows as $rowInfo) {
            $rowNumber = $rowInfo['row_number'];
            $rowData = $rowInfo['data'];
            
            // Convert associative array back to indexed array for processing
            $row = [];
            foreach ($headers as $header) {
                $row[] = $rowData[$header] ?? '';
            }

            try {
                $processResults = [
                    'success' => 0,
                    'errors' => 0,
                    'skipped' => 0,
                    'details' => []
                ];
                
                $this->processUnifiedRow($row, $columnMap, $headers, $rowNumber, $processResults);
                
                // Determine status based on results
                $status = 'completed';
                $message = 'Updated successfully';
                
                if ($processResults['errors'] > 0) {
                    $status = 'error';
                    $message = implode(', ', $processResults['details']);
                    $results['errors']++;
                } elseif ($processResults['success'] > 0) {
                    $status = 'completed';
                    $message = 'Updated successfully';
                    $results['success']++;
                } else {
                    $status = 'skipped';
                    $message = 'No changes (same values)';
                    $results['skipped']++;
                }
                
                $results['processed_rows'][] = [
                    'row_number' => $rowNumber,
                    'post_id' => intval($rowData['ID'] ?? 0),
                    'status' => $status,
                    'message' => $message,
                    'details' => $processResults['details']
                ];
                
            } catch (\Exception $e) {
                $results['processed_rows'][] = [
                    'row_number' => $rowNumber,
                    'post_id' => intval($rowData['ID'] ?? 0),
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'details' => []
                ];
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Get post title by ID
     */
    private function getPostTitle(int $postId): string
    {
        if (!$postId) {
            return 'Invalid ID';
        }
        
        $post = get_post($postId);
        return $post ? $post->post_title : 'Post not found';
    }

    /**
     * Validate uploaded file for AJAX requests (without framework dependencies)
     */
    private function validateUploadedFileForAjax(string $fieldName): ?array
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];
        $fileType = wp_check_filetype($file['name']);
        
        if ($fileType['ext'] !== 'csv') {
            throw new \Exception('Please upload a valid CSV file.');
        }

        return $file;
    }

    /**
     * Process unified CSV file that matches export format
     */
    private function processUnifiedCsv(string $filePath): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        $handle = $this->openCsvFile($filePath);
        $headers = fgetcsv($handle);
        
        // Clean headers and validate - must have ID column at minimum
        if ($headers) {
            $headers = array_map('trim', $headers);
        }
        
        if (!$headers || !in_array('ID', $headers)) {
            throw new \Exception('Invalid CSV format. ID column is required.');
        }

        $idIndex = array_search('ID', $headers);
        $rowNumber = 1;

        // Map headers to their indices for easier processing
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $columnMap[$header] = $index;
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            try {
                $this->processUnifiedRow($row, $columnMap, $headers, $rowNumber, $results);
            } catch (\Exception $e) {
                $results['details'][] = "Row {$rowNumber}: {$e->getMessage()}";
                $results['errors']++;
            }
        }

        fclose($handle);
        return $results;
    }

    /**
     * Process a single unified CSV row
     */
    private function processUnifiedRow(array $row, array $columnMap, array $headers, int $rowNumber, array &$results): void
    {
        if (count($row) <= $columnMap['ID']) {
            throw new \Exception('Invalid row format');
        }

        $postId = intval($row[$columnMap['ID']]);
        if (!$postId) {
            throw new \Exception('Invalid post ID');
        }

        $post = get_post($postId);
        if (!$post) {
            throw new \Exception("Post ID {$postId} not found");
        }

        $updated = [];
        $errors = [];
        $skipped = [];

        // Process each column
        foreach ($columnMap as $columnName => $index) {
            if ($index >= count($row) || $columnName === 'ID') {
                continue; // Skip if no data or ID column
            }
            
            $value = trim($row[$index]);
            if (empty($value)) {
                continue; // Skip empty values
            }

            try {
                $updateResult = $this->updatePostField($post, $columnName, $value, $updated);
                
                // If updatePostField returns false, it means the value was the same
                if ($updateResult === false) {
                    $skipped[] = $columnName;
                }
                
            } catch (\Exception $e) {
                $errors[] = "Failed to update {$columnName}: " . $e->getMessage();
            }
        }

        if (!empty($updated)) {
            $updatedFields = implode(', ', $updated);
            $results['details'][] = "Row {$rowNumber}: Updated post ID {$postId} ('{$post->post_title}') - {$updatedFields}";
            $results['success']++;
        }

        if (!empty($errors)) {
            $results['details'] = array_merge($results['details'], array_map(function($error) use ($rowNumber) {
                return "Row {$rowNumber}: {$error}";
            }, $errors));
            $results['errors'] += count($errors);
        }

        if (empty($updated) && empty($errors)) {
            $results['details'][] = "Row {$rowNumber}: No changes made to post ID {$postId} - all fields were empty";
        }
    }

    /**
     * Update a specific field on a post
     * Returns true if updated, false if same value (skipped), throws exception on error
     */
    private function updatePostField(\WP_Post $post, string $fieldName, string $value, array &$updated): bool
    {
        switch ($fieldName) {
            case 'Post Title':
                $currentValue = $post->post_title;
                $newValue = sanitize_text_field($value);
                if ($currentValue === $newValue) {
                    return false; // Same value, skipped
                }
                wp_update_post([
                    'ID' => $post->ID,
                    'post_title' => $newValue
                ]);
                $updated[] = 'Title';
                return true;

            case 'Post Content':
                $currentValue = $post->post_content;
                $newValue = wp_kses_post($value);
                if ($currentValue === $newValue) {
                    return false; // Same value, skipped
                }
                wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => $newValue
                ]);
                $updated[] = 'Content';
                return true;

            case 'Post Excerpt':
                $currentValue = $post->post_excerpt;
                $newValue = sanitize_textarea_field($value);
                if ($currentValue === $newValue) {
                    return false; // Same value, skipped
                }
                wp_update_post([
                    'ID' => $post->ID,
                    'post_excerpt' => $newValue
                ]);
                $updated[] = 'Excerpt';
                return true;

            case 'Featured Image URL':
                // For featured images, we'll always try to update (hard to compare URLs)
                $this->setFeaturedImageFromUrl($post->ID, $value);
                $updated[] = 'Featured Image';
                return true;

            default:
                error_log("AMFM Import Debug - Processing field: {$fieldName} with value: {$value}");
                
                // Check if it's a taxonomy
                if (taxonomy_exists($fieldName)) {
                    error_log("Field {$fieldName} identified as taxonomy");
                    return $this->updatePostTaxonomy($post->ID, $fieldName, $value, $updated);
                } elseif ($this->isAcfField($fieldName)) {
                    // Handle ACF field
                    error_log("Field {$fieldName} identified as ACF field");
                    return $this->updateAcfField($post->ID, $fieldName, $value, $updated);
                } else {
                    // Try to match by taxonomy label
                    $taxonomy = $this->getTaxonomyByLabel($fieldName);
                    if ($taxonomy) {
                        error_log("Field {$fieldName} identified as taxonomy by label: {$taxonomy->name}");
                        return $this->updatePostTaxonomy($post->ID, $taxonomy->name, $value, $updated);
                    }
                }
                error_log("Field {$fieldName} - Unknown field type, skipping");
                return false; // Unknown field type, skip
        }
    }

    /**
     * Update post taxonomy
     * Returns true if updated, false if same value (skipped)
     */
    private function updatePostTaxonomy(int $postId, string $taxonomy, string $value, array &$updated): bool
    {
        $terms = array_map('trim', explode(',', $value));
        $termIds = [];

        foreach ($terms as $termName) {
            if (empty($termName)) continue;
            
            $term = get_term_by('name', $termName, $taxonomy);
            if (!$term) {
                // Create term if it doesn't exist
                $newTerm = wp_insert_term($termName, $taxonomy);
                if (!is_wp_error($newTerm)) {
                    $termIds[] = $newTerm['term_id'];
                }
            } else {
                $termIds[] = $term->term_id;
            }
        }

        // Check if current terms are the same
        $currentTerms = wp_get_post_terms($postId, $taxonomy, ['fields' => 'ids']);
        if (!is_wp_error($currentTerms)) {
            sort($termIds);
            sort($currentTerms);
            if ($termIds === $currentTerms) {
                return false; // Same terms, skipped
            }
        }

        if (!empty($termIds)) {
            wp_set_post_terms($postId, $termIds, $taxonomy);
            $taxonomyObj = get_taxonomy($taxonomy);
            $updated[] = $taxonomyObj->label ?? $taxonomy;
            return true;
        }
        
        return false;
    }

    /**
     * Update ACF field
     * Returns true if updated, false if same value (skipped)
     */
    private function updateAcfField(int $postId, string $fieldName, string $value, array &$updated): bool
    {
        if (!function_exists('update_field')) {
            throw new \Exception('ACF is not available');
        }
        
        // Get current value for comparison
        $currentValue = get_field($fieldName, $postId);
        
        // Try to detect if it's JSON data
        $processedValue = $value;
        if ((str_starts_with($value, '{') && str_ends_with($value, '}')) || 
            (str_starts_with($value, '[') && str_ends_with($value, ']'))) {
            $decodedValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $processedValue = $decodedValue;
            }
        }
        
        // Debug logging for comparison
        error_log("AMFM Import Debug - Field: {$fieldName}, Post ID: {$postId}");
        error_log("Current Value: " . var_export($currentValue, true));
        error_log("New Value: " . var_export($processedValue, true));
        
        // Compare values (handle arrays and strings)
        if (is_array($processedValue) && is_array($currentValue)) {
            if ($processedValue === $currentValue) {
                error_log("Skipped - Arrays are identical");
                return false; // Same value, skipped
            }
        } elseif ((string)$processedValue === (string)$currentValue) {
            error_log("Skipped - String values are identical: '{$processedValue}' === '{$currentValue}'");
            return false; // Same value, skipped
        }
        
        error_log("Values are different - proceeding with update");
        
        $result = update_field($fieldName, $processedValue, $postId);
        if ($result !== false) {
            $updated[] = "ACF: {$fieldName}";
            return true;
        } else {
            throw new \Exception("Failed to update ACF field '{$fieldName}'");
        }
    }

    /**
     * Set featured image from URL
     */
    private function setFeaturedImageFromUrl(int $postId, string $imageUrl): void
    {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid image URL');
        }

        // Check if attachment already exists with this URL
        $attachmentId = attachment_url_to_postid($imageUrl);
        if ($attachmentId) {
            set_post_thumbnail($postId, $attachmentId);
        } else {
            // You could implement image download and attachment creation here
            // For now, we'll just skip invalid URLs
            throw new \Exception('Image URL could not be processed');
        }
    }

    /**
     * Check if field name is an ACF field (by field name)
     */
    private function isAcfField(string $fieldName): bool
    {
        if (!function_exists('acf_get_field_groups')) {
            return false;
        }

        $fieldGroups = acf_get_field_groups();
        foreach ($fieldGroups as $group) {
            $fields = acf_get_fields($group);
            if ($fields) {
                foreach ($fields as $field) {
                    if ($field['name'] === $fieldName) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    /**
     * Get taxonomy by label
     */
    private function getTaxonomyByLabel(string $label): ?\WP_Taxonomy
    {
        $taxonomies = get_taxonomies([], 'objects');
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->label === $label) {
                return $taxonomy;
            }
        }
        return null;
    }

    /**
     * Process keywords CSV file
     */
    private function processKeywordsCsv(string $filePath): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        $handle = $this->openCsvFile($filePath);
        $headers = fgetcsv($handle);
        
        // Validate headers
        if (!$this->validateKeywordsHeaders($headers)) {
            throw new \Exception('Invalid CSV format. Required headers: ID, Keywords');
        }

        $idIndex = array_search('ID', $headers);
        $keywordsIndex = array_search('Keywords', $headers);
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            try {
                $this->processKeywordsRow($row, $idIndex, $keywordsIndex, $rowNumber, $results);
            } catch (\Exception $e) {
                $results['details'][] = "Row {$rowNumber}: {$e->getMessage()}";
                $results['errors']++;
            }
        }

        fclose($handle);
        return $results;
    }

    /**
     * Process categories CSV file
     */
    private function processCategoriesCsv(string $filePath): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        $handle = $this->openCsvFile($filePath);
        $headers = fgetcsv($handle);
        
        // Validate headers
        if (!$this->validateCategoriesHeaders($headers)) {
            throw new \Exception('Invalid CSV format. Required headers: ID, Categories');
        }

        $headersLower = array_map('strtolower', $headers);
        $idIndex = array_search('id', $headersLower);
        $categoriesIndex = array_search('categories', $headersLower);
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            try {
                $this->processCategoriesRow($row, $idIndex, $categoriesIndex, $rowNumber, $results);
            } catch (\Exception $e) {
                $results['details'][] = "Row {$rowNumber}: {$e->getMessage()}";
                $results['errors']++;
            }
        }

        fclose($handle);
        return $results;
    }

    /**
     * Process a single keywords row
     */
    private function processKeywordsRow(array $row, int $idIndex, int $keywordsIndex, int $rowNumber, array &$results): void
    {
        if (count($row) <= max($idIndex, $keywordsIndex)) {
            throw new \Exception('Invalid row format');
        }

        $postId = intval($row[$idIndex]);
        $keywords = sanitize_text_field($row[$keywordsIndex]);

        if (!$postId) {
            throw new \Exception('Invalid post ID');
        }

        $post = get_post($postId);
        if (!$post) {
            throw new \Exception("Post ID {$postId} not found");
        }

        // Update the amfm_keywords ACF field
        $existingValue = get_field('amfm_keywords', $postId);
        update_field('amfm_keywords', $keywords, $postId);
        
        $newValue = get_field('amfm_keywords', $postId);
        
        if ($newValue === $keywords) {
            if ($existingValue && $existingValue !== $keywords) {
                $results['details'][] = "Row {$rowNumber}: Overwritten post ID {$postId} ('{$post->post_title}') from '{$existingValue}' to: {$keywords}";
            } else {
                $results['details'][] = "Row {$rowNumber}: Updated post ID {$postId} ('{$post->post_title}') with: {$keywords}";
            }
            $results['success']++;
        } else {
            throw new \Exception("Failed to update post ID {$postId} - ACF field not found");
        }
    }

    /**
     * Process a single categories row
     */
    private function processCategoriesRow(array $row, int $idIndex, int $categoriesIndex, int $rowNumber, array &$results): void
    {
        if (count($row) <= max($idIndex, $categoriesIndex)) {
            throw new \Exception('Invalid row format');
        }

        $postId = intval($row[$idIndex]);
        $categoryName = trim($row[$categoriesIndex]);

        if (!$postId) {
            throw new \Exception('Invalid post ID');
        }

        $post = get_post($postId);
        if (!$post) {
            throw new \Exception("Post ID {$postId} not found");
        }

        // Get or create category
        $categoryId = $this->getOrCreateCategory($categoryName, $rowNumber, $results);
        
        // Add category to post
        $this->addCategoryToPost($postId, $categoryId, $categoryName, $post, $rowNumber, $results);
    }

    /**
     * Get or create a category
     */
    private function getOrCreateCategory(string $categoryName, int $rowNumber, array &$results): int
    {
        $category = get_term_by('name', $categoryName, 'category');
        
        if (!$category) {
            $newCategory = wp_insert_term($categoryName, 'category');
            if (is_wp_error($newCategory)) {
                throw new \Exception("Failed to create category '{$categoryName}'");
            }
            $results['details'][] = "Row {$rowNumber}: Created new category '{$categoryName}'";
            return $newCategory['term_id'];
        }
        
        return $category->term_id;
    }

    /**
     * Add category to post
     */
    private function addCategoryToPost(int $postId, int $categoryId, string $categoryName, \WP_Post $post, int $rowNumber, array &$results): void
    {
        $existingCategories = wp_get_post_categories($postId);
        
        if (!in_array($categoryId, $existingCategories)) {
            $existingCategories[] = $categoryId;
            $updated = wp_set_post_categories($postId, $existingCategories);
            
            if ($updated && !is_wp_error($updated)) {
                $results['details'][] = "Row {$rowNumber}: Added category '{$categoryName}' to post ID {$postId} ('{$post->post_title}')";
                $results['success']++;
            } else {
                throw new \Exception("Failed to add category to post ID {$postId}");
            }
        } else {
            $results['details'][] = "Row {$rowNumber}: Category '{$categoryName}' already assigned to post ID {$postId}";
            $results['success']++;
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateUploadedFile(string $fieldName): ?array
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];
        $fileType = wp_check_filetype($file['name']);
        
        if ($fileType['ext'] !== 'csv') {
            $this->addNotice('Please upload a valid CSV file.', 'error');
            return null;
        }

        return $file;
    }

    /**
     * Open CSV file with validation
     */
    private function openCsvFile(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File does not exist');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open file for reading');
        }

        // Check for and skip BOM (Byte Order Mark) if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // No BOM found, rewind to beginning
            rewind($handle);
        }
        // If BOM was found, we've already skipped it by reading 3 bytes

        return $handle;
    }

    /**
     * Validate keywords CSV headers
     */
    private function validateKeywordsHeaders(?array $headers): bool
    {
        return $headers && in_array('ID', $headers) && in_array('Keywords', $headers);
    }

    /**
     * Validate categories CSV headers
     */
    private function validateCategoriesHeaders(?array $headers): bool
    {
        if (!$headers) {
            return false;
        }

        $headersLower = array_map('strtolower', $headers);
        return in_array('id', $headersLower) && in_array('categories', $headersLower);
    }

    /**
     * Verify nonce
     */
    private function verifyNonce(string $nonceField, string $nonceAction): bool
    {
        return isset($_POST[$nonceField]) && wp_verify_nonce($_POST[$nonceField], $nonceAction);
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