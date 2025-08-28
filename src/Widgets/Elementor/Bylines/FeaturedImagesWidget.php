<?php

namespace App\Widgets\Elementor\Bylines;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class FeaturedImagesWidget extends Widget_Base
{
    public function get_name()
    {
        return 'amfm_bylines_featured_images';
    }

    public function get_title()
    {
        return __('Bylines Featured Images', 'amfm-tools');
    }

    public function get_icon()
    {
        return 'eicon-image-gallery';
    }

    public function get_categories()
    {
        return ['amfm-tools'];
    }

    public function get_keywords()
    {
        return ['bylines', 'featured', 'images', 'gallery', 'press', 'author', 'editor'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'related_posts_filter',
            [
                'label' => __('Filter by Byline Type', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'in-the-press',
                'options' => [
                    'all' => __('All Bylines', 'amfm-tools'),
                    'author' => __('Author Only', 'amfm-tools'),
                    'editor' => __('Editor Only', 'amfm-tools'),
                    'reviewer' => __('Reviewer Only', 'amfm-tools'),
                    'in-the-press' => __('In the Press Only', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'post_type',
            [
                'label' => __('Content Type', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'post',
                'options' => [
                    'post' => __('Posts Only', 'amfm-tools'),
                    'page' => __('Pages Only', 'amfm-tools'),
                    'both' => __('Posts & Pages', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'posts_count',
            [
                'label' => __('Number of Items', 'amfm-tools'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'max' => 20,
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Image Size', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => [
                    'thumbnail' => __('Thumbnail (150x150)', 'amfm-tools'),
                    'medium' => __('Medium (300x300)', 'amfm-tools'),
                    'large' => __('Large (1024x1024)', 'amfm-tools'),
                    'full' => __('Full Size', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'image_alignment',
            [
                'label' => __('Image Alignment', 'amfm-tools'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'amfm-tools'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'amfm-tools'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'amfm-tools'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image-item' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'enable_image_link',
            [
                'label' => __('Link Images to Posts', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-tools'),
                'label_off' => __('No', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Title', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'hide_containers_when_empty',
            [
                'label' => __('Hide Containers When Empty', 'amfm-tools'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => '.in-the-press-images-container, .authored-posts-container',
                'description' => __('CSS selectors to hide when no results found (comma-separated)', 'amfm-tools'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Images
        $this->start_controls_section(
            'image_style_section',
            [
                'label' => __('Images', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_width',
            [
                'label' => __('Image Width', 'amfm-tools'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_spacing',
            [
                'label' => __('Spacing Between Images', 'amfm-tools'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image-item:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'label' => __('Image Border', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-featured-image img',
            ]
        );

        $this->add_responsive_control(
            'image_border_radius',
            [
                'label' => __('Border Radius', 'amfm-tools'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Title
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => __('Title Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image-title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .amfm-featured-image-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label' => __('Title Hover Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-featured-image-title',
            ]
        );

        $this->add_responsive_control(
            'title_spacing',
            [
                'label' => __('Title Spacing', 'amfm-tools'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image-title' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Pagination
        $this->start_controls_section(
            'pagination_style_section',
            [
                'label' => __('Pagination Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'pagination_color',
            [
                'label' => __('Pagination Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .amfm-pagination a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'pagination_typography',
                'label' => __('Pagination Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-pagination',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $posts_per_page = $settings['posts_count'];
        $filter = $settings['related_posts_filter'];
        $post_type = $settings['post_type'];
        $hide_containers = $settings['hide_containers_when_empty'];

        $content = $this->fetch_related_posts($posts_per_page, $filter, $settings);
        $is_empty = strpos($content, 'No related posts') !== false || strpos($content, 'No "In the Press" posts') !== false;

        echo '<div id="amfm-featured-images-widget-' . $this->get_id() . '" class="amfm-featured-images-widget" data-amfm-post-type="' . esc_attr($post_type) . '" data-elementor-widget-id="' . $this->get_id() . '" data-filter="' . esc_attr($filter) . '" data-posts-count="' . intval($posts_per_page) . '">';
        echo $content;
        echo '</div>';

        // Hide containers when empty
        if ($is_empty && !empty($hide_containers)) {
            $containers = array_map('trim', explode(',', $hide_containers));
            $selector = implode(', ', array_filter($containers));
            if ($selector) {
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const containers = document.querySelectorAll("' . esc_js($selector) . '");
                        containers.forEach(function(container) {
                            if (container) container.style.display = "none";
                        });
                    });
                </script>';
            }
        }
    }

    private function fetch_related_posts($posts_per_page = 5, $filter = 'all', $settings)
    {
        global $post;

        if (!$post || !$post->ID) {
            return '<p class="amfm-no-related-posts">No related posts found.</p>';
        }

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        // Get all tags of the current post
        $tags = wp_get_post_tags($post->ID);

        if (!$tags) {
            $message = $filter === 'in-the-press' ? 'No "In the Press" posts found.' : 'No related posts found.';
            return '<p class="amfm-no-related-posts">' . $message . '</p>';
        }

        // Filter tags based on the selected filter
        $tag_prefix = '';
        switch ($filter) {
            case 'author':
                $tag_prefix = 'authored-by-';
                break;
            case 'editor':
                $tag_prefix = 'edited-by-';
                break;
            case 'reviewer':
                $tag_prefix = 'reviewed-by-';
                break;
            case 'in-the-press':
                $tag_prefix = 'in-the-press-by-';
                break;
        }

        // Filter tags by prefix if a specific filter is selected
        if ($tag_prefix) {
            $tags = array_filter($tags, function ($tag) use ($tag_prefix) {
                return strpos($tag->slug, $tag_prefix) === 0;
            });
        }

        if (empty($tags)) {
            $message = $filter === 'in-the-press' ? 'No "In the Press" posts found.' : 'No related posts found.';
            return '<p class="amfm-no-related-posts">' . $message . '</p>';
        }

        // Get the tag IDs
        $tag_ids = array_map(function ($tag) {
            return $tag->term_id;
        }, $tags);

        // Create the custom query - only posts with featured images
        $post_type_query = $settings['post_type'];
        $args = [
            'post_type'      => $post_type_query === 'both' ? ['post', 'page'] : $post_type_query,
            'posts_per_page' => $posts_per_page,
            'post__not_in'   => [$post->ID],
            'tag__in'        => $tag_ids,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => $paged,
            'meta_query'     => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $output = '<div class="amfm-featured-images-vertical">';
            while ($query->have_posts()) {
                $query->the_post();
                $featured_image = get_the_post_thumbnail(get_the_ID(), $settings['image_size'], ['class' => 'amfm-featured-image-img']);
                
                if ($featured_image) {
                    $output .= '<div class="amfm-featured-image-item">';
                    
                    // Image with optional link
                    if ($settings['enable_image_link'] === 'yes') {
                        $output .= '<a href="' . get_permalink() . '" class="amfm-featured-image">';
                        $output .= $featured_image;
                        $output .= '</a>';
                    } else {
                        $output .= '<div class="amfm-featured-image">';
                        $output .= $featured_image;
                        $output .= '</div>';
                    }
                    
                    // Optional title
                    if ($settings['show_title'] === 'yes') {
                        $output .= '<h3 class="amfm-featured-image-title">';
                        $output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                        $output .= '</h3>';
                    }
                    
                    $output .= '</div>';
                }
            }
            $output .= '</div>';

            // Pagination
            if ($query->max_num_pages > 1) {
                $output .= '<div class="amfm-pagination">';
                $output .= paginate_links([
                    'base'      => esc_url(add_query_arg('paged', '%#%')),
                    'format'    => '?paged=%#%',
                    'current'   => max(1, $paged),
                    'total'     => $query->max_num_pages,
                    'prev_text' => '&laquo; Previous',
                    'next_text' => 'Next &raquo;',
                ]);
                $output .= '</div>';
            }
        } else {
            $message = $filter === 'in-the-press' 
                ? 'No "In the Press" posts with featured images found.' 
                : 'No related posts with featured images found.';
            $output = '<p class="amfm-no-related-posts">' . $message . '</p>';
        }

        wp_reset_postdata();
        return $output;
    }
}