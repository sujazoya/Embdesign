<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'fea_instance' ) ) {
	function fea_instance() {
		global $fea_instance;

		if ( ! isset( $fea_instance ) ) {
			die( __( 'Frontend Admin is not working properly. Please contact support.', 'frontend-admin' ) );
		}

		return $fea_instance;
	}
}

function feadmin_form_types() {
	 $form_types = array(
		 'general'                                 => __( 'Frontend Form', 'acf-frontend-form-element' ),
		 __( 'Post', 'acf-frontend-form-element' ) => array(
			 'new_post'       => __( 'New Post Form', 'acf-frontend-form-element' ),
			 'edit_post'      => __( 'Edit Post Form', 'acf-frontend-form-element' ),
			 'duplicate_post' => __( 'Duplicate Post Form', 'acf-frontend-form-element' ),
			 'delete_post'    => __( 'Delete Post Button', 'acf-frontend-form-element' ),
			 'status_post'    => __( 'Post Status Button', 'acf-frontend-form-element' ),
		 ),
		 __( 'User', 'acf-frontend-form-element' ) => array(
			 'new_user'    => __( 'New User Form', 'acf-frontend-form-element' ),
			 'edit_user'   => __( 'Edit User Form', 'acf-frontend-form-element' ),
			 'delete_user' => __( 'Delete User Button', 'acf-frontend-form-element' ),
		 ),
		 __( 'Term', 'acf-frontend-form-element' ) => array(
			 'new_term'    => __( 'New Taxonomy Form', 'acf-frontend-form-element' ),
			 'edit_term'   => __( 'Edit Taxonomy Form', 'acf-frontend-form-element' ),
			 'delete_term' => __( 'Delete Term Button', 'acf-frontend-form-element' ),
		 ),
	 );
	 $form_types = apply_filters( 'frontend_admin/forms/form_types', $form_types );

	 return $form_types;
}

function feadmin_user_exists( $id ) {
	global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $id ) );

	if ( $count == 1 ) {
		return true;
	}

	return false;

}

function feadmin_get_field_data( $type = null, $form_fields = false ) {
	 $field_types = array();
	if ( ! $form_fields ) {
		$GLOBALS['only_acf_field_groups'] = 1;
	}
	$acf_field_groups                 = acf_get_field_groups();
	$GLOBALS['only_acf_field_groups'] = 0;
	// bail early if no field groups
	if ( empty( $acf_field_groups ) ) {
		die();
	}
	// loop through array and add to field 'choices'
	if ( $acf_field_groups ) {
		foreach ( $acf_field_groups as $field_group ) {
			if ( ! empty( $field_group['frontend_admin_group'] ) ) {
				continue;
			}
			$field_group_fields = acf_get_fields( $field_group['key'] );
			if ( is_array( $field_group_fields ) ) {
				foreach ( $field_group_fields as $acf_field ) {
					if ( $type ) {
						if ( ( is_array( $type ) && in_array( $acf_field['type'], $type, true ) ) || ( ! is_array( $type ) && $acf_field['type'] == $type ) ) {
							$field_types[ $acf_field['key'] ] = $acf_field['label'];
						}
					} else {
						$field_types[ $acf_field['key'] ]['type']  = $acf_field['type'];
						$field_types[ $acf_field['key'] ]['label'] = $acf_field['label'];
						$field_types[ $acf_field['key'] ]['name']  = $acf_field['name'];
					}
				}
			}
		}
	}
	return $field_types;
}



function feadmin_get_user_roles( $exceptions = array(), $all = false ) {
	if ( ! current_user_can( 'administrator' ) ) {
		$exceptions[] = 'administrator';
	}

	$user_roles = array();

	if ( $all ) {
		$user_roles['all'] = __( 'All', 'acf-frontend-form-element' );
	}
	global $wp_roles;
	// loop through array and add to field 'choices'
	foreach ( $wp_roles->roles as $role => $settings ) {
		if ( ! in_array( strtolower( $role ), $exceptions ) ) {
			$user_roles[ $role ] = $settings['name'];
		}
	}
	return $user_roles;
}
function feadmin_get_user_caps( $exceptions = array(), $all = false ) {
	 $user_caps = array();

	$data = get_userdata( get_current_user_id() );

	if ( is_object( $data ) ) {
		$current_user_caps = $data->allcaps;
		foreach ( $current_user_caps as $cap => $true ) {
			if ( ! in_array( strtolower( $cap ), $exceptions ) ) {
				$user_caps[ $cap ] = $cap;
			}
		}
	}

	return $user_caps;
}

