<?php
/**
 * Redirection Cleanup Admin View
 * 
 * @var array $data View data passed from controller
 */

$analysis = $data['analysis'] ?? [];
$can_process = $data['can_process'] ?? false;
$processing_options = $data['processing_options'] ?? [];
$recent_jobs = $data['recent_jobs'] ?? [];
?>

<div class="wrap amfm-redirection-cleanup">
    <div id="amfm-redirection-cleanup-app" class="amfm-admin-container">
        
        <!-- Header Section -->
        <div class="amfm-header-section">
            <div class="amfm-header-content">
                <h1><?php echo esc_html($data['title']); ?></h1>
                <p class="description">
                    <?php esc_html_e('Eliminate internal redirections by updating URLs throughout your WordPress site to point directly to their final destinations.', 'amfm-tools'); ?>
                </p>
            </div>
            
            <div class="amfm-header-actions">
                <button type="button" class="button button-secondary" id="refresh-analysis">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Refresh Analysis', 'amfm-tools'); ?>
                </button>
                
                <?php if ($can_process): ?>
                    <button type="button" class="button button-primary" id="start-cleanup" <?php echo $can_process ? '' : 'disabled'; ?>>
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e('Start Cleanup Process', 'amfm-tools'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status/Progress Section -->
        <div id="cleanup-progress-section" class="amfm-card" style="display: none;">
            <div class="amfm-card-header">
                <h3><?php esc_html_e('Cleanup Progress', 'amfm-tools'); ?></h3>
                <button type="button" class="button button-link" id="cancel-cleanup">
                    <?php esc_html_e('Cancel', 'amfm-tools'); ?>
                </button>
            </div>
            <div class="amfm-card-body">
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-info">
                        <span class="current-step"><?php esc_html_e('Initializing...', 'amfm-tools'); ?></span>
                        <span class="progress-stats">0 / 0 items processed</span>
                    </div>
                </div>
                <div class="live-log">
                    <div class="log-entries"></div>
                </div>
            </div>
        </div>

        <!-- Analysis Overview -->
        <div class="amfm-row">
            <div class="amfm-col-8">
                <div class="amfm-card">
                    <div class="amfm-card-header">
                        <h3><?php esc_html_e('Redirection Analysis', 'amfm-tools'); ?></h3>
                        <span class="last-updated">
                            <?php if (!empty($analysis['last_analyzed'])): ?>
                                <?php printf(
                                    esc_html__('Last analyzed: %s', 'amfm-tools'),
                                    esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($analysis['last_analyzed'])))
                                ); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="amfm-card-body">
                        <?php if (empty($analysis) || $analysis['total_redirections'] === 0): ?>
                            <div class="amfm-notice amfm-notice-info">
                                <p><?php esc_html_e('No active redirections found in RankMath. Click "Refresh Analysis" to scan for redirections.', 'amfm-tools'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="analysis-overview">
                                <div class="stat-grid">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo esc_html(number_format($analysis['total_redirections'])); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Total Redirections', 'amfm-tools'); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo esc_html(number_format($analysis['active_redirections'])); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Active Redirections', 'amfm-tools'); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo esc_html(number_format($analysis['redirect_chains'])); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Redirect Chains', 'amfm-tools'); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo esc_html(number_format($analysis['estimated_content_items'])); ?></div>
                                        <div class="stat-label"><?php esc_html_e('Content Items', 'amfm-tools'); ?></div>
                                    </div>
                                </div>

                                <!-- Top Redirected URLs -->
                                <?php if (!empty($analysis['top_redirected_sources'])): ?>
                                    <div class="top-redirections">
                                        <h4><?php esc_html_e('Most Used Redirections', 'amfm-tools'); ?></h4>
                                        <table class="wp-list-table widefat striped">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Source Pattern', 'amfm-tools'); ?></th>
                                                    <th><?php esc_html_e('Destination', 'amfm-tools'); ?></th>
                                                    <th><?php esc_html_e('Hits', 'amfm-tools'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($analysis['top_redirected_sources'], 0, 5) as $redirect): ?>
                                                    <?php 
                                                    $sources = maybe_unserialize($redirect['sources']);
                                                    $pattern = is_array($sources) && !empty($sources[0]['pattern']) 
                                                        ? $sources[0]['pattern'] 
                                                        : 'Unknown';
                                                    ?>
                                                    <tr>
                                                        <td><code><?php echo esc_html($pattern); ?></code></td>
                                                        <td><code><?php echo esc_html($redirect['url_to']); ?></code></td>
                                                        <td><?php echo esc_html(number_format($redirect['hits'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="amfm-col-4">
                <!-- Cleanup Options -->
                <?php if ($can_process): ?>
                    <div class="amfm-card">
                        <div class="amfm-card-header">
                            <h3><?php esc_html_e('Cleanup Options', 'amfm-tools'); ?></h3>
                        </div>
                        <div class="amfm-card-body">
                            <form id="cleanup-options-form">
                                <!-- Content Types -->
                                <fieldset class="option-group">
                                    <legend><?php esc_html_e('Content Types to Process', 'amfm-tools'); ?></legend>
                                    <?php foreach ($processing_options['content_types'] as $key => $option): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="content_types[]" value="<?php echo esc_attr($key); ?>" 
                                                   <?php checked($option['default']); ?>>
                                            <span class="checkbox-text">
                                                <strong><?php echo esc_html($option['label']); ?></strong>
                                                <small><?php echo esc_html($option['description']); ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>

                                <!-- Processing Options -->
                                <fieldset class="option-group">
                                    <legend><?php esc_html_e('Processing Settings', 'amfm-tools'); ?></legend>
                                    
                                    <label class="input-label">
                                        <span><?php echo esc_html($processing_options['processing']['batch_size']['label']); ?></span>
                                        <input type="number" name="batch_size" 
                                               value="<?php echo esc_attr($processing_options['processing']['batch_size']['default']); ?>"
                                               min="<?php echo esc_attr($processing_options['processing']['batch_size']['min']); ?>"
                                               max="<?php echo esc_attr($processing_options['processing']['batch_size']['max']); ?>">
                                        <small><?php echo esc_html($processing_options['processing']['batch_size']['description']); ?></small>
                                    </label>

                                    <?php foreach (['dry_run', 'create_backup'] as $key): ?>
                                        <?php $option = $processing_options['processing'][$key]; ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1"
                                                   <?php checked($option['default']); ?>>
                                            <span class="checkbox-text">
                                                <strong><?php echo esc_html($option['label']); ?></strong>
                                                <small><?php echo esc_html($option['description']); ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>

                                <!-- URL Handling Options -->
                                <fieldset class="option-group">
                                    <legend><?php esc_html_e('URL Handling', 'amfm-tools'); ?></legend>
                                    <?php foreach ($processing_options['url_handling'] as $key => $option): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1"
                                                   <?php checked($option['default']); ?>>
                                            <span class="checkbox-text">
                                                <strong><?php echo esc_html($option['label']); ?></strong>
                                                <small><?php echo esc_html($option['description']); ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="amfm-card">
                    <div class="amfm-card-header">
                        <h3><?php esc_html_e('Quick Actions', 'amfm-tools'); ?></h3>
                    </div>
                    <div class="amfm-card-body">
                        <div class="action-buttons">
                            <button type="button" class="button button-secondary button-block" id="detailed-analysis">
                                <span class="dashicons dashicons-analytics"></span>
                                <?php esc_html_e('Detailed Analysis', 'amfm-tools'); ?>
                            </button>
                            
                            <?php if ($can_process): ?>
                                <button type="button" class="button button-secondary button-block" id="preview-changes">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e('Preview Changes', 'amfm-tools'); ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($recent_jobs)): ?>
                            <div class="recent-jobs">
                                <h4><?php esc_html_e('Recent Jobs', 'amfm-tools'); ?></h4>
                                <ul class="job-list">
                                    <?php foreach (array_slice($recent_jobs, 0, 3) as $job): ?>
                                        <li class="job-item status-<?php echo esc_attr($job['status']); ?>">
                                            <div class="job-info">
                                                <span class="job-date"><?php echo esc_html(wp_date('M j, Y H:i', strtotime($job['started_at']))); ?></span>
                                                <span class="job-status status-<?php echo esc_attr($job['status']); ?>">
                                                    <?php echo esc_html(ucfirst($job['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="job-actions">
                                                <button type="button" class="button button-small view-job-details" 
                                                        data-job-id="<?php echo esc_attr($job['id']); ?>">
                                                    <?php esc_html_e('Details', 'amfm-tools'); ?>
                                                </button>
                                                <?php if ($job['status'] === 'completed' && !empty($job['results'])): ?>
                                                    <button type="button" class="button button-small rollback-job" 
                                                            data-job-id="<?php echo esc_attr($job['id']); ?>">
                                                        <?php esc_html_e('Rollback', 'amfm-tools'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section (Hidden by default) -->
        <div id="cleanup-results-section" class="amfm-card" style="display: none;">
            <div class="amfm-card-header">
                <h3><?php esc_html_e('Cleanup Results', 'amfm-tools'); ?></h3>
                <button type="button" class="button button-link" id="close-results">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="amfm-card-body">
                <div id="results-content">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal for Job Details -->
<div id="job-details-modal" class="amfm-modal" style="display: none;">
    <div class="amfm-modal-content">
        <div class="amfm-modal-header">
            <h3><?php esc_html_e('Job Details', 'amfm-tools'); ?></h3>
            <button type="button" class="amfm-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="amfm-modal-body">
            <div id="job-details-content">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
    <div class="amfm-modal-backdrop"></div>
</div>

<!-- JavaScript Templates -->
<script type="text/template" id="progress-log-template">
    <div class="log-entry log-<%= level %>">
        <span class="log-time"><%= timestamp %></span>
        <span class="log-message"><%= message %></span>
    </div>
</script>

<script type="text/template" id="results-template">
    <div class="results-summary">
        <div class="result-stats">
            <div class="result-stat">
                <div class="stat-number"><%= posts_updated %></div>
                <div class="stat-label"><?php esc_html_e('Posts Updated', 'amfm-tools'); ?></div>
            </div>
            <div class="result-stat">
                <div class="stat-number"><%= custom_fields_updated %></div>
                <div class="stat-label"><?php esc_html_e('Custom Fields Updated', 'amfm-tools'); ?></div>
            </div>
            <div class="result-stat">
                <div class="stat-number"><%= menus_updated %></div>
                <div class="stat-label"><?php esc_html_e('Menus Updated', 'amfm-tools'); ?></div>
            </div>
            <div class="result-stat">
                <div class="stat-number"><%= total_url_replacements %></div>
                <div class="stat-label"><?php esc_html_e('URLs Replaced', 'amfm-tools'); ?></div>
            </div>
        </div>
        
        <div class="results-actions">
            <% if (status === 'completed' && !dry_run) { %>
                <button type="button" class="button button-secondary" id="rollback-changes" data-job-id="<%= job_id %>">
                    <span class="dashicons dashicons-undo"></span>
                    <?php esc_html_e('Rollback Changes', 'amfm-tools'); ?>
                </button>
            <% } %>
            
            <button type="button" class="button button-primary" id="view-detailed-results" data-job-id="<%= job_id %>">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('View Details', 'amfm-tools'); ?>
            </button>
        </div>
    </div>
</script>