<?php
namespace Frontend_Admin\Elementor\Classes;

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Element_Base;

class Widgets_Conditions {
    public function __construct() {
        add_action('elementor/element/after_section_end', [$this, 'add_conditions_field'], 10, 3);
        add_filter('elementor/frontend/widget/should_render', [$this, 'apply_conditions_logic'], 9, 2);
    }

    public function add_conditions_field($element, $section_id, $args) {
        if ($section_id === '_section_style') {
            $element->start_controls_section(
                'frontend_admin_conditions',
                [
                    'label' => __('Frontend Admin Conditions', 'acf-frontend-form-element'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $element->add_control(
                'fea_conditions',
                [
                    'label' => __('Visibility Conditions', 'acf-frontend-form-element'),
                    'type' => 'fea_conditions_control',
                ]
            );

            $element->end_controls_section();
        }
    }

    public function apply_conditions_logic($should_render, Widget_Base $widget) {
        $settings = $widget->get_settings_for_display();

        if (!empty($settings['fea_conditions'])) {
            $conditions = json_decode($settings['fea_conditions'], true);
	
            if (!$this->check_conditions($conditions)) {
                return false;
            }
        }

        return $should_render;
    }

    private function check_conditions($conditions) {
        if (!is_array($conditions)) {
            return true; // Show widget if no conditions are set
        }

        foreach ($conditions as $or_group) {
            $or_pass = false;

            foreach ($or_group['or_group'] as $rule) {
                $key = $this->get_dynamic_value($rule['key']);
                $operator = $rule['operator'];
                $value = $this->get_dynamic_value($rule['value']);

                if ( !$this->compare_values( $key, $operator, $value ) ) {
                    $or_pass = false;
                    break;
                }else{
					$or_pass = true;
				}
            }

            if ( $or_pass ) {
                return true;
            }
        }

        return false;
    }

    private function get_dynamic_value($key) {
		$key = str_replace(["{{", "}}"], "", $key);
		$current_id = get_the_ID();
        switch ($key) {
            case 'post_author': return get_post_field('post_author', $current_id);
            case 'post_date': return get_the_date('Y-m-d', $current_id);
            case 'post_title': return get_the_title();
            case 'post_status': return get_post_status();
            case 'current_user': return get_current_user_id();
            case 'current_user_email': return wp_get_current_user()->user_email;
            case 'current_user_role': return wp_get_current_user()->roles[0] ?? '';
			case 'current_date': return date('Y-m-d');
            default: return $key; // Custom values
        }
    }

    private function compare_values($key, $operator, $value) {
        switch ($operator) {
            case '=': return $key == $value;
            case '!=': return $key != $value;
            case '>': return $key > $value;
            case '<': return $key < $value;
            case '>=': return $key >= $value;
            case '<=': return $key <= $value;
            case 'IN': return in_array($key, explode(',', $value));
            case 'NOT IN': return !in_array($key, explode(',', $value));
            default: return false;
        }
    }
}

new Widgets_Conditions();