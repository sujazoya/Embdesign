<?php
namespace Frontend_Admin\Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontendForm extends \Bricks\Element {
	public $category = 'general';
	public $name     = 'frontend-form';
	public $icon     = 'ti-layout-tab';
	public $scripts  = [];
	public $nestable = true;
	public $current_control_group = null;
	use Traits\Controls;

	public function get_label() {
		return esc_html__( 'Frontend Form', 'bricks' ) . ' (' . esc_html__( 'Nestable', 'bricks' ) . ')';
	}

	public function get_keywords() {
		return [ 'nestable' ];
	}


	public function set_controls() {
		$this->add_control_group(
			'form',
			array(
				'title' => esc_html__( 'Form', 'bricks' ),
				'tab'   => 'content',
			)
		);

		$this->custom_fields_control();

		$this->form_actions_controls();

		$this->controls['_conditions']['default'] = [
			[
				[
					'key' => 'user_role',
					'value' => ['administrator'],
				]
			],
			[
				[
					'key' => 'user_id',
					'value' => '{author_id}',
				]
			]
		];

		global $fea_instance;
		if ( isset( $fea_instance->remote_actions ) ) {
			$remote_actions = $fea_instance->remote_actions;
			foreach ( $remote_actions as $action ) {
				$action->bricks_settings_section( $this );
			}
		}
		
		$local_actions = $fea_instance->local_actions;
		
		foreach ( $local_actions as $name => $action ) {			
			$action->bricks_settings_section( $this );
		}
		

	
	}

