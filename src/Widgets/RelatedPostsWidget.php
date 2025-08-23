<?php

namespace App\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class RelatedPostsWidget extends Widget_Base {

    public function get_name() {
        return 'amfm_related_posts';
    }

    public function get_title() {
        return __('AMFM Related Posts', 'amfm-tools');
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return ['amfm-widgets'];
    }

    public function get_keywords() {
        return ['related', 'posts', 'keywords', 'acf'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'keyword_source',
            [
                'label' => __('Keyword Source', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'amfm_keywords',
                'options' => [
                    'amfm_keywords' => __('AMFM Keywords', 'amfm-tools'),
                    'amfm_other_keywords' => __('AMFM Other Keywords', 'amfm-tools'),
                    'both' => __('Both Fields', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'posts_count',
            [
                'label' => __('Number of Posts', 'amfm-tools'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 12,
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Section Title', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label' => __('Section Title', 'amfm-tools'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Related Articles', 'amfm-tools'),
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Vertical Cards', 'amfm-tools'),
                    'horizontal' => __('Horizontal List', 'amfm-tools'),
                ],
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Columns', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6',
                ],
                'condition' => [
                    'layout' => 'grid',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-section-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_background',
            [
                'label' => __('Card Background', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get keywords from ACF or cookies
        $keywords = $this->get_amfm_keywords($settings['keyword_source']);
        
        if (empty($keywords)) {
            echo '<p>' . __('No keywords found to display related posts.', 'amfm-tools') . '</p>';
            return;
        }

        // Query related posts based on keywords
        $posts = $this->get_related_posts($keywords, $settings['posts_count']);
        
        if (empty($posts)) {
            echo '<p>' . __('No related posts found.', 'amfm-tools') . '</p>';
            return;
        }

        // Render the widget
        ?>
        <div class="amfm-related-posts-widget amfm-layout-<?php echo esc_attr($settings['layout']); ?>">
            <?php if ($settings['show_title'] === 'yes' && !empty($settings['section_title'])) : ?>
                <h2 class="amfm-section-title"><?php echo esc_html($settings['section_title']); ?></h2>
            <?php endif; ?>
            
            <div class="amfm-related-posts-grid">
                <?php foreach ($posts as $post) : ?>
                    <article class="amfm-post-card">
                        <?php if (has_post_thumbnail($post->ID)) : ?>
                            <div class="amfm-post-thumbnail">
                                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                    <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="amfm-post-content">
                            <h3 class="amfm-post-title">
                                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </h3>
                            
                            <div class="amfm-post-excerpt">
                                <?php echo wp_trim_words($post->post_excerpt ?: $post->post_content, 20); ?>
                            </div>
                            
                            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="amfm-read-more">
                                <?php echo __('Read More', 'amfm-tools'); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .amfm-related-posts-widget {
                margin: 20px 0;
            }
            .amfm-section-title {
                margin-bottom: 20px;
                font-size: 24px;
                font-weight: bold;
            }
            .amfm-related-posts-grid {
                display: grid;
                gap: 20px;
            }
            .amfm-layout-grid .amfm-related-posts-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .amfm-layout-horizontal .amfm-related-posts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .amfm-post-card {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                overflow: hidden;
                transition: transform 0.3s ease;
            }
            .amfm-post-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .amfm-post-thumbnail img {
                width: 100%;
                height: auto;
            }
            .amfm-post-content {
                padding: 15px;
            }
            .amfm-post-title {
                margin: 0 0 10px;
                font-size: 18px;
            }
            .amfm-post-title a {
                color: #333;
                text-decoration: none;
            }
            .amfm-post-excerpt {
                color: #666;
                margin-bottom: 10px;
            }
            .amfm-read-more {
                color: #0073aa;
                text-decoration: none;
                font-weight: bold;
            }
            @media (max-width: 768px) {
                .amfm-related-posts-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
        <?php
    }

    /**
     * Get keywords from ACF or cookies
     */
    private function get_amfm_keywords($source) {
        $keywords = [];
        
        // Get current post ID
        $post_id = get_queried_object_id();
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        // Try to get from ACF first
        if ($post_id && function_exists('get_field')) {
            if ($source === 'amfm_keywords' || $source === 'both') {
                $acf_keywords = get_field('amfm_keywords', $post_id);
                if ($acf_keywords) {
                    $keywords = array_merge($keywords, explode(',', $acf_keywords));
                }
            }
            
            if ($source === 'amfm_other_keywords' || $source === 'both') {
                $acf_other_keywords = get_field('amfm_other_keywords', $post_id);
                if ($acf_other_keywords) {
                    $keywords = array_merge($keywords, explode(',', $acf_other_keywords));
                }
            }
        }
        
        // Fall back to cookies if no ACF data
        if (empty($keywords)) {
            if ($source === 'amfm_keywords' || $source === 'both') {
                if (isset($_COOKIE['amfm_keywords'])) {
                    $cookie_keywords = json_decode(stripslashes($_COOKIE['amfm_keywords']), true);
                    if (is_array($cookie_keywords)) {
                        $keywords = array_merge($keywords, $cookie_keywords);
                    }
                }
            }
            
            if ($source === 'amfm_other_keywords' || $source === 'both') {
                if (isset($_COOKIE['amfm_other_keywords'])) {
                    $cookie_keywords = json_decode(stripslashes($_COOKIE['amfm_other_keywords']), true);
                    if (is_array($cookie_keywords)) {
                        $keywords = array_merge($keywords, $cookie_keywords);
                    }
                }
            }
        }
        
        // Clean up keywords
        $keywords = array_filter(array_map('trim', $keywords));
        $keywords = array_unique($keywords);
        
        return $keywords;
    }

    /**
     * Get related posts based on keywords
     */
    private function get_related_posts($keywords, $limit = 6) {
        if (empty($keywords)) {
            return [];
        }
        
        // Build meta query for ACF keywords
        $meta_query = ['relation' => 'OR'];
        
        foreach ($keywords as $keyword) {
            $meta_query[] = [
                'key' => 'amfm_keywords',
                'value' => $keyword,
                'compare' => 'LIKE'
            ];
            $meta_query[] = [
                'key' => 'amfm_other_keywords',
                'value' => $keyword,
                'compare' => 'LIKE'
            ];
        }
        
        // Query posts
        $args = [
            'post_type' => 'post',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
            'post__not_in' => [get_the_ID()], // Exclude current post
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $query = new \WP_Query($args);
        
        return $query->posts;
    }
}