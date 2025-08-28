<?php

namespace App\Widgets\Elementor\Bylines;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class PostsWidget extends Widget_Base
{
    public function get_name()
    {
        return 'amfm_bylines_posts';
    }

    public function get_title()
    {
        return __('Bylines Posts', 'amfm-tools');
    }

    public function get_icon()
    {
        return 'eicon-post-list';
    }

    public function get_categories()
    {
        return ['amfm-tools'];
    }

    public function get_keywords()
    {
        return ['bylines', 'posts', 'related', 'author', 'editor', 'reviewer'];
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
                'default' => 'all',
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
            'hide_containers_when_empty',
            [
                'label' => __('Hide Containers When Empty', 'amfm-tools'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => '.authored-posts-container, .edited-posts-container',
                'description' => __('CSS selectors to hide when no results found (comma-separated)', 'amfm-tools'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Title
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => __('Title Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-related-post-title',
            ]
        );

        $this->end_controls_section();

        // Style Section - Links
        $this->start_controls_section(
            'link_style_section',
            [
                'label' => __('Link Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label' => __('Link Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_hover_color',
            [
                'label' => __('Link Hover Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#005177',
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'link_typography',
                'label' => __('Link Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-related-post-link',
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
        $is_empty = strpos($content, 'No related posts found') !== false;

        echo '<div id="amfm-related-posts-widget-' . $this->get_id() . '" class="amfm-related-posts-widget" data-amfm-post-type="' . esc_attr($post_type) . '" data-elementor-widget-id="' . $this->get_id() . '" data-filter="' . esc_attr($filter) . '" data-posts-count="' . intval($posts_per_page) . '">';
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
            return '<p class="amfm-no-related-posts">No related posts found.</p>';
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
            return '<p class="amfm-no-related-posts">No related posts found.</p>';
        }

        // Get the tag IDs
        $tag_ids = array_map(function ($tag) {
            return $tag->term_id;
        }, $tags);

        // Create the custom query
        $post_type_query = $settings['post_type'];
        $args = [
            'post_type'      => $post_type_query === 'both' ? ['post', 'page'] : $post_type_query,
            'posts_per_page' => $posts_per_page,
            'post__not_in'   => [$post->ID],
            'tag__in'        => $tag_ids,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => $paged,
        ];

        $query = new \WP_Query($args);

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
            $output = '<p class="amfm-no-related-posts">No related posts found.</p>';
        }

        wp_reset_postdata();
        return $output;
    }
}