<?php
namespace Frontend_Admin\Elementor\Widgets;


/**

 *
 * @since 1.0.0
 */
class Edit_Term_Widget extends ACF_Form {


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
		return __( 'Edit Taxonomy Form', 'acf-frontend-form-element' );
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
		return 'edit_term';
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
			'custom_fields_save' => 'term',
			'form_title'         => '',
			'submit'             => __( 'Submit', 'acf-frontend-form-element' ),
			'success_message'    => __( 'Term Edited.', 'acf-frontend-form-element' ),
			'field_type'         => 'term_name',
			'save_to_term ' => 'edit_term',
			'fields'             => [
				[ 'field_type' => 'term_name', 'field_label' => 'Term Name' ],
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
		return 'eicon-pencil frontend-icon';
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
		return array( 'frontend-admin-taxonomies' );
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
		$keywords = parent::get_keywords();
		$keywords = array_merge( $keywords, [ 
			'edit taxonomy',
			'edit term',
			'edit category',
			'edit tag',
			'edit post tag',
			'edit product tag',
			'edit product category',
			'edit custom taxonomy',
		] );
		return $keywords;
	}
}
