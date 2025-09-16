<?php
/**
 * Redirection Cleanup Error View
 * 
 * @var array $data View data passed from controller
 */
?>

<div class="wrap amfm-redirection-cleanup-error">
    <div class="amfm-admin-container">
        
        <div class="amfm-header-section">
            <div class="amfm-header-content">
                <h1><?php echo esc_html($data['title']); ?></h1>
            </div>
        </div>

        <div class="amfm-card">
            <div class="amfm-card-body">
                <div class="amfm-notice amfm-notice-error">
                    <div class="notice-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="notice-content">
                        <h3><?php esc_html_e('RankMath Plugin Required', 'amfm-tools'); ?></h3>
                        <p><?php echo esc_html($data['error']); ?></p>
                        <p><?php esc_html_e('The Redirection Cleanup tool requires RankMath to be active and configured with redirections.', 'amfm-tools'); ?></p>
                        
                        <div class="notice-actions">
                            <?php if (!is_plugin_active('seo-by-rank-math/rank-math.php')): ?>
                                <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-primary">
                                    <?php esc_html_e('Go to Plugins', 'amfm-tools'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url(admin_url('admin.php?page=rank-math')); ?>" class="button button-secondary">
                                <?php esc_html_e('Configure RankMath', 'amfm-tools'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="requirements-section">
                    <h3><?php esc_html_e('Requirements', 'amfm-tools'); ?></h3>
                    <ul class="requirements-list">
                        <li class="<?php echo is_plugin_active('seo-by-rank-math/rank-math.php') ? 'requirement-met' : 'requirement-missing'; ?>">
                            <span class="dashicons <?php echo is_plugin_active('seo-by-rank-math/rank-math.php') ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php esc_html_e('RankMath SEO Plugin Active', 'amfm-tools'); ?>
                        </li>
                        <li class="<?php echo class_exists('RankMath\\Redirections\\DB') ? 'requirement-met' : 'requirement-missing'; ?>">
                            <span class="dashicons <?php echo class_exists('RankMath\\Redirections\\DB') ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php esc_html_e('RankMath Redirections Module Enabled', 'amfm-tools'); ?>
                        </li>
                        <?php
                        global $wpdb;
                        $redirections_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}rank_math_redirections'");
                        ?>
                        <li class="<?php echo $redirections_table_exists ? 'requirement-met' : 'requirement-missing'; ?>">
                            <span class="dashicons <?php echo $redirections_table_exists ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php esc_html_e('RankMath Database Tables Present', 'amfm-tools'); ?>
                        </li>
                    </ul>
                </div>

                <div class="help-section">
                    <h3><?php esc_html_e('Getting Started', 'amfm-tools'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Install and activate the RankMath SEO plugin', 'amfm-tools'); ?></li>
                        <li><?php esc_html_e('Run the RankMath setup wizard', 'amfm-tools'); ?></li>
                        <li><?php esc_html_e('Enable the Redirections module in RankMath settings', 'amfm-tools'); ?></li>
                        <li><?php esc_html_e('Add some redirections to test the tool', 'amfm-tools'); ?></li>
                        <li><?php esc_html_e('Return to this page to start cleaning up internal redirections', 'amfm-tools'); ?></li>
                    </ol>
                </div>
            </div>
        </div>

    </div>
</div>