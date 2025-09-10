<?php

namespace AMFM_Maps\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * AMFM Map Filter Widget
 *
 * Elementor widget for AMFM Map filters only.
 */
class MapFilterWidget extends Widget_Base
{

    /**
     * Retrieve the widget name.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'amfm_map_filter';
    }

    /**
     * Retrieve the widget title.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('AMFM Map Filter', 'amfm-maps');
    }

    /**
     * Retrieve the widget icon.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'eicon-filter';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['amfm-maps'];
    }

    /**
     * Retrieve the list of scripts the widget depended on.
     *
     * Used to set scripts dependencies required to run the widget.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return array Widget scripts dependencies.
     */
    public function get_script_depends()
    {
        return ['amfm-maps-script'];
    }

    /**
     * Retrieve the list of styles the widget depended on.
     *
     * Used to set style dependencies required to run the widget.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return array Widget style dependencies.
     */
    public function get_style_depends()
    {
        return ['amfm-maps-style'];
    }

    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Filter Settings', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'filter_title',
            [
                'label' => __('Filter Title', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Filter Locations', 'amfm-maps'),
                'placeholder' => __('Enter filter title', 'amfm-maps'),
            ]
        );

        $this->add_control(
            'use_stored_data',
            [
                'label' => __('Use Stored Data', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Use JSON data stored in WordPress options', 'amfm-maps'),
            ]
        );

        $this->add_control(
            'custom_json_data',
            [
                'label' => __('Custom JSON Data', 'amfm-maps'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 10,
                'placeholder' => __('Enter JSON data here...', 'amfm-maps'),
                'condition' => [
                    'use_stored_data' => '',
                ],
            ]
        );

        $this->add_control(
            'target_map_id',
            [
                'label' => __('Target Map Widget ID', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'description' => __('Enter the Elementor widget ID of the map widget to filter (e.g., "1a2b3c4d"). You can find this in the Navigator panel. Leave empty to target all AMFM maps on the page.', 'amfm-maps'),
                'placeholder' => __('1a2b3c4d', 'amfm-maps'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'filter_toggles_section',
            [
                'label' => __('Filter Categories', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'manage_categories',
            [
                'label' => __('Manage Categories', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Enable widget-level category management. When disabled, global filter configuration is used.', 'amfm-maps'),
            ]
        );

        $this->add_control(
            'show_location_filter',
            [
                'label' => __('Show Location Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        // Location selector section - only visible when location filter is disabled
        $this->add_control(
            'select_location_heading',
            [
                'label' => __('Select Location', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_location_description',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('Select specific locations to display when location filtering is disabled. These will act as default locations.', 'amfm-maps'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_location_california',
            [
                'label' => __('California (CA)', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_location_virginia',
            [
                'label' => __('Virginia (VA)', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_location_washington',
            [
                'label' => __('Washington (WA)', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_location_minnesota',
            [
                'label' => __('Minnesota (MN)', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_location_oregon',
            [
                'label' => __('Oregon (OR)', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_location_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_region_filter',
            [
                'label' => __('Show Region Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        // Region selector section - only visible when region filter is disabled
        $this->add_control(
            'select_region_heading',
            [
                'label' => __('Select Regions', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_region_filter!' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'select_region_description',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('Select specific regions to display when region filtering is disabled. These will act as default regions.', 'amfm-maps'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'manage_categories' => 'yes',
                    'show_region_filter!' => 'yes',
                ],
            ]
        );

        // Get available regions from the JSON data
        $json_data = get_option('amfm_maps_json_data', []);
        $available_regions = [];
        if (!empty($json_data)) {
            foreach ($json_data as $location) {
                if (!empty($location['Region'])) {
                    $region = trim($location['Region']);
                    if (!in_array($region, $available_regions)) {
                        $available_regions[] = $region;
                    }
                }
            }
            sort($available_regions);
        }

        // Add a control for each available region
        foreach ($available_regions as $region) {
            $control_name = 'select_region_' . sanitize_key(str_replace(' ', '_', strtolower($region)));
            $this->add_control(
                $control_name,
                [
                    'label' => $region,
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => __('Yes', 'amfm-maps'),
                    'label_off' => __('No', 'amfm-maps'),
                    'return_value' => 'yes',
                    'default' => '',
                    'condition' => [
                        'manage_categories' => 'yes',
                        'show_region_filter!' => 'yes',
                    ],
                ]
            );
        }

        $this->add_control(
            'show_gender_filter',
            [
                'label' => __('Show Gender Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_conditions_filter',
            [
                'label' => __('Show Conditions Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_programs_filter',
            [
                'label' => __('Show Programs Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_accommodations_filter',
            [
                'label' => __('Show Accommodations Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_level_of_care_filter',
            [
                'label' => __('Show Level of Care Filter', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'manage_categories' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Mobile Drawer Section
        $this->start_controls_section(
            'mobile_drawer_section',
            [
                'label' => __('Mobile Drawer', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_mobile_drawer',
            [
                'label' => __('Enable Full Screen Drawer on Mobile', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Transform the filter into a full screen drawer that can be triggered by clicking an element on mobile devices', 'amfm-maps'),
            ]
        );

        $this->add_control(
            'drawer_trigger_selector',
            [
                'label' => __('Trigger Element Selector', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => '.filter-trigger',
                'placeholder' => __('.filter-trigger, #filter-button, .my-custom-class', 'amfm-maps'),
                'description' => __('CSS selector for the element(s) that will trigger the drawer. Examples: .filter-trigger, #filter-button, .my-custom-class', 'amfm-maps'),
                'condition' => [
                    'enable_mobile_drawer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'drawer_breakpoint',
            [
                'label' => __('Mobile Breakpoint', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 320,
                        'max' => 1024,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 768,
                ],
                'description' => __('Screen width below which the drawer will be activated', 'amfm-maps'),
                'condition' => [
                    'enable_mobile_drawer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'drawer_title',
            [
                'label' => __('Drawer Title', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Filter Options', 'amfm-maps'),
                'placeholder' => __('Enter drawer title', 'amfm-maps'),
                'condition' => [
                    'enable_mobile_drawer' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'drawer_slide_direction',
            [
                'label' => __('Drawer Slide Direction', 'amfm-maps'),
                'type' => Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => __('From Bottom', 'amfm-maps'),
                    'top' => __('From Top', 'amfm-maps'),
                    'left' => __('From Left', 'amfm-maps'),
                    'right' => __('From Right', 'amfm-maps'),
                ],
                'description' => __('Choose the direction from which the drawer will slide in', 'amfm-maps'),
                'condition' => [
                    'enable_mobile_drawer' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Icons Section
        $this->start_controls_section(
            'filter_icons_section',
            [
                'label' => __('Filter Icons', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'filter_layout' => 'buttons',
                ],
            ]
        );

        $this->add_control(
            'show_icons',
            [
                'label' => __('Show Icons', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label' => __('Icon Position', 'amfm-maps'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => __('Left', 'amfm-maps'),
                    'right' => __('Right', 'amfm-maps'),
                    'top' => __('Top', 'amfm-maps'),
                    'bottom' => __('Bottom', 'amfm-maps'),
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'location_icon',
            [
                'label' => __('Location Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-map-marker-alt',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_location_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'region_icon',
            [
                'label' => __('Region Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-map',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_region_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'gender_icon',
            [
                'label' => __('Gender Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-user',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_gender_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'conditions_icon',
            [
                'label' => __('Conditions Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-stethoscope',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_conditions_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'programs_icon',
            [
                'label' => __('Programs Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-clipboard-list',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_programs_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'accommodations_icon',
            [
                'label' => __('Accommodations Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-bed',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_accommodations_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'level_of_care_icon',
            [
                'label' => __('Level of Care Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-hospital',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                    'show_level_of_care_filter' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Controls
        $this->start_controls_section(
            'filter_button_style',
            [
                'label' => __('Filter Buttons', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'filter_layout' => 'buttons',
                ],
            ]
        );

        // Button Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => __('Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-button',
            ]
        );

        // Button Size
        $this->add_responsive_control(
            'button_size',
            [
                'label' => __('Button Size', 'amfm-maps'),
                'type' => Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => [
                    'small' => __('Small', 'amfm-maps'),
                    'medium' => __('Medium', 'amfm-maps'),
                    'large' => __('Large', 'amfm-maps'),
                    'custom' => __('Custom', 'amfm-maps'),
                ],
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => __('Width', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 3,
                        'max' => 30,
                    ],
                ],
                'condition' => [
                    'button_size' => 'custom',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_height',
            [
                'label' => __('Height', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'condition' => [
                    'button_size' => 'custom',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        // Button Normal State
        $this->add_control(
            'button_normal_heading',
            [
                'label' => __('Normal State', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'label' => __('Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-filter-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'label' => __('Border', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-button',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-button !important',
            ]
        );

        // Button Hover State
        $this->add_control(
            'button_hover_heading',
            [
                'label' => __('Hover State', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button:hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_hover_background',
                'label' => __('Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-filter-button:hover',
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __('Border Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button:hover' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-button:hover',
            ]
        );

        $this->add_control(
            'button_hover_transition',
            [
                'label' => __('Transition Duration', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 0,
                        'max' => 3000,
                        'step' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'ms',
                    'size' => 300,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'transition: all {{SIZE}}{{UNIT}} ease-in-out !important;',
                ],
            ]
        );

        // Button Active State
        $this->add_control(
            'button_active_heading',
            [
                'label' => __('Active State', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_active_text_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button.active' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'button_active_background',
                'label' => __('Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-filter-button.active',
            ]
        );

        $this->add_control(
            'button_active_border_color',
            [
                'label' => __('Border Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button.active' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_active_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-button.active',
            ]
        );

        // Button Spacing
        $this->add_control(
            'button_spacing_heading',
            [
                'label' => __('Spacing', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Padding', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label' => __('Margin', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_gap',
            [
                'label' => __('Gap Between Buttons', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 3,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-buttons-wrapper' => 'gap: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        // Icon Styling
        $this->add_control(
            'button_icon_heading',
            [
                'label' => __('Icon Styling', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_icon_size',
            [
                'label' => __('Icon Size', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button i' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .amfm-filter-button svg' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_icon_color',
            [
                'label' => __('Icon Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .amfm-filter-button svg' => 'fill: {{VALUE}} !important;',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_icon_hover_color',
            [
                'label' => __('Icon Hover Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button:hover i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .amfm-filter-button:hover svg' => 'fill: {{VALUE}} !important;',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_icon_active_color',
            [
                'label' => __('Icon Active Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button.active i' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .amfm-filter-button.active svg' => 'fill: {{VALUE}} !important;',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_icon_spacing',
            [
                'label' => __('Icon Spacing', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 2,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-button.icon-left i, {{WRAPPER}} .amfm-filter-button.icon-left svg' => 'margin-right: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .amfm-filter-button.icon-right i, {{WRAPPER}} .amfm-filter-button.icon-right svg' => 'margin-left: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .amfm-filter-button.icon-top i, {{WRAPPER}} .amfm-filter-button.icon-top svg' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .amfm-filter-button.icon-bottom i, {{WRAPPER}} .amfm-filter-button.icon-bottom svg' => 'margin-top: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .amfm-filter-button.icon-top, {{WRAPPER}} .amfm-filter-button.icon-bottom' => 'flex-direction: column !important;',
                    '{{WRAPPER}} .amfm-filter-button.icon-right' => 'flex-direction: row-reverse !important;',
                    '{{WRAPPER}} .amfm-filter-button' => 'display: flex !important; align-items: center !important; justify-content: center !important;',
                ],
                'condition' => [
                    'show_icons' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'filter_container_style',
            [
                'label' => __('Filter Container', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Container Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'container_typography',
                'label' => __('Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-container',
            ]
        );

        // Container Size
        $this->add_responsive_control(
            'container_width',
            [
                'label' => __('Width', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 1200,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    'vw' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_max_width',
            [
                'label' => __('Max Width', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 1400,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    'vw' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'max-width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_height',
            [
                'label' => __('Height', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        // Container Appearance
        $this->add_control(
            'container_appearance_heading',
            [
                'label' => __('Appearance', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'container_text_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'container_background',
                'label' => __('Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-filter-container',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'label' => __('Border', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-container',
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label' => __('Border Radius', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-container',
            ]
        );

        // Container Spacing
        $this->add_control(
            'container_spacing_heading',
            [
                'label' => __('Spacing', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __('Padding', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );
        $this->add_responsive_control(
            'container_margin',
            [
                'label' => __('Margin', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );
        $this->end_controls_section();

        // Filter Group Title Styling
        $this->start_controls_section(
            'filter_group_title_style',
            [
                'label' => __('Filter Group Titles', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'group_title_typography',
                'label' => __('Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-group-title, {{WRAPPER}} .amfm-filter-group h5',
            ]
        );
        $this->add_control(
            'group_title_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-title' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .amfm-filter-group h5' => 'color: {{VALUE}} !important;',
                ],
            ]
        );
        $this->add_responsive_control(
            'group_title_margin',
            [
                'label' => __('Margin', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    '{{WRAPPER}} .amfm-filter-group h5' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );
        $this->add_control(
            'group_title_separator',
            [
                'label' => __('Show Separator', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );
        $this->add_control(
            'group_title_separator_style',
            [
                'label' => __('Separator Style', 'amfm-maps'),
                'type' => Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    'solid' => __('Solid', 'amfm-maps'),
                    'dashed' => __('Dashed', 'amfm-maps'),
                    'dotted' => __('Dotted', 'amfm-maps'),
                ],
                'condition' => [
                    'group_title_separator' => 'yes',
                ],
            ]
        );
        $this->add_control(
            'group_title_separator_color',
            [
                'label' => __('Separator Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'group_title_separator' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-separator' => 'border-bottom-color: {{VALUE}} !important;',
                ],
            ]
        );
        $this->add_control(
            'group_title_border',
            [
                'label' => __('Group Title Border', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'amfm-maps'),
                'label_off' => __('Hide', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );
        $this->add_control(
            'group_title_border_style',
            [
                'label' => __('Border Style', 'amfm-maps'),
                'type' => Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    'solid' => __('Solid', 'amfm-maps'),
                    'dashed' => __('Dashed', 'amfm-maps'),
                    'dotted' => __('Dotted', 'amfm-maps'),
                ],
                'condition' => [
                    'group_title_border' => 'yes',
                ],
            ]
        );
        $this->add_control(
            'group_title_border_color',
            [
                'label' => __('Border Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'group_title_border' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-title' => 'border-bottom-color: {{VALUE}} !important;',
                ],
            ]
        );
        $this->add_responsive_control(
            'group_title_border_width',
            [
                'label' => __('Border Width', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 10,
                    ],
                ],
                'condition' => [
                    'group_title_border' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-title' => 'border-bottom-width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'group_title_distance_to_buttons',
            [
                'label' => __('Group Title Distance to Buttons', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 50 ],
                    'em' => [ 'min' => 0, 'max' => 5 ],
                    'rem' => [ 'min' => 0, 'max' => 5 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-title' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );
        $this->add_responsive_control(
            'group_distance_from_other_groups',
            [
                'label' => __('Group Distance from Other Groups', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                    'em' => [ 'min' => 0, 'max' => 6 ],
                    'rem' => [ 'min' => 0, 'max' => 6 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-group-buttons' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );
        $this->end_controls_section();

        // Filter Title Styling
        $this->start_controls_section(
            'filter_title_style',
            [
                'label' => __('Filter Title', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'filter_title!' => '',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'filter_title_typography',
                'label' => __('Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-title h3',
            ]
        );

        $this->add_control(
            'filter_title_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-title h3' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'filter_title_alignment',
            [
                'label' => __('Alignment', 'amfm-maps'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'amfm-maps'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'amfm-maps'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'amfm-maps'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-title' => 'text-align: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'filter_title_margin',
            [
                'label' => __('Margin', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();

        // Sidebar Layout Specific Styles
        // Mobile Drawer Container Style Section
        $this->start_controls_section(
            'mobile_drawer_container_style',
            [
                'label' => __('Mobile Drawer Container', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_mobile_drawer' => 'yes',
                ],
            ]
        );

        // Mobile Drawer Container Background
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'mobile_drawer_background',
                'label' => __('Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer',
            ]
        );

        // Mobile Drawer Container Width (for side drawers)
        $this->add_responsive_control(
            'mobile_drawer_width',
            [
                'label' => __('Drawer Width', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 600,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                    'vw' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'condition' => [
                    'drawer_slide_direction' => ['left', 'right'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer.amfm-slide-left, {{WRAPPER}} .amfm-mobile-drawer.amfm-slide-right' => 'width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        // Mobile Drawer Container Height (for top/bottom drawers)
        $this->add_responsive_control(
            'mobile_drawer_height',
            [
                'label' => __('Drawer Height', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 800,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                    'vh' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'condition' => [
                    'drawer_slide_direction' => ['top', 'bottom'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer.amfm-slide-top, {{WRAPPER}} .amfm-mobile-drawer.amfm-slide-bottom' => 'height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        // Mobile Drawer Border
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'mobile_drawer_border',
                'label' => __('Border', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer',
            ]
        );

        // Mobile Drawer Border Radius
        $this->add_responsive_control(
            'mobile_drawer_border_radius',
            [
                'label' => __('Border Radius', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        // Mobile Drawer Box Shadow
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'mobile_drawer_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer',
            ]
        );

        // Mobile Drawer Header Styling
        $this->add_control(
            'mobile_drawer_header_heading',
            [
                'label' => __('Header Styling', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'mobile_drawer_header_background',
                'label' => __('Header Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer-header',
            ]
        );

        $this->add_responsive_control(
            'mobile_drawer_header_padding',
            [
                'label' => __('Header Padding', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'mobile_drawer_header_border',
                'label' => __('Header Border', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer-header',
            ]
        );

        // Mobile Drawer Title Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'mobile_drawer_title_typography',
                'label' => __('Title Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer-title',
            ]
        );

        $this->add_control(
            'mobile_drawer_title_color',
            [
                'label' => __('Title Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer-title' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        // Mobile Drawer Content Styling
        $this->add_control(
            'mobile_drawer_content_heading',
            [
                'label' => __('Content Styling', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'mobile_drawer_content_padding',
            [
                'label' => __('Content Padding', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'mobile_drawer_content_background',
                'label' => __('Content Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer-content',
            ]
        );

        // Close Button Styling
        $this->add_control(
            'mobile_drawer_close_button_heading',
            [
                'label' => __('Close Button Styling', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'mobile_drawer_close_button_color',
            [
                'label' => __('Close Button Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer-close' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'mobile_drawer_close_button_hover_color',
            [
                'label' => __('Close Button Hover Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer-close:hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'mobile_drawer_close_button_background',
                'label' => __('Close Button Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer-close',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'mobile_drawer_close_button_hover_background',
                'label' => __('Close Button Hover Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-mobile-drawer-close:hover',
            ]
        );

        $this->add_responsive_control(
            'mobile_drawer_close_button_size',
            [
                'label' => __('Close Button Size', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 60,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-drawer-close' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; font-size: calc({{SIZE}}{{UNIT}} * 0.6) !important;',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'sidebar_layout_style',
            [
                'label' => __('Sidebar Layout', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'filter_layout' => 'sidebar',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'sidebar_background',
                'label' => __('Sidebar Background', 'amfm-maps'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .amfm-filter-panel',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'sidebar_border',
                'label' => __('Sidebar Border', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-panel',
            ]
        );

        $this->add_responsive_control(
            'sidebar_padding',
            [
                'label' => __('Sidebar Padding', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        // Checkbox Styling
        $this->add_control(
            'checkbox_styling_heading',
            [
                'label' => __('Checkbox Styling', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'checkbox_color',
            [
                'label' => __('Checkbox Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-option input[type="checkbox"]' => 'accent-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'checkbox_size',
            [
                'label' => __('Checkbox Size', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 24,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-option input[type="checkbox"]' => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'checkbox_label_typography',
                'label' => __('Label Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-filter-option span',
            ]
        );

        $this->add_control(
            'checkbox_label_color',
            [
                'label' => __('Label Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-filter-option span' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Helper method to determine if a filter category should be shown
     * Prioritizes widget-level settings when manage_categories is enabled,
     * otherwise falls back to global configuration
     *
     * @param string $category The category name (location, gender, etc.)
     * @param array $settings The widget settings
     * @param array $filter_config The global filter configuration
     * @return bool Whether the category should be shown
     */
    protected function should_show_category($category, $settings, $filter_config)
    {
        $manage_categories = $settings['manage_categories'] === 'yes';
        
        if ($manage_categories) {
            // Use widget-level settings when manage_categories is enabled
            $widget_setting_key = 'show_' . $category . '_filter';
            return ($settings[$widget_setting_key] ?? 'yes') === 'yes';
        } else {
            // Fall back to global configuration
            return !empty($filter_config[$category]['enabled']);
        }
    }

    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        // Use Elementor's widget ID for consistent targeting
        $widget_id = $this->get_id();
        $unique_id = 'amfm_filter_' . $widget_id;
        $filter_layout = $settings['filter_layout'] ?? 'buttons';
        $target_map_id = $settings['target_map_id'] ?? '';

        // Get JSON data
        $json_data = [];
        if ($settings['use_stored_data'] === 'yes') {
            $json_data = \Amfm_Maps_Admin::get_filtered_json_data() ?: [];
        } else if (!empty($settings['custom_json_data'])) {
            $json_data = json_decode($settings['custom_json_data'], true) ?: [];
        }

        // Get filter configuration for labels and enabled status
        $filter_config = \Amfm_Maps_Admin::get_filter_config();
        
        // Apply default location and region filtering to JSON data for frontend
        $json_data = $this->apply_default_location_filter($json_data, $settings, $filter_config);
        $json_data = $this->apply_default_region_filter($json_data, $settings, $filter_config);
        
        // Generate filter options from the JSON data
        $filter_options = $this->generate_filter_options($json_data, $settings);

        // Get group title separator settings
        $show_group_separator = ($settings['group_title_separator'] ?? 'no') === 'yes';
        $group_separator_style = $settings['group_title_separator_style'] ?? 'solid';
        $group_separator_color = $settings['group_title_separator_color'] ?? '#ddd';

        // Get icon settings
        $show_icons = $settings['show_icons'] === 'yes';
        $icon_position = $settings['icon_position'] ?? 'left';
        $button_size = $settings['button_size'] ?? 'medium';

        // Get mobile drawer settings
        $enable_mobile_drawer = $settings['enable_mobile_drawer'] === 'yes';
        $drawer_trigger_selector = $settings['drawer_trigger_selector'] ?? '.filter-trigger';
        $drawer_breakpoint = $settings['drawer_breakpoint']['size'] ?? 768;
        $drawer_title = $settings['drawer_title'] ?? __('Filter Options', 'amfm-maps');
        $drawer_slide_direction = $settings['drawer_slide_direction'] ?? 'bottom';

        // Build button classes
        $button_classes = ['amfm-filter-button'];
        if ($show_icons) {
            $button_classes[] = 'icon-' . $icon_position;
        }
        if ($button_size !== 'custom') {
            $button_classes[] = 'size-' . $button_size;
        }
        $button_class_string = implode(' ', $button_classes);

        // Icon map
        $filter_icons = [
            'location' => $settings['location_icon'] ?? [],
            'region' => $settings['region_icon'] ?? [],
            'gender' => $settings['gender_icon'] ?? [],
            'conditions' => $settings['conditions_icon'] ?? [],
            'programs' => $settings['programs_icon'] ?? [],
            'accommodations' => $settings['accommodations_icon'] ?? [],
            'level_of_care' => $settings['level_of_care_icon'] ?? [],
        ];

?>
        <div class="amfm-filter-container amfm-filter-only amfm-layout-<?php echo esc_attr($filter_layout); ?><?php echo $enable_mobile_drawer ? ' amfm-drawer-enabled' : ''; ?>"
            id="<?php echo esc_attr($unique_id); ?>"
            data-target-map="<?php echo esc_attr($target_map_id); ?>"
            <?php if ($enable_mobile_drawer): ?>
                data-drawer-enabled="true"
                data-drawer-trigger="<?php echo esc_attr($drawer_trigger_selector); ?>"
                data-drawer-breakpoint="<?php echo esc_attr($drawer_breakpoint); ?>"
                data-drawer-title="<?php echo esc_attr($drawer_title); ?>"
                data-drawer-slide-direction="<?php echo esc_attr($drawer_slide_direction); ?>"
            <?php endif; ?>>

            <?php if (!empty($settings['filter_title'])): ?>
                <div class="amfm-filter-title">
                    <h3><?php echo esc_html($settings['filter_title']); ?></h3>
                </div>
            <?php endif; ?>

            <!-- Button Filter Layout -->
            <div class="amfm-filter-buttons-container">
                <div class="amfm-filter-buttons-wrapper">
                    <?php if ($this->should_show_category('location', $settings, $filter_config) && !empty($filter_options['location'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['location']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['location'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['location']['label'] ?? __('Location:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['location'] as $location): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="location" data-filter-value="<?php echo esc_attr($location); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['location']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['location'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($location); ?>
                                    <?php if ($show_icons && !empty($filter_icons['location']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['location'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->should_show_category('region', $settings, $filter_config) && !empty($filter_options['region'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['region']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['region'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['region']['label'] ?? __('Region:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['region'] as $region): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="region" data-filter-value="<?php echo esc_attr($region); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['region']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['region'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($region); ?>
                                    <?php if ($show_icons && !empty($filter_icons['region']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['region'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->should_show_category('gender', $settings, $filter_config) && !empty($filter_options['gender'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['gender']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['gender'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['gender']['label'] ?? __('Gender:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['gender'] as $gender): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="gender" data-filter-value="<?php echo esc_attr($gender); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['gender']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['gender'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($gender); ?>
                                    <?php if ($show_icons && !empty($filter_icons['gender']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['gender'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->should_show_category('conditions', $settings, $filter_config) && !empty($filter_options['conditions'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['conditions']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['conditions'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['conditions']['label'] ?? __('Conditions:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['conditions'] as $condition): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="conditions" data-filter-value="<?php echo esc_attr($condition); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['conditions']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['conditions'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($condition); ?>
                                    <?php if ($show_icons && !empty($filter_icons['conditions']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['conditions'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($this->should_show_category('programs', $settings, $filter_config) && !empty($filter_options['programs'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['programs']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['programs'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['programs']['label'] ?? __('Programs:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['programs'] as $program): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="programs" data-filter-value="<?php echo esc_attr($program); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['programs']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['programs'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($program); ?>
                                    <?php if ($show_icons && !empty($filter_icons['programs']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['programs'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->should_show_category('accommodations', $settings, $filter_config) && !empty($filter_options['accommodations'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['accommodations']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['accommodations'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['accommodations']['label'] ?? __('Accommodations:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['accommodations'] as $accommodation): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="accommodations" data-filter-value="<?php echo esc_attr($accommodation); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['accommodations']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['accommodations'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($accommodation); ?>
                                    <?php if ($show_icons && !empty($filter_icons['accommodations']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['accommodations'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->should_show_category('level_of_care', $settings, $filter_config) && !empty($filter_options['level_of_care'])): ?>
                        <div class="amfm-filter-group-buttons">
                            <span class="amfm-filter-group-title"
                                <?php if (($settings['group_title_border'] ?? 'no') === 'yes'): ?>
                                    style="display:block;border-bottom:<?php echo esc_attr($settings['group_title_border_width']['size'] ?? 2); ?>px <?php echo esc_attr($settings['group_title_border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['group_title_border_color'] ?? '#ddd'); ?>;margin-bottom:8px;"
                                <?php endif; ?>
                            >
                                <?php if ($show_icons && !empty($filter_icons['level_of_care']['value'])): ?>
                                    <?php \Elementor\Icons_Manager::render_icon($filter_icons['level_of_care'], ['aria-hidden' => 'true']); ?>
                                <?php endif; ?>
                                <?php echo esc_html($filter_config['level_of_care']['label'] ?? __('Level of Care:', 'amfm-maps')); ?>
                            </span>
                            <?php if ($show_group_separator): ?>
                                <span class="amfm-filter-group-separator" style="display:block;border-bottom:2px <?php echo esc_attr($group_separator_style); ?> <?php echo esc_attr($group_separator_color); ?>;margin:8px 0;"></span>
                            <?php endif; ?>
                            <?php foreach ($filter_options['level_of_care'] as $care_level): ?>
                                <button class="<?php echo esc_attr($button_class_string); ?>" data-filter-type="level_of_care" data-filter-value="<?php echo esc_attr($care_level); ?>">
                                    <?php if ($show_icons && !empty($filter_icons['level_of_care']['value']) && in_array($icon_position, ['left', 'top'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['level_of_care'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                    <?php echo esc_html($care_level); ?>
                                    <?php if ($show_icons && !empty($filter_icons['level_of_care']['value']) && in_array($icon_position, ['right', 'bottom'])): ?>
                                        <?php \Elementor\Icons_Manager::render_icon($filter_icons['level_of_care'], ['aria-hidden' => 'true']); ?>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($enable_mobile_drawer): ?>
                <!-- Mobile Drawer Overlay -->
                <div class="amfm-mobile-drawer-overlay"></div>
                
                <!-- Mobile Drawer -->
                <div class="amfm-mobile-drawer amfm-slide-<?php echo esc_attr($drawer_slide_direction); ?>">
                    <div class="amfm-mobile-drawer-header">
                        <h4 class="amfm-mobile-drawer-title"><?php echo esc_html($drawer_title); ?></h4>
                        <button class="amfm-mobile-drawer-close" aria-label="<?php echo esc_attr(__('Close', 'amfm-maps')); ?>">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="amfm-mobile-drawer-content">
                        <!-- Duplicate filter content for mobile drawer -->
                        <?php if (!empty($settings['filter_title'])): ?>
                            <div class="amfm-filter-title">
                                <h3><?php echo esc_html($settings['filter_title']); ?></h3>
                            </div>
                        <?php endif; ?>
                        
                        <div class="amfm-filter-buttons-container">
                            <div class="amfm-filter-buttons-wrapper">
                                <!-- Content will be cloned via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <style>
            /* Icon positioning styles */
            .amfm-filter-button {
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                border: 1px solid #ddd;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                outline: none;
                background-color: #fff;
                color: #333;
                border-radius: 4px;
                font-family: inherit;
                font-weight: 500;
                line-height: 1;
            }

            .amfm-filter-button:hover {
                background-color: #f5f5f5;
                border-color: #999;
            }

            .amfm-filter-button.active {
                background-color: #007cba;
                color: #fff;
                border-color: #007cba;
            }

            .amfm-filter-button.icon-top,
            .amfm-filter-button.icon-bottom {
                flex-direction: column;
            }

            .amfm-filter-button.icon-right {
                flex-direction: row-reverse;
            }

            .amfm-filter-button.icon-left {
                flex-direction: row;
            }

            /* Button size presets */
            .amfm-filter-button.size-small {
                font-size: 12px;
                padding: 6px 12px;
                min-height: 30px;
            }

            .amfm-filter-button.size-medium {
                font-size: 14px;
                padding: 8px 16px;
                min-height: 36px;
            }

            .amfm-filter-button.size-large {
                font-size: 16px;
                padding: 12px 24px;
                min-height: 48px;
            }

            .amfm-filter-buttons-wrapper {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .amfm-filter-group-buttons {
                margin-bottom: 16px;
            }

            .amfm-filter-group-title {
                display: flex;
                align-items: center;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .amfm-filter-group-title i,
            .amfm-filter-group-title svg {
                margin-right: 6px;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .amfm-filter-buttons-wrapper {
                    flex-direction: column;
                }

                .amfm-filter-button {
                    width: 100%;
                    justify-content: flex-start;
                }

                .amfm-filter-button.icon-right {
                    justify-content: space-between;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Mobile Drawer Functionality
                <?php if ($enable_mobile_drawer): ?>
                function initializeMobileDrawer() {
                    const container = $('#<?php echo esc_js($unique_id); ?>');
                    const drawerSelector = '<?php echo esc_js($drawer_trigger_selector); ?>';
                    const breakpoint = <?php echo esc_js($drawer_breakpoint); ?>;
                    const slideDirection = '<?php echo esc_js($drawer_slide_direction); ?>';
                    const drawer = container.find('.amfm-mobile-drawer');
                    const overlay = container.find('.amfm-mobile-drawer-overlay');
                    const closeBtn = container.find('.amfm-mobile-drawer-close');

                    // Function to check if we're on mobile
                    function isMobile() {
                        return window.innerWidth <= breakpoint;
                    }

                    // Function to update mobile class based on screen size
                    function updateMobileClass() {
                        if (isMobile()) {
                            container.addClass('amfm-is-mobile');
                        } else {
                            container.removeClass('amfm-is-mobile');
                        }
                    }

                    // Function to clone filter content to drawer
                    function cloneContentToDrawer() {
                        const originalContent = container.find('> .amfm-filter-buttons-container').first();
                        const drawerContent = drawer.find('.amfm-filter-buttons-container .amfm-filter-buttons-wrapper');
                        
                        if (originalContent.length && drawerContent.length) {
                            // Clone the wrapper content
                            const originalWrapper = originalContent.find('.amfm-filter-buttons-wrapper');
                            if (originalWrapper.length) {
                                drawerContent.html(originalWrapper.html());
                            }
                        }
                    }

                    // Function to open drawer
                    function openDrawer() {
                        if (!isMobile()) return;
                        
                        drawer.addClass('active');
                        overlay.addClass('active');
                        $('body').addClass('amfm-drawer-open');
                        
                        // Trigger custom event
                        container.trigger('amfm:drawer:opened');
                    }

                    // Function to close drawer
                    function closeDrawer() {
                        drawer.removeClass('active');
                        overlay.removeClass('active');
                        $('body').removeClass('amfm-drawer-open');
                        
                        // Trigger custom event
                        container.trigger('amfm:drawer:closed');
                    }

                    // Bind trigger elements
                    function bindTriggers() {
                        $(document).off('click.amfm-drawer').on('click.amfm-drawer', drawerSelector, function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (isMobile()) {
                                openDrawer();
                            }
                        });
                    }

                    // Bind close events
                    closeBtn.on('click', closeDrawer);
                    overlay.on('click', closeDrawer);

                    // Close drawer on escape key
                    $(document).on('keydown.amfm-drawer', function(e) {
                        if (e.keyCode === 27 && drawer.hasClass('active')) {
                            closeDrawer();
                        }
                    });

                    // Handle window resize
                    $(window).on('resize.amfm-drawer', function() {
                        updateMobileClass();
                        if (!isMobile() && drawer.hasClass('active')) {
                            closeDrawer();
                        }
                    });

                    // Initialize mobile class
                    updateMobileClass();

                    // Clone content to drawer
                    cloneContentToDrawer();

                    // Initialize triggers
                    bindTriggers();

                    // Re-bind triggers after DOM changes (useful for dynamic content)
                    const observer = new MutationObserver(function(mutations) {
                        let shouldRebind = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                shouldRebind = true;
                            }
                        });
                        if (shouldRebind) {
                            setTimeout(bindTriggers, 100);
                        }
                    });

                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });

                    console.log('AMFM Mobile Drawer initialized for selector:', drawerSelector, 'with slide direction:', slideDirection);
                }

                // Initialize mobile drawer
                initializeMobileDrawer();
                <?php endif; ?>

                // Initialize filter widget
                function initializeFilter() {
                    if (typeof amfmMapFilter !== 'undefined') {
                        console.log('Initializing AMFM Filter...');
                        amfmMapFilter.init({
                            unique_id: "<?php echo esc_js($unique_id); ?>",
                            target_map_id: "<?php echo esc_js($target_map_id); ?>",
                            json_data: <?php echo json_encode($json_data); ?>,
                            mobile_drawer: <?php echo $enable_mobile_drawer ? 'true' : 'false'; ?>
                        });
                    } else {
                        console.log('Waiting for amfmMapFilter...');
                        setTimeout(initializeFilter, 500);
                    }
                }

                // Start initialization
                initializeFilter();
            });
        </script>

<?php
        // Ensure scripts are enqueued for this page
        $this->enqueue_filter_scripts();
    }

    private function enqueue_filter_scripts()
    {
        // Force enqueue scripts if not already done
        if (!wp_style_is('amfm-maps-style', 'enqueued')) {
            wp_enqueue_style('amfm-maps-style', plugins_url('../../assets/css/style.min.css', __FILE__), [], AMFM_MAPS_VERSION);
        }

        // Enqueue mobile drawer styles
        if (!wp_style_is('amfm-maps-drawer-responsive', 'enqueued')) {
            wp_enqueue_style('amfm-maps-drawer-responsive', plugins_url('../../assets/css/amfm-maps-drawer-responsive.min.css', __FILE__), ['amfm-maps-style'], AMFM_MAPS_VERSION);
        }

        if (!wp_script_is('amfm-maps-script', 'enqueued')) {
            wp_enqueue_script('amfm-maps-script', plugins_url('../../assets/js/script.min.js', __FILE__), ['jquery'], AMFM_MAPS_VERSION, true);
        }
    }

    private function generate_filter_options($json_data, $settings = [])
    {
        // Get filter configuration from admin settings
        $filter_config = \Amfm_Maps_Admin::get_filter_config();

        $options = [
            'location' => [],
            'region' => [],
            'gender' => [],
            'conditions' => [],
            'programs' => [],
            'accommodations' => [],
            'level_of_care' => []
        ];

        if (empty($json_data)) {
            return $options;
        }

        // Apply default location and region filtering if categories are disabled but specific items are selected
        $json_data = $this->apply_default_location_filter($json_data, $settings, $filter_config);
        $json_data = $this->apply_default_region_filter($json_data, $settings, $filter_config);

        foreach ($json_data as $location) {
            // Extract locations (only if enabled) - look for State field or Location-related fields
            if ($this->should_show_category('location', $settings, $filter_config)) {
                $state_value = null;

                // First try the exact field name
                if (!empty($location['State'])) {
                    $state_value = $location['State'];
                } else {
                    // Look for fields that might contain location info
                    foreach ($location as $key => $value) {
                        if (!empty($value) && (
                            stripos($key, 'state') !== false ||
                            stripos($key, 'location') !== false ||
                            stripos($key, 'address') !== false ||
                            stripos($key, 'city') !== false
                        )) {
                            $state_value = $value;
                            break;
                        }
                    }
                }

                if ($state_value == 1) {
                    continue; // Skip if state value is 1 (indicating no specific state)
                }
                
                if (!empty($state_value)) {
                    $state_name = $this->get_full_state_name($state_value);
                    if (!in_array($state_name, $options['location'])) {
                        $options['location'][] = $state_name;
                    }
                }
            }

            // Extract regions (only if enabled) - look for Region field
            if ($this->should_show_category('region', $settings, $filter_config)) {
                $region_value = null;

                // First try the exact field name
                if (!empty($location['Region'])) {
                    $region_value = $location['Region'];
                } else {
                    // Look for fields that might contain region info
                    foreach ($location as $key => $value) {
                        if (!empty($value) && (
                            stripos($key, 'region') !== false ||
                            stripos($key, 'county') !== false ||
                            stripos($key, 'area') !== false
                        )) {
                            $region_value = $value;
                            break;
                        }
                    }
                }

                if (!empty($region_value) && $region_value !== '') {
                    $region_value = trim($region_value);
                    if (!in_array($region_value, $options['region'])) {
                        $options['region'][] = $region_value;
                    }
                }
            }

            // Extract genders (only if enabled) - look for Gender field or Gender-related fields
            if ($this->should_show_category('gender', $settings, $filter_config)) {
                $gender_value = null;

                // First try the exact field name
                if (!empty($location['Details: Gender'])) {
                    $gender_value = $location['Details: Gender'];
                } else {
                    // Look for fields that might contain gender info
                    foreach ($location as $key => $value) {
                        if (!empty($value) && stripos($key, 'gender') !== false) {
                            $gender_value = $value;
                            break;
                        }
                    }
                }

                if (!empty($gender_value)) {
                    if (!in_array($gender_value, $options['gender'])) {
                        $options['gender'][] = $gender_value;
                    }
                }
            }


            // Extract conditions (only if enabled)
            if ($this->should_show_category('conditions', $settings, $filter_config)) {
                foreach ($location as $key => $value) {
                    if (strpos($key, 'Conditions: ') === 0 && $value == 1) {
                        $condition = str_replace('Conditions: ', '', $key);
                        if (!in_array($condition, $options['conditions'])) {
                            $options['conditions'][] = $condition;
                        }
                    }
                }
            }

            // Extract programs (only if enabled)
            if ($this->should_show_category('programs', $settings, $filter_config)) {
                foreach ($location as $key => $value) {
                    if (strpos($key, 'Programs: ') === 0 && $value == 1) {
                        $program = str_replace('Programs: ', '', $key);
                        if (!in_array($program, $options['programs'])) {
                            $options['programs'][] = $program;
                        }
                    }
                }
            }

            // Extract accommodations (only if enabled)
            if ($this->should_show_category('accommodations', $settings, $filter_config)) {
                foreach ($location as $key => $value) {
                    if (strpos($key, 'Accommodations: ') === 0 && $value == 1) {
                        $accommodation = str_replace('Accommodations: ', '', $key);
                        if (!in_array($accommodation, $options['accommodations'])) {
                            $options['accommodations'][] = $accommodation;
                        }
                    }
                }
            }

            // Extract level of care (only if enabled)
            if ($this->should_show_category('level_of_care', $settings, $filter_config)) {
                // Check for "Level of Care: " prefixed fields
                foreach ($location as $key => $value) {
                    if (strpos($key, 'Level of Care: ') === 0 && $value == 1) {
                        $care_type = str_replace('Level of Care: ', '', $key);
                        if (!in_array($care_type, $options['level_of_care'])) {
                            $options['level_of_care'][] = $care_type;
                        }
                    }
                }
            }
        }

        foreach ($options as $key => $value) {
            if ($this->should_show_category($key, $settings, $filter_config)) {
                // Apply sorting
                if ($filter_config[$key]['sort_order'] === 'desc') {
                    rsort($options[$key]);
                } else {
                    sort($options[$key]);
                }

                // Apply limits
                $limit = intval($filter_config[$key]['limit']);
                if ($limit > 0) {
                    $options[$key] = array_slice($options[$key], 0, $limit);
                }
            } else {
                // Clear disabled filter types
                $options[$key] = [];
            }
        }

        return $options;
    }

    private function get_full_state_name($abbreviation)
    {
        $states = [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming'
        ];
        return $states[strtoupper($abbreviation)] ?? $abbreviation;
    }

    /**
     * Apply default region filtering when region category is disabled
     * but specific regions are selected in the editor
     *
     * @param array $json_data The facility data
     * @param array $settings Widget settings
     * @param array $filter_config Global filter configuration
     * @return array Filtered JSON data
     */
    private function apply_default_region_filter($json_data, $settings, $filter_config)
    {
        // Only apply if we're using widget-level management AND region category is disabled
        if (($settings['manage_categories'] ?? 'no') !== 'yes') {
            return $json_data; // Not using widget-level management
        }
        
        if ($this->should_show_category('region', $settings, $filter_config)) {
            return $json_data; // Region category is enabled, don't apply default filtering
        }

        // Get selected default regions
        $selected_regions = [];
        
        // Get available regions from the master data
        $master_json_data = get_option('amfm_maps_json_data', []);
        $available_regions = [];
        if (!empty($master_json_data)) {
            foreach ($master_json_data as $location) {
                if (!empty($location['Region'])) {
                    $region = trim($location['Region']);
                    if (!in_array($region, $available_regions)) {
                        $available_regions[] = $region;
                    }
                }
            }
        }

        // Check which regions are selected
        foreach ($available_regions as $region) {
            $control_name = 'select_region_' . sanitize_key(str_replace(' ', '_', strtolower($region)));
            if (($settings[$control_name] ?? '') === 'yes') {
                $selected_regions[] = $region;
            }
        }
        
        // Store the selected regions in a transient for cross-widget communication
        if (!empty($selected_regions)) {
            set_transient('amfm_default_regions_' . get_the_ID(), $selected_regions, 300); // Cache for 5 minutes
            
            // Filter the JSON data to only include facilities in selected regions
            $filtered_data = [];
            foreach ($json_data as $location) {
                if (!empty($location['Region']) && in_array($location['Region'], $selected_regions)) {
                    $filtered_data[] = $location;
                }
            }
            return $filtered_data;
        }
        
        return $json_data;
    }

    /**
     * Apply default location filtering when location category is disabled
     * but specific locations are selected in the editor
     *
     * @param array $json_data The facility data
     * @param array $settings Widget settings
     * @param array $filter_config Global filter configuration
     * @return array Filtered JSON data
     */
    private function apply_default_location_filter($json_data, $settings, $filter_config)
    {
        // Only apply if we're using widget-level management AND location category is disabled
        if (($settings['manage_categories'] ?? 'no') !== 'yes') {
            return $json_data; // Not using widget-level management
        }
        
        if ($this->should_show_category('location', $settings, $filter_config)) {
            return $json_data; // Location category is enabled, don't apply default filtering
        }

        // Get selected default locations
        $selected_locations = [];
        
        // Map the control names to state abbreviations
        $location_map = [
            'select_location_california' => 'CA',
            'select_location_virginia' => 'VA', 
            'select_location_washington' => 'WA',
            'select_location_minnesota' => 'MN',
            'select_location_oregon' => 'OR'
        ];

        // Check which locations are selected
        foreach ($location_map as $control_name => $state_code) {
            if (($settings[$control_name] ?? '') === 'yes') {
                $selected_locations[] = $state_code;
            }
        }
        
        // Store the selected locations in a transient for cross-widget communication
        // This allows map widgets to access the location selector configuration
        if (!empty($selected_locations)) {
            set_transient('amfm_location_selector_config', $selected_locations, 300); // 5 minutes cache
        } else {
            delete_transient('amfm_location_selector_config');
        }

        // If no specific locations are selected, return all data
        if (empty($selected_locations)) {
            return $json_data;
        }

        // Filter the JSON data to only include selected locations
        $filtered_data = [];
        foreach ($json_data as $location) {
            $location_state = $location['State'] ?? '';
            if (in_array($location_state, $selected_locations)) {
                $filtered_data[] = $location;
            }
        }

        return $filtered_data;
    }
}
