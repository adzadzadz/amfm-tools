<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

class Elementor_AMFM_Posts_Widget extends \Elementor\Widget_Base
{

    // Define widget name
    public function get_name()
    {
        return 'amfm_posts';
    }

    // Define widget title
    public function get_title()
    {
        return __('AMFM Posts', 'amfm-bylines');
    }

    // Define widget icon
    public function get_icon()
    {
        return 'eicon-post-list';
    }

    // Define widget categories
    public function get_categories()
    {
        return ['general'];
    }

    // Define widget controls
    protected function _register_controls()
    {
        // Content section (already present)
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
                'label' => __('Related Posts Filter', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => __('All', 'amfm-bylines'),
                    'author' => __('Author', 'amfm-bylines'),
                    'editor' => __('Editor', 'amfm-bylines'),
                    'reviewer' => __('Reviewer', 'amfm-bylines'),
                    'in-the-press' => __('In the Press', 'amfm-bylines'),
                ],
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
            'hide_containers_when_empty',
            [
                'label' => __('Hide Containers When Empty', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => '.authored-posts-container, .edited-posts-container, .reviewed-posts-container',
                'description' => __('CSS classes to hide when no results are found (comma-separated)', 'amfm-bylines'),
                'default' => '',
            ]
        );

        $this->end_controls_section();

        // Start Style Section
        $this->start_controls_section(
            'title_section',
            [
                'label' => __('Title', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Text Styles Panel
        $this->add_control(
            'text_styles_panel',
            [
                'label' => __('Text Styles', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-related-post-title',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'link_section',
            [
                'label' => __('Link', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Link Styles Panel
        $this->add_control(
            'link_styles_panel',
            [
                'label' => __('Link Styles', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label' => __('Link Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'link_typography',
                'label' => __('Link Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-related-post-link',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'pagination_section',
            [
                'label' => __('Pagination', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Pagination Styles Panel
        $this->add_control(
            'pagination_styles_panel',
            [
                'label' => __('Pagination Styles', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::HEADING,
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
            return '<p>No related posts found.</p>'; // No tags, return empty if there are no tags
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
            return '<p>No related posts found.</p>'; // No matching tags
        }

        // Get the tag IDs
        $tag_ids = array_map(function ($tag) {
            return $tag->term_id;
        }, $tags);

        // Create the custom query to fetch posts or pages with the same tags
        $post_type = $settings['post_type'];
        $args = [
            'post_type'      => $post_type === 'both' ? ['post', 'page'] : $post_type, // Use the selected post type
            'posts_per_page' => $posts_per_page,
            'post__not_in'   => [$post->ID], // Exclude the current post/page
            'tag__in'        => $tag_ids, // Filter by tags
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => $paged, // Handle pagination
        ];

        // return json_encode($args);

        // Execute the query
        $query = new WP_Query($args);

        // Generate HTML output
        if ($query->have_posts()) {
            $output = '<div class="amfm-related-posts">';
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="amfm-related-post-item">';
                $output .= '<div class="amfm-related-post-title">' . get_the_title() . '</div>';
                $output .= '<a class="amfm-related-post-link amfm-read-more" href="' . get_permalink() . '">Read More</a>';
                $output .= '</div>';
            }
            $output .= '</div>';

            // Pagination links
            $output .= '<div class="amfm-pagination">';
            // $big = 999999999; // A large number for replacing the pagination
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
            $output = '<p class="amfm-no-related-posts">No related posts found.</p>';
        }

        // Reset post data
        wp_reset_postdata();

        return $output;
    }

    // Render widget output
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $posts_per_page    = $settings['posts_count'];
        $related_posts_filter = $settings['related_posts_filter'];
        $post_type = $settings['post_type'];
        $hide_containers = $settings['hide_containers_when_empty'];

        $content = $this->fetch_related_posts($posts_per_page, $related_posts_filter, $settings);
        $is_empty = strpos($content, 'No related posts found') !== false;

        echo '<div id="amfm-related-posts-widget-' . $this->get_id() . '" class="amfm-related-posts-widget" data-amfm-post-type="' . $post_type . '" data-elementor-widget-id="' . $this->get_id() . '" data-filter="' . esc_attr($related_posts_filter) . '" data-posts-count="' . intval($posts_per_page) . '">';
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
\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Elementor_AMFM_Posts_Widget());
