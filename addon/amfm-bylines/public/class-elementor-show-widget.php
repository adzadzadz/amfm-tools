<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

class Elementor_Show_Widget extends \Elementor\Widget_Base
{

    // Widget Name
    public function get_name()
    {
        return 'amfm_show_widget';
    }

    // Widget Title
    public function get_title()
    {
        return __('AMFM Show', 'amfm-bylines');
    }

    // Widget Icon
    public function get_icon()
    {
        return 'eicon-eye';
    }

    // Widget Categories
    public function get_categories()
    {
        return ['general'];
    }

    // Register Controls
    protected function _register_controls()
    {
        $this->start_controls_section(
            'section_classnames',
            [
                'label' => __('Class Names', 'amfm-bylines'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'classnames',
            [
                'label' => __('Class Names', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => __('Enter class names separated by commas.', 'amfm-bylines'),
            ]
        );

        $this->add_control(
            'end_datetime',
            [
                'label' => __('End DateTime', 'amfm-bylines'),
                'type' => \Elementor\Controls_Manager::DATE_TIME,
                'description' => __('Set the end datetime.', 'amfm-bylines'),
            ]
        );

        $this->end_controls_section();
    }

    // Render Output
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $classnames = isset($settings['classnames']) ? esc_attr($settings['classnames']) : '';
        $classnames_array = array_map('trim', explode(',', $classnames));

        $end_datetime = isset($settings['end_datetime']) ? strtotime($settings['end_datetime']) : null;
        $current_datetime = strtotime(date('Y-m-d H:i:s', time()));

        echo '<style>'; 
        echo '.elementor-element-' . $this->get_id() . ' { margin: 0px !important; }'; 
        echo '</style>';

        if ( $end_datetime && $current_datetime > $end_datetime ) {
            $classnames_string = implode(', ', array_map(function ($classname) {
                return '.' . trim($classname);
            }, $classnames_array));

            if ($classnames_string) {
                echo '<style>';
                echo $classnames_string . ' { display: none; }';
                echo '</style>';
            }
        }
    }
}

// Register Widget
\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Elementor_Show_Widget());
