<?php

namespace Frontend_Admin\Elementor\Widgets;

use  Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**

 *
 * @since 1.0.0
 */
class Number_Field extends Base_Field {

	/**
	 * Get widget name.
	 *
	 * Retrieve acf ele form widget name.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'fea_number_field';
	}


	/**
	 * Get widget defaults.
	 *
	 * Retrieve field widget defaults.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget defaults.
	 */
	public function get_field_defaults() {
		return array(
			'field_label_on'     => 'true',
			'field_label'        => '',
			'field_name'         => '',
			'field_placeholder'  => '',
			'field_default_value' => '',
			'field_instruction'  => '',
			'prepend'            => '',
			'append'             => '',
			'custom_fields_save' => 'post',
		);
	
	}

	/**
	 * Is meta field.
	 * 
	 * Check if the field is a meta field.
	 * 
	 * @since 1.0.0
	 */
	public function is_meta_field(){
		return true;
	}


	/**
	 * 
	 * Get meta name.
	 * 
	 * Retrieve the meta name of the field.
	 * 
	 * @since 1.0.0
	 */

	public function get_meta_name(){
		return 'number_field';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve acf ele form widget title.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Number Field', 'acf-frontend-form-element' );
	}

	 /**
	  * Get widget icon.
	  *
	  * Retrieve acf ele form widget icon.
	  *
	  * @since  1.0.0
	  * @access public
	  *
	  * @return string Widget icon.
	  */
	public function get_icon() {
		return 'eicon-form-horizontal frontend-icon';
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array(
			'frontend editing',
			'fields',
			'acf',
			'acf form',
		);
	}


	public function field_specific_controls() {
	
		$this->add_control(
			'field_placeholder',
			array(
				'label'       => __( 'Placeholder', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __( 'Field Placeholder', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);
		
		$this->add_control(
			'field_default_value',
			array(
				'label'       => __( 'Default Value', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::NUMBER,
				'label_block' => true,
				'placeholder' => __( 'Default Value', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'prepend',
			array(
				'label'     => __( 'Prepend', 'acf-frontend-form-element' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'append',
			array(
				'label'     => __( 'Append', 'acf-frontend-form-element' ),
				'type'      => Controls_Manager::TEXT,
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		//Min
		$this->add_control(
			'field_min',
			[
				'label' => __( 'Min', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'min' => 0,
				'step' => 1,
			]
		);
		//Max
		$this->add_control(
			'field_max',
			[
				'label' => __( 'Max', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'min' => 0,
				'step' => 1,
			]
		);
		
		//Step
		$this->add_control(
			'field_step',
			[
				'label' => __( 'Step', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'min' => 0,
				'step' => 1,
			]
		);
	
	}

	
	/**
	 * Get field data.
	 *
	 * Retrieve the field data.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @param array $field Field data.
	 *
	 * @return array Field data.
	 */
	protected function get_field_data( $field ) {
		$field['type'] = 'number';
		$field['prepend'] = $this->get_settings( 'prepend' );
		$field['append'] = $this->get_settings( 'append' );
		$field['placeholder'] = $this->get_settings( 'field_placeholder' );
		$field['default_value'] = $this->get_settings( 'field_default_value' );
		$field['min'] = $this->get_settings( 'field_min' );
		$field['max'] = $this->get_settings( 'field_max' );
		$field['step'] = $this->get_settings( 'field_step' );

		return $field;
	}





}
