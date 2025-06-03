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
class ACF_Fields extends \Bricks\Element {
	public $category = 'general';
	public $name     = 'fea-acf-fields';
	public $icon     = 'ti-layout-tab';
	public $scripts  = [];
	public $nestable = false;
	public $current_control_group = null;
	use Traits\Controls;

	public function get_label() {
		return esc_html__( 'ACF Fields', 'bricks' );
	}

	public function get_keywords() {
		return array(
			'acf',
			'fields',
			'frontend editing',
			'frontend form',
			'frontend dashboard',
		);
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
		return __( 'ACF Fields', 'acf-frontend-form-element' );
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
		return array( 'fea-acf-fields' );
	}

	

	public function set_controls() {
		$this->controls['_width']['default'] = '100%';

		$this->register_field_section();
		$this->register_validation_section();
		//$this->register_style_tab_controls();
	}


	public function register_field_section() {
		$this->add_control_group(
			'fields_section',
			array(
				'title' => __( 'Fields', 'acf-frontend-form-element' ),
				'tab'   => 'content',
			)
		);

	/* 	$this->add_control(
			'show_field_label',
			array(
				'label'        => __( 'Show Labels', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
				'default'      => true,
			)
		); */

		//select fields or field groups
		$this->add_control(
			'fields_select',
			array(
				'label'   => __( 'Select Fields', 'acf-frontend-form-element' ),
				'type'    => 'select',
				'optionsAjax' => [
					'action'   => 'fea_get_acf_fields',
					'groups' => true,
				],
				'multiple'    => true,
				'searchable'  => true,
				'placeholder' => esc_html__( 'Select Fields', 'bricks' ),
			)
		);

		//exclude fields	
		$this->add_control(
			'exclude_fields',
			array(
				'label'   => __( 'Exclude Fields', 'acf-frontend-form-element' ),
				'type'    => 'select',
				'optionsAjax' => [
					'action'   => 'fea_get_acf_fields_exclude',
					'groups' => false,
				],
				'multiple'    => true,
				'searchable'  => true,
				'placeholder' => esc_html__( 'Select Fields', 'bricks' ),
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

		
		$this->field_specific_controls();
		
	
		//$this->custom_fields_control();
		

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
	 * Render fields widget output on the frontend.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	public function render(){
		global $fea_form, $fea_instance;

		$wg_id = $this->id;
		
		$settings = $this->settings;
		
		

		
		$field_ids = $settings['fields_select'] ?? [];
		$exclude_ids = $settings['exclude_fields'] ?? [];

		if( is_string( $field_ids ) ){
			$field_ids = explode( ',', $field_ids );
		}

		$this->set_attribute( '_root', 'class', 'acf-fields-wrapper' );

		echo "<div {$this->render_attributes( '_root' )}>";

		$editor_mode = \Bricks\Helpers::get_editor_mode( get_the_ID() );
		if( $editor_mode && ! $field_ids ){
			echo '<div class="acf-fields-note">' . __( 'No fields selected', 'acf-frontend-form-element' ) . '</div>';
			return;	
		}

		foreach( $field_ids as $field_id ){
			//if it starts with 'group_' it is a field group
			if( strpos( $field_id, 'group_' ) === 0 ){
				$fields = acf_get_fields( $field_id );
				if( $fields ){
					foreach( $fields as $field ){
						//if the field is excluded, skip it
						if( in_array( $field['key'], $exclude_ids ) ) continue;

						if( false === $field ) continue; 
						$this->render_field( $field, $settings );
					}
				}
			}else{
				$field = acf_get_field( $field_id );

				$this->render_field( $field, $settings );
			}
		}
		echo '</div>';


	}

	public function render_field( $field, $settings ){
		global $fea_form, $fea_instance;
		$form_display = $fea_instance->form_display;

		if( ! empty( $settings['field_display_mode'] ) && 'read_only' == $settings['field_display_mode'] ){
			$field['frontend_admin_display_mode'] = 'read_only';
			$field['with_edit'] = $settings['field_inline_edit'];
			$field['no_values_message'] = $field['no_values_message'] ?? $settings['no_values_message'];
		}

		if( ! bricks_is_builder() ){
			$field = $this->prepare_field( $field );
			if( ! $field ) return false;

		}
		if( ! empty( $settings['field_display_mode'] ) && 'read_only' == $settings['field_display_mode'] ){
			echo $fea_instance->dynamic_values->render_field_display( $field );
		}else{
			$form_display->render_field_wrap( $field );
			
			$fea_form['rendered_field'] = true;
		}
	}


	public function prepare_field( $field ){
		if( ! $field ) return false;
		global $fea_form, $fea_instance;
		$form_display = $fea_instance->form_display;


		$field['builder'] = 'bricks';
		
		$field = $form_display->get_field_data_type( $field, $fea_form );
				
		if( ! $field ) return false;

		if ( ! isset( $field['value'] )
			|| $field['value'] === null
		) {
			$field = $form_display->get_field_value( $field, $fea_form );
		}
		
		return $field;
	}


	
	

	public function get_acf_fields() {
		\Bricks\Ajax::verify_request( 'bricks-nonce-builder' );

		$field_groups = acf_get_field_groups();

		$return = [];
		
		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $field_group ) {
				if( $_GET['groups'] ) $return[ $field_group['key'] ] = sprintf( 'All fields from  %s', $field_group['title'] );
				$fields = acf_get_fields( $field_group['key'] );
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $field ) {
						$return[ $field['key'] ] = $field['label'];
					}
				}
			}
		}
		
		wp_send_json_success( $return );
	}
	public function get_acf_fields_exclude() {
		\Bricks\Ajax::verify_request( 'bricks-nonce-builder' );

		$field_groups = acf_get_field_groups();

		$return = [];
		
		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $field_group ) {
				$fields = acf_get_fields( $field_group['key'] );
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $field ) {
						$return[ $field['key'] ] = $field['label'];
					}
				}
			}
		}
		
		wp_send_json_success( $return );
	}


	public function __construct( $settings = [] ) {
		parent::__construct( $settings );


		add_action( 'wp_ajax_fea_get_acf_fields', [ $this, 'get_acf_fields' ] );
		add_action( 'wp_ajax_fea_get_acf_fields_exclude', [ $this, 'get_acf_fields_exclude' ] );


	}
}
