<?php

namespace Frontend_Admin\Elementor\Widgets;

use  Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**

 *
 * @since 1.0.0
 */
class Post_Author_Field extends Text_Field {

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
		return 'fea_post_author_field';
	}

	/**
	 * Is meta field.
	 * 
	 * Check if the field is a meta field.
	 * 
	 * @since 1.0.0
	 */
	public function is_meta_field(){
		return false;
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
		return 'post_author_field';
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
		return __( 'Post Author Field', 'acf-frontend-form-element' );
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
		return 'eicon-form-horizontal frontend-icon';
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

	public function field_specific_controls() {
		$this->add_control(
			'role',
			[
				'label' => __( 'Filter by Roles', 'acf-frontend-form-element' ),
				'type' => Controls_Manager::SELECT2,
				'options' => [
					'administrator' => __( 'Administrator', 'acf-frontend-form-element' ),
					'editor' => __( 'Editor', 'acf-frontend-form-element' ),
					'author' => __( 'Author', 'acf-frontend-form-element' ),
					'contributor' => __( 'Contributor', 'acf-frontend-form-element' ),
					'subscriber' => __( 'Subscriber', 'acf-frontend-form-element' ),
				],
				'multiple' => 1,
				'default' => [],
			]
		);
	}

	protected function get_field_data( $field ) {
		$field = parent::get_field_data( $field );
		$field['type'] = 'post_author';
		$field['role'] = $this->get_settings( 'role' );
		return $field;
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
		return array( 'frontend-admin-posts' );
	}


}
