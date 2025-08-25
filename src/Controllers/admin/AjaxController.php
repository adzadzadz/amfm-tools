<?php

namespace App\Controllers\Admin;

use AdzWP\Core\Controller;
use App\Services\DataExportService;
use App\Services\CsvImportService;

/**
 * AJAX Controller for Import/Export functionality
 * 
 * Handles all AJAX requests for import/export operations
 */
class AjaxController extends Controller
{
    /**
     * Handle export AJAX request
     */
    public function actionWpAjaxAmfmExportData(): void
    {
        // Verify nonce and permissions
        if (!check_ajax_referer('amfm_export_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        try {
            $exportService = $this->service('csv_export');
            if (!$exportService) {
                wp_send_json_error('Export service not available');
                return;
            }

            $result = $exportService->exportData($_POST);
            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle keywords import AJAX request
     */
    public function actionWpAjaxAmfmImportKeywords(): void
    {
        // Verify nonce and permissions
        if (!check_ajax_referer('amfm_keywords_import_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        try {
            $csvService = $this->service('csv_import');
            if (!$csvService) {
                wp_send_json_error('Import service not available');
                return;
            }

            $result = $this->handleKeywordsImport($csvService);
            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle categories import AJAX request
     */
    public function actionWpAjaxAmfmImportCategories(): void
    {
        // Verify nonce and permissions
        if (!check_ajax_referer('amfm_categories_import_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        try {
            $csvService = $this->service('csv_import');
            if (!$csvService) {
                wp_send_json_error('Import service not available');
                return;
            }

            $result = $this->handleCategoriesImport($csvService);
            wp_send_json_success($result);

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle get taxonomies for post type AJAX request
     */
    public function actionWpAjaxAmfmGetPostTypeTaxonomies(): void
    {
        // Verify nonce and permissions
        if (!check_ajax_referer('amfm_export_nonce', 'nonce', false) || !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        if (empty($_POST['post_type'])) {
            wp_send_json_error('No post type provided');
            return;
        }

        $postType = sanitize_key($_POST['post_type']);
        $taxonomies = get_object_taxonomies($postType, 'objects');

        if (empty($taxonomies)) {
            wp_send_json_error('No taxonomies found');
            return;
        }

        $formattedTaxonomies = [];
        foreach ($taxonomies as $taxonomy) {
            $formattedTaxonomies[] = [
                'name' => $taxonomy->name,
                'label' => $taxonomy->label
            ];
        }

        wp_send_json_success($formattedTaxonomies);
    }

    /**
     * Handle keywords import via CSV
     */
    private function handleKeywordsImport(CsvImportService $csvService): array
    {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error uploading file. Please try again.');
        }

        $file = $_FILES['csv_file'];
        $fileType = wp_check_filetype($file['name']);
        
        if ($fileType['ext'] !== 'csv') {
            throw new \Exception('Please upload a valid CSV file.');
        }

        return $this->processKeywordsCsv($file['tmp_name']);
    }

    /**
     * Handle categories import via CSV
     */
    private function handleCategoriesImport(CsvImportService $csvService): array
    {
        if (!isset($_FILES['category_csv_file']) || $_FILES['category_csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error uploading file. Please try again.');
        }

        $file = $_FILES['category_csv_file'];
        $fileType = wp_check_filetype($file['name']);
        
        if ($fileType['ext'] !== 'csv') {
            throw new \Exception('Please upload a valid CSV file.');
        }

        return $this->processCategoriesCsv($file['tmp_name']);
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

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open file for reading');
        }

        $headers = fgetcsv($handle);
        if (!$headers || !in_array('ID', $headers) || !in_array('Keywords', $headers)) {
            fclose($handle);
            throw new \Exception('Invalid CSV format. Required headers: ID, Keywords');
        }

        $idIndex = array_search('ID', $headers);
        $keywordsIndex = array_search('Keywords', $headers);
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if (count($row) <= max($idIndex, $keywordsIndex)) {
                $results['details'][] = "Row {$rowNumber}: Invalid row format";
                $results['errors']++;
                continue;
            }

            $postId = intval($row[$idIndex]);
            $keywords = sanitize_text_field($row[$keywordsIndex]);

            if (!$postId) {
                $results['details'][] = "Row {$rowNumber}: Invalid post ID";
                $results['errors']++;
                continue;
            }

            $post = get_post($postId);
            if (!$post) {
                $results['details'][] = "Row {$rowNumber}: Post ID {$postId} not found";
                $results['errors']++;
                continue;
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
                $results['details'][] = "Row {$rowNumber}: Failed to update post ID {$postId} - amfm_keywords ACF field not found or update failed";
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

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open file for reading');
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Invalid CSV format. Could not read headers');
        }

        $headersLower = array_map('strtolower', $headers);
        $idIndex = array_search('id', $headersLower);
        $categoriesIndex = array_search('categories', $headersLower);

        if ($idIndex === false || $categoriesIndex === false) {
            fclose($handle);
            throw new \Exception('Invalid CSV format. Required headers: id, Categories (case-insensitive)');
        }

        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if (count($row) <= max($idIndex, $categoriesIndex)) {
                $results['details'][] = "Row {$rowNumber}: Invalid row format";
                $results['errors']++;
                continue;
            }

            $postId = intval($row[$idIndex]);
            $categoryName = trim($row[$categoriesIndex]);

            if (!$postId) {
                $results['details'][] = "Row {$rowNumber}: Invalid post ID";
                $results['errors']++;
                continue;
            }

            if (empty($categoryName)) {
                $results['details'][] = "Row {$rowNumber}: Empty category name";
                $results['errors']++;
                continue;
            }

            $post = get_post($postId);
            if (!$post) {
                $results['details'][] = "Row {$rowNumber}: Post ID {$postId} not found";
                $results['errors']++;
                continue;
            }

            // Find or create the category
            $category = get_term_by('name', $categoryName, 'category');
            if (!$category) {
                $newCategory = wp_insert_term($categoryName, 'category');
                if (is_wp_error($newCategory)) {
                    $results['details'][] = "Row {$rowNumber}: Failed to create category '{$categoryName}': " . $newCategory->get_error_message();
                    $results['errors']++;
                    continue;
                }
                $categoryId = $newCategory['term_id'];
                $results['details'][] = "Row {$rowNumber}: Created new category '{$categoryName}' (ID: {$categoryId})";
            } else {
                $categoryId = $category->term_id;
            }

            // Assign category to post (don't remove existing categories)
            $result = wp_set_post_categories($postId, [$categoryId], false);
            if (is_wp_error($result)) {
                $results['details'][] = "Row {$rowNumber}: Failed to assign category to post ID {$postId}: " . $result->get_error_message();
                $results['errors']++;
                continue;
            }

            $results['details'][] = "Row {$rowNumber}: Successfully assigned category '{$categoryName}' to post ID {$postId} ('{$post->post_title}')";
            $results['success']++;
        }

        fclose($handle);
        return $results;
    }
}