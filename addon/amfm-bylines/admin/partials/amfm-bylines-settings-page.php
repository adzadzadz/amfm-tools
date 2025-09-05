<?php 

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://adzjo.online/adz
 * @since      2.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/admin/partials
 */

?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Updater Config</h2>
                    <form method="post" action="options.php">
                        <?php settings_fields('amfm_bylines_options_group'); ?>
                        <div class="mb-3">
                            <label for="amfm_bylines_github_user" class="form-label">GitHub User</label>
                            <input type="text" class="form-control" id="amfm_bylines_github_user" name="amfm_bylines_github_user" value="<?php echo esc_attr(get_option('amfm_bylines_github_user')); ?>" />
                        </div>
                        <div class="mb-3">
                            <label for="amfm_bylines_github_repo" class="form-label">GitHub Repository</label>
                            <input type="text" class="form-control" id="amfm_bylines_github_repo" name="amfm_bylines_github_repo" value="<?php echo esc_attr(get_option('amfm_bylines_github_repo')); ?>" />
                        </div>
                        <div class="mb-3">
                            <label for="amfm_bylines_github_token" class="form-label">GitHub Token</label>
                            <input type="text" class="form-control" id="amfm_bylines_github_token" name="amfm_bylines_github_token" value="<?php echo esc_attr(get_option('amfm_bylines_github_token')); ?>" />
                        </div>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>