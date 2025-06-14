<?php

namespace Frontend_Admin\Bricks\Elements;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**

 *
 * @since 1.0.0
 */
class Text_Field extends Base_Field {
	public $name     = 'fea-text-field';

	public function get_label() {
		return esc_html__( 'Text Field', 'bricks' );
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
		return 'text_field';
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
		return __( 'Text Field', 'acf-frontend-form-element' );
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
				'type'        => 'text',
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
				'type'        => 'text',
				'label_block' => true,
				'description' => __( 'This will populate a field if no value has been given yet. You can use shortcodes from other text fields. For example: [acf:field_name]', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);
		
		$this->add_control(
			'prepend',
			array(
				'label'     => __( 'Prepend', 'acf-frontend-form-element' ),
				'type'      => 'text',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'append',
			array(
				'label'     => __( 'Append', 'acf-frontend-form-element' ),
				'type'      => 'text',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'character_limit',
			array(
				'label'     => __( 'Character Limit', 'acf-frontend-form-element' ),
				'type'      => 'number',
				'dynamic'   => array(
					'active' => true,
				),
			)
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
		$field['type'] = 'text';
		$field['prepend'] = $this->settings['prepend'] ?? '';
		$field['append'] = $this->settings['append'] ?? '';
		$field['placeholder'] = $this->settings['field_placeholder'] ?? '';
		$field['default_value'] = $this->settings['field_default_value'] ?? '';
		$field['maxlength'] = $this->settings['character_limit'] ?? '';

		return $field;
	}





}