function feadmin_get_acf_group_choices() {
	$field_group_choices = array();
	$acf_field_groups    = acf_get_field_groups();
	// loop through array and add to field 'choices'
	if ( is_array( $acf_field_groups ) ) {
		foreach ( $acf_field_groups as $field_group ) {
			if ( is_array( $field_group ) && ! isset( $field_group['frontend_admin_group'] ) ) {
				$field_group_choices[ $field_group['key'] ] = $field_group['title'];
			}
		}
	}
	return $field_group_choices;
}

/*
 add_filter('acf/get_fields', function( $fields, $parent ){
	$group = explode( 'acfef_', $parent['key'] );

	if( empty( $group[1] ) ) return $fields;

	return array();
}, 5, 2);
 */

function feadmin_get_acf_field_choices( $filter = array(), $return = 'label' ) {
	$all_fields = array();
	if ( isset( $filter['groups'] ) ) {
		$acf_field_groups = $filter['groups'];
	} else {
		$acf_field_groups = acf_get_field_groups( $filter );
	}

	// bail early if no field groups
	if ( empty( $acf_field_groups ) ) {
		return array();
	}

	foreach ( $acf_field_groups as $group ) {
		if ( ! is_array( $group ) ) {
			$group = acf_get_field_group( $group );
		}
		if ( ! empty( $field_group['frontend_admin_group'] ) ) {
			continue;
		}

		$group_fields = acf_get_fields( $group );

		if ( is_array( $group_fields ) ) {
			foreach ( $group_fields as $acf_field ) {
				if ( ! is_array( $acf_field ) ) {
					continue;
				}

				$acf_field_key = $acf_field['type'] == 'clone' ? $acf_field['__key'] : $acf_field['key'];
				if ( ! empty( $filter['type'] ) && $filter['type'] == $acf_field['type'] ) {
					$all_fields[ $acf_field['name'] ] = $acf_field[ $return ];
				} else {
					if ( isset( $filter['groups'] ) ) {
						$all_fields[ $acf_field_key ] = $acf_field[ $return ];
					} else {
						$all_fields[ $acf_field_key ] = $acf_field[ $return ];
					}
				}
			}
		}
	}

	return $all_fields;
}

function feadmin_get_post_type_choices() {
	$post_type_choices = array();
	$args              = array();
	$output            = 'names'; // names or objects, note names is the default
	$operator          = 'and'; // 'and' or 'or'
	$post_types        = get_post_types( $args, $output, $operator );
	// loop through array and add to field 'choices'
	if ( is_array( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
			$post_type_choices[ $post_type ] = str_replace( '_', ' ', ucfirst( $post_type ) );
		}
	}
	return $post_type_choices;
}

function feadmin_get_random_string( $length = 15 ) {
	$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen( $characters );
	$randomString     = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
	}
	return $randomString;
}


function feadmin_get_client_ip() {
	$server_ip_keys = array(
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'REMOTE_ADDR',
	);

	foreach ( $server_ip_keys as $key ) {
		if ( isset( $_SERVER[ $key ] ) && filter_var( $_SERVER[ $key ], FILTER_VALIDATE_IP ) ) {
			return esc_html( $_SERVER[ $key ] );
		}
	}

	// Fallback local ip.
	return '127.0.0.1';
}

function feadmin_get_site_domain() {
	return str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
}



function feadmin_duplicate_slug( $prefix = '' ) {
	static $i;
	if ( null === $i ) {
		$i = 2;
	} else {
		$i ++;
	}
	$new_slug = sprintf( '%s_copy%s', $prefix, $i );
	if ( ! feadmin_slug_exists( $new_slug ) ) {
		return $new_slug;
	} else {
		return feadmin_duplicate_slug( $prefix );
	}
}

function feadmin_slug_exists( $post_name ) {
	global $wpdb;
	if ( $wpdb->get_row( "SELECT post_name FROM $wpdb->posts WHERE post_name = '$post_name'", 'ARRAY_A' ) ) {
		return true;
	} else {
		return false;
	}
}

function feadmin_sanitize_input ( $data = false ) {
	if( ! $data ) return $data;
	if( is_array( $data ) ){
		return feadmin_sanitize_array( $data );
	}else{
		return wp_kses_post( $data );
	}
}


