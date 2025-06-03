<?php
namespace Frontend_Admin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Frontend_Admin\Classes\Validate_Form' ) ) :

	#[AllowDynamicProperties]
	class Validate_Form {

		public $errors = array();

		/*
		*  __construct
		*
		*  This function will setup the class functionality
		*
		*  @type    function
		*  @date    5/03/2014
		*  @since   5.0.0
		*
		*  @param   n/a
		*  @return  n/a
		*/

		function __construct() {

			// vars
			$this->errors = array();

			// ajax
			add_action( 'wp_ajax_frontend_admin/validate_form_submit', array( $this, 'validate_submitted_form' ), 2 );
			add_action( 'wp_ajax_nopriv_frontend_admin/validate_form_submit', array( $this, 'validate_submitted_form' ), 2 );


			add_action( 'acf/validate_value', array( $this, 'validate_acf_value' ), 2, 4 );
		}

		public function validate_acf_value( $valid, $value, $field, $input ) {
			if( empty( $_POST['acff'] ) ) return $valid;

			// allow $valid to be a custom error message
			if ( ! empty( $valid ) && is_string( $valid ) ) {

				$message = $valid;
				$valid   = false;

			}else{
				$message = $this->get_error_message( $field );
			}

			if ( ! $valid ) {

				$this->add_error( $input, $message );

			}

			// return
			return true;

		}


		public function validate_submitted_form() {
			// validate
			if ( ! feadmin_verify_ajax() ) {
				die();
			}

			do_action( 'acf/validate_save_post', $this );

			// vars
			$json = array(
				'valid'  => 1,
				'errors' => 0,
			);

			if ( ! empty( $_POST['acff'] ) ) {
				if ( ! empty( $_POST['acff']['_validate_email'] ) ) {
					$this->add_error( '', __( 'Spam Detected', 'acf' ) );
				}
				$data = wp_kses_post_deep( $_POST['acff'] ); 
				$data_types = array( 'form', 'post', 'user', 'term', 'options', 'woo_product', 'submission' );
				foreach ( $data_types as $type ) {
					if ( isset( $data[ $type ] ) ) {
						$this->validate_values( $data[ $type ], 'acff[' . $type . ']' );
					}
				}
			}
			// vars
			$errors = $this->get_errors();

			// bail ealry if no errors
			if ( ! $errors ) {
				wp_send_json_success( $json );
			}

			// update vars
			$json['valid']  = 0;
			$json['errors'] = $errors;

			// return
			wp_send_json_success( $json );

		}

		function validate_values( $values, $input_prefix = '' ) {
			global $fea_instance;
			$form_key = $_POST['_acf_form'] ?? '';

			$form = $fea_instance->form_display->get_form( $form_key );

			
			// bail ealry if form is corrupt
			if ( empty( $form ) ) {
				wp_send_json_error( __( 'No Form Data', 'acf-frontend-form-element' ) );
			}
			
			//get default required message
			if( ! empty( $_POST['_acf_required_message'] ) ){
				$default_required_message = sanitize_text_field( $_POST['_acf_required_message'] );
			}

			// bail early if empty
			if ( empty( $values ) ) {
				return;
			}
			foreach ( $values as $key => $value ) {
				$field = $fea_instance->frontend->get_field( $key );

				if( ! $field ) continue;

				$field['required_message'] = $default_required_message ?? '';

				$input = $input_prefix . '[' . $key . ']';
				// bail early if not found

				if ( isset( $field['frontend_admin_display_mode'] ) && $field['frontend_admin_display_mode'] == 'hidden' ) {
					continue;
				}

				// validate
				$this->validate_value( $value, $field, $input );

			}

		}



		/*
		*  add_error
		*
		*  This function will add an error message for a field
		*
		*  @type    function
		*  @date    25/11/2013
		*  @since   5.0.0
		*
		*  @param   $input (string) name attribute of DOM elmenet
		*  @param   $message (string) error message
		*  @return  $post_id (int)
		*/

		function add_error( $input, $message ) {

			// add to array
			$this->errors[] = array(
				'input'   => $input,
				'message' => $message,
			);

		}


		/*
		*  get_error
		*
		*  This function will return an error for a given input
		*
		*  @type    function
		*  @date    5/03/2016
		*  @since   5.3.2
		*
		*  @param   $input (string) name attribute of DOM elmenet
		*  @return  (mixed)
		*/

		function get_error( $input ) {

			// bail early if no errors
			if ( empty( $this->errors ) ) {
				return false;
			}

			// loop
			foreach ( $this->errors as $error ) {

				if ( $error['input'] === $input ) {
					return $error;
				}
			}

			// return
			return false;

		}


		/*
		*  get_errors
		*
		*  This function will return validation errors
		*
		*  @type    function
		*  @date    25/11/2013
		*  @since   5.0.0
		*
		*  @param   n/a
		*  @return  (array|boolean)
		*/

		function get_errors() {

			// bail early if no errors
			if ( empty( $this->errors ) ) {
				return false;
			}

			// return
			return $this->errors;

		}


		/*
		*  reset_errors
		*
		*  This function will remove all errors
		*
		*  @type    function
		*  @date    4/03/2016
		*  @since   5.3.2
		*
		*  @param   n/a
		*  @return  n/a
		*/

		function reset_errors() {

			$this->errors = array();

		}


		/*
		*  ajax_validate_save_post
		*
		*  This function will validate the $_POST data via AJAX
		*
		*  @type    function
		*  @date    27/10/2014
		*  @since   5.0.9
		*
		*  @param   n/a
		*  @return  n/a
		*/

		function ajax_validate_save_post() {

			// validate
			if ( ! feadmin_verify_ajax() ) {
				die();
			}

			// vars
			$json = array(
				'valid'  => 1,
				'errors' => 0,
			);

			// success
			if ( validate_save_post() ) {

				wp_send_json_success( $json );

			}

			// update vars
			$json['valid']  = 0;
			$json['errors'] = $this->get_errors();

			// return
			wp_send_json_success( $json );

		}

		function get_error_message( $field ) {
			// vars
			if( ! empty( $field['required_message'] ) ) {
				$message = $field['required_message'];
			} else {
				//get the form default required message
				$message = $_POST['_acf_required_message'] ?? '';
				
				if( ! $message ) $message = sprintf( __( '%s value is required', 'acf' ), $field['label'] );

			}

			// return
			return $message;
		}


		/*
		*  validate_value
		*
		*  This function will validate a field's value
		*
		*  @type    function
		*  @date    6/10/13
		*  @since   5.0.0
		*
		*  @param   n/a
		*  @return  n/a
		*/

		function validate_value( $value, $field, $input ) {

			// vars
			$valid   = true;

			$message = $this->get_error_message( $field );

			// valid
			if ( $field['required'] ) {

				// valid is set to false if the value is empty, but allow 0 as a valid value
				if ( empty( $value ) && ! is_numeric( $value ) ) {

					$valid = false;

				}
			}

			/**
			*  Filters whether the value is valid.
			*
			*  @date    28/09/13
			*  @since   5.0.0
			*
			*  @param   bool $valid The valid status. Return a string to display a custom error message.
			*  @param   mixed $value The value.
			*  @param   array $field The field array.
			*  @param   string $input The input element's name attribute.
			*/
			$field_name = $field['_name'] ?? $field['name'];
			$valid = apply_filters( "acf/validate_value/type={$field['type']}", $valid, $value, $field, $input );
			$valid = apply_filters( "acf/validate_value/name={$field_name}", $valid, $value, $field, $input );
			$valid = apply_filters( "acf/validate_value/key={$field['key']}", $valid, $value, $field, $input );
			$valid = apply_filters( 'acf/validate_value', $valid, $value, $field, $input );

			// allow $valid to be a custom error message
			if ( ! empty( $valid ) && is_string( $valid ) ) {

				$message = $valid;
				$valid   = false;

			}

			if ( ! $valid ) {

				$this->add_error( $input, $message );
				return false;

			}

			// return
			return true;

		}

	}

	// initialize
	fea_instance()->form_validate = new Validate_Form();

endif; // class_exists check



