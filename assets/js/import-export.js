/**
 * Import/Export Page JavaScript
 * Simplified version without drawer functionality
 */

// Initialize form handlers
function initializeFormHandlers() {
    const $ = window.jQuery;
    if (!$) {
        console.error('jQuery not available');
        return;
    }

    $(() => {
        // Toggle post data selection
        $('input[name="export_options[]"][value="post_data"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-post-data-selection').show();
            } else {
                $('.amfm-post-data-selection').hide();
            }
        });

        // Toggle specific post columns
        $('input[name="post_data_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-post-columns').show();
            } else {
                $('.amfm-specific-post-columns').hide();
            }
        });

        // Toggle taxonomy selection
        $('input[name="export_options[]"][value="taxonomies"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-taxonomy-selection').show();
            } else {
                $('.amfm-taxonomy-selection').hide();
            }
        });

        // Toggle specific taxonomies
        $('input[name="taxonomy_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-taxonomies').show();
            } else {
                $('.amfm-specific-taxonomies').hide();
            }
        });

        // Toggle ACF selection
        $('input[name="export_options[]"][value="acf_fields"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-acf-selection').show();
            } else {
                $('.amfm-acf-selection').hide();
            }
        });

        // Toggle specific ACF groups
        $('input[name="acf_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-acf-groups').show();
            } else {
                $('.amfm-specific-acf-groups').hide();
            }
        });

        // Show/hide export options and update taxonomies based on post type
        $('#export_post_type').off('change').on('change', function() {
            const postType = $(this).val();
            
            // Show or hide export options section
            if (postType) {
                $('.amfm-export-options').show();
                
                // Load taxonomies for this post type
                if (window.ajaxurl && window.amfmData?.ajaxNonce) {
                    $.ajax({
                        url: window.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'amfm_get_post_type_taxonomies',
                            post_type: postType,
                            nonce: window.amfmData.ajaxNonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $('.amfm-specific-taxonomies').html(response.data);
                            }
                        },
                        error: function() {
                            console.error('Failed to load taxonomies');
                        }
                    });
                }
            } else {
                $('.amfm-export-options').hide();
                $('.amfm-post-data-selection').hide();
                $('.amfm-taxonomy-selection').hide();
                $('.amfm-acf-selection').hide();
            }
        });
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handlers immediately since forms are already in the DOM
    initializeFormHandlers();
});