function feadmin_sanitize_array ($data = array()) {
		if (!is_array($data) || !count($data)) {
		return array();
	}

	foreach ($data as $k => $v) {
		if (!is_array($v) && !is_object($v)) {

			//if $k is "to", "cc", or "bcc" get the text between the < and the >
			if( in_array( $k, [ 'email_to', 'email_to_cc', 'email_to_bcc', 'email_from', 'email_reply_to' ] ) ) {
				$addresses = explode( ',', $v );
				foreach( $addresses as $key => $address ) {
					$address = trim( $address );

					//check if there is "<"
					if( strpos( $address, '<' ) !== false ){
						$address = explode( '<', $address );
						
						$name = $address[0];
						$email = $address[1];
						$email = sanitize_text_field( $email );
						$email = str_replace( '>', '', $email );

						if( $name ){
							$name = sanitize_text_field( $name );
							$address = $name.' <'.$email.'>';
						}else{
							$address = $email;
						}
					}else{
						$address = sanitize_text_field( $address );
						$address = str_replace( '>', '', $address );
					}
					$addresses[ $key ] = $address;
				}
				$v = implode( ', ', $addresses );
			}else{
				if( ! $v ){
					$data[$k] = '';
				}else{	
					$v = wp_kses_post($v);
					$data[$k] = $v;
				}	
			}
		}
		if (is_array($v)) {
			$data[$k] = feadmin_sanitize_array($v);
		}

	}

	return $data;
}

function feadmin_parse_args( $args, $defaults ) {
	$new_args = (array) $defaults;

	if ( ! is_array( $args ) ) {
		return $defaults;
	}
	foreach ( $args as $key => $value ) {
		if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
			$new_args[ $key ] = feadmin_parse_args( $value, $new_args[ $key ] );
		} else {
			$new_args[ $key ] = $value;
		}
	}

	return $new_args;
}

