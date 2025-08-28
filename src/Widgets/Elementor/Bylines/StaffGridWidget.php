<?php

namespace App\Widgets\Elementor\Bylines;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Image_Size;

class StaffGridWidget extends Widget_Base
{
    public function get_name()
    {
        return 'amfm_staff_grid';
    }

    public function get_title()
    {
        return __('Staff Grid', 'amfm-tools');
    }

    public function get_icon()
    {
        return 'eicon-posts-grid';
    }

    public function get_categories()
    {
        return ['amfm-tools'];
    }

    public function get_keywords()
    {
        return ['staff', 'grid', 'team', 'directory', 'people'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Query Settings', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'query_by_staff',
            [
                'label' => __('Query Type', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => __('All Staff', 'amfm-tools'),
                    'selected' => __('Selected Staff', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'query_staff',
            [
                'label' => __('Select Staff', 'amfm-tools'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_staff_options(),
                'condition' => [
                    'query_by_staff' => 'selected',
                ],
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Number of Staff', 'amfm-tools'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => -1,
                'max' => 50,
            ]
        );

        $this->add_control(
            'filter_by_region',
            [
                'label' => __('Filter by Region', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => __('All Regions', 'amfm-tools'),
                    'north' => __('North', 'amfm-tools'),
                    'south' => __('South', 'amfm-tools'),
                    'east' => __('East', 'amfm-tools'),
                    'west' => __('West', 'amfm-tools'),
                    'central' => __('Central', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'menu_order',
                'options' => [
                    'menu_order' => __('Menu Order', 'amfm-tools'),
                    'title' => __('Name', 'amfm-tools'),
                    'date' => __('Date', 'amfm-tools'),
                    'meta_value_num' => __('Sort Order (amfm_sort)', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => __('Order Direction', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => [
                    'ASC' => __('Ascending', 'amfm-tools'),
                    'DESC' => __('Descending', 'amfm-tools'),
                ],
            ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
            'layout_section',
            [
                'label' => __('Layout', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Columns', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => __('Grid Gap', 'amfm-tools'),
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
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
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
            'fallback_image',
            [
                'label' => __('Fallback Image', 'amfm-tools'),
                'type' => Controls_Manager::MEDIA,
                'description' => __('Default image when staff has no profile picture', 'amfm-tools'),
            ]
        );

        $this->end_controls_section();

        // Display Options Section
        $this->start_controls_section(
            'display_section',
            [
                'label' => __('Display Options', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_name',
            [
                'label' => __('Show Name', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_credentials',
            [
                'label' => __('Show Credentials', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
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
            'show_bio',
            [
                'label' => __('Show Bio Excerpt', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'bio_length',
            [
                'label' => __('Bio Length (words)', 'amfm-tools'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'min' => 5,
                'max' => 100,
                'condition' => [
                    'show_bio' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'enable_links',
            [
                'label' => __('Link to Staff Pages', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-tools'),
                'label_off' => __('No', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section - Cards
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => __('Card Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_background',
            [
                'label' => __('Background Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_padding',
            [
                'label' => __('Padding', 'amfm-tools'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 15,
                    'right' => 15,
                    'bottom' => 15,
                    'left' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Typography
        $this->start_controls_section(
            'typography_style_section',
            [
                'label' => __('Typography', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => __('Name Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .amfm-staff-name a' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_name' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'label' => __('Name Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-staff-name',
                'condition' => [
                    'show_name' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        
        // Query arguments
        $query_args = [
            'post_type' => 'staff',
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
        ];

        // Add meta_key for sort order
        if ($settings['orderby'] === 'meta_value_num') {
            $query_args['meta_key'] = 'amfm_sort';
        }

        // Selected staff filter
        if ($settings['query_by_staff'] === 'selected' && !empty($settings['query_staff'])) {
            $query_args['post__in'] = $settings['query_staff'];
        }

        // Region filter
        if ($settings['filter_by_region'] && $settings['filter_by_region'] !== 'all') {
            $query_args['meta_query'] = [
                [
                    'key' => 'region',
                    'value' => $settings['filter_by_region'],
                    'compare' => 'LIKE'
                ]
            ];
        }

        $staff_query = new \WP_Query($query_args);

        if (!$staff_query->have_posts()) {
            echo '<p>' . __('No staff members found.', 'amfm-tools') . '</p>';
            return;
        }

        echo '<div class="amfm-staff-grid">';

        while ($staff_query->have_posts()) {
            $staff_query->the_post();
            $this->render_staff_card($settings);
        }

        echo '</div>';

        wp_reset_postdata();

        // Add basic CSS
        $this->add_widget_styles();
    }

    private function render_staff_card($settings)
    {
        $staff_id = get_the_ID();
        
        echo '<div class="amfm-staff-card">';
        
        // Link wrapper
        if ($settings['enable_links'] === 'yes') {
            echo '<a href="' . get_permalink() . '" class="amfm-staff-link">';
        }

        // Featured image
        echo '<div class="amfm-staff-image">';
        if (has_post_thumbnail()) {
            the_post_thumbnail($settings['image_size']);
        } elseif (!empty($settings['fallback_image']['url'])) {
            echo '<img src="' . esc_url($settings['fallback_image']['url']) . '" alt="' . esc_attr(get_the_title()) . '">';
        }
        echo '</div>';

        echo '<div class="amfm-staff-content">';

        // Name
        if ($settings['show_name'] === 'yes') {
            echo '<h3 class="amfm-staff-name">' . get_the_title() . '</h3>';
        }

        // Credentials
        if ($settings['show_credentials'] === 'yes') {
            $credentials = get_field('credential_name', $staff_id);
            if ($credentials) {
                echo '<div class="amfm-staff-credentials">' . esc_html($credentials) . '</div>';
            }
        }

        // Title
        if ($settings['show_title'] === 'yes') {
            $title = get_field('title', $staff_id);
            if ($title) {
                echo '<div class="amfm-staff-title">' . esc_html($title) . '</div>';
            }
        }

        // Bio excerpt
        if ($settings['show_bio'] === 'yes') {
            $bio = get_field('bio', $staff_id) ?: get_the_excerpt();
            if ($bio) {
                $bio_excerpt = wp_trim_words($bio, $settings['bio_length'], '...');
                echo '<div class="amfm-staff-bio">' . esc_html($bio_excerpt) . '</div>';
            }
        }

        echo '</div>'; // .amfm-staff-content

        if ($settings['enable_links'] === 'yes') {
            echo '</a>';
        }

        echo '</div>'; // .amfm-staff-card
    }

    private function get_staff_options()
    {
        $staff_options = [];
        $staff_query = new \WP_Query([
            'post_type' => 'staff',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        if ($staff_query->have_posts()) {
            while ($staff_query->have_posts()) {
                $staff_query->the_post();
                $staff_options[get_the_ID()] = get_the_title();
            }
            wp_reset_postdata();
        }

        return $staff_options;
    }

    private function add_widget_styles()
    {
        echo '<style>
        .amfm-staff-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .amfm-staff-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .amfm-staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .amfm-staff-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .amfm-staff-image {
            text-align: center;
            overflow: hidden;
        }
        .amfm-staff-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        .amfm-staff-content {
            padding: 15px;
            text-align: center;
        }
        .amfm-staff-name {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: bold;
        }
        .amfm-staff-credentials {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .amfm-staff-title {
            font-size: 14px;
            color: #00997A;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .amfm-staff-bio {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        @media (max-width: 768px) {
            .amfm-staff-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
        }
        @media (max-width: 480px) {
            .amfm-staff-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>';
    }
}