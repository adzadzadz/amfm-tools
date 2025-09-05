<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/admin/partials
 */

// get all data stored in the amfm_bylines table in the database
global $wpdb;
$bylines = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}amfm_bylines");

$plugin_url = defined('AMFM_BYLINES_PLUGIN_URL') ? AMFM_BYLINES_PLUGIN_URL . 'admin/partials/' : plugin_dir_url(__FILE__);
$placeholder = $plugin_url . "placeholder.jpeg";

// if option amfm_use_staff_cpt doesn't exist, create it and set it to false
if (!get_option('amfm_use_staff_cpt')) {
    add_option('amfm_use_staff_cpt', false);
}

$use_staff_cpt = get_option('amfm_use_staff_cpt');

?>

<div class="wrap">
    <h2><strong><?php _e('Manage Bylines', 'amfm-bylines'); ?></strong></h2>
    <p><?php _e('This plugin allows you to manage bylines for your posts and pages. You can add, edit, and remove bylines, as well as assign specific roles and credentials to each byline. This helps in providing proper attribution and recognition to the contributors of your content.', 'amfm-bylines'); ?></p>
</div>

<div id="flash-message-container"></div>

<!-- Bootstrap 5 Toggle Switch -->
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" id="functionToggle" <?php echo $use_staff_cpt ? 'checked' : ''; ?> style="width: 50px; height: 25px;">
    <label class="form-check-label" for="functionToggle" style="font-size: 1.2em; margin-top: -8px;">Use "Staff" CPT instead?</label>
</div>

<?php 

if ($use_staff_cpt) {
    include 'admin-display/cpt-enabled.php';
} else {
    include 'admin-display/cpt-disabled.php';
}

?>