<?php

/**
 * Shortcode Guide for AMFM Bylines Plugin
 *
 * This file provides a guide for using the shortcodes available in the AMFM Bylines plugin.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/admin/partials
 */

?>

<div class="wrap">
    <h1 class="mb-4"><?php _e('AMFM Bylines Shortcode Guide', 'amfm-bylines'); ?></h1>
    <p class="lead"><?php _e('Below is a list of available shortcodes and their usage.', 'amfm-bylines'); ?></p>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0"><?php _e('[amfm_info]', 'amfm-bylines'); ?></h2>
        </div>
        <div class="card-body">
            <p><?php _e('Usage: [amfm_info type="editor" data="job_title"]', 'amfm-bylines'); ?></p>
            <ul>
                <li><?php _e('<strong>type</strong>: author, editor, reviewedBy', 'amfm-bylines'); ?></li>
                <li><?php _e('<strong>data</strong>: name, credentials, job_title, page_url, img', 'amfm-bylines'); ?></li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0"><?php _e('[amfm_author_url]', 'amfm-bylines'); ?></h2>
        </div>
        <div class="card-body">
            <p><?php _e('Returns the author\'s page URL.', 'amfm-bylines'); ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0"><?php _e('[amfm_editor_url]', 'amfm-bylines'); ?></h2>
        </div>
        <div class="card-body">
            <p><?php _e('Returns the editor\'s page URL.', 'amfm-bylines'); ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0"><?php _e('[amfm_reviewer_url]', 'amfm-bylines'); ?></h2>
        </div>
        <div class="card-body">
            <p><?php _e('Returns the reviewer\'s page URL.', 'amfm-bylines'); ?></p>
        </div>
    </div>
</div>