	public function form_actions_controls() {
		$this->add_control_group(
			'form_actions',
			array(
				'title' => esc_html__( 'Actions', 'bricks' ),
				'tab'   => 'content',
			)
		);

		$redirect_options = array(
			'current'     => __( 'Stay on Current Page/Post', 'acf-frontend-form-element' ),
			'custom_url'  => __( 'Custom Url', 'acf-frontend-form-element' ),
			'referer_url' => __( 'Referer', 'acf-frontend-form-element' ),
			'post_url'    => __( 'Post Url', 'acf-frontend-form-element' ),
			'none'       => __( 'None', 'acf-frontend-form-element' ),
		);

		$redirect_options = apply_filters( 'frontend_admin/forms/redirect_options', $redirect_options );

		$this->add_control(
			'redirect',
			array(
				'label'       => __( 'Redirect After Submit', 'acf-frontend-form-element' ),
				'type'        => 'select',
				'default'     => 'current',
				'options'     => $redirect_options,
				'render_type' => 'none',
			)
		);
	
		$this->add_control(
			'redirect_action',
			array(
				'label'       => __( 'After Reload', 'acf-frontend-form-element' ),
				'type'        => 'select',
				'default'     => 'clear',
				'options'     => array(
					''		=> __( 'Nothing', 'acf-frontend-form-element' ),
					'clear' => __( 'Clear Form', 'acf-frontend-form-element' ),
					'edit'  => __( 'Edit Content', 'acf-frontend-form-element' ),
				),
				'render_type' => 'none',
			)
		);
		$this->add_control(
			'custom_url',
			array(
				'label'       => __( 'Custom Url', 'acf-frontend-form-element' ),
				'type'        => 'text',
				'placeholder' => __( 'Enter Url Here', 'acf-frontend-form-element' ),
				'options'     => false,
				'show_label'  => false,
				'required'   => array(
					'redirect', '=', 'custom_url',
				),
				'dynamic'     => array(
					'active' => true,
				),
				'render_type' => 'none',
			)
		);

		$this->add_control(
			'show_update_message',
			array(
				'label'        => __( 'Show Success Message', 'acf-frontend-form-element' ),
				'type' 		   => 'checkbox',
				'default'      => true,
				'render_type'  => 'none',
			)
		);
		$success = $this->form_defaults['success_message'] ?? __( 'Form has been submitted successfully.', 'acf-frontend-form-element' );
		$this->add_control(
			'update_message',
			array(
				'label'       => __( 'Submit Message', 'acf-frontend-form-element' ),
				'type'        => 'textarea',
				'default'     => $success,
				'placeholder' => $success,
				'dynamic'     => array(
					'active' => true,
				),
				'required'   => array(
					'show_update_message', '=', true,
				),
			)
		);
		$this->add_control(
			'error_message',
			array(
				'label'       => __( 'Error Message', 'acf-frontend-form-element' ),
				'type'        => 'textarea',
				'description' => __( 'There shouldn\'t be any problems with the form submission, but if there are, this is what your users will see. If you are expeiencing issues, try and changing your cache settings and reach out to ', 'acf-frontend-form-element' ) . 'support@dynamiapps.com',
				'default'     => __( 'Please fix the form errors and try again.', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
				'render_type' => 'none',
			)
		);
		//default required messaged
		$this->add_control(
			'required_message',
			array(
				'label'       => __( 'Required Message', 'acf-frontend-form-element' ),
				'type'        => 'text',
				'default'     => __( 'This field is required.', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
				'render_type' => 'none',
			)
		);
		//email veified message
		$this->add_control(
			'email_verified_message',
			array(
				'label'       => __( 'Email Verified Message', 'acf-frontend-form-element' ),
				'type'        => 'text',
				'default'     => __( 'Email has been verified.', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active' => true,
				),
				'render_type' => 'none',
				'required'   => array(
					'save_all_data', '=', 'verify_email'
				),
			)
		);

	}

	/**
	 * Get child elements
	 *
	 * @return array Array of child elements.
	 *
	 * @since 1.5
	 */
	public function get_nestable_children() {
		/**
		 * NOTE: Required classes for element styling & script:
		 *
		 * .tab-menu
		 * .tab-title
		 * .tab-content
		 * .tab-pane
		 */
		return [
			// Content
			[
				'name'     => 'block',
				'label'    => esc_html__( 'Form content', 'bricks' ),
				'settings' => [
					'_hidden' => [
						'_cssClasses' => 'tab-content',
					],
				],
				'children' => [
					[
						'name'     => 'fea-text-field',
						'settings' => [
							'field_label' => esc_html__( 'Text Field', 'frontend-admin' ),
							'style' => 'primary'
						],
					],
					[
						'name'     => 'fea-submit-button',
						'settings' => [
							'text' => esc_html__( 'Submit Form', 'frontend-admin' ),
							'style' => 'primary'
						],
					],
				],
			],
		];
	}

	public function get_form_element( $form, $key ){

		if ( strpos( $key, '_bricks_' ) === false ) {
			return false;
		}
		$key = explode( ':', $key )[0];

		// Get Template/page id and element id
		$ids = explode( '_bricks_', $key );

		// If there is no element id, there is no reason to continue 
		if( empty( $ids[1] ) ) return false; 

		$element = \Bricks\Helpers::get_element_data( $ids[0], $ids[1] );


		if( $element ){
			
			return $this->prepare_form( $element['element']['settings'], $key, $element['elements'] );
		}
		return false;

	}
	
	public function prepare_form( $form_data = array(), $id = null, $children = [] ) {
		global $fea_instance, $fea_form, $wp_query;
		$form_display = $fea_instance->form_display;


		$current_post_id = $wp_query->get_queried_object_id();
		if( $id ){
			$element_id = explode( '_bricks_', $id )[1] ?? null;	
		}else{
			$element_id = $form_data['key'] ?? null;
		}
		$form_data['submit_actions'] = true;

		$form_data['post_to_edit'] = $post_to_edit = $form_data['post_to_edit'] ?? 'current_post';

		if( !$id ){
			$form_data['id'] = $form_data['ID'] = $current_post_id . '_bricks_' . $this->id;
		}else{
			$form_data['id'] = $form_data['ID'] = $id;
		}

		$id = $id ?? $this->id ?? null;

		
	
		if( empty( $fea_form['id'] ) || $fea_form['id'] !== $id ){
			$fea_form =  $form_display->validate_form( $form_data );
		}

	
		if( $children ){
			$fields = [];
			foreach( $children as $child ){
				$name = $child['name'] ?? null;
				$settings = $child['settings'] ?? null;

				if( ! $name || ! $settings ) continue;

				if( $child['id'] != $element_id ) continue;

				if( ! empty( $child['children'] ) ){
					foreach( $child['children'] as $child_id ){

						$_fields = $this->prepare_fields( $child_id, $children, $fields, $id );
						if( $_fields ){
							$fields = array_merge( $fields, $_fields );
						}
					}
				}


			}

			$fea_form['fields'] = $fields;


		}


		return $fea_form;

		
	}

	public function prepare_fields( $child_id, $children, $fields = [], $id = null ){

		global $fea_instance, $fea_form;
		$form_display = $fea_instance->form_display;

		//find the child with the id
		$child = array_filter( $children, function( $child ) use ( $child_id ){
			return $child['id'] == $child_id;
		} );
		$child = array_shift( $child );
		if( ! $child ) return false;

		$name = $child['name'] ?? null;
		$settings = $child['settings'] ?? null;

		if( ! $name || ! $settings ) return false;

		

		if( ! empty( $child['children'] ) ){
			foreach( $child['children'] as $child_id ){
				$_fields = $this->prepare_fields( $child_id, $children, $fields, $id );
			

				if( $_fields ){
					$fields = array_merge( $fields, $_fields );
				}

			}
		}else{
			$field = $this->prepare_field( $name, $settings, $child_id );
		
			if( ! $field ) return false;
			$name = $fea_form['ID'] . '_' . $child_id;
			$fields[ $name ] = $field;
		}
		
		return $fields;
		
	}

	public function prepare_field( $name, $settings, $id = null ){
		global $fea_instance, $fea_form;
		$form_display = $fea_instance->form_display;

		$id = $fea_form['ID'] . '_' . $id;
				
		if ( strpos( $name, 'fea-' ) === 0 && strpos( $name, '-field' ) !== false ) {

			$field_type = str_replace( [ 'fea-', '-field' ], '', $name );
			$field_type = str_replace( '-', '_', $field_type );

			$field = [
				'type' => $field_type,
				'key'   => $id,
				'builder' => 'bricks',
				'label' => $settings['field_label'] ?? '',
				'name' => $settings['field_name'] ?? $id,
				'placeholder' => $settings['field_placeholder'] ?? '',
				'default_value' => $settings['field_default_value'] ?? '',
				'required' => $settings['field_required'] ?? false,
				'maxlength' => $settings['field_maxlength'] ?? false,
			];
		
			$field = $form_display->get_field_data_type( $field, $fea_form );
		
			if( ! $field ) return false;

			if ( ! isset( $field['value'] )
				|| $field['value'] === null
			) {
				$field = $form_display->get_field_value( $field, $fea_form );
			}

			return $field;

		}
		return false;

	}

	public function render() {
		global $fea_instance, $fea_form;
		$settings = $this->settings;
		$form_display = $fea_instance->form_display;


		$fea_form = $this->prepare_form( $settings );
		$form_display->maybe_show_success_message( $fea_form );

		echo "<div {$this->render_attributes( '_root' )}>";

		$this->set_attribute( 'frontend-form', 'class', 'frontend-form' );

		echo "<form {$this->render_attributes( 'frontend-form' )}>";

		// Render children elements (= individual items)
		echo \Bricks\Frontend::render_children( $this );

		$fea_instance->form_display->form_render_data( $fea_form );

		echo '</form>';

		echo '</div>';


		$fea_form = null;
	}

	public function get_form_fields( $fields, $form, $key ) {
		if ( strpos( $key, '_bricks_' ) === false ) {
			return $fields;
		}

		$key = explode( ':', $key )[0];

		// Get Template/page id and element id
		$ids = explode( '_bricks_', $key );

		// If there is no element id, there is no reason to continue 
		if( empty( $ids[1] ) ) return $fields; 

		$element = \Bricks\Helpers::get_element_data( $ids[0], $ids[1] );
		if( $element ){
			$fields = $this->prepare_fields( $ids[1], $element['elements'], $fields, $key );

		}
		return $fields;
	}

	public function __construct( $settings = [] ) {
		parent::__construct( $settings );

		add_filter( 'frontend_admin/forms/get_form', [ $this, 'get_form_element' ], 10, 2 );
		add_filter( 'frontend_admin/submissions/form_fields', [ $this, 'get_form_fields' ], 10, 3 );

	}
 
}
