<?php

namespace App\Shortcodes;

/**
 * AMFM Editor URL Shortcode
 * 
 * Returns the editor's page URL
 * Migrated from amfm-bylines plugin
 */
class AmfmEditorUrlShortcode
{
    /**
     * Render amfm_editor_url shortcode
     * Usage: [amfm_editor_url]
     * 
     * @param array $atts Shortcode attributes
     * @return string Editor page URL
     */
    public function render($atts = [])
    {
        $bylines_controller = new \App\Controllers\PublicBylinesController();
        return (string) $bylines_controller->getBylineUrl('editor');
    }
}