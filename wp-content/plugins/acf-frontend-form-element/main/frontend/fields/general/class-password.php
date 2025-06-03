<?php
namespace Frontend_Admin\Field_Types;

if ( ! class_exists( 'password' ) ) :

	class password extends Field_Base {


		/*
		*  initialize
		*
		*  This function will setup the field type data
		*
		*  @type    function
		*  @date    5/03/2014
		*  @since   5.0.0
		*
		*  @param   n/a
		*  @return  n/a
		*/

		function initialize() {

			// vars
			$this->name     = 'password';
			$this->label    = __( 'Password', 'acf' );
			$this->defaults = array(
				'placeholder' => '',
				'prepend'     => '',
				'append'      => '',
			);




		}


		/*
		*  render_field()
		*
		*  Create the HTML interface for your field
		*
		*  @param   $field - an array holding all the field's data
		*
		*  @type    action
		*  @since   3.6
		*  @date    23/01/13
		*/

		function render_field( $field ) {

			$preview = $field['preview_password'] ?? false;

			if( $preview ) $field['class'] .= ' fea-password';

			acf_get_field_type( 'text' )->render_field( $field );

			if( ! $preview ) return;

			//render dashicon for password visibility
			echo '<span class="dashicons dashicons-visibility fea-password-toggle"></span>';

		}



		/**
		 * Renders the field settings used in the "Presentation" tab.
		 *
		 * @since 6.0
		 *
		 * @param array $field The field settings array.
		 * @return void
		 */
		function render_field_settings( $field ) {
			
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Placeholder Text', 'acf' ),
					'instructions' => __( 'Appears within the input', 'acf' ),
					'type'         => 'text',
					'name'         => 'placeholder',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Prepend', 'acf' ),
					'instructions' => __( 'Appears before the input', 'acf' ),
					'type'         => 'text',
					'name'         => 'prepend',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Append', 'acf' ),
					'instructions' => __( 'Appears after the input', 'acf' ),
					'type'         => 'text',
					'name'         => 'append',
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Preview Password', 'acf' ),
					'instructions' => __( 'Allow user to preview the password as plain text', 'acf' ),
					'type'         => 'true_false',
					'name'         => 'preview_password',
					'ui'           => 1,
				)
			);
		}

	}




endif; // class_exists check


