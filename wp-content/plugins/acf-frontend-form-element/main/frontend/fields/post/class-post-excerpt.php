<?php
namespace Frontend_Admin\Field_Types;

if ( ! class_exists( 'post_excerpt' ) ) :

	class post_excerpt extends textarea {



		/*
		*  initialize
		*
		*  This function will setup the field type data
		*
		*  @type    function
		*  @date    5/03/2014
		*  @since    5.0.0
		*
		*  @param    n/a
		*  @return    n/a
		*/

		function initialize() {
			// vars
			$this->name       = 'post_excerpt';
			$this->label      = __( 'Excerpt', 'acf-frontend-form-element' );
			  $this->category = __( 'Post', 'acf-frontend-form-element' );
			$this->defaults   = array(
				'data_name'     => 'excerpt',
				'default_value' => '',
				'new_lines'     => '',
				'maxlength'     => '',
				'placeholder'   => '',
				'rows'          => '',
			);
			add_filter( 'acf/pre_update_value/type=' . $this->name, array( $this, 'pre_update_value' ), 9, 4 );

		}


		function load_field( $field ) {
			 $field['name'] = $field['type'];
			return $field;
		}

		function prepare_field( $field ) {
			$field['type'] = 'textarea';
			return $field;
		}

		public function load_value( $value, $post_id = false, $field = false ) {
			if ( ! $value && $post_id && is_numeric( $post_id ) ) {
				$value = get_post_field( 'post_excerpt', $post_id );
			}
			return $value;
		}

		function pre_update_value( $checked, $value, $post_id, $field ) {
			if( $this->name !== $field['type'] ){
				return $checked;
			}if ( $post_id && is_numeric( $post_id ) ) {
				$post_to_edit                 = array(
					'ID' => $post_id,
				);
				$post_to_edit['post_excerpt'] = $value;
				remove_action( 'acf/save_post', '_acf_do_save_post' );
				wp_update_post( $post_to_edit );
				add_action( 'acf/save_post', '_acf_do_save_post' );

			}
			return true;
		}

		public function update_value( $value, $post_id = false, $field = false ) {
			return null;
		}

	}



endif;


