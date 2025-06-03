<?php
namespace Frontend_Admin\Elementor\Widgets;





/**

 *
 * @since 1.0.0
 */
class New_User_Widget extends ACF_Form {


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
		return __( 'New User Form', 'acf-frontend-form-element' );
	}


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
		return 'new_user';
	}

			/**
	 * Get widget defaults.
	 *
	 * Retrieve acf form widget defaults.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget defaults.
	 */
	public function get_form_defaults() {
		return array(
			'custom_fields_save' => 'user',
			'form_title'         => '',
			'submit'             => __( 'Submit', 'acf-frontend-form-element' ),
			'success_message'    => __( 'Your account has been registered successfully.', 'acf-frontend-form-element' ),
			'field_type'         => 'username',
			'save_to_user' => 'new_user',
			'fields'             => [
				[ 'field_type' => 'username', 'field_label' => 'Username' ],
				[ 'field_type' => 'email', 'field_label' => 'Email' ],
				[ 'field_type' => 'password', 'field_label' => 'Password' ],
				[ 'field_type' => 'first_name', 'field_label' => 'First Name' ],
				[ 'field_type' => 'last_name', 'field_label' => 'Last Name' ],
			]
		);
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
		return 'fas fa-user-plus frontend-icon';
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
		return array( 'frontend-admin-users' );
	}



}
