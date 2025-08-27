<?php

namespace App\Shortcodes;

/**
 * AMFM Reviewer URL Shortcode
 * 
 * Returns the reviewer's page URL
 * Migrated from amfm-bylines plugin
 */
class AmfmReviewerUrlShortcode
{
    /**
     * Render amfm_reviewer_url shortcode
     * Usage: [amfm_reviewer_url]
     * 
     * @param array $atts Shortcode attributes
     * @return string Reviewer page URL
     */
    public function render($atts = [])
    {
        $bylines_controller = new \App\Controllers\PublicBylinesController();
        return (string) $bylines_controller->getBylineUrl('reviewedBy');
    }
}