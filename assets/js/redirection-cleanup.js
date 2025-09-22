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
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        RedirectionCleanup.init();
    });

})(jQuery);