function feadmin_edit_mode() {
	$edit_mode = false;

	if ( ! empty( fea_instance()->elementor ) ) {
		$edit_mode = \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	if ( ! empty( $GLOBALS['admin_form']['preview_mode'] ) ) {
		$edit_mode = true;
	}
    
    $current_screen = get_current_screen();
    if ( $current_screen instanceof WP_Screen && ! empty( $current_screen->is_block_editor ) ) {
        $edit_mode = true;
    }

	return $edit_mode;
}

function feadmin_get_product_object() {
	if ( isset( $GLOBALS['admin_form']['save_to_product'] ) ) {
		$form = $GLOBALS['admin_form'];

		if ( $form['save_to_product'] == 'edit_product' ) {
			return wc_get_product( $form['product_id'] );
		}
	}
	return false;
}

function feadmin_get_field_type_groups( $type = 'all' ) {
	$fields = array();
	if ( $type == 'all' ) {
		$fields['acf']    = array(
			'label'   => __( 'ACF Field', 'acf-frontend-form-element' ),
			'options' => array(
				'ACF_fields'       => __( 'ACF Fields', 'acf-frontend-form-element' ),
				'ACF_field_groups' => __( 'ACF Field Groups', 'acf-frontend-form-element' ),
			),
		);
		$fields['layout'] = array(
			'label'   => __( 'Layout', 'acf-frontend-form-element' ),
			'options' => array(
				'message' => __( 'Message', 'acf-frontend-form-element' ),
			// 'tab'  => __( 'Tab', 'acf-frontend-form-element' ),
			),
		);
	}
	if ( $type == 'all' || $type == 'post' ) {
		$fields['post'] = array(
			'label'   => __( 'Post' ),
			'options' => array(
				'title'          => __( 'Post Title', 'acf-frontend-form-element' ),
				'slug'           => __( 'Slug', 'acf-frontend-form-element' ),
				'content'        => __( 'Post Content', 'acf-frontend-form-element' ),
				'featured_image' => __( 'Featured Image', 'acf-frontend-form-element' ),
				'excerpt'        => __( 'Post Excerpt', 'acf-frontend-form-element' ),
				'categories'     => __( 'Categories', 'acf-frontend-form-element' ),
				'tags'           => __( 'Tags', 'acf-frontend-form-element' ),
				'author'         => __( 'Post Author', 'acf-frontend-form-element' ),
				'published_on'   => __( 'Published On', 'acf-frontend-form-element' ),
				'post_type'      => __( 'Post Type', 'acf-frontend-form-element' ),
				'menu_order'     => __( 'Menu Order', 'acf-frontend-form-element' ),
				'allow_comments' => __( 'Allow Comments', 'acf-frontend-form-element' ),
				'taxonomy'       => __( 'Custom Taxonomy', 'acf-frontend-form-element' ),
			),
		);
	}
	if ( $type == 'all' || $type == 'user' ) {
		$fields['user'] = array(
			'label'   => __( 'User', 'acf-frontend-form-element' ),
			'options' => array(
				'username'         => __( 'Username', 'acf-frontend-form-element' ),
				'password'         => __( 'Password', 'acf-frontend-form-element' ),
				'confirm_password' => __( 'Confirm Password', 'acf-frontend-form-element' ),
				'email'            => __( 'Email', 'acf-frontend-form-element' ),
				'first_name'       => __( 'First Name', 'acf-frontend-form-element' ),
				'last_name'        => __( 'Last Name', 'acf-frontend-form-element' ),
				'nickname'         => __( 'Nickname', 'acf-frontend-form-element' ),
				'display_name'     => __( 'Display Name', 'acf-frontend-form-element' ),
				'bio'              => __( 'Biography', 'acf-frontend-form-element' ),
				'role'             => __( 'Role', 'acf-frontend-form-element' ),
			),
		);
	}
	if ( $type == 'all' || $type == 'term' ) {

		$fields['term'] = array(
			'label'   => __( 'Term', 'acf-frontend-form-element' ),
			'options' => array(
				'term_name'        => __( 'Term Name', 'acf-frontend-form-element' ),
				'term_slug'        => __( 'Term Slug', 'acf-frontend-form-element' ),
				'term_description' => __( 'Term Description', 'acf-frontend-form-element' ),
			),
		);
	}

	$fields = apply_filters( 'frontend_admin/form/elementor/field_select_options', $fields, $type );

	return $fields;
}


/*
*  get_selected_field
*
*  This function will return the label for a given clone choice
*
*  @type    function
*  @date    17/06/2016
*  @since   5.3.8
*
*  @param   $selector (mixed)
*  @return  (string)
*/

function feadmin_get_selected_field( $selector = '', $type = '' ) {
	// bail early no selector
	if ( ! $selector ) {
		return '';
	}

	if ( is_numeric( $selector ) ) {
		return get_post_field( 'post_title', $selector );
	}
	// ajax_fields
	if ( isset( $_POST['fields'][ $selector ] ) ) {
		$selector = sanitize_text_field( $_POST['fields'][ $selector ] );
		return feadmin_field_choice( $selector );

	}

	// field
	if ( acf_is_field_key( $selector ) ) {

		return feadmin_field_choice( acf_get_field( $selector ) );

	}

	// group
	if ( acf_is_field_group_key( $selector ) ) {

		return feadmin_group_choice( acf_get_field_group( $selector ) );

	}
	if ( feadmin_is_admin_form_key( $selector ) ) {

		return feadmin_group_choice( fea_instance()->form_display->get_form( $selector ) );

	}

	// return
	return $selector;

}
/*
*  feadmin_field_choice
*
*  This function will return the text for a field choice
*
*  @type    function
*  @date    20/07/2016
*  @since   5.4.0
*
*  @param   $field (array)
*  @return  (string)
*/

function feadmin_field_choice( $field ) {
	// bail early if no field
	if ( ! $field ) {
		return __( 'Unknown field', 'acf' );
	}

	// title
	$title = $field['label'] ? $field['label'] : __( '(no title)', 'acf' );

	// append type
	$title .= ' (' . $field['type'] . ')';

	// ancestors
	// - allow for AJAX to send through ancestors count
	$ancestors = isset( $field['ancestors'] ) ? $field['ancestors'] : count( acf_get_field_ancestors( $field ) );
	$title     = str_repeat( '- ', $ancestors ) . $title;

	// return
	return $title;

}


/*
*  feadmin_group_choice
*
*  This function will return the text for a group choice
*
*  @type    function
*  @date    20/07/2016
*  @since   5.4.0
*
*  @param   $field_group (array)
*  @return  (string)
*/

function feadmin_group_choice( $field_group ) {
	// bail early if no field group
	if ( ! $field_group ) {
		return __( 'Unknown field group', 'acf' );
	}

	// return
	return sprintf( __( 'All fields from %s', 'acf-frontend-form-element' ), $field_group['title'] );

}

/*
*  get_selected_fields
*
*  This function will return an array of choices data for Select2
*
*  @type    function
*  @date    17/06/2016
*  @since   5.3.8
*
*  @param   $value (mixed)
*  @return  (array)
*/

function feadmin_get_selected_fields( $value, $choices = array() ) {
	// bail early if no $value
	if ( empty( $value ) ) {
		return $choices;
	}

	// force value to array
	$value = acf_get_array( $value );

	// loop
	foreach ( $value as $v ) {

		$choices[ $v ] = feadmin_get_selected_field( $v );

	}

	// return
	return $choices;

}
function feadmin_is_admin_form_key( $id ) {
	if ( is_string( $id ) && substr( $id, 0, 5 ) === 'form_' ) {
		return true;
	}
	return false;
}

function feadmin_form_choices( $choices = array() ) {
	$args = array(
		'post_type'      => 'admin_form',
		'posts_per_page' => '-1',
		'post_status'    => 'any',
	);

	$forms = get_posts( $args );

	if ( empty( $forms ) ) {
		return $choices;
	}

	foreach ( $forms as $form ) {
		$choices[ $form->ID ] = $form->post_title;
	}

	return $choices;
}

/*
* fea_encrypt
*
*  This function will encrypt an array using PHP
*  https://bhoover.com/using-php-openssl_encrypt-openssl_decrypt-encrypt-decrypt-data/
*
*  @type    function
*  @date    02/11/22
*  @since   3.9.11
*
*  @param   $data (array)
*  @return  (string)
*/
function fea_encrypt( $data = array() ) {
	if ( empty( $data ) ) {
		return false;
	}

	$data = json_encode( $data );

	// bail early if no encrypt function
	if ( ! function_exists( 'openssl_encrypt' ) ) {
		wp_die( 'Please enable the openssl extension in your PHP configuration to use the encryption feature.' ); 
	}

	// generate a key
	$key = wp_hash( 'fea_encrypt' );

	// Generate an initialization vector
	$iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );

	// Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
	$encrypted_data = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );

	// The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
	return base64_encode( $encrypted_data . '::' . $iv );

}

