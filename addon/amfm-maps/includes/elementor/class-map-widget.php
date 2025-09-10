<?php

namespace AMFM_Maps\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class MapWidget extends Widget_Base
{
    public function get_name()
    {
        return 'amfm_map_widget';
    }

    public function get_title()
    {
        return __('AMFM Map', 'amfm-maps');
    }

    public function get_icon()
    {
        return 'eicon-map-pin';
    }

    public function get_categories()
    {
        return ['amfm-maps'];
    }

    public function get_script_depends()
    {
        return ['amfm-google-maps', 'amfm-maps-script'];
    }

    public function get_style_depends()
    {
        return ['amfm-maps-style', 'amfm-maps-drawer-responsive'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Map Settings', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'map_title',
            [
                'label' => __('Map Title', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Find AMFM Locations', 'amfm-maps'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'use_stored_data',
            [
                'label' => __('Use Stored JSON Data', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Use data from amfm_maps_json_data option', 'amfm-maps'),
            ]
        );

        $this->add_control(
            'custom_json_data',
            [
                'label' => __('Custom JSON Data', 'amfm-maps'),
                'type' => Controls_Manager::TEXTAREA,
                'description' => __('Input custom JSON data if not using stored data', 'amfm-maps'),
                'default' => '',
                'condition' => [
                    'use_stored_data!' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'map_settings_section',
            [
                'label' => __('Map Settings', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'map_height',
            [
                'label' => __('Map Height', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 1200,
                    ],
                    'vh' => [
                        'min' => 40,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 600,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-map-wrapper' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Mobile Filter Button Section
        $this->start_controls_section(
            'mobile_filter_button_section',
            [
                'label' => __('Mobile Filter Button', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_mobile_filter_button',
            [
                'label' => __('Show Filter Button on Mobile', 'amfm-maps'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'amfm-maps'),
                'label_off' => __('No', 'amfm-maps'),
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Display a filter button on mobile devices that can trigger the filter drawer', 'amfm-maps'),
            ]
        );

        $this->add_control(
            'filter_button_text',
            [
                'label' => __('Button Text', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Filter', 'amfm-maps'),
                'placeholder' => __('Enter button text', 'amfm-maps'),
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_button_icon',
            [
                'label' => __('Button Icon', 'amfm-maps'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-filter',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_button_class',
            [
                'label' => __('Button CSS Class', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => 'amfm-mobile-filter-trigger',
                'placeholder' => __('custom-class-name', 'amfm-maps'),
                'description' => __('Add custom CSS class to the filter button. This class should match the trigger selector in your filter widget.', 'amfm-maps'),
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_button_id',
            [
                'label' => __('Button CSS ID', 'amfm-maps'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => __('custom-id', 'amfm-maps'),
                'description' => __('Add custom CSS ID to the filter button (optional). This ID should match the trigger selector in your filter widget.', 'amfm-maps'),
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_button_position',
            [
                'label' => __('Button Position', 'amfm-maps'),
                'type' => Controls_Manager::SELECT,
                'default' => 'bottom-right',
                'options' => [
                    'top-left' => __('Top Left', 'amfm-maps'),
                    'top-right' => __('Top Right', 'amfm-maps'),
                    'bottom-left' => __('Bottom Left', 'amfm-maps'),
                    'bottom-right' => __('Bottom Right', 'amfm-maps'),
                ],
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_button_offset_x',
            [
                'label' => __('Horizontal Offset', 'amfm-maps'),
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
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_button_offset_y',
            [
                'label' => __('Vertical Offset', 'amfm-maps'),
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
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style controls
        $this->start_controls_section(
            'map_style_section',
            [
                'label' => __('Map Style', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'map_border_radius',
            [
                'label' => __('Map Border Radius', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-map-panel' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .amfm-map-wrapper' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'map_box_shadow',
                'label' => __('Map Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-map-panel',
            ]
        );

        $this->end_controls_section();

        // Mobile Filter Button Style Controls
        $this->start_controls_section(
            'mobile_filter_button_style',
            [
                'label' => __('Mobile Filter Button Style', 'amfm-maps'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_mobile_filter_button' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'filter_button_typography',
                'label' => __('Typography', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-filter-button',
            ]
        );

        $this->add_control(
            'filter_button_padding',
            [
                'label' => __('Padding', 'amfm-maps'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => '12',
                    'right' => '20',
                    'bottom' => '12',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_border_radius',
            [
                'label' => __('Border Radius', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 30,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Normal State
        $this->add_control(
            'filter_button_normal_heading',
            [
                'label' => __('Normal State', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'filter_button_bg_color',
            [
                'label' => __('Background Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007bff',
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_text_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_icon_color',
            [
                'label' => __('Icon Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .amfm-mobile-filter-button svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'filter_button_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-filter-button',
            ]
        );

        // Hover State
        $this->add_control(
            'filter_button_hover_heading',
            [
                'label' => __('Hover State', 'amfm-maps'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'filter_button_hover_bg_color',
            [
                'label' => __('Background Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0056b3',
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_hover_text_color',
            [
                'label' => __('Text Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_hover_icon_color',
            [
                'label' => __('Icon Color', 'amfm-maps'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button:hover i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .amfm-mobile-filter-button:hover svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'filter_button_hover_box_shadow',
                'label' => __('Box Shadow', 'amfm-maps'),
                'selector' => '{{WRAPPER}} .amfm-mobile-filter-button:hover',
            ]
        );

        $this->add_control(
            'filter_button_icon_spacing',
            [
                'label' => __('Icon Spacing', 'amfm-maps'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 5,
                ],
                'selectors' => [
                    '{{WRAPPER}} .amfm-mobile-filter-button i' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .amfm-mobile-filter-button svg' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        
        // Use Elementor's widget ID for consistent targeting
        $widget_id = $this->get_id();
        $unique_id = 'amfm_map_' . $widget_id;
        
        // Get JSON data
        $json_data = [];
        if ($settings['use_stored_data'] === 'yes') {
            $json_data = \Amfm_Maps_Admin::get_filtered_json_data() ?: [];
        } else if (!empty($settings['custom_json_data'])) {
            $json_data = json_decode($settings['custom_json_data'], true) ?: [];
        }
        
        // Apply location and region selector filtering if configured
        $filter_config = \Amfm_Maps_Admin::get_filter_config();
        $json_data = $this->apply_location_selector_filter($json_data, $settings, $filter_config);
        $json_data = $this->apply_region_selector_filter($json_data, $settings, $filter_config);
        
        ?>
        <div class="amfm-map-container amfm-map-only" 
             id="<?php echo esc_attr($unique_id); ?>">
            
            <?php if (!empty($settings['map_title'])): ?>
                <div class="amfm-map-title">
                    <h3><?php echo esc_html($settings['map_title']); ?></h3>
                </div>
            <?php endif; ?>
            
            <div class="amfm-map-wrapper" id="<?php echo esc_attr($unique_id); ?>_map">
                <?php if (empty($json_data)): ?>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        <p>No location data available. Please check the JSON data source.</p>
                        <small>Using stored data: <?php echo $settings['use_stored_data'] === 'yes' ? 'Yes' : 'No'; ?></small>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($settings['show_mobile_filter_button'] === 'yes'): ?>
                <?php
                // Build button attributes
                $button_classes = ['amfm-mobile-filter-button'];
                if (!empty($settings['filter_button_class'])) {
                    $button_classes[] = esc_attr($settings['filter_button_class']);
                }
                $button_class_string = implode(' ', $button_classes);
                
                $button_id = !empty($settings['filter_button_id']) ? 'id="' . esc_attr($settings['filter_button_id']) . '"' : '';
                
                // Position classes
                $position_map = [
                    'top-left' => 'top: ' . $settings['filter_button_offset_y']['size'] . 'px; left: ' . $settings['filter_button_offset_x']['size'] . 'px;',
                    'top-right' => 'top: ' . $settings['filter_button_offset_y']['size'] . 'px; right: ' . $settings['filter_button_offset_x']['size'] . 'px;',
                    'bottom-left' => 'bottom: ' . $settings['filter_button_offset_y']['size'] . 'px; left: ' . $settings['filter_button_offset_x']['size'] . 'px;',
                    'bottom-right' => 'bottom: ' . $settings['filter_button_offset_y']['size'] . 'px; right: ' . $settings['filter_button_offset_x']['size'] . 'px;',
                ];
                $position_style = $position_map[$settings['filter_button_position']] ?? $position_map['bottom-right'];
                ?>
                <button class="<?php echo $button_class_string; ?>" 
                        <?php echo $button_id; ?>
                        style="<?php echo esc_attr($position_style); ?>"
                        aria-label="<?php echo esc_attr(__('Open filters', 'amfm-maps')); ?>">
                    <?php if (!empty($settings['filter_button_icon']['value'])): ?>
                        <?php \Elementor\Icons_Manager::render_icon($settings['filter_button_icon'], ['aria-hidden' => 'true']); ?>
                    <?php endif; ?>
                    <?php if (!empty($settings['filter_button_text'])): ?>
                        <span><?php echo esc_html($settings['filter_button_text']); ?></span>
                    <?php endif; ?>
                </button>
            <?php endif; ?>
        </div>

        <script>
            jQuery(document).ready(function($) {
                console.log('AMFM Map Widget initializing for:', "<?php echo esc_js($unique_id); ?>");
                console.log('JSON Data count:', <?php echo count($json_data); ?>);
                
                // Ensure Google Maps API is loaded first
                function initializeMap() {
                    if (typeof amfmMap !== 'undefined' && typeof google !== 'undefined' && google.maps) {
                        console.log('Initializing AMFM Map...');
                        amfmMap.init({
                            unique_id: "<?php echo esc_js($unique_id); ?>",
                            json_data: <?php echo json_encode($json_data); ?>,
                            api_key: "<?php echo esc_js(AMFM_MAPS_API_KEY); ?>"
                        });
                    } else {
                        console.log('Waiting for dependencies...', {
                            amfmMap: typeof amfmMap,
                            google: typeof google,
                            googleMaps: typeof google !== 'undefined' ? typeof google.maps : 'undefined'
                        });
                        // Retry after a short delay
                        setTimeout(initializeMapV2, 500);
                    }
                }
                
                // Start initialization
                $(window).on("load", function() {                        setTimeout(initializeMap, 100);
                });
                
                // Also try on document ready as fallback
                initializeMap();
            });
        </script>
        
        <?php
        // Ensure scripts are enqueued for this page
        $this->enqueue_map_scripts();
    }
    
    private function enqueue_map_scripts()
    {
        // Force enqueue scripts if not already done
        if (!wp_script_is('amfm-google-maps', 'enqueued')) {
            wp_enqueue_script('amfm-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . AMFM_MAPS_API_KEY . '&loading=async&libraries=places', [], null, false);
        }
        
        if (!wp_style_is('amfm-maps-style', 'enqueued')) {
            wp_enqueue_style('amfm-maps-style', plugins_url('../../assets/css/style.min.css', __FILE__), [], AMFM_MAPS_VERSION);
        }
        
        if (!wp_script_is('amfm-maps-script', 'enqueued')) {
            wp_enqueue_script('amfm-maps-script', plugins_url('../../assets/js/script.min.js', __FILE__), ['jquery', 'amfm-google-maps'], AMFM_MAPS_VERSION, true);
        }
    }

    /**
     * Apply location selector filtering for map widgets
     * This ensures map widgets respect location selector settings from filter widgets
     *
     * @param array $json_data The facility data
     * @param array $settings Widget settings
     * @param array $filter_config Global filter configuration
     * @return array Filtered JSON data
     */
    private function apply_location_selector_filter($json_data, $settings, $filter_config)
    {
        // Check for location selector configuration from filter widgets
        $location_selector_active = $this->check_for_location_selector_config();
        
        if (!$location_selector_active) {
            return $json_data; // No location selector configured
        }
        
        // Apply the same filtering logic as the filter widget
        $selected_locations = $location_selector_active;
        
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
    
    /**
     * Check if location selector is configured by filter widgets
     * Uses cross-widget communication via WordPress transients
     *
     * @return array|false Selected locations or false if not configured
     */
    private function check_for_location_selector_config()
    {
        // Check for configuration set by filter widgets
        $location_selector_config = get_transient('amfm_location_selector_config');
        
        if ($location_selector_config) {
            return $location_selector_config;
        }
        
        // No location selector configuration found
        return false;
    }

    /**
     * Apply region selector filtering from filter widget configuration
     *
     * @param array $json_data The facility data
     * @param array $settings Widget settings
     * @param array $filter_config Global filter configuration
     * @return array Filtered JSON data
     */
    private function apply_region_selector_filter($json_data, $settings, $filter_config)
    {
        // Check for region selector configuration from filter widgets
        $selected_regions = get_transient('amfm_default_regions_' . get_the_ID());
        
        if (!$selected_regions || empty($selected_regions)) {
            return $json_data; // No region selector configured
        }
        
        // Filter the JSON data to only include selected regions
        $filtered_data = [];
        foreach ($json_data as $location) {
            if (!empty($location['Region']) && in_array($location['Region'], $selected_regions)) {
                $filtered_data[] = $location;
            }
        }
        
        return $filtered_data;
    }
}