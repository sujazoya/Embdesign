<?php
namespace Frontend_Admin\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_Edit_Post extends Nested_Form {

	public function get_name() {
		return 'nested-edit-post-form';
	}

	public function get_title() {
		return esc_html__( 'Edit Post Form (Nestable)', 'elementor' );
	}

	public function get_icon() {
		return 'eicon-tabs';
	}

	public function get_keywords() {
		return [ 'nested', 'tabs', 'accordion', 'toggle' ];
	}

	public function get_default_widgets(){
		return [
			[
				'widgetType' => 'heading',
				'settings' => [ 'title' => __( 'Edit Post', 'frontend-admin' ), ],
			],
			[
				'widgetType' => 'fea_post_title_field',
				'settings' => []
			],
			[
				'widgetType' => 'fea_post_content_field',
				'settings' => []
			],
			[
				'widgetType' => 'fea_post_excerpt_field',
				'settings' => []
			],
			[
				'widgetType' => 'fea_featured_image_field',
				'settings' => []
			],
			[
				'widgetType' => 'fea_taxonomy_field',
				'settings' => [
					'field_label' => __( 'Category', 'frontend-admin' ),
					'field_name' => 'category',
					'taxonomy' => 'category',
					'custom_fields_save' => 'post',
					'field_type' => 'multi_select',
				]
			],
			[
				'widgetType' => 'fea_taxonomy_field',
				'settings' => [
					'field_label' => __( 'Tags', 'frontend-admin' ),
					'field_name' => 'tags',
					'taxonomy' => 'post_tag',
					'custom_fields_save' => 'post',
					'field_type' => 'multi_select',
				]
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
		return array( 'frontend-admin-posts' );
	}


}