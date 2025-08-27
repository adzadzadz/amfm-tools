<?php
if (!defined('ABSPATH')) exit;

// Query sorted staff posts
$query1 = new WP_Query([
    'post_type'      => 'staff',
    'posts_per_page' => -1,
    'orderby'        => 'meta_value_num',
    'meta_key'       => 'amfm_sort',
    'order'          => 'ASC',
    'meta_query'     => [
        [
            'key'     => 'amfm_sort',
            'value'   => '0',
            'compare' => '>',
        ]
    ],
]);

// Query unsorted staff posts
$query2 = new WP_Query([
    'post_type'      => 'staff',
    'posts_per_page' => -1,
    'orderby'        => 'ID',
    'order'          => 'ASC',
    'meta_query'     => [
        'relation' => 'OR',
        [
            'key'     => 'amfm_sort',
            'value'   => '0',
            'compare' => '=',
        ],
        [
            'key'     => 'amfm_sort',
            'compare' => 'NOT EXISTS',
        ]
    ],
]);

$merged_posts = array_merge($query1->posts, $query2->posts);
?>

<!-- Staff Grid - Full Width -->
<div class="row staff-grid sortable g-3">
                    <?php foreach ($merged_posts as $post_id) : ?>
                        <?php $byline = get_post($post_id); ?>
                        <div class="col-12 col-md-6 col-lg-4 col-xl-fifths staff-item" data-id="<?php echo $byline->ID; ?>">
                            <div class="bylines-staff-blurb" data-url="<?php echo get_edit_post_link($byline->ID); ?>">
                                <?php 
                                $thumbnail_url = has_post_thumbnail($byline->ID) ? get_the_post_thumbnail_url($byline->ID) : '';
                                $placeholder_url = AMFM_TOOLS_URL . 'assets/imgs/placeholder.jpeg';
                                ?>
                                <div class="bylines-blurb-image">
                                    <?php if ($thumbnail_url) : ?>
                                        <div class="bylines-image-thumb bylines-staff-image" 
                                             style="background-image: url('<?php echo esc_url($thumbnail_url); ?>');"
                                             data-fallback="<?php echo esc_url($placeholder_url); ?>"
                                             data-staff-id="<?php echo $byline->ID; ?>">
                                        </div>
                                    <?php else : ?>
                                        <div class="bylines-image-thumb bylines-placeholder-image">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="bylines-blurb-content">
                                    <h6 class="bylines-name"><?php echo esc_html(stripslashes($byline->post_title)); ?></h6>
                                    <p class="bylines-role">
                                        <?php echo esc_html(stripslashes(get_post_meta($byline->ID, 'title', true))); ?>
                                    </p>
                                </div>
                                <div class="bylines-drag-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
    <?php if (empty($merged_posts)) : ?>
        <div class="col-12">
            <div class="text-center py-5">
                <div class="bg-light rounded p-5">
                    <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mb-3">No Staff Members Found</h5>
                    <p class="text-muted mb-4">Create your first staff member to get started with bylines management.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo admin_url('post-new.php?post_type=staff'); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Staff Member
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=staff'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>View All Staff
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle broken staff images
    $('.bylines-staff-image').each(function() {
        var $element = $(this);
        var backgroundImage = $element.css('background-image');
        var imageUrl = backgroundImage.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
        var fallbackUrl = $element.data('fallback');
        
        if (imageUrl && imageUrl !== 'none') {
            // Test if the image loads
            var img = new Image();
            img.onload = function() {
                // Image loaded successfully, remove any error classes
                $element.removeClass('bylines-image-error');
            };
            img.onerror = function() {
                // Image failed to load, use fallback
                if (fallbackUrl) {
                    $element.css('background-image', 'url(' + fallbackUrl + ')');
                } else {
                    // If no fallback URL, show user icon
                    $element.addClass('bylines-image-error').css('background-image', 'none');
                }
            };
            img.src = imageUrl;
        }
    });
    
    // Make staff items sortable with proper configuration
    $(".sortable").sortable({
        items: ".staff-item",
        cursor: "move",
        opacity: 0.8,
        placeholder: "staff-item sortable-placeholder",
        tolerance: "pointer",
        distance: 2,
        delay: 0,
        scroll: true,
        scrollSensitivity: 40,
        scrollSpeed: 40,
        helper: function(e, item) {
            // Clone the item for dragging
            var clone = item.clone();
            clone.addClass('sortable-helper');
            // CRITICAL: Set width to match original to prevent layout issues
            clone.width(item.width());
            return clone;
        },
        start: function(e, ui) {
            // Store original index
            ui.item.data('start-index', ui.item.index());
            
            // Add dragging class
            $('body').addClass('is-dragging');
            ui.item.addClass('dragging');
            
            // CRITICAL FIX: Make placeholder inherit Bootstrap column classes
            var classes = ui.item.attr('class');
            ui.placeholder.attr('class', classes);
            ui.placeholder.removeClass('dragging').addClass('sortable-placeholder');
            
            // Set placeholder dimensions
            ui.placeholder.css({
                'height': ui.item.outerHeight(),
                'visibility': 'visible'
            });
        },
        stop: function(e, ui) {
            // Remove dragging classes
            $('body').removeClass('is-dragging');
            $('.staff-item').removeClass('dragging');
        },
        update: function(event, ui) {
            var sortedIDs = $(this).sortable("toArray", { attribute: 'data-id' });
            
            $.ajax({
                url: amfmLocalize.ajax_url,
                type: 'POST',
                data: {
                    action: 'amfm_update_staff_order',
                    ids: sortedIDs,
                    nonce: amfmLocalize.updateStaffOrderNonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Staff order updated successfully');
                        // Show success message
                        showMessage('Staff order updated successfully!', 'success');
                    } else {
                        console.error('Error updating order:', response);
                        showMessage('Error updating staff order', 'error');
                    }
                },
                error: function(error) {
                    console.error('AJAX error:', error);
                    showMessage('Error updating staff order', 'error');
                }
            });
        }
    });
    
    // Handle staff card clicks to edit in WordPress
    $(document).on('click', '.bylines-staff-blurb', function(e) {
        e.preventDefault();
        var editUrl = $(this).data('url');
        if (editUrl) {
            window.open(editUrl, '_blank');
        }
    });
    
    // Show message function
    function showMessage(message, type) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
        
        var messageHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            '<i class="' + icon + ' me-2"></i>' + message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>';
        
        $('#flash-message-container').html(messageHtml);
        
        // Auto remove after 3 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    }
});
</script>

