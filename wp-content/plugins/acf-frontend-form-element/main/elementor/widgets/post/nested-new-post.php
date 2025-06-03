<?php
namespace Frontend_Admin\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_New_Post extends Nested_Form {

	public $post_type;

	public function get_name() {
		$type = $this->post_type->name ?? 'post';
 		return 'nested-new-post-form';
	}

	public function get_title() {
		$type = $this->post_type->labels->singular_name ?? 'Post';
		return sprintf( esc_html__( 'New %s Form (Nestable)', 'elementor' ), $type );
	}

	public function get_icon() {
		return 'eicon-tabs ';
	}

	public function get_keywords() {
		return [ 'nested', 'tabs', 'accordion', 'toggle' ];
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
			'custom_fields_save' => 'post',
			'save_to_post'  => 'new_post',
			'new_post_type' => 'post'
		);
	}

	public function get_default_widgets(){
		return [
			[
				'widgetType' => 'heading',
				'settings' => [ 'title' => __( 'Add New Post', 'frontend-admin' ), ],
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

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		global $fea_elementor_post_type;
		$this->post_type = $fea_elementor_post_type;
		

	}


}