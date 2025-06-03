<?php
namespace Frontend_Admin\Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_New_Post extends FrontendForm {
	public $category = 'fea-post';
	public $name     = '';
	public $icon     = 'fas fa-plus'; // FontAwesome 5 icon in builder (https://fontawesome.com/icons)
	public $scripts  = [];
	public $nestable = true;
	public $current_post_type = 'post';
	public $post_type_label = 'Post';
	public $custom_fields_save = 'post';

	public function __construct( $settings = [] ) {

		$this->current_post_type = $settings['post_type'] ?? 'post';
		$this->post_type_label = $settings['label'] ?? 'Post';

		$this->name = 'new-' . $this->current_post_type . '-form';
		parent::__construct( $settings );


	}

	public function get_label() {

		return esc_html__( sprintf( 'New %s Form',$this->post_type_label ), 'frontend-admin' );
	}

	


	public function get_keywords() {
		return [ 'nested', 'post', 'frontend dashboard', 'frontend form', 'new post' ];
	}


	public function set_controls() {
		parent::set_controls();

		$this->controls['save_to_post']['default'] = 'new_post';
		$this->controls['new_post_type']['default'] = $this->current_post_type;
		$this->controls['update_message']['default'] = sprintf( esc_html__( '%s Created Successfully', 'frontend-admin' ), $this->post_type_label );
	
	}

	
	/**
	 * Get child elements
	 *
	 * @return array Array of child elements.
	 *
	 * @since 1.5
	 */
	public function get_nestable_children() {
		$children = [
			[
				'name'     => 'fea-post_title-field',
				'settings' => [
					'field_label' => esc_html__( 'Title', 'frontend-admin' ),
					'style' => 'primary'
				],
			],
			[  
				'name'	 => 'fea-featured_image-field',
				'settings' => [
					'field_label' => esc_html__( 'Featured Image', 'frontend-admin' ),
					'style' => 'primary'
				],
			],
			[
				'name'     => 'fea-post_content-field',
				'settings' => [
					'field_label' => esc_html__( 'Content', 'frontend-admin' ),
					'style' => 'primary'
				],
			],
			[
				'name'     => 'fea-post_excerpt-field',
				'settings' => [
					'field_label' => esc_html__( 'Excerpt', 'frontend-admin' ),
					'style' => 'primary'
				],
			]
		];

		//see if acf is installed
		if ( function_exists('acf_get_field_groups') ) {
			$field_groups = acf_get_field_groups( [ 'post_type' => $this->current_post_type ] );
			if ( ! empty( $field_groups ) ) {
				foreach ( $field_groups as $field_group ) {
					$children[] = [
						'name'     => 'fea-acf-fields',
						'settings' => [
							'fields_select' => [ $field_group['key'] ],
						],
					];
				}
			}
		}

		$children[] = [
			'name'     => 'fea-submit-button',
			'settings' => [
				'text' => esc_html__( 'Submit Form', 'frontend-admin' ),
				'style' => 'primary'
			],
		];

		/**
		 * NOTE: Required classes for element styling & script:
		 *
		 * .tab-menu
		 * .tab-title
		 * .tab-content
		 * .tab-pane
		 */
		return [
			// Content
			[
				'name'     => 'block',
				'label'    => esc_html__( 'Fields', 'bricks' ),
				'settings' => [
					'_hidden' => [
						'_cssClasses' => 'tab-content',
						'_width' => '80%',
					],
				],
				'children' => $children,
			],
		];
	}


}