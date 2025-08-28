<?php

namespace App\Widgets\Elementor\Bylines;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class ShowWidget extends Widget_Base
{
    public function get_name()
    {
        return 'amfm_show';
    }

    public function get_title()
    {
        return __('Show/Hide by Date', 'amfm-tools');
    }

    public function get_icon()
    {
        return 'eicon-eye';
    }

    public function get_categories()
    {
        return ['amfm-tools'];
    }

    public function get_keywords()
    {
        return ['show', 'hide', 'date', 'time', 'visibility', 'schedule'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'amfm-tools'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'classnames',
            [
                'label' => __('Class Names to Hide', 'amfm-tools'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'class1, class2, class3',
                'description' => __('Enter CSS class names separated by commas. These elements will be hidden after the specified date/time.', 'amfm-tools'),
            ]
        );

        $this->add_control(
            'end_datetime',
            [
                'label' => __('Hide After Date/Time', 'amfm-tools'),
                'type' => Controls_Manager::DATE_TIME,
                'description' => __('Elements will be hidden after this date and time.', 'amfm-tools'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $classnames = isset($settings['classnames']) ? sanitize_text_field($settings['classnames']) : '';
        $end_datetime = isset($settings['end_datetime']) ? $settings['end_datetime'] : '';

        if (empty($classnames) || empty($end_datetime)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div style="padding: 15px; background: #f8f9fa; border: 1px dashed #dee2e6; text-align: center;">';
                echo '<i class="eicon-eye" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>';
                echo '<p><strong>Show/Hide Widget</strong></p>';
                echo '<p>Configure class names and date/time to hide elements after a specific date.</p>';
                echo '</div>';
            }
            return;
        }

        $classnames_array = array_map('trim', explode(',', $classnames));
        $end_timestamp = strtotime($end_datetime);
        $current_timestamp = current_time('timestamp');

        // Remove this widget's own margin
        echo '<style>';
        echo '.elementor-element-' . $this->get_id() . ' { margin: 0 !important; }';
        echo '</style>';

        // Hide elements if current time is past the end datetime
        if ($end_timestamp && $current_timestamp > $end_timestamp) {
            $classnames_selectors = array_map(function ($classname) {
                return '.' . trim($classname);
            }, $classnames_array);

            if (!empty($classnames_selectors)) {
                echo '<style>';
                echo implode(', ', $classnames_selectors) . ' { display: none !important; }';
                echo '</style>';
            }
        }

        // Show preview in editor mode
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $status = ($current_timestamp > $end_timestamp) ? 'HIDING' : 'WAITING';
            $color = ($current_timestamp > $end_timestamp) ? '#dc3545' : '#28a745';
            
            echo '<div style="padding: 10px; background: ' . $color . '; color: white; text-align: center; font-size: 12px; margin: 5px 0;">';
            echo '<strong>' . $status . '</strong> elements with classes: <em>' . esc_html($classnames) . '</em>';
            echo '<br>End time: ' . date('Y-m-d H:i:s', $end_timestamp);
            echo '</div>';
        }
    }
}