/*
*  fea_decrypt
*
*  This function will decrypt an encrypted string using PHP
*  https://bhoover.com/using-php-openssl_encrypt-openssl_decrypt-encrypt-decrypt-data/
*
*  @type    function
*  @date    02/11/22
*  @since   3.9.11
*
*  @param   $data (string)
*  @return  (array)
*/

function fea_decrypt( $data = '' ) {
	if ( empty( $data ) ) {
		return false;
	}

	// bail early if no decrypt function
	if ( ! function_exists( 'openssl_decrypt' ) ) {
		wp_die( 'Please enable the openssl extension in your PHP configuration to use the encryption feature.' );
	}



	// generate a key
	$key = wp_hash( 'fea_encrypt' );

	// To decrypt, split the encrypted data from our IV - our unique separator used was "::"
	list($encrypted_data, $iv) = explode( '::', base64_decode( $data ), 2 );

	// decrypt
	$data = openssl_decrypt( $encrypted_data, 'aes-256-cbc', $key, 0, $iv );

	return json_decode( $data, true );

}

/**
 * feadmin_get_time_input
 *
 * Returns the HTML of a text input.
 *
 * @date  3/02/2014
 * @since 5.0.0
 *
 * @param  array $attrs The array of attrs.
 * @return string
 */
function feadmin_get_time_input( $attrs = array() ) {
	$attrs = wp_parse_args(
		$attrs,
		array(
			'type' => 'time',
		)
	);
	if ( isset( $attrs['value'] ) && is_string( $attrs['value'] ) ) {
		$attrs['value'] = htmlspecialchars( $attrs['value'] );
	}
	return sprintf( '<input %s/>', feadmin_get_esc_attrs( $attrs ) );
}

/**
 * Determine whether the current user can edit a given user.
 *
 * Only administrators and users with the 'frontend_admin_manager' metadata field set to the current user's ID can edit a user.
 *
 * @param int $user_id The ID of the user to check.
 * @param array $args Additional arguments passed to the function.
 * @return int|bool true if the user can edit it, false if they cannot.
 */
function feadmin_can_edit_user( $user_id, $args ) {
	$current_user = get_current_user_id();

    if( current_user_can( 'edit_users' ) || $current_user == $user_id ){		
		return true;
	}

    // Return the user ID if the user can edit it, or false if they cannot
    return false;
}

