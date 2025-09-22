<?php
/**
 * Redirection Cleanup Admin View
 *
 * @var array $data View data passed from controller
 */

$current_data = $data['current_data'] ?? [];
$recent_jobs = $data['recent_jobs'] ?? [];
$has_csv = $data['has_csv'] ?? false;
$notice = $data['notice'] ?? '';
?>

<div class="wrap amfm-redirection-cleanup">
    <div id="amfm-redirection-cleanup-app" class="amfm-admin-container">

        <!-- Header Section -->
        <div class="amfm-header-section">
            <div class="amfm-header-content">
                <h1><?php echo esc_html($data['title']); ?></h1>
                <p class="description">
                    <?php esc_html_e('Import crawl report CSV files to find and replace redirected URLs with their final destinations.', 'amfm-tools'); ?>
                </p>
            </div>
        </div>

        <?php echo $notice; ?>

        <div class="amfm-row">
            <!-- CSV Upload Section -->
            <div class="amfm-col-6">
                <div class="amfm-card">
                    <div class="amfm-card-header">
                        <h3><?php esc_html_e('CSV Import', 'amfm-tools'); ?></h3>
                    </div>
                    <div class="amfm-card-body">
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('amfm_upload_csv', 'amfm_csv_nonce'); ?>

                            <div class="form-group">
                                <label for="amfm-csv-file">
                                    <?php esc_html_e('Select Crawl Report CSV', 'amfm-tools'); ?>
                                </label>
                                <input type="file" name="csv_file" id="amfm-csv-file" accept=".csv" class="form-control amfm-file-input" required>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('Upload a CSV file with "Redirected URL" and "Final URL" columns', 'amfm-tools'); ?>
                                </small>
                            </div>

                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php esc_html_e('Process CSV', 'amfm-tools'); ?>
                            </button>

                            <?php if ($has_csv): ?>
                                <button type="button" class="button button-secondary" id="clear-data">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Clear Data', 'amfm-tools'); ?>
                                </button>
                                <button type="button" class="button button-primary" id="fix-malformed-urls" style="background-color: #d63384; border-color: #d63384;">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php esc_html_e('Fix Malformed URLs', 'amfm-tools'); ?>
                                </button>
                            <?php endif; ?>
                        </form>

                        <?php if ($has_csv && !empty($current_data['stats'])): ?>
                            <div class="csv-stats">
                                <h4><?php esc_html_e('Current CSV Data', 'amfm-tools'); ?></h4>
                                <ul>
                                    <li><?php esc_html_e('File:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['csv_file']); ?></strong></li>
                                    <li><?php esc_html_e('Unique URLs:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['stats']['unique_urls'] ?? 0); ?></strong></li>
                                    <li><?php esc_html_e('Total Occurrences:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['stats']['total_occurrences'] ?? 0); ?></strong></li>
                                    <li><?php esc_html_e('Imported:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['last_import'] ?? 'Unknown'); ?></strong></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($has_csv && !empty($current_data['stats']['top_redirections'])): ?>
                    <div class="amfm-card">
                        <div class="amfm-card-header">
                            <h3><?php esc_html_e('Top Redirections', 'amfm-tools'); ?></h3>
                        </div>
                        <div class="amfm-card-body">
                            <table class="wp-list-table widefat striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Redirected URL', 'amfm-tools'); ?></th>
                                        <th><?php esc_html_e('Count', 'amfm-tools'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_data['stats']['top_redirections'] as $item): ?>
                                        <tr>
                                            <td>
                                                <code><?php echo esc_html($item['url']); ?></code>
                                                <br>
                                                <small>→ <?php echo esc_html($item['final_url']); ?></small>
                                            </td>
                                            <td><?php echo esc_html($item['occurrences']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Processing Section -->
            <div class="amfm-col-6">
                <?php if ($has_csv): ?>
                    <div class="amfm-card">
                        <div class="amfm-card-header">
                            <h3><?php esc_html_e('Process Replacements', 'amfm-tools'); ?></h3>
                        </div>
                        <div class="amfm-card-body">
                            <!-- Analysis Section -->
                            <div class="analysis-section">
                                <button type="button" class="button button-secondary" id="analyze-content">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php esc_html_e('Analyze Content', 'amfm-tools'); ?>
                                </button>

                                <?php if (!empty($current_data['analysis'])): ?>
                                    <div class="analysis-results">
                                        <h4><?php esc_html_e('Analysis Results', 'amfm-tools'); ?></h4>
                                        <ul>
                                            <li><?php esc_html_e('Posts with URLs:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['analysis']['posts'] ?? 0); ?></strong></li>
                                            <li><?php esc_html_e('Meta fields with URLs:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['analysis']['postmeta'] ?? 0); ?></strong></li>
                                            <li><?php esc_html_e('Total items:', 'amfm-tools'); ?> <strong><?php echo esc_html($current_data['analysis']['total'] ?? 0); ?></strong></li>
                                        </ul>
                                        <small><?php esc_html_e('Last analysis:', 'amfm-tools'); ?> <?php echo esc_html($current_data['last_analysis'] ?? 'Unknown'); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Processing Options -->
                            <div class="processing-section">
                                <h4><?php esc_html_e('Processing Options', 'amfm-tools'); ?></h4>

                                <form id="processing-form">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="dry_run" id="dry_run" checked>
                                            <strong><?php esc_html_e('Dry Run', 'amfm-tools'); ?></strong>
                                            <small><?php esc_html_e('Preview changes without making actual updates', 'amfm-tools'); ?></small>
                                        </label>
                                    </div>

                                    <div class="form-group">
                                        <label><?php esc_html_e('Content Types:', 'amfm-tools'); ?></label>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="checkbox" name="content_types[]" value="all_tables" id="all_tables_checkbox">
                                            <strong><?php esc_html_e('Comprehensive Mode', 'amfm-tools'); ?></strong>
                                            <small><?php esc_html_e('(Includes: Posts, Meta, Elementor, Widgets, Menus, Options)', 'amfm-tools'); ?></small>
                                        </label>
                                        <label style="display: block; margin-left: 20px;">
                                            <input type="checkbox" name="content_types[]" value="posts" checked class="standard-content-type">
                                            <?php esc_html_e('Posts & Pages', 'amfm-tools'); ?>
                                        </label>
                                        <label style="display: block; margin-left: 20px;">
                                            <input type="checkbox" name="content_types[]" value="postmeta" checked class="standard-content-type">
                                            <?php esc_html_e('Post Meta', 'amfm-tools'); ?>
                                        </label>
                                    </div>

                                    <button type="button" class="button button-primary" id="process-replacements">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <?php esc_html_e('Process Replacements', 'amfm-tools'); ?>
                                    </button>
                                </form>
                            </div>

                            <!-- Progress Section -->
                            <div id="progress-section" style="display: none;">
                                <h4><?php esc_html_e('Processing...', 'amfm-tools'); ?></h4>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <div id="progress-message"></div>
                                <div id="progress-stats" style="margin-top: 10px; font-size: 14px; color: #666;">
                                    <span id="posts-progress">Posts: 0</span> |
                                    <span id="meta-progress">Meta: 0</span> |
                                    <span id="urls-progress">URLs: 0</span>
                                </div>
                            </div>

                            <!-- Results Section -->
                            <div id="results-section" style="display: none;">
                                <h4><?php esc_html_e('Results', 'amfm-tools'); ?></h4>
                                <div id="results-content"></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="amfm-card">
                        <div class="amfm-card-body">
                            <div class="notice notice-info inline">
                                <p><?php esc_html_e('Please process a CSV file to begin analyzing and replacing redirections.', 'amfm-tools'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Jobs -->
                <?php if (!empty($recent_jobs)): ?>
                    <div class="amfm-card">
                        <div class="amfm-card-header">
                            <h3><?php esc_html_e('Recent Jobs', 'amfm-tools'); ?></h3>
                        </div>
                        <div class="amfm-card-body">
                            <table class="wp-list-table widefat striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Date', 'amfm-tools'); ?></th>
                                        <th><?php esc_html_e('Type', 'amfm-tools'); ?></th>
                                        <th><?php esc_html_e('Results', 'amfm-tools'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_jobs as $job): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                if (isset($job['timestamp']) && $job['timestamp'] !== 'Unknown') {
                                                    echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($job['timestamp'])));
                                                } else {
                                                    echo esc_html__('Unknown', 'amfm-tools');
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo isset($job['options']['dry_run']) && $job['options']['dry_run'] ?
                                                    '<span class="dashicons dashicons-visibility"></span> ' . esc_html__('Dry Run', 'amfm-tools') :
                                                    '<span class="dashicons dashicons-yes"></span> ' . esc_html__('Live', 'amfm-tools'); ?>
                                            </td>
                                            <td>
                                                <?php echo esc_html(sprintf(
                                                    __('%d posts, %d URLs', 'amfm-tools'),
                                                    $job['results']['posts_updated'] ?? 0,
                                                    $job['results']['urls_replaced'] ?? 0
                                                )); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>