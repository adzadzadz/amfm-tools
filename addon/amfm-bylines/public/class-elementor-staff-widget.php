<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

class Elementor_Staff_Grid_Widget extends \Elementor\Widget_Base
{

    // Widget Name
    public function get_name()
    {
        return 'amfm_staff_grid';
    }

    // Widget Title
    public function get_title()
    {
        return __('AMFM Staff Grid', 'amfm-bylines');
    }

    // Widget Icon
    public function get_icon()
    {
        return 'eicon-posts-grid';
    }

    // Widget Categories
    public function get_categories()
    {
        return ['general'];
    }

    // Get Staff Options
    private function get_staff_options()
    {
        $staff_options = [];
        $staff_query = new WP_Query([
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

    // Get Region Options
    private function get_region_options()
    {
        $region_options = ['all' => __('All Regions', 'amfm-bylines')];
        $staff_query = new WP_Query([
            'post_type' => 'staff',
            'posts_per_page' => -1,
            'meta_key' => 'region',
            'meta_compare' => 'EXISTS'
        ]);

        if ($staff_query->have_posts()) {
            $regions = [];
            while ($staff_query->have_posts()) {
                $staff_query->the_post();
                $region = get_field('region', get_the_ID());
                if ($region) {
                    // Normalize the region data (lowercase and trim)
                    $normalized_region = strtolower(trim($region));
                    if (!in_array($normalized_region, $regions)) {
                        $regions[] = $normalized_region;
                        // Use the original region as the label and normalized as the value
                        $region_options[$normalized_region] = $region;
                    }
                }
            }
            wp_reset_postdata();
        }

        return $region_options;
    }

    // Register Controls
    protected function _register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'amfm-bylines'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label'   => __('Posts Per Page', 'amfm-bylines'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => __('Columns (Desktop)', 'amfm-bylines'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => __('1 Column', 'amfm-bylines'),
                    '2' => __('2 Columns', 'amfm-bylines'),
                    '3' => __('3 Columns', 'amfm-bylines'),
                    '4' => __('4 Columns', 'amfm-bylines'),
                    '5' => __('5 Columns', 'amfm-bylines'),
                    '6' => __('6 Columns', 'amfm-bylines'),
                ],
            ]
        );

