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
class Base_Field extends \Bricks\Element {
	public $category = 'general';
	public $name     = 'fea-base-field';
	public $icon     = 'ti-layout-tab';
	public $scripts  = [];
	public $nestable = false;
	public $current_control_group = null;
	use Traits\Controls;

	public function get_label() {
		return esc_html__( 'Fields', 'bricks' );
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
			'show_field_label'     => 'true',
			'field_label'        => '',
			'field_name'         => '',
			'field_placeholder'  => '',
			'field_default_value' => '',
			'field_instruction'  => '',
			'prepend'            => '',
			'append'             => '',
			'custom_fields_save' => 'post',
			'field_required'     => false,
			'show_error_message' => true,
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

	

	public function set_controls() {
		$this->register_field_section();
		$this->register_validation_section();
		//$this->register_style_tab_controls();
	}


	public function register_field_section() {
		$this->add_control_group(
			'fields_section',
			array(
				'title' => __( 'Field', 'acf-frontend-form-element' ),
				'tab'   => 'content',
			)
		);

		$this->add_control(
			'show_field_label',
			array(
				'label'        => __( 'Show Label', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
				'default'      => true,
			)
		);

		$defualt_label = str_replace( ' Field', '', $this->get_title() );
		$this->add_control(
			'field_label',
			array(
				'label'       => __( 'Label', 'acf-frontend-form-element' ),
				'type'        => 'text',
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
					'type'        => 'text',
					'name'        => 'field_name',
					'default'     => $meta_name,
					'label_block' => true,
					'instruction' => 'This is the name of the field in the meta table in the database. It should be unique and not contain spaces. Use underscores instead of spaces. For example: text_field',
					'placeholder' => $meta_name,
				)
			);
		}

		//required
		$this->add_control(
			'field_required',
			array(
				'label'        => __( 'Required', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
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
				'type'    => 'select',
				'default' => 'edit',
				'options' => array(
					'edit'	=> __( 'Edit', 'acf-frontend-form-element' ),
					'read_only'	=> __( 'Read', 'acf-frontend-form-element' ),
					'hidden'	=> __( 'Hidden', 'acf-frontend-form-element' ),
				)
			)
		);

		//if read only, add "allow edit" option
		$this->add_control(
			'field_inline_edit',
			array(
				'label'        => __( 'Inline Edit', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'return_value' => 'true',
				'default'      => '',
				'required'    => array(
					'field_display_mode', '=', 'read_only',
				),
			)
		);

		//no value placeholder textarea
		$this->add_control(
			'no_values_message',
			array(
				'label'       => __( 'No Value Message', 'acf-frontend-form-element' ),
				'type'        => 'textarea',
				'label_block' => true,
				'placeholder' => __( 'Undefined Value', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
				'required'    => array(
					'field_display_mode', '=', 'read_only',
				),
			)
		);

		$this->add_control(
			'field_instruction',
			array(
				'label'       => __( 'Instructions', 'acf-frontend-form-element' ),
				'type'        => 'textarea',
				'label_block' => true,
				'placeholder' => __( 'Field Instruction', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);
		
		$this->field_specific_controls();
		
	
		$this->custom_fields_control();
		

	}

	public function field_specific_controls(){
		// Override in child class
	}

	public function field_specific_validation(){
		// Override in child class
	}

	protected function register_validation_section() {
		$this->add_control_group(
			'validation_section',
			array(
				'title' => __( 'Validation', 'acf-frontend-form-element' ),
				'tab'   => 'content',
			)
		);

		//whether to show error message
		$this->add_control(
			'show_error_message',
			array(
				'label'        => __( 'Show Error Message', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
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
				'type'        => 'text',
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
				'type'        => 'text',
				'label_block' => true,
				'placeholder' => __( 'Field is invalid', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$this->field_specific_validation();


	}


	public function register_style_tab_controls() {
		if ( ! isset( fea_instance()->pro_features ) ) {

			$this->start_controls_section(
				'style_promo_section',
				array(
					'label' => __( 'Styles', 'acf-frontend-form-element' ),
					'tab'   => 'style',
				)
			);

			$this->add_control(
				'styles_promo',
				array(
					'type'            => 'raw_html',
					'raw'             => __( '<p><a target="_blank" href="https://www.dynamiapps.com/"><b>Go Pro</b></a> to unlock styles.</p>', 'acf-frontend-form-element' ),
					'content_classes' => 'acf-fields-note',
				)
			);


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
	public function prepare_field( $form_data = array(), $id = null, $children = [] ) {
		global $fea_form, $fea_instance, $wp_query;		
		$current_post_id = $wp_query->get_queried_object_id();


		$field_id = $id ?? $this->id ?? null;

		if( $this->id ){
			$field_id = ! empty( $fea_form['id'] ) ? $fea_form['id'] . '_' . $this->id : $current_post_id . '_bricks_' . $this->id;
		}

		$settings = $this->settings;

		$form_display = $fea_instance->form_display;
		$current_id = $fea_instance->bricks->get_current_post_id();


		$settings = wp_parse_args( $settings, $this->get_field_defaults() );	

		$field_name = $this->get_meta_name();
		$show_field_label = $settings['show_field_label'] ?? true;
		$field = array(
			'label'       => $settings['field_label'],
			'field_label_hide'  => ! $show_field_label,
			'name'        => $field_name,
			'_name'		  => $field_name,
			'description' => $settings['field_instruction'],
			'key' => $field_id,
			'required' => !empty( $settings['field_required'] ),
			'required_message' => $settings['field_required_message'] ?? '',
			'no_error_message' => ! ( $settings['show_error_message'] ?? false ),
			'validation_message' => $settings['field_validation_message'] ?? '',
		);
	

		$field = $this->get_field_data( $field );		
		
		$field = $form_display->get_field_data_type( $field, $fea_form );
	
		if( ! $field ) return;

		$editor_mode = \Bricks\Helpers::get_editor_mode( get_the_ID() );

		if ( ( ! isset( $field['value'] )
			|| $field['value'] === null ) && empty( $editor_mode )
		) {
			$field = $form_display->get_field_value( $field, $fea_form );
		}

		if( $fea_form )	$fea_form['fields'][$field_id] = $field;

		if( ! empty( $settings['field_display_mode'] ) && 'read_only' == $settings['field_display_mode'] ){
			$field['frontend_admin_display_mode'] = 'read_only';
			$field['no_values_message'] = $settings['no_values_message'];
			$field['with_edit'] = 'true' == $settings['field_inline_edit'];
			$field['display'] = true;
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
	public function render(){
		global $fea_instance;
		$form_display = $fea_instance->form_display;

		$settings = $this->settings;


		if( empty( $settings ) ) return;

		$field = $this->prepare_field();

		if( ! empty( $settings['field_display_mode'] ) && 'read_only' == $settings['field_display_mode'] ){
			//$source = $fea_instance->bricks->get_current_post_id();
			echo $fea_instance->dynamic_values->render_field_display( $field );
		}else{
			$form_display->render_field_wrap( $field );
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

	
	public function get_field_element( $field, $key ){

		if ( strpos( $key, '_bricks_' ) === false ) {
			return false;
		}

		// Get Template/page id and element id
		$ids = explode( '_bricks_', $key );

		// If there is no element id, there is no reason to continue 
		if( empty( $ids[1] ) ) return false; 

		$element = \Bricks\Helpers::get_element_data( $ids[0], $ids[1] );
		if( $element ){					
			return $this->prepare_field($element['element']['settings'], $key, $element['elements']);
		}
		return false;

	}


	public function __construct( $settings = [] ) {
		parent::__construct( $settings );

		add_filter( 'frontend_admin/fields/get_field', [ $this, 'get_field_element' ], 10, 2 );

	}
}
