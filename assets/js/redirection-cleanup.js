/**
 * Redirection Cleanup JavaScript
 */
(function($) {
    'use strict';

    const RedirectionCleanup = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#analyze-content').on('click', this.analyzeContent.bind(this));
            $('#process-replacements').on('click', this.processReplacements.bind(this));
            $('#process-replacements-batch').on('click', this.processReplacementsBatch.bind(this));
            $('#clear-data').on('click', this.clearData.bind(this));
            $('#fix-malformed-urls').on('click', this.fixMalformedUrls.bind(this));

            // Handle "All Database Tables" checkbox
            $('#all_tables_checkbox').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.standard-content-type').prop('checked', false).prop('disabled', true);
                } else {
                    $('.standard-content-type').prop('disabled', false).prop('checked', true);
                }
            });

            // File upload interactions
            this.initFileUpload();
        },

        analyzeContent: function(e) {
            e.preventDefault();

            const $button = $(e.target);
            const originalText = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + amfmRedirectionCleanup.strings.analyzing);

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'analyze_content',
                    nonce: amfmRedirectionCleanup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || amfmRedirectionCleanup.strings.error);
                    }
                },
                error: function() {
                    alert(amfmRedirectionCleanup.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        processReplacements: function(e) {
            e.preventDefault();

            const dryRun = $('#dry_run').is(':checked');
            const confirmMsg = dryRun ?
                'This will preview URL replacements without making changes. Continue?' :
                amfmRedirectionCleanup.strings.confirm_process;

            if (!confirm(confirmMsg)) {
                return;
            }

            const $button = $(e.target);
            const $progressSection = $('#progress-section');
            const $resultsSection = $('#results-section');

            $button.prop('disabled', true);
            $progressSection.show();
            $resultsSection.hide();

            // Start time-based progress feedback
            let seconds = 0;
            const progressTimer = setInterval(() => {
                seconds++;
                $('#progress-message').text(`${amfmRedirectionCleanup.strings.processing} (${seconds}s)`);

                // Update progress stats with time-based messages
                if (seconds < 5) {
                    $('#posts-progress').text('Posts: Scanning...');
                    $('#meta-progress').text('Meta: Preparing...');
                    $('#urls-progress').text('URLs: Analyzing...');
                } else if (seconds < 15) {
                    $('#posts-progress').text('Posts: Processing...');
                    $('#meta-progress').text('Meta: Processing...');
                    $('#urls-progress').text('URLs: Replacing...');
                } else {
                    $('#posts-progress').text('Posts: Working...');
                    $('#meta-progress').text('Meta: Working...');
                    $('#urls-progress').text('URLs: Working...');
                }
            }, 1000);

            const contentTypes = [];
            $('input[name="content_types[]"]:checked').each(function() {
                contentTypes.push($(this).val());
            });

            if (contentTypes.length === 0) {
                alert('Please select at least one content type to process.');
                $button.prop('disabled', false);
                $progressSection.hide();
                return;
            }

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'process_replacements',
                    nonce: amfmRedirectionCleanup.nonce,
                    dry_run: dryRun,
                    content_types: contentTypes
                },
                success: function(response) {
                    clearInterval(progressTimer);

                    if (response.success) {
                        const results = response.results;

                        // Update progress stats with final numbers
                        $('#posts-progress').text(`Posts: ${results.posts_updated}`);
                        $('#meta-progress').text(`Meta: ${results.meta_updated}`);
                        $('#urls-progress').text(`URLs: ${results.urls_replaced}`);
                        $('#progress-message').text(response.dry_run ? 'Dry run complete!' : 'Processing complete!');

                        // Show results
                        const html = `
                            <div class="notice notice-${response.dry_run ? 'info' : 'success'} inline">
                                <p><strong>${response.dry_run ? 'Dry Run Results' : 'Processing Complete'}</strong></p>
                                <ul>
                                    <li>Posts updated: ${results.posts_updated}</li>
                                    <li>Meta fields updated: ${results.meta_updated}</li>
                                    <li>URLs replaced: ${results.urls_replaced}</li>
                                </ul>
                            </div>
                        `;
                        $('#results-content').html(html);
                        $resultsSection.show();

                        if (!response.dry_run) {
                            // Refresh the page after a successful live run to update recent jobs
                            setTimeout(() => location.reload(), 2000);
                        }
                    } else {
                        $('#progress-message').text('Error occurred');
                        alert(response.message || amfmRedirectionCleanup.strings.error);
                    }
                },
                error: function() {
                    clearInterval(progressTimer);
                    $('#progress-message').text('Error occurred');
                    alert(amfmRedirectionCleanup.strings.error);
                },
                complete: function() {
                    clearInterval(progressTimer);
                    $button.prop('disabled', false);
                    $progressSection.hide();
                }
            });
        },

        clearData: function(e) {
            e.preventDefault();

            if (!confirm(amfmRedirectionCleanup.strings.confirm_clear)) {
                return;
            }

            const $button = $(e.target);
            const originalText = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Clearing...');

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'clear_redirection_data',
                    nonce: amfmRedirectionCleanup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || amfmRedirectionCleanup.strings.error);
                    }
                },
                error: function() {
                    alert(amfmRedirectionCleanup.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        fixMalformedUrls: function(e) {
            e.preventDefault();

            if (!confirm('This will fix malformed URLs created by the replacement process. Continue?')) {
                return;
            }

            const $button = $(e.target);
            const originalText = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Fixing URLs...');

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fix_malformed_urls',
                    nonce: amfmRedirectionCleanup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message || amfmRedirectionCleanup.strings.error);
                    }
                },
                error: function() {
                    alert(amfmRedirectionCleanup.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        processReplacementsBatch: function(e) {
            e.preventDefault();

            const dryRun = $('#dry_run').is(':checked');
            const confirmMsg = dryRun ?
                'This will preview URL replacements in batches of 10 without making changes. Continue?' :
                'This will process URL replacements in batches of 10. This is safer and shows real-time progress. Continue?';

            if (!confirm(confirmMsg)) {
                return;
            }

            const contentTypes = [];
            $('input[name="content_types[]"]:checked').each(function() {
                contentTypes.push($(this).val());
            });

            if (contentTypes.length === 0) {
                alert('Please select at least one content type to process.');
                return;
            }

            // Initialize batch processing
            this.batchData = {
                dryRun: dryRun,
                contentTypes: contentTypes,
                currentBatch: 0,
                totalBatches: 0,
                totalUrls: 0,
                totalProcessed: 0,
                batchSize: 10
            };

            // Show batch progress section
            $('#batch-progress-section').show();
            $('#batch-progress-tbody').empty();
            $('#batch-progress-fill').css('width', '0%');

            // Start batch processing
            this.processBatch(0);
        },

        processBatch: function(batchStart) {
            const self = this;

            $('#batch-progress-info').text(`Processing batch ${Math.floor(batchStart / this.batchData.batchSize) + 1}...`);

            $.ajax({
                url: amfmRedirectionCleanup.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'process_replacements_batch',
                    nonce: amfmRedirectionCleanup.nonce,
                    dry_run: this.batchData.dryRun,
                    content_types: this.batchData.contentTypes,
                    batch_start: batchStart,
                    batch_limit: this.batchData.batchSize
                },
                success: function(response) {
                    if (response.success) {
                        const results = response.results;
                        const batchNum = Math.floor(batchStart / self.batchData.batchSize) + 1;

                        // Update total mappings count on first batch
                        if (batchStart === 0 && results.total_mappings) {
                            self.batchData.totalUrls = results.total_mappings;
                            self.batchData.totalBatches = Math.ceil(results.total_mappings / self.batchData.batchSize);
                        }

                        // Add row to progress table
                        const statusClass = results.urls_replaced > 0 ? 'success' : 'info';
                        const statusText = results.urls_replaced > 0 ? 'Completed' : 'No changes';

                        const row = `<tr>
                            <td>${batchNum}</td>
                            <td>${results.batch_start + 1}-${results.batch_end + 1}</td>
                            <td>${results.posts_updated || 0}</td>
                            <td>${results.meta_updated || 0}</td>
                            <td>${results.options_updated || 0}</td>
                            <td><strong>${results.urls_replaced || 0}</strong></td>
                            <td><span class="notice notice-${statusClass} inline" style="margin: 0; padding: 2px 8px;">${statusText}</span></td>
                        </tr>`;

                        $('#batch-progress-tbody').append(row);

                        // Update progress bar
                        const progress = self.batchData.totalBatches > 0 ?
                            Math.round((batchNum / self.batchData.totalBatches) * 100) : 0;
                        $('#batch-progress-fill').css('width', progress + '%');
                        $('#batch-progress-info').text(`Processed ${batchNum} of ${self.batchData.totalBatches} batches (${progress}%)`);

                        // Continue with next batch or finish
                        if (!results.is_complete) {
                            setTimeout(() => {
                                self.processBatch(batchStart + self.batchData.batchSize);
                            }, 500); // Small delay between batches
                        } else {
                            $('#batch-progress-info').html(`<strong>✅ Batch processing complete!</strong> Processed ${self.batchData.totalUrls} URL mappings.`);
                            if (!self.batchData.dryRun) {
                                setTimeout(() => location.reload(), 2000);
                            }
                        }
                    } else {
                        alert(response.message || 'Error during batch processing');
                    }
                },
                error: function() {
                    alert('Error during batch processing');
                }
            });
        },

        initFileUpload: function() {
            const fileInput = $('#amfm-csv-file');
            const fileWrapper = $('.amfm-file-upload-wrapper');
            const filePlaceholder = $('.amfm-file-placeholder');
            const fileStatus = $('.amfm-file-selection-status');
            const fileName = $('.amfm-file-name');
            const fileSize = $('.amfm-file-size');
            const uploadDisplay = $('.amfm-file-upload-display');

            if (fileInput.length === 0) return; // No file input on this page

            // Format file size
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Handle file selection
            fileInput.on('change', function() {
                const file = this.files[0];
                if (file) {
                    // Update file display
                    filePlaceholder.text('File selected - choose a different file or drag & drop to replace');
                    fileName.text(file.name);
                    fileSize.text(formatFileSize(file.size));
                    fileStatus.show();
                    uploadDisplay.hide();
                } else {
                    // Reset display
                    filePlaceholder.text('Choose CSV file or drag & drop here');
                    fileStatus.hide();
                    uploadDisplay.show();
                }
            });

            // Handle remove file button
            $('.amfm-remove-file').on('click', function() {
                fileInput.val('');
                fileInput.trigger('change');
            });

            // Drag and drop functionality
            uploadDisplay.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            uploadDisplay.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            uploadDisplay.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    fileInput.trigger('change');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        RedirectionCleanup.init();
    });

})(jQuery);