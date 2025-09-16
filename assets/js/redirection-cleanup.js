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
            const { total_items, processed_items, current_step, errors } = progress.progress;
            const percentage = total_items > 0 ? Math.round((processed_items / total_items) * 100) : 0;
            
            this.updateProgress(percentage, current_step, `${processed_items} / ${total_items} items processed`);

            // Add any new errors to log
            if (errors && errors.length > 0) {
                errors.forEach(error => this.addLogEntry('error', error));
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

            const resultsHtml = this.templates.results({
                ...jobData.results,
                job_id: jobData.id,
                status: jobData.status,
                dry_run: jobData.options?.dry_run || false
            });

            $('#results-content').html(resultsHtml);
            
            // Populate the results table with detailed information
            this.populateResultsTable(jobData);
            
            // Show results section
            $('#cleanup-results-section').show();
        },

        populateResultsTable: function(jobData) {
            const $tableBody = $('#results-table-body');
            $tableBody.empty();
            
            // Get job details to access logs
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

        parseLogsForResults: function(logs, jobOptions) {
            const results = [];
            const isDryRun = jobOptions?.dry_run || false;
            const actionWord = isDryRun ? 'Found' : 'Updated';
            const status = isDryRun ? 'Analyzed' : 'Updated';
            const siteUrl = window.location.origin;
            
            logs.forEach(log => {
                if (log.level === 'info') {
                    const message = log.message;
                    
                    // Parse Post entries: "Post ID 123: Updated URL from "old-url" to "new-url""
                    const postMatch = message.match(/^Post ID (\d+): (Found|Updated) URL from "([^"]+)" to "([^"]+)"$/);
                    if (postMatch) {
                        const postId = postMatch[1];
                        const oldUrl = postMatch[3];
                        const newUrl = postMatch[4];
                        
                        // Check if we already have an entry for this post and merge URLs
                        const existingIndex = results.findIndex(r => r.post_id === postId && r.type === 'Post');
                        if (existingIndex >= 0) {
                            results[existingIndex].url_changes++;
                            results[existingIndex].old_urls.push(oldUrl);
                            results[existingIndex].new_urls.push(newUrl);
                        } else {
                            results.push({
                                title: `Post ID ${postId}`,
                                type: 'Post',
                                url_changes: 1,
                                status: status,
                                post_id: postId,
                                old_urls: [oldUrl],
                                new_urls: [newUrl],
                                source_link: `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit`,
                                edit_link: `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit`
                            });
                        }
                    }
                    
                    // Parse Meta entries: "Meta ID 456 (Post 123, Key: hero_image): Updated URL from "old-url" to "new-url""
                    const metaMatch = message.match(/^Meta ID (\d+) \(Post (\d+), Key: ([^)]+)\): (Found|Updated) URL from "([^"]+)" to "([^"]+)"$/);
                    if (metaMatch) {
                        const postId = metaMatch[2];
                        const metaKey = metaMatch[3];
                        const oldUrl = metaMatch[5];
                        const newUrl = metaMatch[6];
                        
                        // Check if we already have an entry for this meta key and merge URLs
                        const existingIndex = results.findIndex(r => r.post_id === postId && r.meta_key === metaKey && r.type === 'Custom Field');
                        if (existingIndex >= 0) {
                            results[existingIndex].url_changes++;
                            results[existingIndex].old_urls.push(oldUrl);
                            results[existingIndex].new_urls.push(newUrl);
                        } else {
                            results.push({
                                title: `${metaKey} (Post ${postId})`,
                                type: 'Custom Field',
                                url_changes: 1,
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
                    
                    // Parse Option entries: "Option "widget_text": Updated URL from "old-url" to "new-url""
                    const optionMatch = message.match(/^Option "([^"]+)": (Found|Updated) URL from "([^"]+)" to "([^"]+)"$/);
                    if (optionMatch) {
                        const optionName = optionMatch[1];
                        const oldUrl = optionMatch[3];
                        const newUrl = optionMatch[4];
                        
                        // Check if we already have an entry for this option and merge URLs
                        const existingIndex = results.findIndex(r => r.option_name === optionName && r.type === 'Widget/Option');
                        if (existingIndex >= 0) {
                            results[existingIndex].url_changes++;
                            results[existingIndex].old_urls.push(oldUrl);
                            results[existingIndex].new_urls.push(newUrl);
                        } else {
                            results.push({
                                title: optionName,
                                type: 'Widget/Option',
                                url_changes: 1,
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
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        RedirectionCleanup.init();
    });

})(jQuery);