/**
 * Determine whether the current user can edit a given post.
 *
 * Only administrators and the post author can edit a post.
 *
 * @param int $post_id The ID of the post to check.
 * @param array $args Additional arguments passed to the function.
 * @return int|bool true if the user can edit it, false if they cannot.
 */
function feadmin_can_edit_post( $post_id, $args ) {
	$current_user = get_current_user_id();
	if( current_user_can( 'edit_others_posts' ) || $current_user == get_post_field( 'post_author', $post_id ) ){
		return true;
	}

	return false;

}

/*
*  feadmin_verify_nonce
*
*  This function will look at the $_POST['_acf_nonce'] value and return true or false
*
*  @type    function
*  @date    15/10/13
*  @since   3.12
*
*  @param   $nonce (string)
*  @return  (boolean)
*/

function feadmin_verify_nonce( $value ) {

	// vars
	$nonce = acf_maybe_get_POST( '_acf_nonce' );

	// bail early nonce does not match (post|user|comment|term)
	if ( ! $nonce || ! wp_verify_nonce( $nonce, $value ) ) {
		return false;
	}

	// reset nonce (only allow 1 save)
	$_POST['_acf_nonce'] = false;

	// return
	return true;

}


/*
*  feadmin_verify_ajax
*
*  This function will return true if the current AJAX request is valid
*  It's action will also allow WPML to set the lang and avoid AJAX get_posts issues
*
*  @type    function
*  @date    7/08/2015
*  @since   5.2.3
*
*  @param   n/a
*  @return  (boolean)
*/


/**
 * Returns true if the current AJAX request is valid.
 * It's action will also allow WPML to set the lang and avoid AJAX get_posts issues
 *
 * @since   5.2.3
 *
 * @param string $nonce  The nonce to check.
 * @param string $action The action of the nonce.
 * @return boolean
 */
function feadmin_verify_ajax( $nonce = '', $action = '' ) {
	// Bail early if we don't have a nonce to check.
	if ( empty( $nonce ) && empty( $_REQUEST['nonce'] ) ) {
		return false;
	}

	$nonce_to_check = ! empty( $nonce ) ? $nonce : $_REQUEST['nonce']; // phpcs:ignore WordPress.Security -- We're verifying a nonce here.
	$nonce_action   = ! empty( $action ) ? $action : 'acf_nonce';

	// Bail if nonce can't be verified.
	if ( ! wp_verify_nonce( sanitize_text_field( $nonce_to_check ), $nonce_action ) ) {
		return false;
	}

	// Action for 3rd party customization (WPML).
	do_action( 'acf/verify_ajax' );

	return true;
}


/**
 * feadmin_get_esc_attrs
 *
 * Generated valid HTML from an array of attrs.
 *
 * @date    11/6/19
 * @since   5.8.1
 *
 * @param   array $attrs The array of attrs.
 * @return  string
 */
function feadmin_get_esc_attrs( $attrs ) {
	$html = '';

	// Loop over attrs and validate data types.
	foreach ( $attrs as $k => $v ) {

		// String (but don't trim value).
		if ( is_string( $v ) && ( $k !== 'value' ) ) {
			$v = trim( $v );

			// Boolean
		} elseif ( is_bool( $v ) ) {
			$v = $v ? 1 : 0;

			// Object
		} elseif ( is_array( $v ) || is_object( $v ) ) {
			$v = json_encode( $v );
		}

		// Generate HTML.
		$html .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
	}

	// Return trimmed.
	return trim( $html );
}


/*
*  feadmin_encode_choices
*
*  description
*
*  @type    function
*  @date    4/06/2014
*  @since   5.0.0
*
*  @param   $post_id (int)
*  @return  $post_id (int)
*/

function feadmin_encode_choices( $array = array(), $show_keys = true ) {

	// bail early if not array (maybe a single string)
	if ( ! is_array( $array ) ) {
		return $array;
	}

	// bail early if empty array
	if ( empty( $array ) ) {
		return '';
	}

	// vars
	$string = '';

	// if allowed to show keys (good for choices, not for default values)
	if ( $show_keys ) {

		// loop
		foreach ( $array as $k => $v ) {

			// ignore if key and value are the same
			if ( strval( $k ) == strval( $v ) ) {
				continue;
			}

			// show key in the value
			$array[ $k ] = $k . ' : ' . $v;

		}
	}

	// implode
	$string = implode( "\n", $array );

	// return
	return $string;

}

