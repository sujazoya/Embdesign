<?php
namespace Frontend_Admin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Frontend_Admin\Classes\Display_Form' ) ) :

	class Display_Form {

		public function get_form_data( $form ) {
			global $post;
			$active_user = wp_get_current_user();

			if ( ! empty( $form['options'] ) ) {
				return $form;
			}
			global $fea_instance, $fea_form;
			if ( ! isset( $fea_instance->local_actions ) ) {
				return $form;
			}

			$types = array( 'post', 'user', 'term', 'product', 'plan', 'subscription', 'taxonomy', 'post_type' );
			if( ! empty(  $_POST['_acf_objects'] ) ){
				$objects = fea_decrypt( $_POST['_acf_objects'] );
				$objects = json_decode( $objects, true );

				if( ! empty( $objects['submission'] ) ){
					$form = apply_filters( 'frontend_admin/forms/get_submission', $form, $objects['submission'] );
				}else{
					$form['record'] = [];
				}
				foreach ( $types as $type ) {
					if ( isset( $objects[$type] ) ) {
						
						$form['record'][ $type ] = sanitize_text_field( $objects[$type] );
					}
				}

				if ( isset( $objects['taxonomy_type'] ) ) {
					$form['record']['taxonomy_type'] = sanitize_text_field( $objects['taxonomy_type'] );
				}
				if ( isset( $objects['post_type'] ) ) {
					$form['record']['post_type'] = sanitize_text_field( $objects['post_type'] );
				}
			}

			
			$local_actions = fea_instance()->local_actions;
			foreach ( $local_actions as $type => $action ) {		

				if( empty( $form[ $type . '_id' ] ) ){
					if ( empty( $form['record'][ $type ] ) ) {
						$form = $action->load_data( $form );
					}else{
						$form[ $type . '_id' ] = $form['record'][ $type ];
					}
				}
			}


			$form = apply_filters( 'frontend_admin/form_data', $form );
			$fea_form = $form;
			return $form;
		}

		


		public function get_form( $key, $element = false ) {
			if( ! $key ) return false;

			if ( is_numeric( $key ) && get_post_type( $key ) == 'admin_form' ) {
				$form = get_post( $key );
				return $this->get_form_args( $form );
			}
			if ( strpos( $key, 'form_' ) !== false ) {
				$args = array(
					'post_type'      => 'admin_form',
					'posts_per_page' => '1',
					'meta_key'       => 'form_key',
					'meta_value'     => $key,
					'post_status'    => 'any',
				);
	
				$forms = get_posts( $args );
	
				if ( $forms ) {
					$form = $this->get_form_args( $forms[0] );
				}
				
			}
			
			if ( empty( $form ) ) {
				$form = false;
			}

			$form = apply_filters( 'frontend_admin/forms/get_form', $form, $key, $element );
			if( $form ){
				return $this->validate_form( $form );
			}
			

			return false;
		}

		public function get_form_args( $form ) {
			// Get form object if $form is the ID
			if ( is_numeric( $form ) ) {
				$form = get_post( $form );
			}

			// Make sure we have a post and that it's a form
			if ( empty( $form ) || 'admin_form' != $form->post_type ) {
				return false;
			}

			$form_args = $form->post_content ? maybe_unserialize( $form->post_content ) : array();

			$form_args['ID'] = $form->ID;


			$form_args['status'] = $form->post_status;

			$form_key = get_post_meta( $form->ID, 'form_key', 1 );

			if ( ! $form_key ) {
				$form_key = $form->ID;
			}

			$form_args['id'] = $form_key;

			$form_args               = $this->get_form_fields( $form->ID, $form_args );
			$form_args['title']      = $form->post_title;
			$form_args['form_title'] = '';
			return $form_args;
		}

		public function get_form_fields( $form, $args = array() ) {
			global $fea_instance;
			 $fields_args = array(
				 'post_type'      => 'acf-field',
				 'posts_per_page' => '-1',
				 'post_parent'    => $form,
				 'orderby'        => 'menu_order',
				 'order'          => 'ASC',
			 );

			 $fields = get_posts( $fields_args );
			 $content_types = array( 'post', 'product', 'user' );


			 if ( $fields ) {

				 foreach ( $fields as $index => $field ) {
					 $object = $fea_instance->frontend->get_field( $field->post_name );

					 if ( ! $object ) {
						 continue;
					 }
					 $object['parent'] = $form;
					 do_action( 'frontend_admin/form_assets/type=' . $object['type'], $object );

					 foreach( $content_types as $type ){
						if( $object['type'] == $type.'_to_edit' ){						
							$url_query_key = $object['url_query'] ?? $type.'_id';

							if( ! empty( $_GET[$url_query_key] ) ){
								$args[$type.'_id'] = sanitize_text_field( $_GET[$url_query_key] );
							}else{
								continue;
							}
							$args[$type.'_id'] = $object['value'] ?? $args[$type.'_id'] ?? 'none';

							$is_correct_post_type = true;

							if( empty( $args['post_type'] ) && ! empty( $object['post_type'] ) ){
								$args['post_type'] = (array) $object['post_type'];
							}

							if( ! empty( $args['post_type'][0] ) ) $args['hidden_fields']['post_type'] = $args['post_type'][0];

							if( 'post' == $type && ! empty( $args['post_type'] ) && is_numeric( $args[$type.'_id'] ) ){
								$args['post_type'] = (array) $object['post_type'];
								$post_type = get_post_type( $args[$type.'_id'] );

								$allowed_types = (array) $args['post_type'];
								if( is_array( $allowed_types ) && ! in_array( $post_type, $allowed_types ) ){
									$is_correct_post_type = false;
								}
								

							}

							if( 'product' == $type && get_post_type( $args[$type.'_id'] ) != 'product' ){
								$is_correct_post_type = false;
							}

							if( 'user' == $type ){
								$allowed_roles = (array) $args['roles'];

								$user_id = $args[$type.'_id'];
								$user = get_userdata( $user_id );

								if( ! $user ){
									$args['user_id'] = 'none';
								}else{
									$current_user = get_current_user_id();

									if( ! empty( $args['roles'] ) && $current_user != $args['user_id'] ){
										$roles = $user->roles;
										//check if the roles are valid
										$roles = array_intersect( $roles, $args['roles'] );
										if( empty( $roles ) ){
											$args['user_id'] = 'none';
										}else{
											$args['user_id'] = $user_id;
										}
										
									}
								}

							}

							if( ! $is_correct_post_type ){
								$args[$type.'_id'] = 'none';
							}


						}
					 }

					 $args['fields'][ $object['key'] ] = $object;
				 }
			 }

			 return $args;
		}

		public function validate_form( $form ) {
			if( ! $form ) return false;

			if ( ! is_array( $form ) ) {
				$form = $this->get_form( $form );
			}
			
			/*
			 if( empty( $form['no_cookies'] ) && empty( $form['no_record'] ) && ! feadmin_edit_mode() ){
			$form = $this->get_record( $form );
			}  */

			$form_class = empty( $form['form_attributes']['class'] ) ? 'frontend-form -submit' : 'frontend-form -submit ' . $form['form_attributes']['class'];

			// defaults
			$form = feadmin_parse_args(
				$form,
				array(
					'id'                    => isset( $form['ID'] ) ? $form['ID'] : 'acf-form',
					'ID'					=> '',
					'parent_form'           => '',
					'custom_fields_save'    => 'post',
					'fields'                => array(),
					'field_objects'         => false,
					'form'                  => true,
					'form_title'            => '',
					'show_form_title'       => false,
					'form_attributes'       => array(
						'data-id' 	 => $form['id'] ?? $form['ID'] ?? '', 
						'class'      => $form_class,
						'action'     => '',
						'method'     => 'post',
						'novalidate' => 'novalidate',
						'data-error-message' => $form['error_message'] ?? '',
					),
					'saved_drafts'          => array(),
					'saved_revisions'       => array(),
					'save_progress'         => '',
					'message_location'      => 'other',
					'hidden_fields'         => array(),
					'submit_value'          => __( 'Update', 'acf-frontend-form-element' ),
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'field_el'              => 'div',
					'honeypot'              => true,
					'show_update_message'   => true,
					'update_message'        => __( 'Post updated', 'acf-frontend-form-element' ),
					'html_updated_message'  => '<div class="frontend-admin-message"><div class="acf-notice -success acf-success-message -dismiss"><p class="success-msg">%s</p><span class="frontend-admin-dismiss close-msg acf-notice-dismiss acf-icon -cancel small"></span></div></div>',
					'kses'                  => isset( $form['no_kses'] ) ? ! $form['no_kses'] : true,
					'new_post_type'         => 'post',
					'post_type'				=> [ 'any' ],
					'new_post_status'       => 'publish',
					'redirect'              => 'current',
					'custom_url'            => '',
				)
			);

	
						$form = $this->get_form_data( $form );

			// filter
			$form = apply_filters( 'frontend_admin/forms/validate_form', $form );

			// return
			return $form;

		}

		public function render_submit_button( $form, $hidden = false ) {
			echo '<div class="fea-submit-buttons"><button type="button" class="fea-submit-button button" data-state="publish" >' . esc_html( $form['submit_value'] ) . '</button></div>';

		}


		public function form_set_data( $form = array() ) {
			global $wp;

			// defaults
			$data = wp_parse_args(
				$form['hidden_fields'],
				array(
					'screen'     => 'fea_form',    // Current screen loaded (post, user, taxonomy, etc)
					'nonce'      => '',        // nonce used for $_POST validation (defaults to screen)
					'validation' => 1,        // enables form validation
					'changed'    => 0,        // used by revisions and unload to detect change
					'status'     => '',
					'message'    => '',
					'required_message' => $form['default_required_message'] ?? '',
					'current_url' => $_POST['current_url'] ?? home_url( $wp->request ),
				)
			);
			if( ! empty( $form['id'] ) ){
				$data['form'] = $form['id'];
			}

			if( is_admin() && ! wp_doing_ajax() ){
				$data['current_url'] = admin_url( $wp->request );
			}

			if( isset( $_SERVER['QUERY_STRING'] ) ){
				$query_string = sanitize_text_field( $_SERVER['QUERY_STRING'] );
				if( $query_string ){
					$data['current_url'] .= '?' . $query_string;
				}
			}

			$data['referer_url'] = $data['current_url'];
			$referer = wp_get_referer();
			if ( $referer ) {
				$data['referer_url'] = $referer;
			}


			$data_types = array( 'post', 'user', 'term', 'product' );
			foreach ( $data_types as $type ) {
				if ( ! empty( $form[ $type . '_id' ] ) ) {
					$data[ $type ] = $form[ $type . '_id' ];
				}
			}

			// create nonce
			$data['nonce'] = wp_create_nonce( $data['screen'] );

			// return
			return $data;
		}


		public function form_render_data( $form = array() ) {
			// set form data
			$data = $this->form_set_data( $form );

			?>
			<div class="acf-form-data acf-hidden">
			<?php

			$types = array( 'post', 'user', 'term', 'product', 'plan', 'subscription', 'submission', 'taxonomy', 'post_type' );

			$objects = array();
			// loop
			foreach ( $data as $name => $value ) {
				if( in_array( $name, $types ) ){
					$objects[$name] = $value;
					continue;
				}

				// input
				acf_hidden_input(
					array(
						'name'  => '_acf_' . $name,
						'value' => $value,
					)
				);
			}
			if( $objects ){

				$objects = json_encode( $objects );
				$objects = fea_encrypt( $objects );
				acf_hidden_input(
					array(
						'name'  => '_acf_objects',
						'value' => $objects
					)
				);
			}

			// actions
			do_action( 'acf/form_data', $data );
			do_action( 'acf/input/form_data', $data );

			?>
			</div>
			<?php
		}

		public function render_field_setting( $field, $setting, $global = false ) {
			// Validate field.
			$setting = acf_validate_field( $setting );

			// Add custom attributes to setting wrapper.
			$setting['wrapper']['data-key'] = $setting['name'];
			$setting['wrapper']['class']   .= ' acf-field-setting-' . $setting['name'];
			if ( ! $global ) {
				$setting['wrapper']['data-setting'] = $field['type'];
			}

			// Copy across prefix.
			$setting['prefix'] = $field['prefix'];

			// Find setting value from field.
			if ( $setting['value'] === null ) {

				// Name.
				if ( isset( $field[ $setting['name'] ] ) ) {
					$setting['value'] = $field[ $setting['name'] ];

					// Default value.
				} elseif ( isset( $setting['default_value'] ) ) {
					$setting['value'] = $setting['default_value'];
				}
			}

			// Add append attribute used by JS to join settings.
			if ( isset( $setting['_append'] ) ) {
				$setting['wrapper']['data-append'] = $setting['_append'];
			}

			// Render setting.
			$this->render_field_wrap( $setting, 'tr', 'label' );
		}


		public function render_field_wrap( $field, $element = 'div', $instruction = 'label' ) {
			//enqueue required scripts
			do_action( 'frontend_admin/field/enqueue_scripts', $field );


			if ( is_string( $field ) ) {
				$field = fea_instance()->frontend->get_field( $field );
			}


			if ( ! $field ) {
				return;
			}

			$field = apply_filters( 'frontend_admin/prepare_field', $field );
			$field = apply_filters( 'frontend_admin/prepare_field/type=' . $field['type'], $field );


			if ( isset( $field['key'] ) ) {
				$field = apply_filters( 'frontend_admin/prepare_field/key=' . $field['key'], $field );
			}

			if ( isset( $field['name'] ) ) {
				$field = apply_filters( 'frontend_admin/prepare_field/name=' . $field['name'], $field );
			}
			$field = feadmin_parse_args(
				$field,
				array(
					'prefix'       => '',
					'type'         => '',
					'required'     => 0,
					'instructions' => '',
					'_name'        => '',
					'wrapper'      => array(
						'class' => '',
						'id'    => '',
						'width' => '',
					),
				)
			);


			if ( empty( $field['_prepare'] ) ) {
				// Ensure field is complete (adds all settings).
				if ( function_exists( 'acf_validate_field' ) ) {
					$field = acf_validate_field( $field );
				}

				// Prepare field for input (modifies settings).
				if ( function_exists( 'acf_prepare_field' ) ) {
					$field = acf_prepare_field( $field );
				}
			}

			// Allow filters to cancel render.
			if ( ! $field ) {
				return;
			}


			if( isset( $field['prev_value'] ) ){
				$field['wrapper']['data-prev-value'] = '1';
				$field['wrapper']['class'] .= ' has-prev-value';

			}


			// Determine wrapping element.
			$elements = array(
				'div' => 'div',
				'tr'  => 'td',
				'td'  => 'div',
				'ul'  => 'li',
				'ol'  => 'li',
				'dl'  => 'dt',
			);

			if ( isset( $elements[ $element ] ) ) {
				$inner_element = $elements[ $element ];
			} else {
				$element = $inner_element = 'div';
			}

			if ( empty( $field['no_wrap'] ) ) {

				// Generate wrapper attributes.
				$wrapper = array(
					'id'        => '',
					'class'     => 'acf-field',
					'width'     => '',
					'style'     => '',
					'data-name' => $field['_name'],
					'data-type' => $field['type'],
					'data-key'  => $field['key'],
				);

				// Add field type attributes.
				$wrapper['class'] .= " acf-field-{$field['type']}";

				// add field key attributes
				if ( $field['key'] ) {
					$wrapper['class'] .= " acf-field-{$field['key']}";
				}

				// Add required attributes.
				// Todo: Remove data-required
				if ( $field['required'] ) {
					$wrapper['class']        .= ' is-required';
					$wrapper['data-required'] = 1;
				}

				//check whether to show error
				if( ! empty( $field['no_error_message'] ) ){
					$wrapper['class'] .= ' fea-no-error';
				}

				// Clean up class attribute.
				$wrapper['class'] = str_replace( '_', '-', $wrapper['class'] );
				$wrapper['class'] = str_replace( 'field-field-', 'field-', $wrapper['class'] );

				// Merge in field 'wrapper' setting without destroying class and style.
				if ( $field['wrapper'] ) {
					$wrapper = acf_merge_attributes( $wrapper, $field['wrapper'] );
				}

				// Extract wrapper width and generate style.
				// Todo: Move from $wrapper out into $field.
				$width = acf_extract_var( $wrapper, 'width' );
				if ( $width ) {
					$width = acf_numval( $width );
					if ( $element !== 'tr' && $element !== 'td' ) {
						$wrapper['data-width'] = $width;
						$wrapper['style']     .= " width:{$width}%;";
					}
				}

				// Clean up all attributes.
				$wrapper = array_map( 'trim', $wrapper );
				$wrapper = array_filter( $wrapper );

				/**
				 * Filters the $wrapper array before rendering.
				 *
				 * @date  21/1/19
				 * @since 5.7.10
				 *
				 * @param array $wrapper The wrapper attributes array.
				 * @param array $field The field array.
				 */
				$wrapper = apply_filters( 'acf/field_wrapper_attributes', $wrapper, $field );
			
				// Append conditional logic attributes.
				if ( ! empty( $field['conditional_logic'] ) ) {
					$wrapper['data-conditions'] = $field['conditional_logic'];
				}
				if ( ! empty( $field['conditions'] ) ) {
					$wrapper['data-conditions'] = $field['conditions'];
				}


				// Render HTML
				echo '<' . esc_html( $element ) . ' ' . acf_esc_attrs( $wrapper ) . '>';
				if ( $element !== 'td' && ( ! isset( $field['field_label_hide'] ) || ! $field['field_label_hide'] ) ) {
					echo '<' . esc_html( $inner_element ) . '  class="acf-label">';
					acf_render_field_label( $field );
					echo '</' . esc_html( $inner_element ) . '>';
				}

				if( isset( $field['prev_value'] ) ){
					if( $field['prev_value'] ){
						echo '<a href="#" class="fea-view-changes">(' . __( 'View Changes', 'acf-frontend-form-element' ) . ')</a>';
						echo '<a href="#" class="fea-edit-changes">(' . __( 'Edit', 'acf-frontend-form-element' ) . ')</a>';
						echo '<div class="fea-prev-value">';
							echo wp_kses_post( fea_instance()->dynamic_values->display_field( $field ) );
						echo '</div>';
					}else{
						echo '<p>(' . __( 'New', 'acf-frontend-form-element' ) . ')</p>';
					}

						
				}

					echo '<' . esc_html( $inner_element ) . ' class="acf-input">';
				if ( $instruction == 'label' ) {
					acf_render_field_instructions( $field );
				}
			}

			if( 'message' == $field['type'] ){
				$message = $field['message'];
				if( ! empty( $field['dynamic'] ) ){
					$message = fea_instance()->dynamic_values->get_dynamic_values( $message );
				}
				$message = do_shortcode( $message );
				echo '<div class="acf-message">' . $message . '</div>';
			}else{

				if( ! empty( $field['display_only'] ) ){
					echo wp_kses_post( fea_instance()->dynamic_values->display_field( $field ) );

					global $fea_form;
					if( $fea_form ){
						echo '<div class="frontend-admin-hidden">';
							acf_render_field( $field );
						echo '</div>';
					}
				}else{
					acf_render_field( $field );
				}
			}
			
			if ( empty( $field['no_wrap'] ) ) {
				if ( $instruction == 'field' ) {
					acf_render_field_instructions( $field );
				}
				echo '</' . esc_html( $inner_element ) . '>';
				echo '</' . esc_html( $element ) . '>';
			}

				do_action( 'frontend_admin/after_field', $field );
				do_action( 'frontend_admin/after_field/type=' . $field['type'], $field );

			if ( isset( $field['key'] ) ) {
				do_action( 'frontend_admin/after_field/key=' . $field['key'], $field );
			}

			if ( isset( $field['name'] ) ) {
				do_action( 'frontend_admin/after_field/name=' . $field['name'], $field );
			}

		}

		public function get_field_data_type( $field, $form ) {
			$data_type = fea_instance()->frontend->find_field_type_group( $field['type'] );

			if ( ! $data_type || in_array( $data_type, array( 'general', 'mailchimp', 'pro-form', 'advanced' ) ) ) {
				$data_type = $form['custom_fields_save'] ?? 'post';
				if ( ! empty( $field['custom_fields_save'] ) ) {
					$data_type = $field['custom_fields_save'];
				}
			} else {
				$builder = $field['builder'] ?? 'none';
				$field['fea_wp_core'] = 1;
				$new_field_name       = 'fea_' . $field['type'];
				if ( empty( $field['_name'] ) || $new_field_name != $field['_name'] ) {
					$field['name']  = $new_field_name;
					$field['_name'] = $new_field_name;
					//if( ! $builder ) acf_update_field( $field );
				}
			}

			if ( $data_type != 'options' && isset( $form[ "{$data_type}_id" ] ) ) {
				$data_id = $form[ "{$data_type}_id" ];
			} else {
				$data_id = $data_type;
			}
			if ( ! feadmin_edit_mode()
				&& $data_id == 'none'
				&& $field['type'] != $data_type . '_to_edit'
			) {
				return false;
			}

			if( empty( $field['prefix'] ) || 'acf' == $field['prefix'] ){
				if ( $data_type == 'product' ) {
					$field['prefix'] = 'acff[woo_' . $data_type . ']';
					$data_type       = 'product';
				} else {
					$field['prefix'] = 'acff[' . $data_type . ']';
				}
				if ( $field['key'] == '_validate_email' ) {
					$field['prefix'] == 'acff';
				} 
				if ( isset( $form['admin_options'] ) ) {
					$field['prefix'] = 'acff[admin_options]';
				} 
			}	

			$field['custom_fields_save'] = $data_type;

			return $field;
		}


		public function get_field_to_display( $field_data, $fields ) {
			
			if ( is_string( $field_data ) ) {
				$field_data = fea_instance()->frontend->get_field( $field_data );
			}

			if ( ! empty( $field_data['sub_fields'] ) ) {
				$sub_fields = array();
				foreach ( $field_data['sub_fields'] as $sub_field ) {
						$sub_fields = $this->get_field_to_display( $sub_field, $sub_fields );
				}
				$field_data['sub_fields'] = $sub_fields;
				$fields[] = $field_data;
			} else {
				$fields[] = $field_data;
				$GLOBALS['form_fields'][ $field_data['type'] ] = $field_data['key'];
			}
			
			return $fields;
		}

		public function get_fields_to_display( $form, $current_fields = array() ) {
			if ( $form['field_objects'] ) {
				$fields = $form['field_objects'];
			} else {
				$fields = array();
				if ( $current_fields ) {
					foreach ( $current_fields as $index => $field_data ) {

						if ( empty( $field_data ) ) {
							unset( $current_fields[ $index ] );
							continue;
						}

						if ( isset( $field_data['type'] ) ) {
							$field_type          = $field_data['type'];
							$exclude_in_approval = array( 'submit_button', 'save_progress' );

							if ( in_array( $field_type, $exclude_in_approval ) && isset( $form['approval'] ) ) {
								continue;
							}
						}

						$fields = $this->get_field_to_display( $field_data, $fields );

					}
				}
			}
			if ( empty( $fields ) ) {
				return false;
			}
			return $fields;
		}

		public function render_fields( $fields = array(), $form = array(), $defaults = false ) {
			if ( empty( $form ) ) {
				$form = $GLOBALS['admin_form'];
			}

			$cf_save = $form['custom_fields_save'];
			if ( $cf_save == 'none' ) {
				$cf_save = 'form';
			}
			$el          = $form['field_el'];
			$instruction = $form['instruction_placement'];

			/**
			 * Filters the $fields array before they are rendered.
			 *
			 * @date  12/02/2014
			 * @since 5.0.0
			 *
			 * @param array $fields An array of fields.
			 * @param array $form An array of all of the form data.
			 */
			$fields = apply_filters( 'frontend_admin/pre_render_fields', $fields, $form );
			$_fields = array();
			foreach ( $fields as $index => $field ) {
				if( isset( $field['type'] ) ){
					$GLOBALS['form_fields'][$field['type']] = $field['key'];
					
				}

				if ( ! empty( $field['fields_select'] ) ) {

					foreach ( $field['fields_select'] as $sub_field ) {
						if( is_string( $sub_field )  ){
							if( strpos( $sub_field, 'group_' ) !== false ){
								echo $sub_field;
								$_fields = array_merge( $_fields, acf_get_fields( $sub_field ) );
							}else{
								$_fields[] = fea_instance()->frontend->get_field( $sub_field );
							}
						}

						if( is_array( $sub_field ) ){
							$_fields[$sub_field['key']] = $sub_field;
						}

						
					}
				}else{
					$_fields[] = $field;
				}
			}
			$fields = $_fields;

			// Loop over and render fields.
			if ( $fields ) {
				// Filter our false results.
				$fields = array_filter( $fields );
				if ( $defaults ) {
					$fields = array_merge( $this->hidden_default_fields( $form ), $fields );
				}

				foreach ( $fields as $field ) {
					if ( ! $field ) {
						continue;
					}

					if ( isset( $field['_input'] ) ) {
						$field['value'] = $field['_input'];
						if ( is_string( $field['value'] ) ) {
							$field['value'] = stripslashes( $field['value'] );
						}
					}

					$field = $this->get_field_data_type( $field, $form );

					
					if( ! $field ) continue;

					

					if ( ! isset( $field['value'] )
						|| $field['value'] === null
					) {
						$field = $this->get_field_value( $field, $form );
					}

					if ( empty( $field['no_data_collect'] ) ) {
						$show_submit_button = 1;
					}

					if ( 'submit_button' == $field['type'] ) {
						$GLOBALS['admin_form']['submit_button_field'] = $field['key'];
					}

					if ( 'form_step' == $field['type'] && empty( $field['steps_wrapper'] ) ) {
						continue;
					}

					// Render wrap.
					$this->render_field_wrap( $field, $el, $instruction );
				}


			}

			if ( isset( $show_submit_button ) ) {
				if ( isset( $form['default_submit_button'] ) ) {
					$form['show_button'] = $form['default_submit_button'];
				}
			}

			/**
			*  Fires after fields have been rendered.
			*
			*  @date  12/02/2014
			*  @since 5.0.0
			*
			* @param array $fields An array of fields.
			* @param array $form An array of all of the form data.
			*/
			do_action( 'frontend_admin/render_fields', $fields, $form );

			return $form;
		}

		function get_field_value( $field, $form = array() ) {
			$type = $field['custom_fields_save'] ?? $form['custom_fields_save'] ?? 'post';
			$type = strtolower( $type );
			
			$id = $form['source'] ?? 0;
			if( ! $id ){
				if ( $type == 'options' ){
					$id = 'options';
				}else{
					$id = $form[ "{$type}_id" ] ?? '';
				}
				
			}

			global $wp_query, $post;


			if( ! $id ){

				if( 'term' == $type && $wp_query->loop_term ){
					$term_obj = $wp_query->loop_term;
					if( isset( $term_obj->slug ) ){
						$id = $term_obj->term_id;
						$object_id = 'term_' . $id;

					}
				}
			}

			if( empty( $object_id ) ){
				if( $id && ! is_numeric( $id ) ){
					$object_id = $id;
				}elseif ( 'post' == $type || 'product' == $type ) {
					if( '' == $id && isset( $post->ID ) ){
						$id = $post->ID;
					}
					$object_id = $id;
				} else {
					$object_id = $type . '_' . $id;
				}
			}

			
			if( empty( $form ) ){
				$field['source'] = $object_id;
			}
			
			// Get field name.
			$field_name = $field['name'];


			if ( isset( $form['record'] ) ) {
				if( 'product' == $type ){
					$group = 'woo_product';
				}else{
					$group = $type;
				}

				$field_name = str_replace( 'fea_'.$field['type'], $field['type'], $field_name );
				
				$field_record = $form['record']['fields'][ $group ][ $field_name ] ?? false;

				if( ! $field_record ){
					$field_record = $form['record']['fields'][ $group ][ $field['key'] ] ?? false;
				}

				if ( $field_record && $field_record['_input'] ) {
					
					$field['value'] = $field_record['_input'];

					$form['save_to_'.$type] = $form['save_to_'.$type] ?? 'edit_'.$type;

					if( 'edit_'.$type == $form['save_to_'.$type] && isset( $field_record['prev'] ) ){
						$field['prev_value'] = $field_record['prev'] ?? '';
					}
					return $field;
				}
			}

			// Allow filter to short-circuit load_value logic.
			$value = apply_filters( 'acf/pre_load_value', null, $object_id, $field );
			if ( $value !== null ) {
				$field['value'] = $value;
				return $field;
			}		

			// Check store.
			$store = acf_get_store( 'values' );

			if ( $store->has( "$object_id:$field_name" ) ) {
				$field['value'] = $store->get( "$object_id:$field_name" );
				
				return $field;
			}

			// Load value from database.
			// todo: investigate this line causing new post forms
			$null = apply_filters( 'acf/pre_load_metadata', null, $object_id, $field_name, false );

			if ( $null !== null ) {
				$field['value'] = ( $null === '__return_null' ) ? null : $null;
				return $field;
			}

			if ( 'options' == $object_id ) {
				$value = get_option( 'options_' . $field_name, null );
			} else {
				if ( is_numeric( $id ) ) {
					$meta  = get_metadata( $type, $id, $field_name, false );
					$value = isset( $meta[0] ) ? $meta[0] : null;
				}
			}

			// Use field's default_value if no meta was found.
			if ( $value === null && isset( $field['default_value'] ) ) {
				if( is_string( $field['default_value'] ) ){
					$dynamic = fea_instance()->dynamic_values->get_dynamic_values( $field['default_value'] );
					if( $dynamic ){
						$value = $dynamic;
					}
				}else{
					$value = $field['default_value'];
				}
			}
			/**
			 * Filters the $value after it has been loaded.
			 *
			 * @date  28/09/13
			 * @since 5.0.0
			 *
			 * @param mixed $value The value to preview.
			 * @param string $object_id The post ID for this value.
			 * @param array $field The field array.
			 */

			$value = apply_filters( 'acf/load_value', $value, $object_id, $field );

			// Update store.
			$store->set( "$object_id:$field_name", $value );

			// $value = acf_format_value( $value, $object_id, $field );

			// Return value.

			$field['value'] = $value;

			return $field;
		}

		public function get_record( $form ) {
			if ( empty( $form['id'] ) || ! isset( $_COOKIE[ $form['id'] ] ) ) {
				return $form;
			}
			$record = fea_instance()->submissions_handler->get_submission( absint( $_COOKIE[ $form['id'] ] ) );

			if ( empty( $record->id ) ) {
				return $form;
			}

			if ( $record->status == 'in_progress' ) {
				$form   = $this->get_form( $record->form );
				$fields = json_decode( fea_decrypt( $record->fields ), true );
				if ( ! isset( $fields['record'] ) ) {
					$form['record'] = $fields;
				} else {
					$form['record'] = $fields['record'];
				}

				$form['submission'] = $record->id;
				return $form;
			}
			return $form;
		}

	

		public function get_field_group_filters( $form ) {
			$filters = array( 'post_id' => $form['post_id'] );

			if ( $form['save_to_post'] == 'new_post' ) {
				$filters = array( 'post_type' => $form['new_post_type'] );
			} else {
				$filters = array( 'post_id' => $form['post_id'] );
			}

			return $filters;
		}

		public function delete_record( $form ) {
			if ( ! empty( $form['id'] ) && isset( $_COOKIE[ $form['id'] ] ) ) {
				$expiration_time = time();
				setcookie( $form['id'], '0', $expiration_time, '/' );
			}
		}

		public function show_messages( $form ) {
			if ( feadmin_edit_mode() && ! empty( $form['style_messages'] ) ) {
				echo '<div class="acf-notice -success acf-sucess-message -dismiss"><p >' . esc_html( $form['update_message'] ) . '</p><a href="#" class="acf-notice-dismiss acf-icon -cancel"></a></div>';
				echo '<div class="acf-notice -error acf-error-message -dismiss"><p>'. esc_html( __( 'Validation failed.', 'acf-frontend-form-element' ) ) . '</p><a href="#" class="acf-notice-dismiss acf-icon -cancel"></a></div>';
				echo '<div class="acf-notice -limit frontend-admin-limit-message"><p>'. esc_html( __( 'Limit Reached.', 'acf-frontend-form-element' ) ) . '</p></div>';
			}
		}

		public function render_submissions( $form ) {
			 $editor = feadmin_edit_mode();
			$form    = $this->validate_form( $form );
			$form    = wp_parse_args(
				$form,
				array(
					'submissions_per_page' => 10,
					'total_submissions'    => '',
				)
			);

			$form = apply_filters( 'frontend_admin/show_form', $form, 'submissions' );

			if ( empty( $form['display'] ) ) {
				if ( ! empty( $form['message'] ) && $form['message'] !== 'NOTHING' ) {
					esc_html_e( $form['message'] );
				}
				return;
			}

			$args = array();
			if ( isset( $form['id'] ) ) {
				$args['form_key'] = $form['id'];
			}
			if ( isset( $form['ID'] ) ) {
				$args['form_id'] = $form['ID'];
			}

			$request = wp_parse_args( $_REQUEST,
				[
					'item_count' => 0,
					'load_more'  => 0,
					'current_page' => 0,
				]
			);

			$total_submits = $submissions = fea_instance()->submissions_handler->record_count( $args );

			if ( $form['submissions_per_page'] ) {
				$args['per_page'] = $form['submissions_per_page'];
			} else {
				$args['per_page'] = 10;
			}

			if ( $form['total_submissions'] && $total_submits > $form['total_submissions'] ) {
				$total_submits = $form['total_submissions'];

				if ( ! empty( $request['item_count'] ) ) {
					$item_count = (int) $request['item_count'];
					if ( ( $item_count + $args['per_page'] ) > $total_submits ) {
						$args['per_page'] = $total_submits - $item_count;
					}
				}
			}
			$submissions = fea_instance()->submissions_handler->get_submissions( $args );
			if ( ! $submissions ) {
				if ( ! empty( $form['no_submissions_message'] ) ) {
					echo '<div class="acf-notice -error acf-error-message -dismiss"><p >' . esc_html( $form['no_submissions_message'] ) . '</p></div>';
				}
				return;
			}

			$rows = array();

			if ( empty( $request['load_more'] ) ) {
				?>
				<div class="fea-list-container">
				<?php
			}
			global $fea_scripts;
			$fea_scripts = 'admin';
			foreach ( $submissions as $submission ) {
				$this->render_submission_item( $submission );
			}

			$count = count( $submissions );

			if ( $count < $total_submits && ! isset( $request['current_page'] ) ) {
				$total_pages = ceil( $total_submits / $args['per_page'] );

				?>
					<div class="load-more-results" data-form="<?php esc_attr_e( $form['ID'] ); ?>" data-page="1" data-count="<?php esc_attr_e( $count ); ?>" data-total="<?php esc_attr_e( $total_pages ); ?>"><span class="fea-loader acf-hidden"></span></div>
				<?php
			}
			if ( empty( $request['load_more'] ) ) {
				?>
			</div>
				<?php
			}

		}

		function render_submission_item( $submission ) {
			?>
			<div class="fea-list-item" data-item="submission" data-id="<?php esc_html_e( $submission['id'] ); ?>">
			<?php
			if ( empty( $submission['title'] ) ) {
				$submission['title'] = sprintf( __( 'Submission #%d', 'acf-frontend-form-element' ), $submission['id'] );
			}

			global $wp;
			$submission_link = home_url(
				add_query_arg(
					array(
						'submission' => $submission['id'],
					),
					$wp->request
				)
			);
			?>
			<a href="<?php echo esc_attr( $submission_link ); ?>"><h4 class="item-title"><?php echo esc_html( $submission['title'] ); ?></h4></a>
			<div class="actions"><a href="#" class="dashicons dashicons-edit small dark render-form"  data-name="edit_item"></a>
			
			<?php
			
			do_action( 'frontend_admin/items_list/item_actions', $submission, 'submission' );
			?>
			</div>
						<div class="meta">
			<?php
			if ( $submission['user'] ) {
				$user = get_user_by( 'ID', $submission['user'] );
				if ( isset( $user->user_login ) ) {
					  $user_text = $user->user_login;
					if ( $user->display_name ) {
						$user_text .= " ({$user->display_name})";
					} elseif ( $user->first_name && $user->last_name ) {
						$user_text .= " ({$user->first_name} {$user->last_name})";
					} elseif ( $user->first_name ) {
						$user_text .= " ({$user->first_name})";
					}
					echo '<p class="item-user dashicons dashicons-admin-users" >' . esc_html( $user_text ) . '</p>';
				}
			}
			$status_label = fea_instance()->submissions_handler->get_status_label( $submission['status'] );
			echo '<p class="item-status dashicons dashicons-flag" >' . esc_html( $status_label ) . '</p>';
			echo '<p class="item-date dashicons dashicons-calendar-alt">' .  esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission['created_at'] ) ) ) . '</p>';
			?>
			</div>
			</div>
			<?php
		}

		function handle_get_params( $form ) {
			if ( isset( $_GET['submission'] ) ) {
				if ( isset( $_GET['email-address'] ) ) {
					$address = sanitize_email( $_GET['email-address'] );
					if ( ! empty( $GLOBALS[ $address . '_verified' ] ) ) {
						echo '<div class="frontend-admin-message"><div class="acf-notice -success acf-success-message -dismiss"><p class="success-msg">' . esc_html( __( 'Email Verified Successfully' ) ) . '</p><span class="frontend-admin-dismiss close-msg acf-notice-dismiss acf-icon -cancel small"></span></div></div>';
						return;
					}
				}
				$fea           = fea_instance();
				$submission_id = absint( $_GET['submission'] );

				$form = $fea->submissions_handler->get_form( $submission_id );

				$op = empty( $_GET['edit'] ) ? 'view' : 'edit';

				$form = apply_filters( 'frontend_admin/show_form', $form, $op );
				if ( ! $form ) {
					return false;
				}

				if ( $op == 'view' ) {
					?>
					 <h4 class="item-title"><?php echo esc_html( $form['submission_title'] ); ?></h4>
					<?php
					$html = $fea->dynamic_values->get_all_fields_values( $form, true );
					echo wp_kses_post( $html, array(
						'img' => array(
							'src' => array(),
						),
					) );
					return false;
				}
			}
			if ( isset( $_GET['submissions'] ) ) {
				$this->render_submissions( $form );
				return false;
			}

			return $form;
		}

		public function render_form( $form ) {			
			if ( ! empty( $_GET ) ) {
				$form = $this->handle_get_params( $form );
				if ( ! $form ) {
					return;
				}
			}
			
			$form = $this->validate_form( $form );
			

			if ( empty( $form['submission'] ) ) {
				$form = apply_filters( 'frontend_admin/show_form', $form, 'form' );
				if ( empty( $form['display'] ) ) {
					return;
				}else{
					if( ! empty( $form['hide_if_no_post'] ) && 'none' == $form['post_id'] ){
						return;
					}
				}

			}

			$this->show_messages( $form );

			global $fea_scripts;
			$fea_scripts = 'admin';


			if( ! empty( $form['approval'] ) ){
				unset( $form['show_in_modal'] );
			}

			if ( ! empty( $form['show_in_modal'] ) ) {
				if( empty( $form['page_builder'] ) ) {
					$attrs = array(
						'class'     => 'modal-button render-form',
						'data-name' => 'admin_form',
						'data-form' => $this->form_set_data( $form ),
					);
					if ( isset( $form['modal_width'] ) ) {
						$attrs['data-form_width'] = $form['modal_width'];
					}

					if ( ! empty( $form['hide_modal_button'] ) ) {
						$attrs['class'] .= ' acf-hidden';
					}

					$button_text = $form['modal_button_text'];
					if ( ! empty( $form['modal_button_icon']['value'] ) ) {
						$button_text .= ' <span class="' . $form['modal_button_icon']['value'] . '"></span';
					}

					echo '<button ' . acf_esc_attrs( $attrs ) . ' >' . esc_html( $button_text ) . '</button>';
					$this->maybe_show_success_message( $form );

					return;
				}
			}else{
				$this->maybe_show_success_message( $form );
			}

			$form['submit_button_field'] = 0;

			$GLOBALS['admin_form'] = $form;
			global $fea_form;
			$fea_form = $form;

			$form = apply_filters( 'frontend_admin/forms/before_render', $form );

			$form_title   = $form['form_title'];

			?>
			<form <?php echo acf_esc_attrs( $form['form_attributes'] ); ?>> 
			<?php

			$this->form_render_data( $form );
			?>
			<div class="acf-fields acf-form-fields -<?php echo esc_attr( $form['label_placement'] ); ?>">				  
			<?php
			$form = $this->render_fields( $form['fields'], $form, true );

			if ( ( isset( $form['show_button'] ) && empty( $GLOBALS['admin_form']['submit_button_field'] ) || isset( $form['approval'] ) ) ) {
				?>
				<?php $this->render_submit_button( $form ); ?>
				<?php
			}

			?>
			</div>
			</form>
			<?php

			do_action( 'frontend_admin/after_form', $form );

			if ( feadmin_edit_mode() ) {
				echo '</div>';
			}

		}

		function check_form_context( $form, $context ) {
			$types = array( 'post', 'user', 'product', 'term', 'comment' ); 

			// check if the type is in context and if it is numeric, it matches the form type_id
			foreach ( $types as $type ) {
				if( empty( $context[ $type ] ) || empty( $form[ "{$type}_id" ] ) ){
					continue;
				}	
				if( ! is_numeric( $form[ "{$type}_id" ] ) ){
					continue;
				}
				
				if( $context[ $type ] != $form[ "{$type}_id" ] ){
					return false;
				}

			}
				

			return true;
		}

		function maybe_show_success_message( $form = array() ) {
			global $form_success;
			if ( isset( $form_success ) ) {
				if ( empty( $form_success['frontend-form-nonce'] ) || ! wp_verify_nonce( $form_success['frontend-form-nonce'], 'frontend-form' ) ) {
					return;
					
				}

				if( ! $this->check_form_context( $form, $form_success ) ){
					return;
				}			

				if ( isset( $form_success['success_message'] ) && $form_success['location'] == 'current'
						&& isset( $form_success['form_element'] ) && $form_success['form_element'] == $form['id']
					) {
						?>
						<div class="frontend-admin-message form-<?php echo esc_attr( $form_success['form_element'] ); ?>">
							<div class="acf-notice -success acf-success-message -dismiss"><p class="success-msg"><?php echo wp_unslash( wp_kses( $form_success['success_message'], 'post' ) ); ?></p><span class="frontend-admin-dismiss close-msg acf-notice-dismiss acf-icon -cancel small"></span></div>
						</div>
						<?php
				}
			}
		}

		function render_meta_fields( $prefix, $values = '', $button = true ) {
			echo '<div class="file-meta-data';
			if ( $values == 'clone' ) {
				echo ' clone';
			}
			echo '">';
			
			$file_data = array(
				array(
					'label' => __( 'Title' ),
					'type'  => 'text',
					'name'  => 'title',
				),
				array(
					'label'        => __( 'Alternative Text' ),
					'instructions' => __( 'Leave empty if the image is purely decorative.' ),
					'type'         => 'text',
					'name'         => 'alt',
				),
				array(
					'label' => __( 'Caption' ),
					'type'  => 'textarea',
					'name'  => 'caption',
					'rows'  => 3,
				),
				array(
					'label' => __( 'Description' ),
					'type'  => 'textarea',
					'name'  => 'description',
					'rows'  => 3,
				),
			);
			if ( is_numeric( $values ) ) {
				$values = $this->get_file_meta_values( $values );
			}
			foreach ( $file_data as $data ) {
				$data['prefix'] = $prefix;
				$data['disabled'] = true;
				$data['class']  = 'fea-file-meta' . ' ' .  $data['name'];
				if ( isset( $values[ $data['name'] ] ) ) {
					$data['value'] = $values[ $data['name'] ];
				}
				$this->render_field_wrap( $data );
			}
		
			if ( $button ) {
				echo '<button type="button" class="update-meta button button-primary">' . esc_html( __( 'Update Image', 'acf-frontend-form-element' ) ) . '</button>';
			}
			echo '</div>';

		}

		function get_file_meta_values( $id ) {
			$values = array(
				'title'       => '',
				'alt'         => '',
				'capt'        => '',
				'description' => '',
			);

			if ( ! $id || $id == 'clone' ) {
				return $values;
			}

			$attachment = get_post( $id );

			if ( isset( $attachment->post_title ) ) {

				$values['title']       = $attachment->post_title;
				$values['description'] = $attachment->post_content;
				$values['caption']        = $attachment->post_excerpt;
				$values['alt']         = get_post_meta( $id, '_wp_attachment_image_alt', true );
			}

			return $values;

		}

		public function hidden_default_fields( $form ) {
			$fields = array();
			if ( $form['honeypot'] ) {
				$kses_field = array(
					'prefix'          => 'acff',
					'name'            => '_validate_email',
					'key'             => '_validate_email',
					'no_data_collect' => 1,
					'type'            => 'text',
					'value'           => '',
					'no_save'         => 1,
					'wrapper'         => array( 'style' => 'display:none !important' ),
				);
				acf_add_local_field( $kses_field );
				$fields[] = $kses_field;
			}
			$element_id = $form['id'];

			if ( ! feadmin_edit_mode() && ! empty( $form['product_id'] ) ) {
				if ( empty( $GLOBALS['form_fields']['product_types'] ) ) {
					$field_key = $element_id . '_product_type';
					acf_add_local_field(
						array(
							'name'            => $field_key,
							'key'             => $field_key,
							'type'            => 'product_types',
							'no_data_collect' => 1,
							'wrapper'         => array( 'style' => 'display:none !important' ),
						)
					);
					$GLOBALS['form_fields']['product_types'] = $field_key;
					$fields[]                                = fea_instance()->frontend->get_field( $field_key );
				} else {
					acf_hidden_input(
						array(
							'name'  => 'acff[woo_product][types]',
							'value' => $GLOBALS['form_fields']['product_types'],
						)
					);
				}
				if ( empty( $GLOBALS['form_fields']['manage_stock'] ) ) {
					$field_key = $element_id . '_manage_stock';
					$_field = array(
						'name'            => $field_key,
						'key'             => $field_key,
						'type'            => 'manage_stock',
						'no_data_collect' => 1,
						'ui'              => 0,
						'wrapper'         => array( 'style' => 'display:none !important' ),
					);
					acf_add_local_field(
						$_field
					);
					$GLOBALS['form_fields']['manage_stock'] = $field_key;
					$fields[] = $_field;

				}
			}

			return $fields;
		}

		public function ajax_get_submissions() {
			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error();
			}

			$form_id = absint( $_REQUEST['form_id'] );

			if ( ! $form_id ) {
				wp_send_json_error();
			}

			$form = $this->get_form( $form_id );

			if ( $form ) {
				$this->render_submissions( $form );
				die;
			}

			wp_send_json_error( __( 'No Submissions Found', 'acf-frontend-form-element' ) );

		}

		public function change_form() {
			if ( ! feadmin_verify_ajax() ) wp_send_json_error( __( 'Authentication Error. Please try refreshing the page.', 'acf-frontend-form-element' ) );

			if ( empty( $_REQUEST['form_data'] ) ) {
				wp_send_json_error();
			}


			$request = wp_parse_args(  $_REQUEST,
				[
					'item_id' => '',
					'type' => '',
					'form_data' => '',
					'step' => false,
					'field_key' => '',
					'current_url' => '',
				]
			);

			$form = $this->get_form( $request['form_data'] );
			$field = fea_instance()->frontend->get_field( $request['field_key'] );
			if ( ! $form ) {
				wp_send_json_error();
			}

			if ( $request['item_id'] ) {
				$type                  = $request['type'];
				$form[ $type . '_id' ] = $request['item_id'];
				if ( $form[ $type . '_id' ] ) {
					if ( is_numeric( $form[ $type . '_id' ] ) ) {
						$form[ 'save_to_' . $type ] = 'edit_' . $type;
					} else {
						$form[ 'save_to_' . $type ] = 'new_' . $type;
					}
				}

				if( $field && 'post' == $type ){
					//get the post type from the field
					if( ! empty( $field['post_type'][0] ) ){
						$form['new_post_type'] = $field['post_type'][0] ?? 'post';
						$form['hidden_fields']['post_type'] = $form['new_post_type'];
					}
				}
			} 

			if( $request['current_url'] ){
				$form['hidden_fields']['current_url'] = $request['current_url'];
			}

			$form['show_in_modal'] = false;
			$GLOBALS['admin_form'] = $form;

			ob_start();
			// $form['no_cookies'] = 1;
			$this->render_form( $form );
			$reload_form = ob_get_contents();
			ob_end_clean();

			wp_send_json_success(
				array(
					'reload_form' => $reload_form,
					'to_top'      => true,
				)
			);
			die;
		}

		public function get_steps( $field ) {
			if ( $field['field_type'] == 'step' ) {
				return true;
			}
			return false;
		}

		public function ajax_add_form() {
			// vars
			$args = wp_parse_args(
				$_POST,
				array(
					'nonce'       => '',
					'field_key'   => '',
					'parent_form' => '',
					'form_action' => '',
					'data_type'   => 'post',
					'form' => 0,
				)
			);

			// verify nonce
			if ( ! feadmin_verify_ajax() ) {
				die();
			}

			global $ajax_render_form;
			$ajax_render_form = true;
			
			if( 'admin_form' == $args['form_action'] && $args['form'] ){
				$data = $args['form'];				
				$form = $this->get_form( $data['form'] );

				if( $form ){
					$form['hidden_fields'] = array_merge( 
						$form['hidden_fields'],
						$data
					);
					$form['show_in_modal'] = false;
					$data_types = array( 'post', 'user', 'term', 'product' );
					foreach ( $data_types as $type ) {
						if ( isset( $data[ $type ] ) ) {
							$form[ $type . '_id' ] = $data[ $type ];
						}
					}
					$this->render_form( $form );
				}				
				die();
			}

			do_action( 'frontend_admin/ajax_add_form', $args );

		}

		function render_field_group_display( $atts ) {
			$group_pre = explode( 'group_', $atts['group'] );
			if ( ! isset( $group_pre[1] ) ) {
				$atts['group'] = 'group_' . $atts['group'];
			}

			$fields = acf_get_fields( $atts['group'] );

			if( $fields ){
				foreach( $fields as $field ){
					$group = acf_get_field_group( $atts['group'] );
					if( ! empty( $group['no_values_message'] ) ){
						$atts['no_values_message'] = $group['no_values_message'];
					}
					$atts['field'] = $field['key'];

					$this->render_field_display( $atts );
				}
			}
		}

		function render_field_display( $atts ) {
			$source = get_the_ID();

			if ( isset( $atts['source'] ) ) {
				$source = $atts['source'];
			}

			$field = get_field_object( $atts['field'], $source, false );

			if( ! $field ){
				return;
			}

			if( empty( $field['no_values_message'] ) && ! empty( $atts['no_values_message'] ) ){
				$field['no_values_message'] = $atts['no_values_message'];
			}

			if ( ! empty( $atts['edit'] ) && $atts['edit'] == 'true' ) {
				$field['with_edit'] = true;
			}

			//wrapper tag
			$field['wrapper_tag'] = $atts['tag'] ?? 'div';


			echo fea_instance()->dynamic_values->render_field_display( $field, $source );

		}

		/**
		 * Registers the shortcode [frontend_admin] which renders the form specified by the "form" attribute
		 *
		 * @since 1.0.0
		 */
		public function shortcode( $atts ) {
			if ( isset( $atts['field'] ) ) {
				ob_start();
				$field_pre = explode( 'field_', $atts['field'] );
				if ( ! isset( $field_pre[1] ) ) {
					$atts['field'] = 'field_' . $atts['field'];
				}
				$this->render_field_display( $atts );
				$output = ob_get_clean();
				return $output;
			}

			if ( isset( $atts['group'] ) ) {
				ob_start();
				$this->render_field_group_display( $atts );
				$output = ob_get_clean();
				return $output;
			}

			if ( isset( $atts['form'] ) ) {
				$form = $this->get_form_args( $atts['form'] );

				$data_types = array( 'post', 'user', 'term', 'product' );
				foreach ( $data_types as $type ) {
					if ( isset( $atts[ $type ] ) ) {
						$form[ $type . '_id' ] = $atts[ $type ];
					}
				}

				ob_start();

				$this->render_form( $form );

				$output = ob_get_clean();

				return $output;
			}
			if ( isset( $atts['submissions'] ) ) {
				$form_id = $atts['submissions'];

				ob_start();

				$this->render_submissions( $form_id );

				$output = ob_get_clean();

				return $output;
			}
		}

		function success_message_cookie() {
			if ( isset( $_COOKIE['admin_form_success'] ) ) {
				global $form_success;
				$form_success = json_decode( stripslashes( $_COOKIE['admin_form_success'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitizing below.	
				if( ! is_array( $form_success ) ){
					return;
				}
				$form_success = array_map( 'sanitize_text_field', $form_success );

				if( isset($form_success['used'] ) ) {
                    $expiration_time = time() - 600;
                    setcookie('admin_form_success', '', $expiration_time, '/');
                }else{
                    $form_success['used'] = 1;
                    $expiration_time = time() + 600;
                    setcookie('admin_form_success', json_encode( $form_success ), $expiration_time, '/');
                }
			}

		}

		public function ajax_render_field_settings() {
			// Verify the current request.
			if ( ! feadmin_verify_ajax() || ! acf_current_user_can_admin() ) {
				wp_send_json_error();
			}

			// Make sure we have a field.
			$field = acf_maybe_get_POST( 'field' );
			if ( ! $field ) {
				wp_send_json_error();
			}

			$field['prefix'] = acf_maybe_get_POST( 'prefix' );
			$field           = acf_get_valid_field( $field );

			$tabs = array(
				'general',
				'validation',
				'presentation',
				'conditional_logic',
				'shortcode',
			);

			foreach ( $tabs as $tab ) {
				ob_start();

				if ( 'general' === $tab ) {
					// Back-compat for fields not using tab-specific hooks.
					do_action( 'acf/render_field_settings', $field );
					do_action( "acf/render_field_settings/type={$field['type']}", $field );
					do_action( "acf/render_field_settings/name={$field['name']}", $field );
					do_action( "acf/render_field_settings/key={$field['key']}", $field );
				}

				do_action( "acf/render_field_{$tab}_settings", $field );
				do_action( "acf/render_field_{$tab}_settings/type={$field['type']}", $field );
				do_action( "acf/render_field_{$tab}_settings/name={$field['name']}", $field );
				do_action( "acf/render_field_{$tab}_settings/key={$field['key']}", $field );

				$sections[ $tab ] = ob_get_clean();
			}

			wp_send_json_success( $sections );
		}

					
		public function form_message() {
			global $form_success, $fea_scripts;

			if ( empty( $form_success['frontend-form-nonce'] ) || ! wp_verify_nonce( $form_success['frontend-form-nonce'], 'frontend-form' ) ) {
				return;
			}

			if ( isset( $form_success['success_message'] ) && $form_success['location'] == 'other' ) {
				$message = $form_success['success_message'];
				fea_instance()->frontend->enqueue_scripts( 'frontend_admin_form' );
				$fea_scripts = true;
				?>
				<div class="-fixed frontend-admin-message form-<?php echo esc_attr( $form_success['form_element'] ); ?>">
					<div class="acf-notice -success acf-success-message -dismiss"><p class="success-msg"><?php echo wp_unslash( wp_kses( $message, 'post' ) ); ?></p><span class="frontend-admin-dismiss close-msg acf-notice-dismiss acf-icon -cancel small"></span></div>
				</div>
				<?php
			}

		}

		public function __construct() {
			add_action( 'wp_footer', array( $this, 'form_message' ) );

			add_shortcode( 'frontend_admin', array( $this, 'shortcode' ) );
			add_shortcode( 'acf_frontend', array( $this, 'shortcode' ) );
			add_action( 'init', array( $this, 'success_message_cookie' ) );
			add_action( 'wp_ajax_frontend_admin/forms/get_submissions', array( $this, 'ajax_get_submissions' ) );
			add_action( 'wp_ajax_frontend_admin/forms/change_form', array( $this, 'change_form' ) );
			add_action( 'wp_ajax_nopriv_frontend_admin/forms/change_form', array( $this, 'change_form' ) );
			add_action( 'wp_ajax_frontend_admin/forms/add_form', array( $this, 'ajax_add_form' ) );
			add_action( 'wp_ajax_nopriv_frontend_admin/forms/add_form', array( $this, 'ajax_add_form' ) );
			add_action( 'wp_ajax_fea/form/render_field_settings', array( $this, 'ajax_render_field_settings' ) );
		}
	}

	fea_instance()->form_display = new Display_Form();

endif;
