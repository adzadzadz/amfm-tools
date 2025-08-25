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
            wp_redirect(admin_url('admin.php?page=amfm-tools&tab=import-export&imported=keywords'));
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
            wp_redirect(admin_url('admin.php?page=amfm-tools&tab=import-export&imported=categories'));
            exit;
        } catch (\Exception $e) {
            $this->addNotice('Import failed: ' . $e->getMessage(), 'error');
        }
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
    private function openCsvFile(string $filePath): \SplFileObject
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File does not exist');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open file for reading');
        }

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