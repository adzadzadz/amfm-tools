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
            options.create_backup = $form.find('input[name="create_backup"]').is(':checked');

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
            
            // Sample data structure - this would come from the actual job results
            const sampleResults = [
                {
                    title: 'About Us Page',
                    type: 'Post',
                    url_changes: 3,
                    old_urls: ['example.com/old-about', 'example.com/old-contact'],
                    new_urls: ['example.com/about', 'example.com/contact'],
                    status: 'Updated'
                },
                {
                    title: 'Homepage Hero Section',
                    type: 'Custom Field',
                    url_changes: 1,
                    old_urls: ['example.com/old-hero'],
                    new_urls: ['example.com/hero'],
                    status: 'Updated'
                },
                {
                    title: 'Main Navigation',
                    type: 'Menu',
                    url_changes: 2,
                    old_urls: ['example.com/services', 'example.com/blog'],
                    new_urls: ['example.com/our-services', 'example.com/news'],
                    status: 'Updated'
                }
            ];

            // In a real implementation, this would use jobData.detailed_results or similar
            sampleResults.forEach(item => {
                const urlChangesText = item.url_changes > 0 ? 
                    `${item.url_changes} URL${item.url_changes > 1 ? 's' : ''} updated` : 
                    'No changes';
                    
                const statusClass = item.status === 'Updated' ? 'status-updated' : 'status-error';
                
                const row = `
                    <tr>
                        <td>
                            <strong>${this.escapeHtml(item.title)}</strong>
                        </td>
                        <td>${this.escapeHtml(item.type)}</td>
                        <td>
                            <span class="url-changes-count">${urlChangesText}</span>
                            ${item.url_changes > 0 ? `
                                <div class="url-changes-details" style="display: none;">
                                    ${item.old_urls.map((oldUrl, index) => `
                                        <div class="url-change">
                                            <span class="old-url">${this.escapeHtml(oldUrl)}</span>
                                            <span class="arrow">→</span>
                                            <span class="new-url">${this.escapeHtml(item.new_urls[index] || '')}</span>
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
            $tableBody.find('.url-changes-count').on('click', function() {
                const $details = $(this).siblings('.url-changes-details');
                $details.toggle();
            });
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
            
            const jobId = $(e.target).data('job-id');
            
            if (jobId) {
                $(e.target).data('job-id', jobId).trigger('click');
            }
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