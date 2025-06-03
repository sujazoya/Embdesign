<?php

namespace Frontend_Admin\Elementor\Widgets;

use  Elementor\Controls_Manager;
use  Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**

 *
 * @since 1.0.0
 */
class Base_Field extends Widget_Base {

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
		return 'base_field';
	}


	protected function is_dynamic_content(): bool {
		return true;
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
		return 'base_field';
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
		return __( 'Base Field', 'acf-frontend-form-element' );
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

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the acf ele form widget belongs to.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'frontend-admin-fields' );
	}

	/**
	 * Register acf ele form widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->register_field_section();
		$this->register_validation_section();

		$this->register_style_tab_controls();

		do_action( 'frontend_admin/styles_controls', $this );

	}


	protected function register_field_section() {
		$this->start_controls_section(
			'fields_section',
			array(
				'label' => __( 'Field', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'field_label_on',
			array(
				'label'        => __( 'Show Label', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'return_value' => 'true',
				'default'      => 'true',
			)
		);

		$defualt_label = str_replace( ' Field', '', $this->get_title() );
		$this->add_control(
			'field_label',
			array(
				'label'       => __( 'Label', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __( 'Field Label', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
				'default' => $defualt_label ?? '',
			)
		);


		if( $this->is_meta_field() ){
			$meta_name = $this->get_meta_name();
			$this->add_control(
				'field_name',
				array(
					'label'       => __( 'Meta Name', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXT,
					'name'        => 'field_name',
					'default'     => $meta_name,
					'label_block' => true,
					'instructions' => 'This is the name of the field in the meta table in the database. It should be unique and not contain spaces. Use underscores instead of spaces. For example: text_field',
					'placeholder' => $meta_name,
				)
			);
		}

		//required
		$this->add_control(
			'field_required',
			array(
				'label'        => __( 'Required', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'return_value' => 'true',
				'default'      => '',
			)
		);

		//display mode
		$this->add_control(
			'field_display_mode',
			array(
				'label'   => __( 'Display Mode', 'acf-frontend-form-element' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'edit',
				'options' => array(
					'edit'	=> __( 'Edit', 'acf-frontend-form-element' ),
					'read_only'	=> __( 'Read Only', 'acf-frontend-form-element' ),
					'hidden'	=> __( 'Hidden', 'acf-frontend-form-element' ),
				)
			)
		);

		//if read only, add "allow edit" option
		$this->add_control(
			'field_inline_edit',
			array(
				'label'        => __( 'Inline Edit', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'return_value' => 'true',
				'default'      => '',
				'condition'    => array(
					'field_display_mode' => 'read_only',
				),
			)
		);

		//no value placeholder textarea
		$this->add_control(
			'no_values_message',
			array(
				'label'       => __( 'No Value Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXTAREA,
				'label_block' => true,
				'placeholder' => __( 'Undefined Value', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
				'condition'   => array(
					'field_display_mode' => 'read_only',
				),
			)
		);

		$this->add_control(
			'field_instruction',
			array(
				'label'       => __( 'Instructions', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __( 'Field Instruction', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);
		
		$this->field_specific_controls();
		
	
		if( $this->is_meta_field() ){
			$this->custom_fields_control();
		}
		$this->end_controls_section();

	}

	public function field_specific_controls(){
		// Override in child class
	}

	public function field_specific_validation(){
		// Override in child class
	}

	protected function register_validation_section() {
		$this->start_controls_section(
			'validation_section',
			array(
				'label' => __( 'Validation', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		//whether to show error message
		$this->add_control(
			'show_error_message',
			array(
				'label'        => __( 'Show Error Message', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'return_value' => 'true',
				'default'      => 'true',
			)
		);

		//message to show if field is required
		$this->add_control(
			'field_required_message',
			array(
				'label'       => __( 'Required Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __( 'Field is required', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		//message to show if other validation fails
		$this->add_control(
			'field_validation_message',
			array(
				'label'       => __( 'Validation Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => __( 'Field is invalid', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->field_specific_validation();


		$this->end_controls_section();
	}
	
	public function custom_fields_control( $repeater = false ) {
		
		$controls_settings = array(
			'label'     => __( 'Save Custom Fields to...', 'acf-frontend-form-element' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'post',

		);

		$custom_fields_options = array(
			''	=> __( 'Form Default', 'acf-frontend-form-element' ),
			'submission' => __( 'Submission', 'acf-frontend-form-element' ),
			'post' => __( 'Post', 'acf-frontend-form-element' ),
			'user' => __( 'User', 'acf-frontend-form-element' ),
			'term' => __( 'Term', 'acf-frontend-form-element' ),
		);
		if ( ! empty( fea_instance()->pro_features ) ) {
			$custom_fields_options['options'] = __( 'Site Options', 'acf-frontend-form-element' );
			if ( class_exists( 'woocommerce' ) ) {
				$custom_fields_options['product'] = __( 'Product', 'acf-frontend-form-element' );
			}
		}
		$controls_settings['options'] = $custom_fields_options;
		$this->add_control( 'custom_fields_save', $controls_settings );

	}


	public function register_style_tab_controls() {
		if ( empty( fea_instance()->pro_features ) ) {

			$this->start_controls_section(
				'style_promo_section',
				array(
					'label' => __( 'Styles', 'acf-frontend-form-element' ),
					'tab'   => Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'styles_promo',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => __( '<p><a target="_blank" href="https://www.dynamiapps.com/"><b>Go Pro</b></a> to unlock styles.</p>', 'acf-frontend-form-element' ),
					'content_classes' => 'acf-fields-note',
				)
			);

			$this->end_controls_section();

		} else {
			do_action( 'frontend_admin/style_tab_settings', $this );
		}
	}

	

	/**
	 * Prepare fields widget output on the frontend.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	public function prepare_field( $key = false ) {
		global $fea_form, $fea_instance;		

		$wg_id = $this->get_id();
		$settings = $this->get_settings_for_display();
		$form_display = $fea_instance->form_display;
		$current_id = $fea_instance->elementor->get_current_post_id();

		$key = $key ? $key : $current_id . '_elementor_' . $this->get_id();

		$field = array(
			'label'       => $settings['field_label'],
			'field_label_hide'  => !$settings['field_label_on'],
			'name'        => $settings['field_name'] ?? $this->get_meta_name(),
			'_name'		  => $settings['field_name'] ?? $this->get_meta_name(),
			'instructions' => $settings['field_instruction'],
			'key' => $key,
			'required' => 'true' == $settings['field_required'],
			'required_message' => $settings['field_required_message'],
			'no_error_message' => 'true' !== $settings['show_error_message'],
			'validation_message' => $settings['field_validation_message'],
			'custom_fields_save' => $settings['custom_fields_save'] ?? 'post',
		);
		$field = $this->get_field_data( $field );		

		$field['builder'] = 'elementor';
		$field = $form_display->get_field_data_type( $field, $fea_form );

		if( ! $field ) return false;

		if ( ! isset( $field['value'] )
			|| $field['value'] === null
		) {
			$field = $form_display->get_field_value( $field, $fea_form );
		}

		if( $fea_form ){
			$fea_form['fields'][$key] = $field;
		}

		if( 'read_only' == $settings['field_display_mode'] ){
			$field['frontend_admin_display_mode'] = 'read_only';
			$field['no_values_message'] = $settings['no_values_message'];
			$field['with_edit'] = 'true' == $settings['field_inline_edit'];
			$field['wrapper'] = [
				'class' => 'fea-read-only-field',
			];
		}
		return $field;

	}

	/**
	 * Render fields widget output on the frontend.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function render(){
		global $fea_instance, $fea_form;
		$form_display = $fea_instance->form_display;

		$settings = $this->get_settings_for_display();

		$field = $this->prepare_field();

		if( ! $field ) return;

		if( $settings['field_display_mode'] == 'read_only' ){
			//$source = $fea_instance->elementor->get_current_post_id();
			echo $fea_instance->dynamic_values->render_field_display( $field );
		}else{
			$form_display->render_field_wrap( $field );

			if($fea_form) $fea_form['rendered_field'] = true;
		}

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
		return $field;
	}

}