function feadmin_decode_choices( $string = '', $array_keys = false ) {

	// bail early if already array
	if ( is_array( $string ) ) {

		return $string;

		// allow numeric values (same as string)
	} elseif ( is_numeric( $string ) ) {

		// do nothing

		// bail early if not a string
	} elseif ( ! is_string( $string ) ) {

		return array();

		// bail early if is empty string
	} elseif ( $string === '' ) {

		return array();

	}

	// vars
	$array = array();

	// explode
	$lines = explode( "\n", $string );

	// key => value
	foreach ( $lines as $line ) {

		// vars
		$k = trim( $line );
		$v = trim( $line );

		// look for ' : '
		if ( feadmin_str_exists( ' : ', $line ) ) {

			$line = explode( ' : ', $line );

			$k = trim( $line[0] );
			$v = trim( $line[1] );

		}

		// append
		$array[ $k ] = $v;

	}

	// return only array keys? (good for checkbox default_value)
	if ( $array_keys ) {

		return array_keys( $array );

	}

	// return
	return $array;

}


/**
 * feadmin_maybe_get
 *
 * This function will return a var if it exists in an array or a default if set
 *
 * @since   5.1.5
 *
 * @param   $array (array) the array to look within
 * @param   $key (key) the array key to look for. Nested values may be found using '/'
 * @param   $default (mixed) the value returned if not found
 * @return  $value | $default (mixed)
 */
function feadmin_maybe_get( $array = array(), $key = 0, $default = null ) {

	return isset( $array[ $key ] ) ? $array[ $key ] : $default;
}