        $this->add_control(
            'columns_tablet',
            [
                'label'   => __('Columns (Tablet)', 'amfm-bylines'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '2',
                'options' => [
                    '1' => __('1 Column', 'amfm-bylines'),
                    '2' => __('2 Columns', 'amfm-bylines'),
                    '3' => __('3 Columns', 'amfm-bylines'),
                    '4' => __('4 Columns', 'amfm-bylines'),
                    '5' => __('5 Columns', 'amfm-bylines'),
                    '6' => __('6 Columns', 'amfm-bylines'),
                ],
            ]
        );

        $this->add_control(
            'columns_mobile',
            [
                'label'   => __('Columns (Mobile)', 'amfm-bylines'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '1',
                'options' => [
                    '1' => __('1 Column', 'amfm-bylines'),
                    '2' => __('2 Columns', 'amfm-bylines'),
                    '3' => __('3 Columns', 'amfm-bylines'),
                    '4' => __('4 Columns', 'amfm-bylines'),
                    '5' => __('5 Columns', 'amfm-bylines'),
                    '6' => __('6 Columns', 'amfm-bylines'),
                ],
            ]
        );

        $this->add_control(
            'grid_gap',
            [
                'label'   => __('Grid Gap (px)', 'amfm-bylines'),
                'type'    => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'   => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'default' => ['size' => 20],
            ]
        );

        $this->add_control(
            'item_max_width',
            [
                'label' => __('Item Max Width', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 1500,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 500,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-item' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Image Size', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => $this->get_image_sizes(),
            ]
        );

        $this->add_control(
            'hide_if_no_image',
            [
                'label' => __('Hide if No Featured Image', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-bylines'),
                'label_off' => __('No', 'amfm-bylines'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'fallback_image',
            [
                'label'   => __('Fallback Image', 'amfm-bylines'),
                'type'    => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'hide_if_no_image!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'image_width',
            [
                'label' => __('Image Width', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 1000,
                        'step' => 10,
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
                    '{{WRAPPER}} .amfm-staff-image img' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_max_width',
            [
                'label' => __('Image Max Width', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 1500,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 500,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-image img' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'show_credentials',
            [
                'label' => __('Show Credentials', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-bylines'),
                'label_off' => __('Hide', 'amfm-bylines'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'query',
            [
                'label' => __('Query', 'amfm-bylines'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Query by staff select field
        $this->add_control(
            'query_by_staff',
            [
                'label' => __('Query by Staff', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'all' => __('All Staff', 'amfm-bylines'),
                    'selected' => __('Selected Staff', 'amfm-bylines'),
                ],
                'default' => 'all',
            ]
        );

        // Staff select field
        $this->add_control(
            'query_staff',
            [
                'label' => __('Staff', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_staff_options(),
                'multiple' => true,
                'label_block' => true,
                'condition' => [
                    'query_by_staff' => 'selected',
                ],
                'sortable' => true, // Enable sorting
            ]
        );

        // Region filter field
        $this->add_control(
            'filter_by_region',
            [
                'label' => __('Filter by Region', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_region_options(),
                'default' => 'all',
                'label_block' => true,
            ]
        );

        $this->end_controls_section();

        // Start Text Section
        $this->start_controls_section(
            'text_section',
            [
                'label' => __('Text', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Start Tabs
        $this->start_controls_tabs('text_tabs');

        // Title Tab
        $this->start_controls_tab(
            'title_tab',
            [
                'label' => __('Title', 'amfm-bylines'),
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-staff-name',
            ]
        );

        $this->add_control(
            'title_alignment',
            [
                'label' => __('Title Alignment', 'amfm-bylines'),
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
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-name' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Meta Tab
        $this->start_controls_tab(
            'meta_tab',
            [
                'label' => __('Meta', 'amfm-bylines'),
            ]
        );

        $this->add_control(
            'meta_color',
            [
                'label' => __('Meta Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'meta_typography',
                'label' => __('Meta Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-staff-meta',
            ]
        );

        $this->add_control(
            'meta_alignment',
            [
                'label' => __('Meta Alignment', 'amfm-bylines'),
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
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-meta' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Credentials Tab
        $this->start_controls_tab(
            'credentials_tab',
            [
                'label' => __('Credentials', 'amfm-bylines'),
            ]
        );

        $this->add_control(
            'credentials_color',
            [
                'label' => __('Credentials Color', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-credentials' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'credentials_typography',
                'label' => __('Credentials Typography', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-staff-credentials',
            ]
        );

        $this->add_control(
            'credentials_alignment',
            [
                'label' => __('Credentials Alignment', 'amfm-bylines'),
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
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-credentials' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'credentials_spacing',
            [
                'label' => __('Credentials Spacing', 'amfm-bylines'),
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
                    'size' => 5,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-credentials' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // End Tabs
        $this->end_controls_tabs();

        // End Text Section
        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'amfm-bylines'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Image Styles
        $this->add_control(
            'image_border_radius',
            [
                'label' => __('Image Border Radius', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'label' => __('Image Border', 'amfm-bylines'),
                'selector' => '{{WRAPPER}} .amfm-staff-image img',
            ]
        );

        // Staff Item Styles
        $this->add_control(
            'item_padding',
            [
                'label' => __('Item Padding', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'item_margin',
            [
                'label' => __('Item Margin', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-staff-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function get_image_sizes()
    {
        global $_wp_additional_image_sizes;

        $sizes = [];
        foreach (get_intermediate_image_sizes() as $_size) {
            if (in_array($_size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
                $sizes[$_size] = ucwords($_size);
            } elseif (isset($_wp_additional_image_sizes[$_size])) {
                $sizes[$_size] = ucwords(str_replace('_', ' ', $_size)) . ' (' . $_wp_additional_image_sizes[$_size]['width'] . 'x' . $_wp_additional_image_sizes[$_size]['height'] . ')';
            }
        }

        return $sizes;
    }

    // Render Output
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $posts_per_page = $settings['posts_per_page'];
        $columns = $settings['columns'];
        $columns_tablet = $settings['columns_tablet'];
        $columns_mobile = $settings['columns_mobile'];
        $grid_gap = isset($settings['grid_gap']['size']) ? $settings['grid_gap']['size'] : 20;
        $image_size = $settings['image_size'];
        $fallback_image = isset($settings['fallback_image']['url']) ? $settings['fallback_image']['url'] : '';
        $query_by_staff = $settings['query_by_staff'];
        $query_staff = $settings['query_staff'];
        $filter_by_region = $settings['filter_by_region'];

        // Determine the query arguments based on the selected staff option
        $query_args = [
            'post_type'      => 'staff',
            'posts_per_page' => $posts_per_page,
        ];

        // Prepare base meta_query for region filtering
        $base_meta_query = [];
        if ($filter_by_region && $filter_by_region !== 'all') {
            $base_meta_query[] = [
                'key'     => 'region',
                'compare' => 'EXISTS'
            ];
        }

        if ($query_by_staff === 'selected' && !empty($query_staff)) {
            $query_args['post__in'] = $query_staff;
            $query_args['orderby'] = 'post__in'; // Maintain the order of selected staff
            
            // Add region filter to selected staff query
            if (!empty($base_meta_query)) {
                $query_args['meta_query'] = $base_meta_query;
            }

            $query_selected_staff = new WP_Query($query_args);

            $unique_posts = $query_selected_staff->posts;
        } else {
            // Build meta_query for first query (amfm_sort > 0)
            $query1_meta = [
                [
                    'key'     => 'amfm_sort',
                    'value'   => '0',
                    'compare' => '>',
                ]
            ];
            if (!empty($base_meta_query)) {
                $query1_meta = array_merge($base_meta_query, $query1_meta);
                if (count($query1_meta) > 1) {
                    $query1_meta['relation'] = 'AND';
                }
            }

            // First query: Fetch staff posts with amfm_sort > 0
            $query1 = new WP_Query(array_merge($query_args, [
                'orderby'        => 'meta_value_num',
                'meta_key'       => 'amfm_sort', // The ACF field to sort by
                'order'          => 'ASC', // Descending order to get higher amfm_sort values first
                'meta_query'     => $query1_meta,
            ]));

            // Build meta_query for second query (amfm_sort = 0 or null)
            $query2_meta = [
                'relation' => 'OR',
                [
                    'key'     => 'amfm_sort',
                    'value'   => '0',
                    'compare' => '=',
                ],
                [
                    'key'     => 'amfm_sort',
                    'compare' => 'NOT EXISTS', // To get posts where amfm_sort is null
                ],
                [
                    'key'     => 'amfm_hide',
                    'compare' => 'NOT EXISTS',
                ]
            ];
            if (!empty($base_meta_query)) {
                $query2_meta = [
                    'relation' => 'AND',
                    $base_meta_query[0], // Region filter
                    $query2_meta // The sorting conditions
                ];
            }

            // Second query: Fetch staff posts where amfm_sort is 0 or null
            $query2 = new WP_Query(array_merge($query_args, [
                'orderby'        => 'ID',
                'order'          => 'ASC', // Ascending order so these appear last
                'meta_query'     => $query2_meta,
            ]));

            // Merge the results from both queries and remove duplicates
            $merged_posts = array_merge($query1->posts, $query2->posts);
            $unique_posts = [];
            $post_ids = [];

            foreach ($merged_posts as $post) {
                if (!in_array($post->ID, $post_ids)) {
                    $unique_posts[] = $post;
                    $post_ids[] = $post->ID;
                }
            }
        }

        // Generate CSS for responsive breakpoints
        $custom_css = "
            <style>
                .amfm-staff-grid {
                    display: grid;
                    grid-template-columns: repeat(" . esc_attr($columns) . ", 1fr);
                    gap: " . esc_attr($grid_gap) . "px;
                }
                @media (max-width: 1024px) {
                    .amfm-staff-grid {
                        grid-template-columns: repeat(" . esc_attr($columns_tablet) . ", 1fr);
                    }
                    .amfm-staff-item {
                        padding: 10px;
                    }
                }
                @media (max-width: 768px) {
                    .amfm-staff-grid {
                        grid-template-columns: repeat(" . esc_attr($columns_mobile) . ", 1fr);
                    }
                    .amfm-staff-item {
                        padding: 5px;
                    }
                }
            </style>
        ";

        echo $custom_css;

        // Start rendering the grid
        echo '<div class="amfm-staff-grid">';

        if (!empty($unique_posts)) {
            foreach ($unique_posts as $post) {
                $post_id = $post->ID;
                $link = get_permalink($post); // Correct the permalink

                $amfm_hide = get_field('amfm_hide', $post_id);

                // Skip if amfm_hide is true
                if ($amfm_hide && $amfm_hide === true) {
                    continue; // Skip if amfm_hide is true
                }

                // Skip if hide_if_no_image is enabled and there is no featured image
                if ('yes' === $settings['hide_if_no_image'] && !has_post_thumbnail($post_id)) {
                    continue;
                }

                // Filter by region with data normalization
                if ($filter_by_region && $filter_by_region !== 'all') {
                    $post_region = get_field('region', $post_id);
                    if ($post_region) {
                        $normalized_post_region = strtolower(trim($post_region));
                        if ($normalized_post_region !== $filter_by_region) {
                            continue; // Skip if region doesn't match
                        }
                    } else {
                        continue; // Skip if no region field
                    }
                }

                echo '<div class="amfm-staff-item">';
                echo '<a href="' . esc_url($link) . '">';

                // Display the staff's thumbnail or fallback image
                if (has_post_thumbnail($post_id)) {
                    echo '<div class="amfm-staff-image">' . get_the_post_thumbnail($post_id, $image_size) . '</div>';
                } elseif ($fallback_image) {
                    echo '<div class="amfm-staff-image"><img src="' . esc_url($fallback_image) . '" alt="' . esc_attr(get_the_title($post_id)) . '"></div>';
                }

                // Display the staff's name
                echo '<h3 class="amfm-staff-name">' . get_the_title($post_id) . '</h3>';

                // Get custom fields: title and region
                $title = get_field('title', $post_id);
                $region = get_field('region', $post_id);
                $credentials = get_field('credential_name', $post_id);

                // Display credentials if they exist and setting is enabled
                if ($credentials && $settings['show_credentials'] === 'yes') {
                    echo '<div class="amfm-staff-credentials">' . esc_html($credentials) . '</div>';
                }

                // Display custom fields if they exist
                if ($title || $region) {
                    echo '<div class="amfm-staff-meta">' . esc_html($title) . ', ' . esc_html($region) . '</div>';
                }

                echo '</a>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('No staff members found.', 'amfm-bylines') . '</p>';
        }

        echo '</div>';
        wp_reset_postdata();
    }
}

// Register Widget
\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor_Staff_Grid_Widget());
