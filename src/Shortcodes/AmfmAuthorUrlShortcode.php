<?php

namespace App\Shortcodes;

/**
 * AMFM Author URL Shortcode
 * 
 * Returns the author's page URL
 * Migrated from amfm-bylines plugin
 */
class AmfmAuthorUrlShortcode
{
    /**
     * Render amfm_author_url shortcode
     * Usage: [amfm_author_url]
     * 
     * @param array $atts Shortcode attributes
     * @return string Author page URL
     */
    public function render($atts = [])
    {
        $bylines_controller = new \App\Controllers\PublicBylinesController();
        return (string) $bylines_controller->getBylineUrl('author');
    }
}