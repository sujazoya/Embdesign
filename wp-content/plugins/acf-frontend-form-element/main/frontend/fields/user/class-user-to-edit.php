<?php
namespace Frontend_Admin\Field_Types;

if ( ! class_exists( 'user_to_edit' ) ) :

	class user_to_edit extends Field_Base {



		/*
		*  __construct
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
			$this->name     = 'user_to_edit';
			$this->label    = __( 'User To Edit', 'acf' );
			$this->category = __( 'User', 'acf-frontend-form-element' );
			$this->defaults = array(
				'role'                 => '',
				'allow_null'      => 0,
				'add_new'         => 1,
				'add_new_text'    => __( 'New User', 'acf-frontend-form-element' ),
				'placeholder'     => __( 'Select User', 'acf-frontend-form-element' ),
				'url_query'       => 'user_id',
				'multiple'        => 0,
				'ui'              => 1,
				'no_data_collect' => 1,
			);

			// extra
			add_action( 'wp_ajax_acf/fields/user_to_edit/query', array( $this, 'ajax_query' ) );
			add_action( 'wp_ajax_nopriv_acf/fields/user_to_edit/query', array( $this, 'ajax_query' ) );

		}


		/*
		*  ajax_query
		*
		*  description
		*
		*  @type    function
		*  @date    24/10/13
		*  @since   5.0.0
		*
		*  @param   $user_id (int)
		*  @return  $user_id (int)
		*/

		function ajax_query() {
			$field_key = $_POST['field_key'] ?? '';
			$nonce = $_POST['nonce'] ?? '';

			$action = 'acf_field_' . $this->name . '_' . $field_key;
			
			// validate
			if ( ! feadmin_verify_ajax( $nonce, $action ) ) {
				die();
			}

			// get choices
			$response = $this->get_ajax_query( $_POST );

			// return
			acf_send_ajax_results( $response );

		}


		/*
		*  get_ajax_query
		*
		*  This function will return an array of data formatted for use in a select2 AJAX response
		*
		*  @type    function
		*  @date    15/10/2014
		*  @since   5.0.9
		*
		*  @param   $options (array)
		*  @return  (array)
		*/

		function get_ajax_query( $options = array() ) {
			// defaults
			$options = acf_parse_args(
				$options,
				array(
					'user_id'   => 0,
					's'         => '',
					'field_key' => '',
					'paged'     => 1,
					'role' => 'subscriber'
				)
			);

			// load field
			$field = acf_get_field( $options['field_key'] );
			if ( ! $field ) {
				return false;
			}

			// vars
			$results = array();

			if ( ! empty( $field['add_new'] ) && $options['paged'] == 1 && ! $options['s'] ) {
				$new_item = true;
				
					$type         = 'user';
					$default_text = 'New User';
				$add_new_text = ! empty( $field['add_new_text'] ) ? $field['add_new_text'] : __( $default_text, 'acf-frontend-form-element' );
				$results      = array(
					array(
						'id'   => 'add_' . $type,
						'text' => $add_new_text,
					),
				);
			}

			$args      = array(
				'fields' => [
					'ID', 'display_name', 'user_login', 'nicename'
				],
				'include' => []
			);
			$s         = false;
			$is_search = false;

			// paged
			$args['number'] = 20;
			$args['paged']          = $options['paged'];

			// search
			if ( $options['s'] !== '' ) {

				// strip slashes (search may be integer)
				$s = wp_unslash( strval( $options['s'] ) );

				// update vars
				$args['search'] = '*'.$s.'*';
				$args['search_columns'] = [ 'user_login', 'user_nicename', 'user_email' ];

				$is_search = true;

			}

			// Add specific roles.
			
			if ( $field['role'] ) {
				$args['role__in'] = acf_array( $field['role'] );
			}

			// taxonomy
			if ( ! empty( $field['taxonomy'] ) ) {

				// vars
				$terms = acf_decode_taxonomy_terms( $field['taxonomy'] );

				// append to $args
				$args['tax_query'] = array();

				// now create the tax queries
				foreach ( $terms as $k => $v ) {

					$args['tax_query'][] = array(
						'taxonomy' => $k,
						'field'    => 'slug',
						'terms'    => $v,
					);

				}
			}

			/* if ( ! empty( $field['user_author'] ) ) {
				$args['author'] = get_current_user_id();
			}
 */
			// filters
			$args = apply_filters( 'frontend_admin/fields/user_to_edit/query', $args, $field, $options['user_id'] );
			$args = apply_filters( 'frontend_admin/fields/user_to_edit/query/name=' . $field['name'], $args, $field, $options['user_id'] );
			$args = apply_filters( 'frontend_admin/fields/user_to_edit/query/key=' . $field['key'], $args, $field, $options['user_id'] );


			// get users grouped by user type
			$users = acf_get_users(
				$args
			);

			error_log( print_r( $args, true ) );
			error_log( print_r( $users, true ) );


			// bail early if no users
			if ( empty( $users ) && ! isset( $new_item ) ) {
				return false;
			}

			foreach ( $users as $user ) {

				$results[] = array(
					'id'   => $user->ID,
					'text' => $this->get_user_title( $user, $field, $options['user_id'], $is_search ),
				);
			}

			// vars
			$response = array(
				'results' => $results,
				'limit'   => $args['number'],
			);

			// return
			return $response;

		}


		/*
		*  get_user_result
		*
		*  This function will return an array containing id, text and maybe description data
		*
		*  @type    function
		*  @date    7/07/2016
		*  @since   5.4.0
		*
		*  @param   $id (mixed)
		*  @param   $text (string)
		*  @return  (array)
		*/

		function get_user_result( $id, $text ) {
			// vars
			$result = array(
				'id'   => $id,
				'text' => $text,
			);

			// look for parent
			$search = '| ' . __( 'Parent', 'acf' ) . ':';
			$pos    = strpos( $text, $search );

			if ( $pos !== false ) {

				$result['description'] = substr( $text, $pos + 2 );
				$result['text']        = substr( $text, 0, $pos );

			}

			// return
			return $result;

		}


		/*
		*  get_user_title
		*
		*  This function returns the HTML for a result
		*
		*  @type    function
		*  @date    1/11/2013
		*  @since   5.0.0
		*
		*  @param   $user (object)
		*  @param   $field (array)
		*  @param   $user_id (int) the user_id to which this value is saved to
		*  @return  (string)
		*/

		function get_user_title( $user, $field, $user_id = 0, $is_search = 0 ) {
			// get user_id
			if ( ! $user_id ) {
				$user_id = acf_get_form_data( 'user_id' );
			}

			// vars
			$title = $user->display_name;

			if( ! $title ){
				$title = $user->nicename ?? '';
			}
			if( ! $title ){
				$title = $user->user_login;
			}

		


			// filters
			$title = apply_filters( 'frontend_admin/fields/user_to_edit/result', $title, $user, $field, $user_id );
			$title = apply_filters( 'frontend_admin/fields/user_to_edit/result/name=' . $field['_name'], $title, $user, $field, $user_id );
			$title = apply_filters( 'frontend_admin/fields/user_to_edit/result/key=' . $field['key'], $title, $user, $field, $user_id );

			// return
			return $title;
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
			if ( empty( $field['placeholder'] ) ) {
				$field['placeholder'] = __( 'Select User', 'acf-frontend-form-element' );
			}

			// Change Field into a select
			$field['allow_null'] = 1;
			$field['type']       = 'select';
			$field['ui']         = 1; 
			$field['ajax']       = 1;
			$field['nonce']   = wp_create_nonce( 'acf_field_' . $this->name . '_' . $field['key'] );

			if ( $field['add_new'] ) {
				$add_new_text     = $field['add_new_text'] ? $field['add_new_text'] : __( 'New User', 'acf-frontend-form-element' );
				$field['choices'] = array( 'add_user' => $add_new_text );
			} else {
				$field['choices'] = array();
			}

			// Populate choices.
			if ( $field['value'] ) {

				// Clean value into an array of IDs.
				$user_ids = array_map( 'intval', acf_array( $field['value'] ) );

				// Find users in database (ensures all results are real).
				$users = acf_get_users(
					array(
						'include' => $user_ids,
					)
				);

				// Append.
				if ( $users ) {
					foreach ( $users as $user ) {
						$field['choices'][ $user->ID ] = $this->get_user_title( $user, $field, $field['value'] );
					}
				}
			}
			// render
			acf_render_field( $field );
		}


		/*
		*  render_field_settings()
		*
		*  Create extra options for your field. This is rendered when editing a field.
		*  The value of $field['name'] can be used (like bellow) to save extra data to the $field
		*
		*  @type    action
		*  @since   3.6
		*  @date    23/01/13
		*
		*  @param   $field  - an array holding all the field's data
		*/

		function render_field_settings( $field ) {
			/* acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Filter by User', 'acf-frontend-form-element' ),
					'instructions' => __( 'Only show users by the following users', 'acf-frontend-form-element' ),
					'type'         => 'select',
					'name'         => 'user_author',
					'choices'      => array( 'current_user' => __( 'Current User' ) ),
					'multiple'     => 1,
					'ui'           => 1,
					'allow_null'   => 1,
					'placeholder'  => '',
				)
			); */

			//url query to set the id of the user to edit
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Url Query', 'acf-frontend-form-element' ),
					'instructions' => __( 'Set the user to edit by the url query', 'acf-frontend-form-element' ),
					'type'         => 'text',
					'name'         => 'url_query',
					'placeholder'  => 'user_id',
				)
			);


			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Filter by Role', 'acf' ),
					'instructions' => '',
					'type'         => 'select',
					'name'         => 'role',
					'choices'      => acf_get_user_role_labels(),
					'multiple'     => 1,
					'ui'           => 1,
					'allow_null'   => 1,
					'placeholder'  => __( 'All user roles', 'acf' ),
				)
			);

			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Placeholder', 'acf-frontend-form-element' ),
					'instructions' => '',
					'name'         => 'placeholder',
					'type'         => 'text',
					'placeholder'  => __( 'Select User', 'acf-frontend-form-element' ),
				)
			);
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'Add New User?', 'acf-frontend-form-element' ),
					'instructions' => '',
					'name'         => 'add_new',
					'type'         => 'true_false',
					'ui'           => 1,
				)
			);
			acf_render_field_setting(
				$field,
				array(
					'label'        => __( 'New User Text', 'acf-frontend-form-element' ),
					'instructions' => '',
					'name'         => 'add_new_text',
					'type'         => 'text',
					'placeholder'  => __( 'New User', 'acf-frontend-form-element' ),
					'conditions'   => array(
						array(
							array(
								'field'    => 'add_new',
								'operator' => '==',
								'value'    => 1,
							),
						),
					),
				)
			);

			/*
			 // multiple
			acf_render_field_setting(
			$field,
			array(
			'label'        => __( 'Select multiple values?', 'acf' ),
			'instructions' => '',
			'name'         => 'multiple',
			'type'         => 'true_false',
			'ui'           => 1,
			)
			); */

		}


		/*
		*  load_value()
		*
		*  This filter is applied to the $value after it is loaded from the db
		*
		*  @type    filter
		*  @since   3.6
		*  @date    23/01/13
		*
		*  @param   $value (mixed) the value found in the database
		*  @param   $user_id (mixed) the $user_id from which the value was loaded
		*  @param   $field (array) the field array holding all the field options
		*  @return  $value
		*/

		function load_value( $value, $post_id, $field ) {
			global $fea_form;
			$user_id = $fea_form['user_id'] ?? 'none';

			if ( $user_id == 'none' ) {
				return '';
			}

			// return
			return $user_id;

		}

		/*
		*  update_value()
		*
		*  This filter is appied to the $value before it is updated in the db
		*
		*  @type    filter
		*  @since   3.6
		*  @date    23/01/13
		*
		*  @param   $value - the value which will be saved in the database
		*  @param   $user_id - the $user_id of which the value will be saved
		*  @param   $field - the field array holding all the field options
		*
		*  @return  $value - the modified value
		*/

		function update_value( $value, $user_id, $field ) {
			 return null;
		}

		
		function prepare_field( $field ) {
			if( ! empty( $GLOBALS['admin_form'] ) ){
				$form = $GLOBALS['admin_form'];
				if( ! empty( $form['submission'] ) ){
					return false;
				}
			}

			if( $field['url_query'] ){
				$field['wrapper']['data-url_query'] = $field['url_query'];
			}
			return $field;
		}


		/*
		*  get_users
		*
		*  This function will return an array of users for a given field value
		*
		*  @type    function
		*  @date    13/06/2014
		*  @since   5.0.0
		*
		*  @param   $value (array)
		*  @return  $value
		*/

		function get_users( $value, $field ) {
			// numeric
			$value = acf_get_numeric( $value );

			// bail early if no value
			if ( empty( $value ) ) {
				return false;
			}

			$args = array(
				'include'  => $value,
				'role__in' => $field['role'],
			);
			/* if ( ! empty( $field['user_author'] ) ) {
				$args['author'] = get_current_user_id();
			} */
			// get users
			$users = acf_get_users(
				array(
					'include' => $user_ids,
				)
			);

			// return
			return $users;

		}

	}




endif; // class_exists check


