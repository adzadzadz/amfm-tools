<?php

namespace adz\models;

use AdzWP\Model;

class ImportModel extends Model
{
    public function processCsvUpload($file)
    {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );

        // Validate file
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $results['details'][] = 'Error uploading file. Please try again.';
            $results['errors']++;
            return $results;
        }

        $file_type = wp_check_filetype($file['name']);
        if ($file_type['ext'] !== 'csv') {
            $results['details'][] = 'Please upload a valid CSV file.';
            $results['errors']++;
            return $results;
        }

        return $this->processCsvFile($file['tmp_name']);
    }

    public function processCategoryCsvUpload($file)
    {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );

        // Similar validation as above
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $results['details'][] = 'Error uploading file. Please try again.';
            $results['errors']++;
            return $results;
        }

        $file_type = wp_check_filetype($file['name']);
        if ($file_type['ext'] !== 'csv') {
            $results['details'][] = 'Please upload a valid CSV file.';
            $results['errors']++;
            return $results;
        }

        return $this->processCategoryCsvFile($file['tmp_name']);
    }

    private function processCsvFile($file_path)
    {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );

        if (!file_exists($file_path)) {
            $results['details'][] = 'File does not exist';
            $results['errors']++;
            return $results;
        }

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $results['details'][] = 'Could not open file for reading';
            $results['errors']++;
            return $results;
        }

        $headers = fgetcsv($handle);
        
        // Validate headers
        if (!$headers || !in_array('ID', $headers) || !in_array('Keywords', $headers)) {
            $results['details'][] = 'Invalid CSV format. Required headers: ID, Keywords';
            $results['errors']++;
            fclose($handle);
            return $results;
        }

        $id_index = array_search('ID', $headers);
        $keywords_index = array_search('Keywords', $headers);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $post_id = intval($data[$id_index]);
            $keywords = $data[$keywords_index];

            if (!$post_id || !get_post($post_id)) {
                $results['details'][] = "Post ID {$post_id} does not exist";
                $results['errors']++;
                continue;
            }

            // Process keywords - split by comma and clean
            $keywords_array = array_map('trim', explode(',', $keywords));
            $keywords_array = array_filter($keywords_array);

            if (update_field('keywords', $keywords_array, $post_id)) {
                $results['success']++;
                $results['details'][] = "Updated post ID {$post_id} with " . count($keywords_array) . " keywords";
            } else {
                $results['errors']++;
                $results['details'][] = "Failed to update post ID {$post_id}";
            }
        }

        fclose($handle);
        return $results;
    }

    private function processCategoryCsvFile($file_path)
    {
        $results = array(
            'success' => 0,
            'errors' => 0,
            'details' => array()
        );

        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $results['details'][] = 'Could not open file for reading';
            $results['errors']++;
            return $results;
        }

        $headers = fgetcsv($handle);
        
        // Validate headers for category import
        if (!$headers || !in_array('ID', $headers) || !in_array('Categories', $headers)) {
            $results['details'][] = 'Invalid CSV format. Required headers: ID, Categories';
            $results['errors']++;
            fclose($handle);
            return $results;
        }

        $id_index = array_search('ID', $headers);
        $categories_index = array_search('Categories', $headers);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $post_id = intval($data[$id_index]);
            $categories = $data[$categories_index];

            if (!$post_id || !get_post($post_id)) {
                $results['details'][] = "Post ID {$post_id} does not exist";
                $results['errors']++;
                continue;
            }

            // Process categories
            $category_names = array_map('trim', explode(',', $categories));
            $category_ids = array();

            foreach ($category_names as $category_name) {
                if (empty($category_name)) continue;
                
                $category = get_term_by('name', $category_name, 'category');
                if (!$category) {
                    // Create category if it doesn't exist
                    $category_result = wp_insert_term($category_name, 'category');
                    if (!is_wp_error($category_result)) {
                        $category_ids[] = $category_result['term_id'];
                    }
                } else {
                    $category_ids[] = $category->term_id;
                }
            }

            if (!empty($category_ids)) {
                wp_set_post_categories($post_id, $category_ids);
                $results['success']++;
                $results['details'][] = "Updated post ID {$post_id} with " . count($category_ids) . " categories";
            } else {
                $results['errors']++;
                $results['details'][] = "No valid categories found for post ID {$post_id}";
            }
        }

        fclose($handle);
        return $results;
    }
}
