<?php
namespace Frontend_Admin\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_New_Term extends Nested_Form {

	public function get_name() {
		return 'nested-new-term-form';
	}

	public function get_title() {
		return esc_html__( 'New Taxonomy Form (Nestable)', 'elementor' );
	}

	public function get_icon() {
		return 'eicon-tabs ';
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
			'save_to_term'  => 'new_term',
		);
	}

	public function get_default_widgets(){
		return [
			[
				'widgetType' => 'heading',
				'settings' => [ 'title' => __( 'Add New Term', 'frontend-admin' ), ],
			],
			[
				'widgetType' => 'fea_term_name_field',
				'settings' => []
			],
			[
				'widgetType' => 'fea_term_slug_field',
				'settings' => []
			],
			[
				'widgetType' => 'submit_button',
				'settings' => [ 'text' => __( 'Submit', 'frontend-admin' ), ],
			],
		];
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
		return array( 'frontend-admin-terms' );
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