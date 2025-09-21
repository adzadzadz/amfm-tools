/**
 * Redirection Cleanup JavaScript
 * 
 * Handles the admin interface interactions for the redirection cleanup tool
 */

(function($) {
    'use strict';

    const RedirectionCleanup = {
        currentJobId: null,
        progressInterval: null,
        strings: {},

        init: function() {
            this.strings = amfmRedirectionCleanup.strings || {};
            this.bindEvents();
            this.initializeTemplates();
        },

        bindEvents: function() {
            // Main action buttons
            $('#refresh-analysis').on('click', this.refreshAnalysis.bind(this));
            $('#start-cleanup').on('click', this.startCleanup.bind(this));

            // Progress and results
            $('#cancel-cleanup').on('click', this.cancelCleanup.bind(this));
            $('#close-results').on('click', this.closeResults.bind(this));

            // Job management
            $(document).on('click', '.view-job-details', this.viewJobDetails.bind(this));
            $(document).on('click', '.rollback-job', this.rollbackJob.bind(this));
            $(document).on('click', '#rollback-changes', this.rollbackCurrentJob.bind(this));
            $(document).on('click', '#download-results', this.downloadResults.bind(this));

            // Malformed URL cleanup
            $('#scan-malformed-urls').on('click', this.scanMalformedUrls.bind(this));
            $('#fix-malformed-urls').on('click', this.fixMalformedUrls.bind(this));

            // Modal
            $(document).on('click', '.amfm-modal-close, .amfm-modal-backdrop', this.closeModal.bind(this));
        },

        initializeTemplates: function() {
            // Compile Underscore.js templates
            if (typeof _ !== 'undefined') {
                this.templates = {
                    progressLog: _.template($('#progress-log-template').html() || ''),
                    results: _.template($('#results-template').html() || '')
                };
            } else {
                console.warn('Underscore.js not loaded - templates will not work');
                this.templates = {
                    progressLog: null,
                    results: null
                };
            }
        },

        refreshAnalysis: function(e) {
            e.preventDefault();
            
            const $button = $(e.target).closest('button');
            const originalText = $button.html();
            
            $button.prop('disabled', true).html(
                '<span class="dashicons dashicons-update spin"></span> ' + this.strings.analyzing
            );

            this.ajaxRequest('analyze_redirections', {}, {
                success: (response) => {
                    this.showNotice('Analysis completed successfully!', 'success');
                    // Reload page to show updated analysis
                    setTimeout(() => window.location.reload(), 1500);
                },
                error: (error) => {
                    this.showNotice('Analysis failed: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        startCleanup: function(e) {
            e.preventDefault();

            // Collect form options
            const options = this.collectFormOptions();
            
            // Show progress section
            this.showProgressSection();

            this.ajaxRequest('start_cleanup', { options: options }, {
                success: (response) => {
                    this.currentJobId = response.job_id;
                    this.startProgressMonitoring();
                },
                error: (error) => {
                    this.hideProgressSection();
                    this.showNotice('Failed to start cleanup: ' + error.message, 'error');
                }
            });
        },

        collectFormOptions: function() {
            const $form = $('#cleanup-options-form');
            const options = {};

            // Content types
            options.content_types = [];
            $form.find('input[name="content_types[]"]:checked').each(function() {
                options.content_types.push($(this).val());
            });

            // Processing settings
            options.batch_size = parseInt($form.find('input[name="batch_size"]').val()) || 50;
            options.dry_run = $form.find('input[name="dry_run"]').is(':checked');
            options.create_backup = $form.find('input[name="create_backup"]:not(:disabled)').is(':checked');

            // URL handling
            options.include_relative = $form.find('input[name="include_relative"]').is(':checked');
            options.handle_query_params = $form.find('input[name="handle_query_params"]').is(':checked');

            return options;
        },

        showProgressSection: function() {
            $('#cleanup-progress-section').show();
            this.updateProgress(0, this.strings.analyzing, '0 / 0 items processed');
            $('.log-entries').empty();
        },

        hideProgressSection: function() {
            $('#cleanup-progress-section').hide();
            this.stopProgressMonitoring();
        },

        startProgressMonitoring: function() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }

            this.progressInterval = setInterval(() => {
                this.checkProgress();
            }, 2000);

            // Initial check
            this.checkProgress();
        },

        stopProgressMonitoring: function() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
        },

        checkProgress: function() {
            if (!this.currentJobId) {
                return;
            }

            this.ajaxRequest('get_cleanup_progress', { job_id: this.currentJobId }, {
                success: (response) => {
                    this.updateProgressFromResponse(response);

                    if (response.status === 'completed' || response.status === 'error') {
                        this.stopProgressMonitoring();
                        this.handleJobCompletion(response);
                    }
                },
                error: (error) => {
                    this.addLogEntry('error', 'Failed to check progress: ' + error.message);
                }
            });
        },

        updateProgressFromResponse: function(progress) {
            const progressData = progress.progress;

            // Handle regular progress format
            const { total_items, processed_items, current_step, errors } = progressData;
            const percentage = total_items > 0 ? Math.round((processed_items / total_items) * 100) : 0;

            this.updateProgress(percentage, current_step, `${processed_items} / ${total_items} items processed`);

            // Add any new errors to log
            if (progressData.errors && progressData.errors.length > 0) {
                progressData.errors.forEach(error => this.addLogEntry('error', error));
            }
        },

        updateProgress: function(percentage, step, stats) {
            $('.progress-fill').css('width', percentage + '%');
            $('.current-step').text(step);
            $('.progress-stats').text(stats);
        },

        addLogEntry: function(level, message) {
            if (!this.templates.progressLog) {
                return;
            }

            const timestamp = new Date().toLocaleTimeString();
            const logHtml = this.templates.progressLog({
                level: level,
                timestamp: timestamp,
                message: message
            });

            const $logContainer = $('.log-entries');
            $logContainer.append(logHtml);
            $logContainer.scrollTop($logContainer[0].scrollHeight);
        },

        handleJobCompletion: function(jobData) {
            if (jobData.status === 'completed') {
                this.showResults(jobData);
                this.showNotice(this.strings.complete, 'success');
            } else if (jobData.status === 'error') {
                this.showNotice('Cleanup failed: ' + (jobData.error || 'Unknown error'), 'error');
            }

            this.hideProgressSection();
        },

        showResults: function(jobData) {
            // Handle regular job results
            const isDryRun = jobData.options?.dry_run === true || jobData.options?.dry_run === 'true' || jobData.options?.dry_run === '1';
            let templateData = {
                ...jobData.results,
                job_id: jobData.id,
                status: jobData.status,
                dry_run: isDryRun
            };

            // Ensure all required template variables exist with fallbacks
            const defaults = {
                posts_updated: 0,
                custom_fields_updated: 0,
                menus_updated: 0,
                widgets_updated: 0,
                total_url_replacements: 0,
                job_id: jobData.id || 'unknown',
                status: jobData.status || 'unknown'
            };

            // Merge defaults with templateData, preserving the dry_run value
            templateData = {
                ...defaults,
                ...templateData
            };

            let resultsHtml;

            if (this.templates && this.templates.results) {
                // Use template if available
                resultsHtml = this.templates.results(templateData);
            } else {
                // Fallback simple HTML if template system fails
                const modeText = templateData.dry_run ? 'Dry Run - No Changes Made' : 'Live Processing - Changes Applied';
                const modeClass = templateData.dry_run ? 'dry-run-badge' : 'live-processing-badge';

                resultsHtml = `
                    <div class="results-summary">
                        <div class="result-stats">
                            <div class="result-stat">
                                <div class="stat-number">${templateData.posts_updated}</div>
                                <div class="stat-label">Posts Updated</div>
                            </div>
                            <div class="result-stat">
                                <div class="stat-number">${templateData.custom_fields_updated}</div>
                                <div class="stat-label">Custom Fields Updated</div>
                            </div>
                            <div class="result-stat">
                                <div class="stat-number">${templateData.menus_updated}</div>
                                <div class="stat-label">Menus Updated</div>
                            </div>
                            <div class="result-stat">
                                <div class="stat-number">${templateData.total_url_replacements}</div>
                                <div class="stat-label">URLs Replaced</div>
                            </div>
                        </div>
                        <div class="${modeClass}">
                            <span class="dashicons dashicons-${templateData.dry_run ? 'info' : 'yes-alt'}"></span>
                            ${modeText}
                        </div>

                        <div class="results-details">
                            <h4>Updated Pages & Content</h4>
                            <div class="results-table-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Content Sources</th>
                                            <th>URL Changes</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="results-table-body">
                                        <!-- Table content will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="results-actions">
                            ${!templateData.dry_run ? `
                                <button type="button" class="button button-secondary" id="rollback-changes" data-job-id="${templateData.job_id}">
                                    <span class="dashicons dashicons-undo"></span>
                                    Rollback Changes
                                </button>
                            ` : ''}

                            <button type="button" class="button button-secondary" id="download-results">
                                <span class="dashicons dashicons-download"></span>
                                Download Report
                            </button>
                        </div>
                    </div>
                `;
            }

            $('#results-content').html(resultsHtml);

            // Populate the results table with detailed information
            this.populateResultsTable(jobData);

            // Show results section
            $('#cleanup-results-section').show();
        },

        populateResultsTable: function(jobData) {
            const $tableBody = $('#results-table-body');
            $tableBody.empty();



            // Get job details to access logs for regular jobs
            this.ajaxRequest('get_job_details', { job_id: jobData.id }, {
                success: (response) => {
                    const logs = response.logs || [];
                    const jobOptions = response.job?.options || jobData.options || {};
                    const results = this.parseLogsForResults(logs, jobOptions);
                    this.renderResultsTable(results, $tableBody);
                },
                error: () => {
                    // Fallback: show summary results only
                    this.renderSummaryResults(jobData.results, $tableBody);
                }
            });
        },

        ajaxRequest: function(action, data = {}, callbacks = {}) {
            const requestData = {
                action: action,
                nonce: amfmRedirectionCleanup.nonce,
                ...data
            };

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: requestData,
                success: (response) => {
                    if (response.success) {
                        if (callbacks.success) callbacks.success(response.data);
                    } else {
                        if (callbacks.error) callbacks.error(response.data || { message: 'Unknown error' });
                    }
                },
                error: (xhr, status, error) => {
                    if (callbacks.error) callbacks.error({ message: error });
                },
                complete: callbacks.complete
            });
        },

        showNotice: function(message, type = 'info') {
            // Create and show WordPress-style notice
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${this.escapeHtml(message)}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            // Insert after header
            $('.amfm-header-section').after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);

            // Manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(() => $notice.remove());
            });
        },

        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        cancelCleanup: function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to cancel the cleanup process?')) {
                this.stopProgressMonitoring();
                this.hideProgressSection();
                this.currentJobId = null;
                this.showNotice('Cleanup process cancelled', 'info');
            }
        },

        viewJobDetails: function(e) {
            e.preventDefault();

            const jobId = $(e.target).data('job-id');

            this.ajaxRequest('get_job_details', { job_id: jobId }, {
                success: (response) => {
                    this.showJobDetailsModal(response);
                },
                error: (error) => {
                    this.showNotice('Failed to load job details: ' + error.message, 'error');
                }
            });
        },

        rollbackJob: function(e) {
            e.preventDefault();

            if (!confirm(this.strings.confirm_rollback || 'This will revert all changes. Are you sure?')) {
                return;
            }

            const jobId = $(e.target).data('job-id');
            const $button = $(e.target);
            const originalText = $button.text();

            $button.prop('disabled', true).text('Rolling back...');

            this.ajaxRequest('rollback_cleanup', { job_id: jobId }, {
                success: (response) => {
                    this.showNotice('Changes rolled back successfully!', 'success');
                    // Refresh the page to update UI
                    setTimeout(() => window.location.reload(), 1500);
                },
                error: (error) => {
                    this.showNotice('Rollback failed: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        rollbackCurrentJob: function(e) {
            e.preventDefault();

            if (!confirm(this.strings.confirm_rollback || 'This will revert all changes. Are you sure?')) {
                return;
            }

            const jobId = $(e.target).data('job-id');
            if (!jobId) {
                this.showNotice('No job ID found for rollback', 'error');
                return;
            }

            const $button = $(e.target);
            const originalText = $button.text();

            $button.prop('disabled', true).text('Rolling back...');

            this.ajaxRequest('rollback_cleanup', { job_id: jobId }, {
                success: (response) => {
                    this.showNotice('Changes rolled back successfully!', 'success');
                    // Close results and refresh the page to update UI
                    this.closeResults();
                    setTimeout(() => window.location.reload(), 1500);
                },
                error: (error) => {
                    this.showNotice('Rollback failed: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        parseLogsForResults: function(logs, jobOptions) {
            const results = [];
            const isDryRun = jobOptions?.dry_run || false;
            const actionWord = isDryRun ? 'Found' : 'Updated';
            const status = isDryRun ? 'Analyzed' : 'Updated';
            const siteUrl = window.location.origin;

            logs.forEach(log => {
                if (log.level === 'info') {
                    const message = log.message;

                    // Parse Post entries: "Post ID 123: Updated 2 occurrences of URL from "old-url" to "new-url""
                    const postMatch = message.match(/^Post ID (\d+): (Found|Updated) (\d+) occurrences? of URL from "([^"]+)" to "([^"]+)"$/);
                    if (postMatch) {
                        const postId = postMatch[1];
                        const count = parseInt(postMatch[3]);
                        const oldUrl = postMatch[4];
                        const newUrl = postMatch[5];

                        // Check if we already have an entry for this post and merge URLs
                        const existingIndex = results.findIndex(r => r.post_id === postId && r.type === 'Post');
                        if (existingIndex >= 0) {
                            results[existingIndex].url_changes += count;
                            results[existingIndex].old_urls.push(oldUrl);
                            results[existingIndex].new_urls.push(newUrl);
                        } else {
                            results.push({
                                title: `Post ID ${postId}`,
                                type: 'Post',
                                url_changes: count,
                                status: status,
                                post_id: postId,
                                old_urls: [oldUrl],
                                new_urls: [newUrl],
                                source_link: `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit`,
                                edit_link: `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit`
                            });
                        }
                    }

                    // Parse Meta entries: "Meta ID 456 (Post 123, Key: hero_image): Updated 2 occurrences of URL from "old-url" to "new-url""
                    const metaMatch = message.match(/^Meta ID (\d+) \(Post (\d+), Key: ([^)]+)\): (Found|Updated) (\d+) occurrences? of URL from "([^"]+)" to "([^"]+)"$/);
                    if (metaMatch) {
                        const postId = metaMatch[2];
                        const metaKey = metaMatch[3];
                        const count = parseInt(metaMatch[5]);
                        const oldUrl = metaMatch[6];
                        const newUrl = metaMatch[7];

                        // Check if we already have an entry for this meta key and merge URLs
                        const existingIndex = results.findIndex(r => r.post_id === postId && r.meta_key === metaKey && r.type === 'Custom Field');
                        if (existingIndex >= 0) {
                            results[existingIndex].url_changes += count;
                            results[existingIndex].old_urls.push(oldUrl);
                            results[existingIndex].new_urls.push(newUrl);
                        } else {
                            results.push({
                                title: `${metaKey} (Post ${postId})`,
                                type: 'Custom Field',
                                url_changes: count,
                                status: status,
                                post_id: postId,
                                meta_key: metaKey,
                                old_urls: [oldUrl],
                                new_urls: [newUrl],
                                source_link: `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit`,
                                edit_link: `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit`
                            });
                        }
                    }

                    // Parse Menu entries: "Menu Item "About": Updated URL from /old-about to /about"
                    const menuMatch = message.match(/^Menu Item "([^"]+)": (Would update|Updated) URL from (.+) to (.+)$/);
                    if (menuMatch) {
                        const oldUrl = menuMatch[3];
                        const newUrl = menuMatch[4];
                        results.push({
                            title: menuMatch[1],
                            type: 'Menu',
                            url_changes: 1,
                            old_urls: [oldUrl],
                            new_urls: [newUrl],
                            status: status,
                            source_link: `${siteUrl}/wp-admin/nav-menus.php`,
                            old_url: oldUrl,
                            new_url: newUrl
                        });
                    }

                    // Parse Option entries: "Option "widget_text": Updated 2 occurrences of URL from "old-url" to "new-url""
                    const optionMatch = message.match(/^Option "([^"]+)": (Found|Updated) (\d+) occurrences? of URL from "([^"]+)" to "([^"]+)"$/);
                    if (optionMatch) {
                        const optionName = optionMatch[1];
                        const count = parseInt(optionMatch[3]);
                        const oldUrl = optionMatch[4];
                        const newUrl = optionMatch[5];

                        // Check if we already have an entry for this option and merge URLs
                        const existingIndex = results.findIndex(r => r.option_name === optionName && r.type === 'Widget/Option');
                        if (existingIndex >= 0) {
                            results[existingIndex].url_changes += count;
                            results[existingIndex].old_urls.push(oldUrl);
                            results[existingIndex].new_urls.push(newUrl);
                        } else {
                            results.push({
                                title: optionName,
                                type: 'Widget/Option',
                                url_changes: count,
                                status: status,
                                option_name: optionName,
                                old_urls: [oldUrl],
                                new_urls: [newUrl],
                                source_link: `${siteUrl}/wp-admin/options-general.php`
                            });
                        }
                    }
                }
            });

            return results;
        },

        renderResultsTable: function(results, $tableBody) {
            if (results.length === 0) {
                $tableBody.append(`
                    <tr>
                        <td colspan="3" class="no-results">
                            <em>No detailed results available. Check the processing logs for more information.</em>
                        </td>
                    </tr>
                `);
                return;
            }

            // Group results by post_id to show all content sources for each post
            const groupedResults = this.groupResultsByPost(results);

            groupedResults.forEach(group => {
                const totalChanges = group.sources.reduce((sum, source) => sum + source.url_changes, 0);
                const urlChangesText = totalChanges > 0 ?
                    `${totalChanges} URL${totalChanges > 1 ? 's' : ''}` :
                    'No changes';

                // All sources should have the same status, use the first one
                const status = group.sources[0].status;
                const statusClass = status === 'Updated' ? 'status-updated' :
                                   status === 'Analyzed' ? 'status-analyzed' : 'status-error';

                const row = `
                    <tr>
                        <td>
                            <div class="content-sources-header">
                                <span class="content-sources-count clickable">${group.sources.length} Content Source${group.sources.length > 1 ? 's' : ''}</span>
                                <div class="content-sources-preview">
                                    <strong>${this.escapeHtml(group.title)}</strong>
                                    ${group.edit_link ? `<br><a href="${group.edit_link}" target="_blank" class="source-link">Edit Post</a>` : ''}
                                </div>
                            </div>
                            <div class="content-sources-details" style="display: none;">
                                ${group.sources.map(source => `
                                    <div class="content-source-item">
                                        <div class="source-type">${this.escapeHtml(source.type)}</div>
                                        <div class="source-details">
                                            ${source.meta_key ? `<strong>Field:</strong> ${this.escapeHtml(source.meta_key)}<br>` : ''}
                                            <strong>Changes:</strong> ${source.url_changes} URL${source.url_changes > 1 ? 's' : ''}
                                            ${source.source_link ? `<br><a href="${source.source_link}" target="_blank" class="source-link">Edit ${source.type}</a>` : ''}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </td>
                        <td>
                            <span class="url-changes-count clickable">${urlChangesText}</span>
                            <div class="url-changes-details" style="display: none;">
                                ${group.sources.flatMap(source =>
                                    (source.old_urls || []).map((oldUrl, index) => `
                                        <div class="url-change">
                                            <a href="${this.escapeHtml(oldUrl)}" target="_blank" class="old-url">${this.escapeHtml(oldUrl)}</a>
                                            <span class="arrow">→</span>
                                            <a href="${this.escapeHtml(source.new_urls[index] || '')}" target="_blank" class="new-url">${this.escapeHtml(source.new_urls[index] || '')}</a>
                                        </div>
                                    `)
                                ).join('')}
                            </div>
                        </td>
                        <td><span class="status-badge ${statusClass}">${this.escapeHtml(status)}</span></td>
                    </tr>
                `;

                $tableBody.append(row);
            });

            // Add click handlers for expandable sections
            $tableBody.find('.content-sources-count').off('click').on('click', function(e) {
                e.preventDefault();
                const $details = $(this).closest('.content-sources-header').siblings('.content-sources-details');
                $details.slideToggle(200);
                $(this).toggleClass('expanded');
            });

            $tableBody.find('.url-changes-count').off('click').on('click', function(e) {
                e.preventDefault();
                const $details = $(this).siblings('.url-changes-details');
                $details.slideToggle(200);
                $(this).toggleClass('expanded');
            });
        },

        groupResultsByPost: function(results) {
            const groups = {};

            results.forEach(item => {
                const groupKey = item.post_id || item.title || 'unknown';

                if (!groups[groupKey]) {
                    groups[groupKey] = {
                        title: item.title || `Content ID ${groupKey}`,
                        edit_link: item.edit_link || item.source_link,
                        sources: []
                    };
                }

                groups[groupKey].sources.push(item);
            });

            return Object.values(groups);
        },

        renderSummaryResults: function(summary, $tableBody) {
            // Fallback when detailed logs aren't available
            const summaryItems = [];

            if (summary.posts_updated > 0) {
                summaryItems.push({
                    title: 'Posts & Pages',
                    type: 'Content',
                    url_changes: summary.posts_updated,
                    status: 'Updated'
                });
            }

            if (summary.custom_fields_updated > 0) {
                summaryItems.push({
                    title: 'Custom Fields',
                    type: 'Meta Data',
                    url_changes: summary.custom_fields_updated,
                    status: 'Updated'
                });
            }

            if (summary.menus_updated > 0) {
                summaryItems.push({
                    title: 'Navigation Menus',
                    type: 'Menu',
                    url_changes: summary.menus_updated,
                    status: 'Updated'
                });
            }

            if (summary.widgets_updated > 0) {
                summaryItems.push({
                    title: 'Widgets & Options',
                    type: 'Widget',
                    url_changes: summary.widgets_updated,
                    status: 'Updated'
                });
            }

            if (summaryItems.length === 0) {
                $tableBody.append(`
                    <tr>
                        <td colspan="4" class="no-results">
                            <em>No changes were made during this cleanup process.</em>
                        </td>
                    </tr>
                `);
                return;
            }

            // Render summary items using the same format as detailed results
            this.renderResultsTable(summaryItems, $tableBody);
        },

        checkJobCompletion: function() {
            if (!this.currentJobId) return;

            this.ajaxRequest('get_cleanup_progress', { job_id: this.currentJobId }, {
                success: (response) => {
                    if (response.status === 'completed') {
                        this.stopProgressMonitoring();
                        this.showResults(response);
                        this.showNotice('Processing completed!', 'success');
                    }
                },
                error: () => {
                    // Continue with normal monitoring if immediate check fails
                }
            });
        },

        closeResults: function(e) {
            e.preventDefault();
            $('#cleanup-results-section').hide();
            $('#cleanup-progress-section').hide();
            this.currentJobId = null;
        },

        closeModal: function(e) {
            if (e.target === e.currentTarget) {
                $('.amfm-modal').hide();
            }
        },

        downloadResults: function(e) {
            e.preventDefault();

            if (!this.currentJobId) {
                this.showNotice('No job ID available for download', 'error');
                return;
            }

            const $button = $(e.target).closest('button');
            const originalText = $button.html();

            $button.prop('disabled', true).html(
                '<span class="dashicons dashicons-update spin"></span> Preparing Report...'
            );

            this.ajaxRequest('get_job_details', { job_id: this.currentJobId }, {
                success: (response) => {
                    this.generateAndDownloadReport(response);
                    this.showNotice('Report generated successfully!', 'success');
                },
                error: (error) => {
                    this.showNotice('Failed to generate report: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        generateAndDownloadReport: function(jobDetails) {
            const job = jobDetails.job || {};
            const logs = jobDetails.logs || [];

            // Generate CSV content
            let csvContent = "data:text/csv;charset=utf-8,";

            // Add header
            csvContent += "Job Report - Redirection Cleanup\n";
            csvContent += "Generated: " + new Date().toLocaleString() + "\n\n";

            // Job Summary
            csvContent += "Job Summary\n";
            csvContent += "Job ID," + (job.id || 'Unknown') + "\n";
            csvContent += "Status," + (job.status || 'Unknown') + "\n";
            csvContent += "Started," + (job.started_at || 'Unknown') + "\n";
            csvContent += "Completed," + (job.completed_at || 'Unknown') + "\n";
            csvContent += "Total Items," + (job.total_items || 0) + "\n";
            csvContent += "Processed Items," + (job.processed_items || 0) + "\n";
            csvContent += "Mode," + (job.options?.dry_run ? 'Dry Run' : 'Live Processing') + "\n\n";

            // Processing Results
            csvContent += "Processing Results\n";
            csvContent += "Type,Item,URL Changes,Status,Old URLs,New URLs\n";

            // Parse logs for detailed results
            const results = this.parseLogsForResults(logs, job.options || {});

            results.forEach(item => {
                const oldUrls = (item.old_urls || []).join('; ');
                const newUrls = (item.new_urls || []).join('; ');

                csvContent += `"${this.escapeCsv(item.type)}",`;
                csvContent += `"${this.escapeCsv(item.title)}",`;
                csvContent += `"${item.url_changes}",`;
                csvContent += `"${this.escapeCsv(item.status)}",`;
                csvContent += `"${this.escapeCsv(oldUrls)}",`;
                csvContent += `"${this.escapeCsv(newUrls)}"\n`;
            });

            // Create and trigger download
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `redirection-cleanup-report-${job.id || 'unknown'}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        escapeCsv: function(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/"/g, '""');
        },

        // Malformed URL cleanup methods
        scanMalformedUrls: function(e) {
            e.preventDefault();

            const $button = $(e.target);
            const $resultDiv = $('#malformed-urls-scan-result');
            const $count = $('#malformed-count');
            const $details = $('#malformed-details');
            const $breakdown = $('#malformed-breakdown');
            const $fixButton = $('#fix-malformed-urls');
            const $options = $('.malformed-url-options');

            $button.prop('disabled', true).find('.dashicons').addClass('spin');

            this.ajaxRequest('scan_malformed_urls', {}, {
                success: (response) => {
                    if (response.success && response.total_found > 0) {
                        $count.text(response.total_found);
                        $resultDiv.show();

                        // Show breakdown by table
                        $breakdown.empty();
                        Object.entries(response.affected_tables).forEach(([table, count]) => {
                            const tableLabel = table === 'posts' ? 'Posts/Pages' :
                                             table === 'postmeta' ? 'Custom Fields' :
                                             table === 'options' ? 'Settings/Options' : table;
                            $breakdown.append(`<li>${tableLabel}: ${count} items</li>`);
                        });
                        $details.show();

                        $fixButton.show();
                        $options.show();
                        this.showNotice(`Found ${response.total_found} items with malformed URLs`, 'warning');
                    } else {
                        $resultDiv.show();
                        $count.text('0');
                        $details.hide();
                        $fixButton.hide();
                        $options.hide();
                        this.showNotice('No malformed URLs found', 'success');
                    }
                },
                error: (error) => {
                    this.showNotice('Failed to scan: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).find('.dashicons').removeClass('spin');
                }
            });
        },

        fixMalformedUrls: function(e) {
            e.preventDefault();

            const isDryRun = $('#malformed-dry-run').is(':checked');
            const confirmMessage = isDryRun ?
                'This will scan for malformed URLs and show what would be fixed. Continue?' :
                'This will fix malformed URLs in your database. Create a backup first! Continue?';

            if (!confirm(confirmMessage)) {
                return;
            }

            const $button = $(e.target);
            const $progress = $('#malformed-progress');
            const $results = $('#malformed-results');
            const $dryRunNotice = $('#malformed-dry-run-notice');

            $button.prop('disabled', true);
            $progress.show();
            $results.hide();

            this.ajaxRequest('fix_malformed_urls', {
                options: {
                    dry_run: isDryRun,
                    batch_size: 50
                }
            }, {
                success: (response) => {
                    $progress.hide();
                    $results.show();

                    // Update results display
                    $('#malformed-posts-fixed').text(response.posts_updated || 0);
                    $('#malformed-meta-fixed').text(response.postmeta_updated || 0);
                    $('#malformed-options-fixed').text(response.options_updated || 0);

                    if (response.dry_run) {
                        $dryRunNotice.show();
                    } else {
                        $dryRunNotice.hide();
                    }

                    const message = response.dry_run ?
                        `Analysis complete: ${response.total_fixes} items would be fixed` :
                        `Successfully fixed ${response.total_fixes} malformed URLs`;

                    this.showNotice(message, 'success');

                    // Hide fix button and options after successful run
                    if (!response.dry_run && response.total_fixes > 0) {
                        $('#fix-malformed-urls').hide();
                        $('.malformed-url-options').hide();
                        // Trigger a new scan to refresh counts
                        setTimeout(() => {
                            $('#scan-malformed-urls').click();
                        }, 2000);
                    }
                },
                error: (error) => {
                    $progress.hide();
                    this.showNotice('Failed to fix URLs: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        RedirectionCleanup.init();
    });

})(jQuery);