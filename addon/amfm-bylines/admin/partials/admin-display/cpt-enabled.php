<?php

// Ensure WordPress environment is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// First query: Fetch staff posts with amfm_sort > 0
$query1 = new WP_Query([
    'post_type'      => 'staff',
    'posts_per_page' => -1,
    'orderby'        => 'meta_value_num',
    'meta_key'       => 'amfm_sort', // The ACF field to sort by
    'order'          => 'ASC', // Descending order to get higher amfm_sort values first
    'meta_query'     => [
        [
            'key'     => 'amfm_sort',
            'value'   => '0',
            'compare' => '>',
        ]
    ],
]);

// Second query: Fetch staff posts where amfm_sort is 0 or null
$query2 = new WP_Query([
    'post_type'      => 'staff',
    'posts_per_page' => -1,
    'orderby'        => 'ID',
    'order'          => 'ASC', // Ascending order so these appear last
    'meta_query'     => [
        'relation' => 'OR',
        [
            'key'     => 'amfm_sort',
            'value'   => '0',
            'compare' => '=',
        ],
        [
            'key'     => 'amfm_sort',
            'compare' => 'NOT EXISTS', // To get posts where amfm_sort is null
        ]
    ],
]);

// Merge the results from both queries
$merged_posts = array_merge($query1->posts, $query2->posts);

?>

<div class="wrap">
    <div class="row">
        <div class="col-12 mt-4">
            <div class="row staff-grid sortable"> 
                <?php foreach ($merged_posts as $post_id) : ?>
                    <?php 
                        $byline = get_post($post_id); // Get the full post object from the post ID
                    ?>
                    <div class="col-6 col-md-4 col-lg-2 mb-4 staff-item" data-id="<?php echo $byline->ID; ?>">
                        <div class="card amfm-card amfm-card-item amfm-card-staff-item" data-url="<?php echo get_edit_post_link($byline->ID); ?>">
                            <?php if (has_post_thumbnail($byline->ID)) : ?>
                                <div class="card-img-top" style="background-image: url('<?php echo get_the_post_thumbnail_url($byline->ID); ?>'); background-size: cover; background-position: center; width: 100%; padding-top: 100%;"></div>
                            <?php else : ?>
                                <img src="<?= $placeholder ?>" class="card-img-top" alt="<?php echo esc_attr(stripslashes($byline->post_title)); ?>">
                            <?php endif; ?>
                            <div class="card-body ">
                                <h5 class="card-title"><?php echo esc_html(stripslashes($byline->post_title)); ?></h5>
                                <p class="card-text"><?php echo esc_html(stripslashes(get_post_meta($byline->ID, 'title', true))); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        $(".sortable").sortable({
            items: ".staff-item", // Only make staff items sortable
            update: function(event, ui) {
                var sortedIDs = $(this).sortable("toArray", { attribute: 'data-id' });
                console.log(sortedIDs);
                $.ajax({
                    url: ajaxurl, // WordPress AJAX URL
                    type: 'POST',
                    data: {
                        action: 'update_staff_order', // Your AJAX action
                        ids: sortedIDs,
                        nonce: '<?php echo wp_create_nonce("update_staff_order_nonce"); ?>' // Nonce for security
                    },
                    success: function(response) {
                        if (response.data === 'success') {
                            console.log('Order updated successfully');
                        } else {
                            console.error('Error updating order:', response);
                        }
                    },
                    error: function(error) {
                         console.error('AJAX error:', error);
                    }
                });
            }
        });
    });
</script>