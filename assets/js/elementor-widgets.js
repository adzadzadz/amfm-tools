jQuery(document).ready(function ($) {

    console.log("AMFM Elementor Widgets Running");

    $(document).on('click', '.amfm-pagination a', function (e) {
        e.preventDefault();

        console.log("Pagination Clicked");

        let widget_id = $(this).closest('.amfm-related-posts-widget').data('elementor-widget-id');

        let page = $(this).attr('href').split('paged=')[1];
        let filter = $(this).closest('#amfm-related-posts-widget-' + widget_id).data('filter');
        let postsPerPage = $('#amfm-related-posts-widget-' + widget_id).data('posts-count');
        let post_type = $('#amfm-related-posts-widget-' + widget_id).data('amfm-post-type');
        
        // Use the global AJAX object if available
        if (typeof amfm_ajax_object !== 'undefined') {
            $.ajax({
                url: amfm_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'amfm_fetch_related_posts',
                    security: amfm_ajax_object.nonce,
                    paged: page,
                    filter: filter,
                    posts_per_page: postsPerPage,
                    post_id: amfm_ajax_object.post_id,
                    widget_id: widget_id,
                    post_type: post_type
                },
                beforeSend: function () {
                    $('#amfm-related-posts-widget-' + widget_id + ' .amfm-related-posts').html('<p>Loading...</p>');
                },
                success: function (response) {
                    if (response.content) {
                        $('#amfm-related-posts-widget-' + widget_id + ' .amfm-related-posts').html(response.content);
                        $('#amfm-related-posts-widget-' + widget_id + ' .amfm-pagination').html(response.pagination);
                    }
                },
                error: function () {
                    $('#amfm-related-posts-widget-' + widget_id + ' .amfm-related-posts').html('<p>Error loading posts.</p>');
                },
            });
        } else {
            console.error('AMFM AJAX object not found');
        }
    });
});