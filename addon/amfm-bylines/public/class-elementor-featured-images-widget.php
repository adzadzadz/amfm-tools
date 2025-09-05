<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

class Elementor_AMFM_Featured_Images_Widget extends \Elementor\Widget_Base
{

    // Define widget name
    public function get_name()
    {
        return 'amfm_featured_images';
    }

    // Define widget title
    public function get_title()
    {
        return __('AMFM Related Press Images', 'amfm-bylines');
    }

    // Define widget icon
    public function get_icon()
    {
        return 'eicon-image-gallery';
    }

    // Define widget categories
    public function get_categories()
    {
        return ['general'];
    }

    // Define widget controls
    protected function _register_controls()
    {
        // Content section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'related_posts_filter',
            [
                'label' => __('Filter', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'default' => 'in-the-press',
            ]
        );

        $this->add_control(
            'post_type',
            [
                'label' => __('Post Type', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'post',
                'options' => [
                    'post' => __('Post', 'amfm-bylines'),
                    'page' => __('Page', 'amfm-bylines'),
                    'both' => __('Both', 'amfm-bylines'),
                ],
            ]
        );

        $this->add_control(
            'posts_count',
            [
                'label' => __('Number of Posts', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Image Size', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => [
                    'thumbnail' => __('Thumbnail (150x150)', 'amfm-bylines'),
                    'medium' => __('Medium (300x300)', 'amfm-bylines'),
                    'large' => __('Large (1024x1024)', 'amfm-bylines'),
                    'full' => __('Full Size', 'amfm-bylines'),
                ],
            ]
        );

        $this->add_control(
            'image_alignment',
            [
                'label' => __('Image Alignment', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'amfm-bylines'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'amfm-bylines'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'amfm-bylines'),
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
                'label' => __('Link Images to Posts', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-bylines'),
                'label_off' => __('No', 'amfm-bylines'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'hide_containers_when_empty',
            [
                'label' => __('Hide Containers When Empty', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => '.authored-posts-container, .edited-posts-container, .reviewed-posts-container, .in-the-press-images-container',
                'description' => __('CSS classes to hide when no results are found (comma-separated)', 'amfm-bylines'),
                'default' => '',
            ]
        );

        $this->end_controls_section();

        // Style Section - Images
        $this->start_controls_section(
            'image_section',
            [
                'label' => __('Images', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_width',
            [
                'label' => __('Image Width', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SLIDER,
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
                'label' => __('Spacing Between Images', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SLIDER,
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
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'label' => __('Image Border', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-featured-image img',
            ]
        );

        $this->add_responsive_control(
            'image_border_radius',
            [
                'label' => __('Border Radius', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Title
        $this->start_controls_section(
            'title_section',
            [
                'label' => __('Title', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Title', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-bylines'),
                'label_off' => __('Hide', 'amfm-bylines'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-featured-image-title' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-featured-image-title',
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'title_spacing',
            [
                'label' => __('Title Spacing', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SLIDER,
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
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Pagination Section
        $this->start_controls_section(
            'pagination_section',
            [
                'label' => __('Pagination', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'pagination_color',
            [
                'label' => __('Pagination Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-pagination a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'pagination_typography',
                'label' => __('Pagination Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-pagination',
            ]
        );

        $this->end_controls_section();
    }

    // Fetch related posts logic
    private function fetch_related_posts($posts_per_page = 5, $filter = 'all', $settings)
    {
        global $post;

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        // Get all tags of the current post
        $tags = wp_get_post_tags($post->ID);

        if (!$tags) {
            return '<p>No "In the Press" posts found.</p>';
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
            return '<p>No "In the Press" posts found.</p>';
        }

        // Get the tag IDs
        $tag_ids = array_map(function ($tag) {
            return $tag->term_id;
        }, $tags);

        // Create the custom query to fetch posts or pages with the same tags
        $post_type = $settings['post_type'];
        $args = [
            'post_type'      => $post_type === 'both' ? ['post', 'page'] : $post_type,
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

        // Execute the query
        $query = new WP_Query($args);

        // Generate HTML output
        if ($query->have_posts()) {
            $output = '<div class="amfm-featured-images-vertical">';
            while ($query->have_posts()) {
                $query->the_post();
                $featured_image = get_the_post_thumbnail(get_the_ID(), $settings['image_size'], ['class' => 'amfm-featured-image-img']);
                
                if ($featured_image) {
                    $output .= '<div class="amfm-featured-image-item">';
                    
                    if ($settings['enable_image_link'] === 'yes') {
                        $output .= '<a href="' . get_permalink() . '" class="amfm-featured-image">';
                        $output .= $featured_image;
                        $output .= '</a>';
                    } else {
                        $output .= '<div class="amfm-featured-image">';
                        $output .= $featured_image;
                        $output .= '</div>';
                    }
                    
                    if ($settings['show_title'] === 'yes') {
                        $output .= '<h3 class="amfm-featured-image-title">';
                        $output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                        $output .= '</h3>';
                    }
                    
                    $output .= '</div>';
                }
            }
            $output .= '</div>';

            // Pagination links
            $output .= '<div class="amfm-pagination">';
            $output .= paginate_links(array(
                'base'      => esc_url(add_query_arg('paged', '%#%')),
                'format'    => '?paged=%#%',
                'current'   => max(1, $paged),
                'total'     => $query->max_num_pages,
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ));
            $output .= '</div>';
        } else {
            $output = '<p class="amfm-no-related-posts">No "In the Press" posts with featured images found.</p>';
        }

        // Reset post data
        wp_reset_postdata();

        return $output;
    }

    // Render widget output
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $posts_per_page = $settings['posts_count'];
        $related_posts_filter = $settings['related_posts_filter'];
        $post_type = $settings['post_type'];
        $hide_containers = $settings['hide_containers_when_empty'];

        $content = $this->fetch_related_posts($posts_per_page, $related_posts_filter, $settings);
        $is_empty = strpos($content, 'No "In the Press" posts') !== false;

        echo '<div id="amfm-featured-images-widget-' . $this->get_id() . '" class="amfm-featured-images-widget" data-amfm-post-type="' . $post_type . '" data-elementor-widget-id="' . $this->get_id() . '" data-filter="' . esc_attr($related_posts_filter) . '" data-posts-count="' . intval($posts_per_page) . '">';
        echo $content;
        echo '</div>';

        // Add JavaScript to hide containers when empty
        if ($is_empty && !empty($hide_containers)) {
            $containers = array_map('trim', explode(',', $hide_containers));
            $selector = implode(', ', $containers);
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const containers = document.querySelectorAll("' . esc_js($selector) . '");
                    containers.forEach(function(container) {
                        container.style.display = "none";
                    });
                });
            </script>';
        }
    }
}

// Register the widget
\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Elementor_AMFM_Featured_Images_Widget());