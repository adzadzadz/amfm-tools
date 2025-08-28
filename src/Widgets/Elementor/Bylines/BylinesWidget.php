<?php

namespace App\Widgets\Elementor\Bylines;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class BylinesWidget extends Widget_Base
{
    public function get_name()
    {
        return 'amfm_bylines_display';
    }

    public function get_title()
    {
        return __('Bylines Display', 'amfm-tools');
    }

    public function get_icon()
    {
        return 'eicon-person';
    }

    public function get_categories()
    {
        return ['amfm-tools'];
    }

    public function get_keywords()
    {
        return ['bylines', 'author', 'editor', 'reviewer', 'staff', 'team'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Display Options', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_author',
            [
                'label' => __('Show Author', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'author_label',
            [
                'label' => __('Author Label', 'amfm-tools'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Author:', 'amfm-tools'),
                'condition' => [
                    'show_author' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_editor',
            [
                'label' => __('Show Editor', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'editor_label',
            [
                'label' => __('Editor Label', 'amfm-tools'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Editor:', 'amfm-tools'),
                'condition' => [
                    'show_editor' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_reviewer',
            [
                'label' => __('Show Reviewer', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'reviewer_label',
            [
                'label' => __('Reviewer Label', 'amfm-tools'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Medically Reviewed by:', 'amfm-tools'),
                'condition' => [
                    'show_reviewer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_in_the_press',
            [
                'label' => __('Show In the Press', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'in_the_press_label',
            [
                'label' => __('In the Press Label', 'amfm-tools'),
                'type' => Controls_Manager::TEXT,
                'default' => __('In the Press:', 'amfm-tools'),
                'condition' => [
                    'show_in_the_press' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'amfm-tools'),
                'type' => Controls_Manager::SELECT,
                'default' => 'horizontal',
                'options' => [
                    'horizontal' => __('Horizontal', 'amfm-tools'),
                    'vertical' => __('Vertical', 'amfm-tools'),
                    'grid' => __('Grid', 'amfm-tools'),
                ],
            ]
        );

        $this->add_control(
            'show_images',
            [
                'label' => __('Show Profile Images', 'amfm-tools'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-tools'),
                'label_off' => __('Hide', 'amfm-tools'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Image Size', 'amfm-tools'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 100,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 40,
                ],
                'condition' => [
                    'show_images' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-byline-image img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Container
        $this->start_controls_section(
            'container_style_section',
            [
                'label' => __('Container Style', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_background',
            [
                'label' => __('Background Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .amfm-bylines-container' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'label' => __('Border', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-bylines-container',
            ]
        );

        $this->add_control(
            'container_padding',
            [
                'label' => __('Padding', 'amfm-tools'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-bylines-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'label_color',
            [
                'label' => __('Label Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-byline-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'label' => __('Label Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-byline-label',
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => __('Name Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .amfm-byline-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .amfm-byline-name a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'label' => __('Name Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-byline-name',
            ]
        );

        $this->add_control(
            'credentials_color',
            [
                'label' => __('Credentials Color', 'amfm-tools'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .amfm-byline-credentials' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'credentials_typography',
                'label' => __('Credentials Typography', 'amfm-tools'),
                'selector' => '{{WRAPPER}} .amfm-byline-credentials',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        
        // Get bylines data
        $bylines = [
            'author' => $this->fetch_user_info('author'),
            'editor' => $this->fetch_user_info('editor'),
            'reviewer' => $this->fetch_user_info('reviewedBy'),
            'inThePress' => $this->fetch_user_info('inThePress'),
        ];

        $layout_class = 'amfm-layout-' . $settings['layout'];
        
        echo '<div class="amfm-bylines-container ' . esc_attr($layout_class) . '">';

        // Author
        if ($settings['show_author'] === 'yes' && $bylines['author']) {
            $this->render_byline_item('author', $bylines['author'], $settings['author_label'], $settings);
        }

        // Editor
        if ($settings['show_editor'] === 'yes' && $bylines['editor']) {
            $this->render_byline_item('editor', $bylines['editor'], $settings['editor_label'], $settings);
        }

        // Reviewer
        if ($settings['show_reviewer'] === 'yes' && $bylines['reviewer']) {
            $this->render_byline_item('reviewer', $bylines['reviewer'], $settings['reviewer_label'], $settings);
        }

        // In the Press
        if ($settings['show_in_the_press'] === 'yes' && $bylines['inThePress']) {
            $this->render_byline_item('in-the-press', $bylines['inThePress'], $settings['in_the_press_label'], $settings);
        }

        echo '</div>';

        // Add basic CSS
        $this->add_widget_styles();
    }

    private function render_byline_item($type, $byline, $label, $settings)
    {
        if (!$byline) return;

        echo '<div class="amfm-byline-item amfm-byline-' . esc_attr($type) . '">';
        
        if (!empty($label)) {
            echo '<div class="amfm-byline-label">' . esc_html($label) . '</div>';
        }

        echo '<div class="amfm-byline-content">';

        // Profile image
        if ($settings['show_images'] === 'yes' && !empty($byline['img'])) {
            echo '<div class="amfm-byline-image">' . $byline['img'] . '</div>';
        }

        echo '<div class="amfm-byline-info">';

        // Name with optional link
        if (!empty($byline['name'])) {
            echo '<div class="amfm-byline-name">';
            if (!empty($byline['url'])) {
                echo '<a href="' . esc_url($byline['url']) . '">' . esc_html($byline['name']) . '</a>';
            } else {
                echo esc_html($byline['name']);
            }
            echo '</div>';
        }

        // Credentials
        if (!empty($byline['credentials'])) {
            echo '<div class="amfm-byline-credentials">' . esc_html($byline['credentials']) . '</div>';
        }

        // Title
        if (!empty($byline['title'])) {
            echo '<div class="amfm-byline-title">' . esc_html($byline['title']) . '</div>';
        }

        echo '</div>'; // .amfm-byline-info
        echo '</div>'; // .amfm-byline-content
        echo '</div>'; // .amfm-byline-item
    }

    private function fetch_user_info($type)
    {
        // Use the existing PublicBylinesController logic
        if (class_exists('App\\Controllers\\PublicBylinesController')) {
            $controller = new \App\Controllers\PublicBylinesController();
            return $controller->getBylineData($type);
        }

        // Fallback: simplified version
        global $post;
        if (!$post) return null;

        $field_mappings = [
            'author' => 'amfm_author',
            'editor' => 'amfm_editor', 
            'reviewedBy' => 'amfm_reviewer',
            'inThePress' => 'amfm_in_the_press'
        ];

        $field_name = $field_mappings[$type] ?? null;
        if (!$field_name || !function_exists('get_field')) return null;

        $user_id = get_field($field_name, $post->ID);
        if (!$user_id) return null;

        $user_data = get_userdata($user_id);
        if (!$user_data) return null;

        $staff_post = get_posts([
            'post_type' => 'staff',
            'meta_key' => 'user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);

        $staff_id = $staff_post ? $staff_post[0]->ID : null;
        $image = '';
        $credentials = '';
        $title = '';

        if ($staff_id) {
            $image = get_the_post_thumbnail($staff_id, [40, 40], ['class' => 'amfm-staff-image']);
            $credentials = get_field('credential_name', $staff_id) ?: '';
            $title = get_field('title', $staff_id) ?: '';
        }

        return [
            'name' => $user_data->display_name,
            'img' => $image,
            'credentials' => $credentials,
            'title' => $title,
            'url' => $staff_id ? get_permalink($staff_id) : '',
        ];
    }

    private function add_widget_styles()
    {
        echo '<style>
        .amfm-bylines-container {
            display: flex;
            gap: 20px;
            align-items: center;
            background: #fff;
            padding: 15px;
        }
        .amfm-layout-vertical .amfm-bylines-container {
            flex-direction: column;
            align-items: flex-start;
        }
        .amfm-layout-grid .amfm-bylines-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .amfm-byline-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .amfm-layout-vertical .amfm-byline-item {
            width: 100%;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .amfm-byline-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .amfm-layout-vertical .amfm-byline-content {
            flex: 1;
        }
        .amfm-byline-image img {
            border-radius: 50%;
            object-fit: cover;
        }
        .amfm-byline-label {
            font-weight: bold;
            font-size: 12px;
            white-space: nowrap;
        }
        .amfm-byline-name {
            font-weight: bold;
            font-size: 14px;
        }
        .amfm-byline-name a {
            text-decoration: none;
        }
        .amfm-byline-name a:hover {
            text-decoration: underline;
        }
        .amfm-byline-credentials {
            font-size: 12px;
        }
        .amfm-byline-title {
            font-size: 12px;
            color: #00997A;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .amfm-bylines-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        </style>';
    }
}