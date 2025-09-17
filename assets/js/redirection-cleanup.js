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

            // CSV Import
            $('#csv-upload-button').on('click', this.triggerFileUpload.bind(this));
            $('#csv-file-input').on('change', this.handleFileSelect.bind(this));
            $('#csv-import-form').on('submit', this.handleCsvImport.bind(this));
            $(document).on('click', '#process-csv-redirections', this.processCsvRedirections.bind(this));

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

            // Handle CSV progress format
            if (progressData.current !== undefined && progressData.total !== undefined) {
                const percentage = progressData.total > 0 ? Math.round((progressData.current / progressData.total) * 100) : 0;
                const message = progressData.message || 'Processing...';
                const stats = `${progressData.current} / ${progressData.total} URLs analyzed`;

                this.updateProgress(percentage, message, stats);
                this.addLogEntry('info', `Batch completed: ${progressData.current}/${progressData.total} URLs`);
            } else {
                // Handle regular progress format
                const { total_items, processed_items, current_step, errors } = progressData;
                const percentage = total_items > 0 ? Math.round((processed_items / total_items) * 100) : 0;

                this.updateProgress(percentage, current_step, `${processed_items} / ${total_items} items processed`);
            }

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
            if (!this.templates.results) {
                return;
            }

            // Handle different result structures (CSV vs regular jobs)
            let templateData;

            // Check for CSV job (can be identified by having 'result' instead of 'results' or by job type)
            const isCsvJob = (jobData.result && (jobData.result.type === 'dry_run' || jobData.result.summary)) ||
                           (jobData.type === 'csv_import') ||
                           (!jobData.results || (Array.isArray(jobData.results) && jobData.results.length === 0)) ||
                           (jobData.id && jobData.id.startsWith('csv_job_'));

            if (isCsvJob && jobData.result) {
                // CSV job dry run results
                const affectedContent = jobData.result.affected_content || {};
                templateData = {
                    posts_updated: affectedContent.posts || 0,
                    custom_fields_updated: affectedContent.custom_fields || 0,
                    menus_updated: affectedContent.menus || 0,
                    widgets_updated: affectedContent.widgets || 0,
                    total_url_replacements: jobData.result.total_changes || 0,
                    job_id: jobData.id,
                    status: jobData.status,
                    dry_run: true,
                    csv_results: jobData.result // Store full CSV results for detailed display
                };

            } else if (isCsvJob && !jobData.result) {
                // CSV job without result data - use defaults and fetch details in populateResultsTable
                templateData = {
                    posts_updated: 0,
                    custom_fields_updated: 0,
                    menus_updated: 0,
                    widgets_updated: 0,
                    total_url_replacements: 0,
                    job_id: jobData.id,
                    status: jobData.status,
                    dry_run: true
                };
            } else {
                // Regular job results
                templateData = {
                    ...jobData.results,
                    job_id: jobData.id,
                    status: jobData.status,
                    dry_run: jobData.options?.dry_run || false
                };
            }

            // Ensure all required template variables exist with fallbacks
            templateData = {
                posts_updated: 0,
                custom_fields_updated: 0,
                menus_updated: 0,
                widgets_updated: 0,
                total_url_replacements: 0,
                job_id: jobData.id || 'unknown',
                status: jobData.status || 'unknown',
                dry_run: false,
                ...templateData // Override with actual values
            };

            const resultsHtml = this.templates.results(templateData);

            $('#results-content').html(resultsHtml);
            
            // Populate the results table with detailed information
            this.populateResultsTable(jobData);
            
            // Show results section
            $('#cleanup-results-section').show();
        },

        populateResultsTable: function(jobData) {
            const $tableBody = $('#results-table-body');
            $tableBody.empty();


            // Handle CSV results differently - check multiple possible locations
            let csvResult = null;

            if (jobData.result && jobData.result.type === 'dry_run') {
                csvResult = jobData.result;
            } else if (jobData.result && jobData.result.summary) {
                csvResult = jobData.result;
            } else if (jobData.type === 'csv_import') {
                // For CSV jobs, we need to get the full job details
                this.ajaxRequest('get_job_details', { job_id: jobData.id }, {
                    success: (response) => {
                        if (response.job && response.job.result) {
                            this.renderCsvResults(response.job.result, $tableBody);
                        } else {
                            $tableBody.append('<tr><td colspan="4">No CSV results found in job details</td></tr>');
                        }
                    },
                    error: () => {
                        $tableBody.append('<tr><td colspan="4">Failed to load CSV results</td></tr>');
                    }
                });
                return;
            }

            if (csvResult) {
                this.renderCsvResults(csvResult, $tableBody);
                return;
            }

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

        renderCsvResults: function(csvResult, $tableBody) {

            // Show CSV-specific detailed results
            if (csvResult.detailed_report && csvResult.detailed_report.would_fix) {
                csvResult.detailed_report.would_fix.forEach(item => {
                    const row = `
                        <tr>
                            <td>
                                <strong>${this.escapeHtml(item.type.replace('_', ' '))}</strong>
                                ${item.sample_posts ? '<br><small>Sample: ' + item.sample_posts.map(p => p.title).slice(0, 2).join(', ') + '</small>' : ''}
                                ${item.sample_fields ? '<br><small>Fields: ' + item.sample_fields.map(f => f.meta_key).slice(0, 2).join(', ') + '</small>' : ''}
                            </td>
                            <td>
                                <span class="content-type-badge">${this.escapeHtml(item.type)}</span>
                            </td>
                            <td>
                                <div class="url-change">
                                    <div class="old-url">${this.escapeHtml(item.old_url)}</div>
                                    <div class="arrow">→</div>
                                    <div class="new-url">${this.escapeHtml(item.new_url)}</div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge dry-run">
                                    ${item.occurrences} would be updated
                                </span>
                            </td>
                        </tr>
                    `;
                    $tableBody.append(row);
                });
            }

            // Show recommendations if available
            if (csvResult.recommendations && csvResult.recommendations.length > 0) {
                const recommendationsRow = `
                    <tr class="recommendations-row">
                        <td colspan="4">
                            <div class="csv-recommendations">
                                <h5>Recommendations:</h5>
                                <ul>
                                    ${csvResult.recommendations.map(rec =>
                                        `<li class="recommendation-${rec.level}">${this.escapeHtml(rec.message)}</li>`
                                    ).join('')}
                                </ul>
                            </div>
                        </td>
                    </tr>
                `;
                $tableBody.append(recommendationsRow);
            }

            // If no results to show
            if (!csvResult.detailed_report || csvResult.detailed_report.would_fix.length === 0) {
                $tableBody.append(`
                    <tr>
                        <td colspan="4" class="no-results">
                            No matching content found for the imported URLs.
                        </td>
                    </tr>
                `);
            }
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
                        <td colspan="4" class="no-results">
                            <em>No detailed results available. Check the processing logs for more information.</em>
                        </td>
                    </tr>
                `);
                return;
            }
            
            results.forEach(item => {
                const urlChangesText = item.url_changes > 0 ? 
                    `${item.url_changes} URL${item.url_changes > 1 ? 's' : ''}` : 
                    'No changes';
                    
                const statusClass = item.status === 'Updated' ? 'status-updated' : 
                                   item.status === 'Analyzed' ? 'status-analyzed' : 'status-error';
                
                const row = `
                    <tr>
                        <td>
                            <strong>${this.escapeHtml(item.title)}</strong>
                            ${item.source_link ? `<br><a href="${item.source_link}" target="_blank" class="source-link">Edit Source</a>` : ''}
                        </td>
                        <td>${this.escapeHtml(item.type)}</td>
                        <td>
                            <span class="url-changes-count">${urlChangesText}</span>
                            ${item.old_urls && item.new_urls ? `
                                <div class="url-changes-details" style="display: none;">
                                    ${item.old_urls.map((oldUrl, index) => `
                                        <div class="url-change">
                                            <a href="${this.escapeHtml(oldUrl)}" target="_blank" class="old-url">${this.escapeHtml(oldUrl)}</a>
                                            <span class="arrow">→</span>
                                            <a href="${this.escapeHtml(item.new_urls[index] || '')}" target="_blank" class="new-url">${this.escapeHtml(item.new_urls[index] || '')}</a>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </td>
                        <td><span class="status-badge ${statusClass}">${this.escapeHtml(item.status)}</span></td>
                    </tr>
                `;
                
                $tableBody.append(row);
            });
            
            // Add click handler to show/hide URL changes details
            $tableBody.find('.url-changes-count').on('click', function(e) {
                e.preventDefault();
                const $details = $(this).siblings('.url-changes-details');
                $details.slideToggle(200);
                
                // Update text to show expand/collapse state
                const $this = $(this);
                const isVisible = $details.is(':visible');
                if (isVisible) {
                    $this.addClass('expanded');
                } else {
                    $this.removeClass('expanded');
                }
            });
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
            
            this.renderResultsTable(summaryItems, $tableBody);
        },

        closeResults: function() {
            $('#cleanup-results-section').hide();
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
            
            if (!confirm(this.strings.confirm_rollback)) {
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

        downloadResults: function(e) {
            e.preventDefault();
            
            const jobId = this.currentJobId;
            if (!jobId) {
                this.showNotice('No job data available for download', 'error');
                return;
            }

            // Get job details for the report
            this.ajaxRequest('get_job_details', { job_id: jobId }, {
                success: (response) => {
                    this.generateAndDownloadReport(response);
                },
                error: (error) => {
                    this.showNotice('Failed to generate report: ' + error.message, 'error');
                }
            });
        },

        generateAndDownloadReport: function(jobDetails) {
            const job = jobDetails.job;

            // Check if this is a CSV import job
            if (job.type === 'csv_import' && job.result) {
                return this.generateCsvImportReport(job);
            }

            const logs = jobDetails.logs || [];

            // Parse the logs to get detailed results
            const results = this.parseLogsForResults(logs, job.options || {});

            // Generate CSV content
            let csvContent = '';
            
            // CSV Headers
            const headers = [
                'Item Name',
                'Type', 
                'Status',
                'Post ID',
                'Meta Key',
                'Old URL',
                'New URL',
                'Source Edit Link',
                'Timestamp'
            ];
            csvContent += headers.map(h => `"${h}"`).join(',') + '\n';
            
            // CSV Data rows - create one row per URL change
            results.forEach(item => {
                if (item.old_urls && item.new_urls && item.old_urls.length > 0) {
                    // Multiple URL changes - create one row per URL pair
                    item.old_urls.forEach((oldUrl, index) => {
                        const row = [
                            item.title || '',
                            item.type || '',
                            item.status || '',
                            item.post_id || '',
                            item.meta_key || '',
                            oldUrl || '',
                            item.new_urls[index] || '',
                            item.source_link || '',
                            new Date().toISOString()
                        ];
                        csvContent += row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',') + '\n';
                    });
                } else {
                    // Single or no URL changes
                    const row = [
                        item.title || '',
                        item.type || '',
                        item.status || '',
                        item.post_id || '',
                        item.meta_key || '',
                        item.old_url || '',
                        item.new_url || '',
                        item.source_link || '',
                        new Date().toISOString()
                    ];
                    csvContent += row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',') + '\n';
                }
            });
            
            // Add summary section
            csvContent += '\n\n"SUMMARY INFORMATION"\n';
            csvContent += `"Job ID","${job.id}"\n`;
            csvContent += `"Status","${job.status}"\n`;
            csvContent += `"Started","${job.started_at}"\n`;
            if (job.completed_at) {
                csvContent += `"Completed","${job.completed_at}"\n`;
            }
            csvContent += `"Mode","${job.options?.dry_run ? 'Dry Run (Analysis Only)' : 'Live Update'}"\n`;
            
            if (job.results) {
                csvContent += `"Posts Updated","${job.results.posts_updated || 0}"\n`;
                csvContent += `"Custom Fields Updated","${job.results.custom_fields_updated || 0}"\n`;
                csvContent += `"Menus Updated","${job.results.menus_updated || 0}"\n`;
                csvContent += `"Widgets Updated","${job.results.widgets_updated || 0}"\n`;
                csvContent += `"Total URL Replacements","${job.results.total_url_replacements || 0}"\n`;
            }
            
            // Create and trigger download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `redirection-cleanup-report-${job.id}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            this.showNotice('CSV report downloaded successfully!', 'success');
        },

        generateCsvImportReport: function(job) {
            let csvContent = '';

            // Main data - URLs that need fixing
            csvContent += '"Old URL","New URL","Content Type","Occurrences"\n';

            if (job.result && job.result.detailed_report && job.result.detailed_report.would_fix) {
                // Sort by occurrences (highest first)
                const sortedItems = [...job.result.detailed_report.would_fix].sort((a, b) =>
                    (b.occurrences || 0) - (a.occurrences || 0)
                );

                sortedItems.forEach(item => {
                    const row = [
                        item.old_url || '',
                        item.new_url || '',
                        item.type?.replace(/_/g, ' ') || '',
                        item.occurrences || '0'
                    ];
                    csvContent += row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',') + '\n';
                });
            }

            // Add empty line before summary
            csvContent += '\n';

            // Summary at the bottom
            csvContent += '"SUMMARY"\n';

            if (job.result && job.result.summary) {
                csvContent += `"Total URLs Analyzed","${job.result.summary.total_url_mappings || 0}"\n`;
                csvContent += `"URLs Found in Content","${job.result.summary.urls_with_matches || 0}"\n`;
                csvContent += `"URLs Not Found","${job.result.summary.urls_without_matches || 0}"\n`;
                csvContent += `"Total Changes Needed","${job.result.summary.total_potential_changes || 0}"\n`;
            }

            if (job.result && job.result.affected_content) {
                csvContent += '\n"CONTENT BREAKDOWN"\n';
                csvContent += `"Posts with URLs","${job.result.affected_content.posts || 0}"\n`;
                csvContent += `"Custom Fields with URLs","${job.result.affected_content.custom_fields || 0}"\n`;
                csvContent += `"Menu Items with URLs","${job.result.affected_content.menus || 0}"\n`;
                csvContent += `"Widgets with URLs","${job.result.affected_content.widgets || 0}"\n`;
            }

            csvContent += '\n"PROCESSING INFO"\n';
            csvContent += `"Analysis Date","${job.completed_at || job.started_at}"\n`;
            csvContent += `"Job ID","${job.id}"\n`;

            // Create and trigger download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `csv-import-analysis-${job.id}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            this.showNotice('CSV import analysis report downloaded successfully!', 'success');
        },

        showAnalysisModal: function(analysisData) {
            const content = this.formatAnalysisContent(analysisData);
            this.showModal('Detailed Analysis', content);
        },

        showJobDetailsModal: function(jobData) {
            const content = this.formatJobDetailsContent(jobData);
            this.showModal('Job Details', content);
        },

        showModal: function(title, content) {
            const $modal = $('#job-details-modal');
            $modal.find('h3').text(title);
            $modal.find('#job-details-content').html(content);
            $modal.show();
        },

        closeModal: function(e) {
            if (e.target === e.currentTarget) {
                $('.amfm-modal').hide();
            }
        },

        formatAnalysisContent: function(data) {
            // Format analysis data for modal display
            let html = '<div class="analysis-details">';
            
            if (data.url_mappings) {
                html += `<h4>URL Mappings (${data.url_mappings})</h4>`;
                html += '<p>The tool will replace these URLs throughout your content:</p>';
                
                if (data.url_mapping && Object.keys(data.url_mapping).length > 0) {
                    html += '<div class="url-mapping-preview">';
                    Object.entries(data.url_mapping).slice(0, 10).forEach(([source, destination]) => {
                        html += `<div class="mapping-item">`;
                        html += `<code class="source">${this.escapeHtml(source)}</code>`;
                        html += `<span class="arrow">→</span>`;
                        html += `<code class="destination">${this.escapeHtml(destination)}</code>`;
                        html += `</div>`;
                    });
                    
                    if (Object.keys(data.url_mapping).length > 10) {
                        html += `<p><em>... and ${Object.keys(data.url_mapping).length - 10} more</em></p>`;
                    }
                    
                    html += '</div>';
                }
            }
            
            if (data.content_analysis) {
                html += '<h4>Content Analysis</h4>';
                html += '<ul>';
                Object.entries(data.content_analysis).forEach(([type, count]) => {
                    if (type !== 'total_matches') {
                        html += `<li><strong>${type.replace('_', ' ').toUpperCase()}:</strong> ${count} items contain redirected URLs</li>`;
                    }
                });
                html += '</ul>';
            }
            
            if (data.processing_time_estimate) {
                html += `<p><strong>Estimated Processing Time:</strong> ${data.processing_time_estimate}</p>`;
            }
            
            html += '</div>';
            
            return html;
        },

        formatJobDetailsContent: function(data) {
            const job = data.job;
            const logs = data.logs || [];
            
            let html = '<div class="job-details">';
            
            // Job info
            html += '<div class="job-info">';
            html += `<p><strong>Status:</strong> <span class="status-${job.status}">${job.status}</span></p>`;
            html += `<p><strong>Started:</strong> ${job.started_at}</p>`;
            if (job.completed_at) {
                html += `<p><strong>Completed:</strong> ${job.completed_at}</p>`;
            }
            html += '</div>';
            
            // Results
            if (job.results) {
                html += '<h4>Results</h4>';
                html += '<ul>';
                Object.entries(job.results).forEach(([key, value]) => {
                    if (typeof value === 'number') {
                        html += `<li><strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${value}</li>`;
                    }
                });
                html += '</ul>';
            }
            
            // Logs
            if (logs.length > 0) {
                html += '<h4>Processing Log</h4>';
                html += '<div class="job-logs">';
                logs.slice(-20).forEach(log => {
                    html += `<div class="log-entry log-${log.level}">`;
                    html += `<span class="log-time">${log.timestamp}</span>`;
                    html += `<span class="log-message">${this.escapeHtml(log.message)}</span>`;
                    html += `</div>`;
                });
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
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

        // CSV Import Methods
        triggerFileUpload: function(e) {
            e.preventDefault();
            $('#csv-file-input').trigger('click');
        },

        handleFileSelect: function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    this.showNotice('File size exceeds 10MB limit', 'error');
                    e.target.value = '';
                    return;
                }

                // Show file info
                $('#csv-file-name').text(file.name);
                $('#csv-file-info').show();
                $('#csv-import-button').prop('disabled', false);
            }
        },

        handleCsvImport: function(e) {
            e.preventDefault();

            const fileInput = $('#csv-file-input')[0];
            if (!fileInput.files.length) {
                this.showNotice('Please select a CSV file', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', fileInput.files[0]);
            formData.append('action', 'import_csv_redirections');
            formData.append('nonce', amfmRedirectionCleanup.nonce);

            const $button = $('#csv-import-button');
            const originalText = $button.text();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Importing...');

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.displayCsvImportResults(response.data);
                        this.showNotice('CSV imported successfully!', 'success');
                    } else {
                        this.showNotice(response.data.message || 'Import failed', 'error');
                    }
                },
                error: () => {
                    this.showNotice('Failed to import CSV file', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        displayCsvImportResults: function(data) {
            const resultsHtml = `
                <div class="csv-import-summary">
                    <h4>Import Summary</h4>
                    <ul>
                        <li><strong>Total Rows:</strong> ${data.total_rows}</li>
                        <li><strong>Valid Redirections:</strong> ${data.valid_redirections}</li>
                        <li><strong>Skipped (Query Strings):</strong> ${data.skipped_count || 0}</li>
                        <li><strong>Unique Sources:</strong> ${data.unique_sources}</li>
                        <li><strong>Unique Destinations:</strong> ${data.unique_destinations}</li>
                    </ul>

                    ${data.skipped_count > 0 && data.skipped_samples ? `
                        <div class="skipped-urls" style="margin-top: 10px;">
                            <h5>Sample Skipped URLs (Query String Variations):</h5>
                            <ul style="font-size: 12px; color: #666;">
                                ${data.skipped_samples.map(skip => `<li>${this.escapeHtml(skip)}</li>`).join('')}
                            </ul>
                            <p style="font-size: 11px; color: #999;">
                                These URLs only differ by query parameters (pagination, filters, etc.) and are not true redirections.
                            </p>
                        </div>
                    ` : ''}

                    ${data.errors.length > 0 ? `
                        <div class="import-errors">
                            <h5>Errors:</h5>
                            <ul style="color: #d63638; font-size: 12px;">
                                ${data.errors.map(error => `<li>${this.escapeHtml(error)}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}

                    ${data.valid_redirections > 0 ? `
                        <div style="margin-top: 15px;">
                            <button type="button" class="button button-primary" id="process-csv-redirections">
                                Process ${data.valid_redirections} Redirections
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;

            $('#csv-import-results').html(resultsHtml).show();
        },

        processCsvRedirections: function(e) {
            e.preventDefault();

            const options = this.collectFormOptions();
            options.fix_redirections = true;

            const $button = $(e.target);
            const originalText = $button.text();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Processing...');

            this.ajaxRequest('process_csv_redirections', {
                options: options,
                dry_run: options.dry_run || true,
                batch_size: options.batch_size || 50,
                content_types: options.content_types || ['posts', 'custom_fields', 'menus', 'widgets']
            }, {
                success: (response) => {
                    this.currentJobId = response.job_id;
                    this.showProgressSection();
                    this.startProgressMonitoring();
                    this.showNotice('CSV analysis started! Processing in batches...', 'success');
                },
                error: (error) => {
                    this.showNotice('Failed to process CSV: ' + error.message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        checkJobCompletion: function() {
            if (!this.currentJobId) return;

            this.ajaxRequest('get_cleanup_progress', { job_id: this.currentJobId }, {
                success: (response) => {
                    if (response.status === 'completed') {
                        this.stopProgressMonitoring();
                        this.showResults(response);
                        this.showNotice('Analysis completed!', 'success');
                    }
                },
                error: () => {
                    // Continue with normal monitoring if immediate check fails
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        RedirectionCleanup.init();
    });

})(jQuery);