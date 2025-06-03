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
class Textarea_Field extends Base_Field {

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
		return 'fea_textarea_field';
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
		return 'textarea_field';
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
		return __( 'Textarea Field', 'acf-frontend-form-element' );
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
	
		//rows
		$this->add_control(
			'rows',
			[
				'label' => __( 'Rows', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 4,
				'min' => 1,
				'max' => 10,
				'step' => 1,
			]
		);

		//character limit
		$this->add_control(
			'character_limit',
			[
				'label' => __( 'Character Limit', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'min' => 0,
				'step' => 1,
			]
		);

		//field placeholder
		$this->add_control(
			'field_placeholder',
			[
				'label' => __( 'Placeholder', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
			]
		);
		
		//field default value
		$this->add_control(
			'field_default_value',
			[
				'label' => __( 'Default Value', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
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
		$field['type'] = 'textarea';
		
		$field['placeholder'] = $this->get_settings( 'field_placeholder' );
		$field['default_value'] = $this->get_settings( 'field_default_value' );
		$field['maxlength'] = $this->get_settings( 'character_limit' );
		$field['rows'] = $this->get_settings( 'rows' );

		return $field;
	}





}