function feadmin_get_currencies(){
	if ( function_exists( 'get_woocommerce_currencies' ) ) {
		return get_woocommerce_currencies();
	}

	if ( class_exists( 'Easy_Digital_Downloads' ) ) {
		return edd_get_currencies();
	}
	

	return [
		'AFN' => 'Afghan Afghani',
		'ALL' => 'Albanian Lek',
		'DZD' => 'Algerian Dinar',
		'AOA' => 'Angolan Kwanza',
		'ARS' => 'Argentine Peso',
		'AMD' => 'Armenian Dram',
		'AWG' => 'Aruban Florin',
		'AUD' => 'Australian Dollar',
		'AZN' => 'Azerbaijani Manat',
		'BSD' => 'Bahamian Dollar',
		'BDT' => 'Bangladeshi Taka',
		'BBD' => 'Barbadian Dollar',
		'BZD' => 'Belize Dollar',
		'BMD' => 'Bermudian Dollar',
		'BOB' => 'Bolivian Boliviano',
		'BAM' => 'Bosnia & Herzegovina Convertible Mark',
		'BWP' => 'Botswana Pula',
		'BRL' => 'Brazilian Real',
		'GBP' => 'British Pound',
		'BND' => 'Brunei Dollar',
		'BGN' => 'Bulgarian Lev',
		'BIF' => 'Burundian Franc',
		'KHR' => 'Cambodian Riel',
		'CAD' => 'Canadian Dollar',
		'CVE' => 'Cape Verdean Escudo',
		'KYD' => 'Cayman Islands Dollar',
		'XAF' => 'Central African Cfa Franc',
		'XPF' => 'Cfp Franc',
		'CLP' => 'Chilean Peso',
		'CNY' => 'Chinese Renminbi Yuan',
		'COP' => 'Colombian Peso',
		'KMF' => 'Comorian Franc',
		'CDF' => 'Congolese Franc',
		'CRC' => 'Costa Rican Colón',
		'HRK' => 'Croatian Kuna',
		'CZK' => 'Czech Koruna',
		'DKK' => 'Danish Krone',
		'DJF' => 'Djiboutian Franc',
		'DOP' => 'Dominican Peso',
		'XCD' => 'East Caribbean Dollar',
		'EGP' => 'Egyptian Pound',
		'ETB' => 'Ethiopian Birr',
		'EUR' => 'Euro',
		'FKP' => 'Falkland Islands Pound',
		'FJD' => 'Fijian Dollar',
		'GMD' => 'Gambian Dalasi',
		'GEL' => 'Georgian Lari',
		'GIP' => 'Gibraltar Pound',
		'GTQ' => 'Guatemalan Quetzal',
		'GNF' => 'Guinean Franc',
		'GYD' => 'Guyanese Dollar',
		'HTG' => 'Haitian Gourde',
		'HNL' => 'Honduran Lempira',
		'HKD' => 'Hong Kong Dollar',
		'HUF' => 'Hungarian Forint',
		'ISK' => 'Icelandic Króna',
		'INR' => 'Indian Rupee',
		'IDR' => 'Indonesian Rupiah',
		'ILS' => 'Israeli New Sheqel',
		'JMD' => 'Jamaican Dollar',
		'JPY' => 'Japanese Yen',
		'KZT' => 'Kazakhstani Tenge',
		'KES' => 'Kenyan Shilling',
		'KGS' => 'Kyrgyzstani Som',
		'LAK' => 'Lao Kip',
		'LBP' => 'Lebanese Pound',
		'LSL' => 'Lesotho Loti',
		'LRD' => 'Liberian Dollar',
		'MOP' => 'Macanese Pataca',
		'MKD' => 'Macedonian Denar',
		'MGA' => 'Malagasy Ariary',
		'MWK' => 'Malawian Kwacha',
		'MYR' => 'Malaysian Ringgit',
		'MVR' => 'Maldivian Rufiyaa',
		'MRO' => 'Mauritanian Ouguiya',
		'MUR' => 'Mauritian Rupee',
		'MXN' => 'Mexican Peso',
		'MDL' => 'Moldovan Leu',
		'MNT' => 'Mongolian Tögrög',
		'MAD' => 'Moroccan Dirham',
		'MZN' => 'Mozambican Metical',
		'MMK' => 'Myanmar Kyat',
		'NAD' => 'Namibian Dollar',
		'NPR' => 'Nepalese Rupee',
		'ANG' => 'Netherlands Antillean Gulden',
		'TWD' => 'New Taiwan Dollar',
		'NZD' => 'New Zealand Dollar',
		'NIO' => 'Nicaraguan Córdoba',
		'NGN' => 'Nigerian Naira',
		'NOK' => 'Norwegian Krone',
		'PKR' => 'Pakistani Rupee',
		'PAB' => 'Panamanian Balboa',
		'PGK' => 'Papua New Guinean Kina',
		'PYG' => 'Paraguayan Guaraní',
		'PEN' => 'Peruvian Nuevo Sol',
		'PHP' => 'Philippine Peso',
		'PLN' => 'Polish Złoty',
		'QAR' => 'Qatari Riyal',
		'RON' => 'Romanian Leu',
		'RUB' => 'Russian Ruble',
		'RWF' => 'Rwandan Franc',
		'STD' => 'São Tomé and Príncipe Dobra',
		'SHP' => 'Saint Helenian Pound',
		'SVC' => 'Salvadoran Colón',
		'WST' => 'Samoan Tala',
		'SAR' => 'Saudi Riyal',
		'RSD' => 'Serbian Dinar',
		'SCR' => 'Seychellois Rupee',
		'SLL' => 'Sierra Leonean Leone',
		'SGD' => 'Singapore Dollar',
		'SBD' => 'Solomon Islands Dollar',
		'SOS' => 'Somali Shilling',
		'ZAR' => 'South African Rand',
		'KRW' => 'South Korean Won',
		'LKR' => 'Sri Lankan Rupee',
		'SRD' => 'Surinamese Dollar',
		'SZL' => 'Swazi Lilangeni',
		'SEK' => 'Swedish Krona',
		'CHF' => 'Swiss Franc',
		'TJS' => 'Tajikistani Somoni',
		'TZS' => 'Tanzanian Shilling',
		'THB' => 'Thai Baht',
		'TOP' => 'Tongan Paʻanga',
		'TTD' => 'Trinidad and Tobago Dollar',
		'TRY' => 'Turkish Lira',
		'UGX' => 'Ugandan Shilling',
		'UAH' => 'Ukrainian Hryvnia',
		'AED' => 'United Arab Emirates Dirham',
		'USD' => 'United States Dollar',
		'UYU' => 'Uruguayan Peso',
		'UZS' => 'Uzbekistani Som',
		'VUV' => 'Vanuatu Vatu',
		'VND' => 'Vietnamese Đồng',
		'XOF' => 'West African Cfa Franc',
		'YER' => 'Yemeni Rial',
		'ZMW' => 'Zambian Kwacha'
	];
}
