<?php
namespace Frontend_Admin\Elementor\Widgets;





/**

 *
 * @since 1.0.0
 */
class New_Term_Widget extends ACF_Form {


	
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
		return __( 'New Taxonomy Form', 'acf-frontend-form-element' );
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
		return 'new_term';
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
			'success_message'    => __( 'Term Added.', 'acf-frontend-form-element' ),
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
		return 'eicon-plus frontend-icon';
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
			'new term',
			'new taxonomy',
			'mew category',
			'new tag',
			'add term',
			'add taxonomy',
			'add category',
			'add tag',
			'add post tag',
			'add product tag',
			'add product category',
			'add custom taxonomy',
		] );
		return $keywords;
	}
}
