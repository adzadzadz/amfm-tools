<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Related_Posts_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'amfm_related_posts';
    }

    public function get_title() {
        return __( 'AMFM Related Posts', 'amfm-tools' );
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return [ 'amfm-widgets' ];
    }

    public function get_keywords() {
        return [ 'related', 'posts', 'keywords', 'acf' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'amfm-tools' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'keyword_source',
            [
                'label' => __( 'Keyword Source', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'amfm_keywords',
                'options' => [
                    'amfm_keywords' => __( 'AMFM Keywords', 'amfm-tools' ),
                    'amfm_other_keywords' => __( 'AMFM Other Keywords', 'amfm-tools' ),
                    'both' => __( 'Both Fields', 'amfm-tools' ),
                ],
            ]
        );

        $this->add_control(
            'posts_count',
            [
                'label' => __( 'Number of Posts', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 12,
            ]
        );

        $this->add_control(
            'mobile_count',
            [
                'label' => __( 'Mobile Posts Count', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'max' => 6,
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __( 'Show Section Title', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Show', 'amfm-tools' ),
                'label_off' => __( 'Hide', 'amfm-tools' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label' => __( 'Section Title', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Related Articles', 'amfm-tools' ),
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => __( 'Layout', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __( 'Vertical Cards', 'amfm-tools' ),
                    'horizontal' => __( 'Horizontal List', 'amfm-tools' ),
                ],
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => __( 'Columns', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
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
                    '{{WRAPPER}} .amfm-layout-grid .amfm-related-posts-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_responsive_control(
            'horizontal_columns',
            [
                'label' => __( 'Columns', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '2',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'layout' => 'horizontal',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-layout-horizontal .amfm-related-posts-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_control(
            'excluded_pages',
            [
                'label' => __( 'Exclude Pages/Posts', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_all_posts_and_pages(),
                'description' => __( 'Select specific pages or posts to exclude from related posts results.', 'amfm-tools' ),
            ]
        );

        $this->add_control(
            'excluded_with_children',
            [
                'label' => __( 'Exclude Pages/Posts with Children', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_all_posts_and_pages(),
                'description' => __( 'Select pages or posts to exclude along with all their children from related posts results.', 'amfm-tools' ),
            ]
        );

        $this->add_control(
            'mobile_layout',
            [
                'label' => __( 'Mobile Layout', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '1',
                'options' => [
                    '1' => __( 'Full Width (1 Column)', 'amfm-tools' ),
                    '2' => __( '2 Columns', 'amfm-tools' ),
                    'inherit' => __( 'Same as Desktop', 'amfm-tools' ),
                ],
                'prefix_class' => 'amfm-mobile-cols-',
                'description' => __( 'Choose how cards display on mobile devices.', 'amfm-tools' ),
            ]
        );

        $this->end_controls_section();

        // Grid Layout Controls
        $this->start_controls_section(
            'grid_layout_section',
            [
                'label' => __( 'Grid Layout', 'amfm-tools' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => __( 'Grid Gap', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_margin',
            [
                'label' => __( 'Grid Margin', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_padding',
            [
                'label' => __( 'Grid Padding', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_min_height',
            [
                'label' => __( 'Minimum Grid Height', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                    'vh' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'grid_alignment',
            [
                'label' => __( 'Grid Alignment', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'start',
                'options' => [
                    'start' => __( 'Start', 'amfm-tools' ),
                    'center' => __( 'Center', 'amfm-tools' ),
                    'end' => __( 'End', 'amfm-tools' ),
                    'stretch' => __( 'Stretch', 'amfm-tools' ),
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'align-items: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'grid_justify',
            [
                'label' => __( 'Grid Justify', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'start',
                'options' => [
                    'start' => __( 'Start', 'amfm-tools' ),
                    'center' => __( 'Center', 'amfm-tools' ),
                    'end' => __( 'End', 'amfm-tools' ),
                    'space-between' => __( 'Space Between', 'amfm-tools' ),
                    'space-around' => __( 'Space Around', 'amfm-tools' ),
                    'space-evenly' => __( 'Space Evenly', 'amfm-tools' ),
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-posts-grid' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Card Controls
        $this->start_controls_section(
            'card_section',
            [
                'label' => __( 'Cards', 'amfm-tools' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'card_height',
            [
                'label' => __( 'Card Height', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 80,
                    ],
                    'em' => [
                        'min' => 5,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_min_height',
            [
                'label' => __( 'Card Minimum Height', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 600,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 60,
                    ],
                    'em' => [
                        'min' => 5,
                        'max' => 40,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_margin',
            [
                'label' => __( 'Card Margin', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Controls
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Card Style', 'amfm-tools' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_background',
            [
                'label' => __( 'Card Background', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .amfm-related-post-card',
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => __( 'Border Radius', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label' => __( 'Card Padding', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label' => __( 'Content Padding', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em' ],
                'default' => [
                    'top' => 15,
                    'right' => 15,
                    'bottom' => 15,
                    'left' => 15,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'label' => __( 'Card Shadow', 'amfm-tools' ),
                'selector' => '{{WRAPPER}} .amfm-related-post-card',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_hover_shadow',
                'label' => __( 'Card Hover Shadow', 'amfm-tools' ),
                'selector' => '{{WRAPPER}} .amfm-related-post-card:hover',
            ]
        );

        $this->end_controls_section();

        // Typography Controls
        $this->start_controls_section(
            'typography_section',
            [
                'label' => __( 'Typography', 'amfm-tools' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __( 'Title Typography', 'amfm-tools' ),
                'selector' => '{{WRAPPER}} .amfm-post-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __( 'Title Color', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label' => __( 'Title Hover Color', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'title_margin',
            [
                'label' => __( 'Title Margin', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Image Controls
        $this->start_controls_section(
            'image_section',
            [
                'label' => __( 'Images', 'amfm-tools' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => __( 'Image Border Radius', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'label' => __( 'Image Border', 'amfm-tools' ),
                'selector' => '{{WRAPPER}} .amfm-post-image img',
            ]
        );

        $this->add_control(
            'image_opacity',
            [
                'label' => __( 'Image Opacity', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0.1,
                        'max' => 1,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-post-image img' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'image_hover_opacity',
            [
                'label' => __( 'Image Hover Opacity', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0.1,
                        'max' => 1,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 0.8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-related-post-card:hover .amfm-post-image img' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'horizontal_image_width',
            [
                'label' => __( 'Image Width (Horizontal Layout)', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 300,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 70,
                    ],
                ],
                'default' => [
                    'size' => 80,
                    'unit' => 'px',
                ],
                'condition' => [
                    'layout' => 'horizontal',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-layout-horizontal .amfm-post-image' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'horizontal_image_height',
            [
                'label' => __( 'Image Height (Horizontal Layout)', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 300,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 200,
                    ],
                ],
                'default' => [
                    'size' => 80,
                    'unit' => 'px',
                ],
                'condition' => [
                    'layout' => 'horizontal',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-layout-horizontal .amfm-post-image' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'horizontal_image_ratio_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __( '<strong>Tip:</strong> Use percentage width (e.g., 30%) for responsive image-to-text ratios, or fixed pixels (e.g., 100px) for consistent sizing.', 'amfm-tools' ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'layout' => 'horizontal',
                ],
            ]
        );

        $this->add_control(
            'mobile_horizontal_image_width',
            [
                'label' => __( 'Mobile Image Width (Horizontal Layout)', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 150,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 60,
                    'unit' => 'px',
                ],
                'condition' => [
                    'layout' => 'horizontal',
                ],
                'selectors' => [
                    '(mobile){{WRAPPER}} .amfm-layout-horizontal .amfm-post-image' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_horizontal_image_height',
            [
                'label' => __( 'Mobile Image Height (Horizontal Layout)', 'amfm-tools' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 150,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 150,
                    ],
                ],
                'default' => [
                    'size' => 60,
                    'unit' => 'px',
                ],
                'condition' => [
                    'layout' => 'horizontal',
                ],
                'selectors' => [
                    '(mobile){{WRAPPER}} .amfm-layout-horizontal .amfm-post-image' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $related_posts = $this->get_related_posts( $settings );

        if ( empty( $related_posts ) ) {
            return;
        }

        $layout_class = $settings['layout'] === 'horizontal' ? 'amfm-layout-horizontal' : 'amfm-layout-grid';
        ?>
        <div class="amfm-related-posts-wrapper <?php echo esc_attr( $layout_class ); ?>">
            <?php if ( $settings['show_title'] === 'yes' && ! empty( $settings['section_title'] ) ) : ?>
                <h3 class="amfm-related-posts-title"><?php echo esc_html( $settings['section_title'] ); ?></h3>
            <?php endif; ?>
            
            <div class="amfm-related-posts-grid" data-mobile-count="<?php echo esc_attr( $settings['mobile_count'] ); ?>">
                <?php foreach ( $related_posts as $index => $post ) : ?>
                    <div class="amfm-related-post-card <?php echo $index >= $settings['mobile_count'] ? 'amfm-hidden-mobile' : ''; ?>">
                        <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
                            <div class="amfm-post-image">
                                <a href="<?php echo get_permalink( $post->ID ); ?>">
                                    <?php echo get_the_post_thumbnail( $post->ID, $settings['layout'] === 'horizontal' ? 'thumbnail' : 'medium', [ 'loading' => 'lazy' ] ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="amfm-post-content">
                            <h4 class="amfm-post-title">
                                <a href="<?php echo get_permalink( $post->ID ); ?>">
                                    <?php echo get_the_title( $post->ID ); ?>
                                </a>
                            </h4>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
        .amfm-related-posts-wrapper {
            margin: 20px 0;
        }
        
        .amfm-related-posts-title {
            margin-bottom: 20px;
            font-size: 1.5em;
            font-weight: bold;
        }
        
        /* Grid Layout (Default - Vertical Cards) */
        .amfm-layout-grid .amfm-related-posts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-auto-rows: 1fr;
            gap: 20px;
        }
        
        .amfm-layout-grid .amfm-related-post-card {
            background: #fff;
            border: none;
            border-radius: 0px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .amfm-layout-grid .amfm-related-post-card:hover {
            transform: translateY(-5px);
        }
        
        .amfm-layout-grid .amfm-post-image img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .amfm-layout-grid .amfm-post-content {
            padding: 15px;
            flex-grow: 1;
        }
        
        /* Horizontal Layout */
        .amfm-layout-horizontal .amfm-related-posts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: 1fr;
            gap: 20px;
        }
        
        .amfm-layout-horizontal .amfm-related-post-card {
            background: #fff;
            border: none;
            border-radius: 0px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: row;
            align-items: center;
            height: 100%;
        }
        
        .amfm-layout-horizontal .amfm-related-post-card:hover {
            transform: translateX(5px);
        }
        
        .amfm-layout-horizontal .amfm-post-image {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
        }
        
        .amfm-layout-horizontal .amfm-post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .amfm-layout-horizontal .amfm-post-content {
            padding: 15px;
            flex-grow: 1;
        }
        
        /* Common Styles */
        .amfm-post-title {
            margin: 0;
            font-size: 1.1em;
            line-height: 1.4;
        }
        
        .amfm-post-title a {
            text-decoration: none;
            color: inherit;
        }
        
        .amfm-post-title a:hover {
            color: #0073aa;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .amfm-related-posts-grid .amfm-hidden-mobile {
                display: none;
            }
            
            /* Default: Full width single column on mobile */
            .amfm-mobile-cols-1 .amfm-related-posts-grid {
                grid-template-columns: 1fr !important;
                width: 100%;
            }
            
            /* 2 columns on mobile */
            .amfm-mobile-cols-2 .amfm-related-posts-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                width: 100%;
            }
            
            /* Inherit desktop layout - no override */
            .amfm-mobile-cols-inherit .amfm-related-posts-grid {
                /* Desktop settings will apply */
            }
            
            /* Ensure cards fill available width */
            .amfm-related-post-card {
                width: 100%;
            }
            
            /* Horizontal layout maintains horizontal card design on mobile */
            .amfm-layout-horizontal .amfm-related-post-card {
                flex-direction: row;
                align-items: center;
            }
            
            /* Adjust image size for mobile but keep horizontal layout */
            .amfm-layout-horizontal .amfm-post-image {
                flex-shrink: 0;
            }
            
            .amfm-layout-horizontal .amfm-post-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            /* Ensure text content takes remaining space */
            .amfm-layout-horizontal .amfm-post-content {
                flex-grow: 1;
                padding: 10px;
            }
            
            /* Smaller font size for mobile */
            .amfm-layout-horizontal .amfm-post-title {
                font-size: 0.9em;
            }
        }
        </style>
        <?php
    }

    private function get_related_posts( $settings ) {
        global $post;
        
        if ( ! $post ) {
            return [];
        }

        $current_keywords = $this->get_current_post_keywords( $settings['keyword_source'] );
        
        if ( empty( $current_keywords ) ) {
            return [];
        }

        $related_posts = [];
        $posts_count = intval( $settings['posts_count'] );

        // Priority 1: Posts with exact same keyword values
        $exact_match_posts = $this->query_posts_by_keywords( $current_keywords, 'exact', $posts_count, [], $settings );
        $related_posts = array_merge( $related_posts, $exact_match_posts );

        // Priority 2: Posts with at least one matching keyword
        if ( count( $related_posts ) < $posts_count ) {
            $remaining = $posts_count - count( $related_posts );
            $existing_ids = wp_list_pluck( $related_posts, 'ID' );
            $partial_match_posts = $this->query_posts_by_keywords( $current_keywords, 'partial', $remaining, $existing_ids, $settings );
            $related_posts = array_merge( $related_posts, $partial_match_posts );
        }

        // Priority 3: Posts with same parent
        if ( count( $related_posts ) < $posts_count && $post->post_parent ) {
            $remaining = $posts_count - count( $related_posts );
            $existing_ids = wp_list_pluck( $related_posts, 'ID' );
            $sibling_posts = $this->query_posts_by_parent( $post->post_parent, $remaining, $existing_ids, $settings );
            $related_posts = array_merge( $related_posts, $sibling_posts );
        }

        return array_slice( $related_posts, 0, $posts_count );
    }

    private function get_current_post_keywords( $source ) {
        global $post;
        
        $keywords = [];
        
        if ( $source === 'amfm_keywords' || $source === 'both' ) {
            $amfm_keywords = get_field( 'amfm_keywords', $post->ID );
            if ( $amfm_keywords ) {
                $keywords = array_merge( $keywords, array_map( 'trim', explode( ',', $amfm_keywords ) ) );
            }
        }
        
        if ( $source === 'amfm_other_keywords' || $source === 'both' ) {
            $other_keywords = get_field( 'amfm_other_keywords', $post->ID );
            if ( $other_keywords ) {
                $keywords = array_merge( $keywords, array_map( 'trim', explode( ',', $other_keywords ) ) );
            }
        }
        
        return array_filter( array_unique( $keywords ) );
    }

    private function query_posts_by_keywords( $keywords, $match_type = 'partial', $limit = 6, $exclude_ids = [], $settings = [] ) {
        global $post;
        
        $meta_query = [ 'relation' => 'OR' ];
        
        foreach ( $keywords as $keyword ) {
            if ( $match_type === 'exact' ) {
                $meta_query[] = [
                    'key' => 'amfm_keywords',
                    'value' => $keyword,
                    'compare' => '='
                ];
                $meta_query[] = [
                    'key' => 'amfm_other_keywords',
                    'value' => $keyword,
                    'compare' => '='
                ];
            } else {
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
        }

        $exclude_ids[] = $post->ID;
        
        // Add excluded pages from settings
        if ( ! empty( $settings['excluded_pages'] ) && is_array( $settings['excluded_pages'] ) ) {
            $exclude_ids = array_merge( $exclude_ids, $settings['excluded_pages'] );
        }
        
        // Add excluded pages with children from settings
        if ( ! empty( $settings['excluded_with_children'] ) && is_array( $settings['excluded_with_children'] ) ) {
            $exclude_ids = array_merge( $exclude_ids, $settings['excluded_with_children'] );
            $children_ids = $this->get_all_children_ids( $settings['excluded_with_children'] );
            $exclude_ids = array_merge( $exclude_ids, $children_ids );
        }

        $query = new WP_Query([
            'post_type' => [ 'post', 'page' ],
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => $exclude_ids,
            'meta_query' => $meta_query,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        return $query->posts;
    }

    private function query_posts_by_parent( $parent_id, $limit = 6, $exclude_ids = [], $settings = [] ) {
        global $post;
        
        $exclude_ids[] = $post->ID;
        
        // Add excluded pages from settings
        if ( ! empty( $settings['excluded_pages'] ) && is_array( $settings['excluded_pages'] ) ) {
            $exclude_ids = array_merge( $exclude_ids, $settings['excluded_pages'] );
        }
        
        // Add excluded pages with children from settings
        if ( ! empty( $settings['excluded_with_children'] ) && is_array( $settings['excluded_with_children'] ) ) {
            $exclude_ids = array_merge( $exclude_ids, $settings['excluded_with_children'] );
            $children_ids = $this->get_all_children_ids( $settings['excluded_with_children'] );
            $exclude_ids = array_merge( $exclude_ids, $children_ids );
        }

        $query = new WP_Query([
            'post_type' => [ 'post', 'page' ],
            'post_status' => 'publish',
            'post_parent' => $parent_id,
            'posts_per_page' => $limit,
            'post__not_in' => $exclude_ids,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        return $query->posts;
    }

    private function get_all_posts_and_pages() {
        $options = [];
        
        // Get all published posts and pages
        $query = new WP_Query([
            'post_type' => [ 'post', 'page' ],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();
                $post_type = get_post_type();
                $post_slug = get_post_field( 'post_name', $post_id );
                
                // Format: "Post Title | slug-name (Post Type)"
                $label = $post_title . ' | ' . $post_slug . ' (' . ucfirst( $post_type ) . ')';
                $options[ $post_id ] = $label;
            }
            wp_reset_postdata();
        }

        return $options;
    }

    private function get_all_children_ids( $parent_ids ) {
        if ( empty( $parent_ids ) || ! is_array( $parent_ids ) ) {
            return [];
        }

        $all_children = [];
        
        foreach ( $parent_ids as $parent_id ) {
            $children = get_children([
                'post_parent' => $parent_id,
                'post_type' => [ 'post', 'page' ],
                'post_status' => 'publish',
                'numberposts' => -1
            ]);
            
            if ( $children ) {
                $child_ids = array_keys( $children );
                $all_children = array_merge( $all_children, $child_ids );
                
                // Recursively get children of children
                $grandchildren = $this->get_all_children_ids( $child_ids );
                $all_children = array_merge( $all_children, $grandchildren );
            }
        }
        
        return array_unique( $all_children );
    }
}