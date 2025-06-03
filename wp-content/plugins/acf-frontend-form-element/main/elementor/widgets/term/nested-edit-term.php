<?php
namespace Frontend_Admin\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_Edit_Term extends Nested_Form {

	public function get_name() {
		return 'nested-edit-term-form';
	}

	public function get_title() {
		return esc_html__( 'Edit Taxonomy Form (Nestable)', 'elementor' );
	}

	public function get_icon() {
		return 'eicon-tabs ';
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
			'save_to_term'  => 'edit_term',
		);
	}

	public function get_default_widgets(){
		return [
			[
				'widgetType' => 'heading',
				'settings' => [ 'title' => __( 'Edit Term', 'frontend-admin' ), ],
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


}