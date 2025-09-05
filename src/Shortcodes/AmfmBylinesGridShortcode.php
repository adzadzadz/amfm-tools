<?php

namespace App\Shortcodes;

/**
 * AMFM Bylines Grid Shortcode
 * 
 * Displays a grid of bylines (author, editor, reviewer)
 * Migrated from amfm-bylines plugin
 */
class AmfmBylinesGridShortcode
{
    /**
     * Render amfm_bylines_grid shortcode
     * Usage: [amfm_bylines_grid]
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered bylines grid
     */
    public function render($atts = [])
    {
        // Get byline data using other shortcodes
        $author = [
            "name" => \do_shortcode('[amfm_info type="author" data="name"]'),
            "title" => \do_shortcode('[amfm_info type="author" data="job_title"]'),
            "credentials" => \do_shortcode('[amfm_info type="author" data="credentials"]'),
            "img" => \do_shortcode('[amfm_info type="author" data="img"]')
        ];

        $editor = [
            "name" => \do_shortcode('[amfm_info type="editor" data="name"]'),
            "title" => \do_shortcode('[amfm_info type="editor" data="job_title"]'),
            "credentials" => \do_shortcode('[amfm_info type="editor" data="credentials"]'),
            "img" => \do_shortcode('[amfm_info type="editor" data="img"]')
        ];

        $reviewer = [
            "name" => \do_shortcode('[amfm_info type="reviewedBy" data="name"]'),
            "title" => \do_shortcode('[amfm_info type="reviewedBy" data="job_title"]'),
            "credentials" => \do_shortcode('[amfm_info type="reviewedBy" data="credentials"]'),
            "img" => \do_shortcode('[amfm_info type="reviewedBy" data="img"]')
        ];

        return $this->renderGrid($author, $editor, $reviewer);
    }

    /**
     * Render the bylines grid HTML
     * 
     * @param array $author Author data
     * @param array $editor Editor data
     * @param array $reviewer Reviewer data
     * @return string Grid HTML
     */
    private function renderGrid($author, $editor, $reviewer)
    {
        $output = '<div class="amfm-bylines-container">';

        // Author column
        if (!empty($author['name']) && $author['name'] !== 'No byline found') {
            $output .= $this->renderColumn('author', 'Author:', $author);
        }

        // Editor column
        if (!empty($editor['name']) && $editor['name'] !== 'No byline found') {
            $output .= $this->renderColumn('editor', 'Editor:', $editor);
        }

        // Reviewer column
        if (!empty($reviewer['name']) && $reviewer['name'] !== 'No byline found') {
            $output .= $this->renderColumn('reviewer', 'Reviewer:', $reviewer);
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render individual column
     * 
     * @param string $type Column type (author, editor, reviewer)
     * @param string $label Column label
     * @param array $data Byline data
     * @return string Column HTML
     */
    private function renderColumn($type, $label, $data)
    {
        return sprintf(
            '<div class="amfm-column" id="amfm-byline-col-%s">
                <div class="amfm-text">%s</div>
                <div class="amfm-image">%s</div>
                <div class="amfm-row-text-container">
                    <div class="amfm-row-text-name">%s</div>
                    <div class="amfm-row-text-credentials">%s</div>
                    <div class="amfm-row-text-title">%s</div>
                </div>
            </div>',
            \esc_attr($type),
            \esc_html($label),
            $data['img'], // Already escaped in AmfmInfoShortcode
            \esc_html($data['name']),
            \esc_html($data['credentials']),
            \esc_html($data['title'])
        );
    }
}