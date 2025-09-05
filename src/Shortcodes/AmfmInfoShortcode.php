<?php

namespace App\Shortcodes;

/**
 * AMFM Info Shortcode
 * 
 * Displays byline information (author, editor, reviewer data)
 * Migrated from amfm-bylines plugin
 */
class AmfmInfoShortcode
{
    /**
     * Render amfm_info shortcode
     * Usage: [amfm_info type="editor" data="job_title"]
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered content
     */
    public function render($atts = [])
    {
        // Default attributes
        $defaults = [
            'type' => 'author',
            'data' => 'name'
        ];
        
        // Parse attributes
        $atts = \shortcode_atts($defaults, $atts, 'amfm_info');
        
        // Validate type parameter
        if (!in_array($atts['type'], ['author', 'editor', 'reviewedBy'])) {
            return "Type must be either 'author', 'editor', 'reviewedBy'";
        }

        // Validate data parameter
        if (!in_array($atts['data'], ['name', 'credentials', 'job_title', 'page_url', 'img'])) {
            return "Data must be either 'name', 'credentials', 'job_title', 'page_url', 'img'";
        }

        // Get byline data
        $bylines_controller = new \App\Controllers\PublicBylinesController();
        $use_staff_cpt = get_option('amfm_use_staff_cpt');
        $byline = $bylines_controller->getByline($atts['type'], $use_staff_cpt);

        if (!$byline) {
            return "No byline found";
        }

        return $this->formatBylineData($byline, $atts['data'], $use_staff_cpt);
    }

    /**
     * Format byline data based on requested field
     * 
     * @param object $byline Byline data object
     * @param string $data_field Requested data field
     * @param bool $use_staff_cpt Whether using staff CPT or database
     * @return string Formatted output
     */
    private function formatBylineData($byline, $data_field, $use_staff_cpt)
    {
        if (!$use_staff_cpt) {
            // Database-based bylines
            $byline_data = json_decode($byline->data, true);
            
            switch ($data_field) {
                case 'name':
                    return $byline->byline_name ?? '';
                    
                case 'credentials':
                    return $byline_data['honorificSuffix'] ?? '';
                    
                case 'job_title':
                    return $byline_data['jobTitle'] ?? '';
                    
                case 'page_url':
                    $url = $byline_data['page_url'] ?? '';
                    return preg_replace('/^https?:\/\//', '', $url);
                    
                case 'img':
                    $profile_url = $byline->profile_image ?? AMFM_TOOLS_URL . 'assets/imgs/placeholder.jpeg';
                    $name = $byline->byline_name ?? '';
                    return sprintf(
                        '<div style="text-align: center; display: inline-block;"><img style="width: 40px; border-radius: 50%%; border: 2px #00245d solid;" src="%s" alt="%s" /></div>',
                        \esc_url($profile_url),
                        \esc_attr($name)
                    );
            }
        } else {
            // Staff CPT-based bylines
            $byline_data = \get_fields($byline->ID);
            $staff_profile_image = \get_the_post_thumbnail_url($byline->ID);
            
            switch ($data_field) {
                case 'name':
                    return $byline->post_title ?? '';
                    
                case 'credentials':
                    return $byline_data['honorific_suffix'] ?? '';
                    
                case 'job_title':
                    return $byline_data['job_title'] ?? '';
                    
                case 'page_url':
                    $url = \get_permalink($byline->ID);
                    return preg_replace('/^https?:\/\//', '', $url);
                    
                case 'img':
                    $profile_url = $staff_profile_image ?? AMFM_TOOLS_URL . 'assets/imgs/placeholder.jpeg';
                    $name = $byline->post_title ?? '';
                    return sprintf(
                        '<img src="%s" alt="%s" />',
                        \esc_url($profile_url),
                        \esc_attr($name)
                    );
            }
        }

        return '';
    }
}