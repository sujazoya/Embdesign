<?php
namespace Frontend_Admin\Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontendFormPopup extends \Bricks\Element {
	public $category = 'general';
	public $name     = 'new-post-button';
	public $nestable = true;


	public function get_label() {
		return esc_html__( 'New Post Button', 'bricks' );
	}

	public function set_controls() {
		parent::set_controls();

	
		$this->controls['_conditions']['default'] = [
			[
				[
					'key' => 'user_role',
					'value' => ['administrator'],
				]
			],
			[
				[
					'key' => 'user_id',
					'value' => '{author_id}',
				]
			]
		];
	}

	public function get_nestable_children() {
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
						'name'     => 'button',
						'label'    => esc_html__( 'Add Post', 'bricks' ),
						'settings' => [
							'text' => esc_html__( 'New Post', 'frontend-admin' ),
							'style' => 'primary',
							'icon' => [
								'library' => 'fontawesomeRegular',
								'icon'    => 'fa fa-plus',
							],
							'_interactions' => [
								[
									'trigger' => 'click',
									'action' => 'toggleOffCanvas',
									'offCanvasSelector' => '.new-post-form',
								]
							],
						],
					],
					[
						'name'     => 'offcanvas',
						'label'    => esc_html__( 'New Post Form', 'bricks' ),
						'settings' => [
							'_hidden' => [
								'_cssClasses' => 'offcanvas offcanvas-end new-post-form',
							],
						],
						'children' => [
							[
								'name'      => 'block',
								'label'     => esc_html__( 'Content', 'bricks' ),
								'deletable' => false, // Prevent deleting this element directly. NOTE: Undocumented (@since 1.8)
								'cloneable' => false, // Prevent cloning this element directly.  NOTE: Undocumented (@since 1.8)
								'children'  => [
									[
										'name'     => 'frontend-form',
										'children' => [
											[
												'name'     => 'block',
												'label'    => esc_html__( 'Pane', 'bricks' ),
												'settings' => [
													'_hidden' => [
														'_cssClasses' => 'tab-pane',
													],
												],
												'children' => [
													[
														'name'     => 'fea-post_title-field',
														'settings' => [
															'field_label' => esc_html__( 'Post Title', 'frontend-admin' ),
														],
													],
													[
														'name'     => 'fea-submit-button',
														'settings' => [
															'text' => esc_html__( 'Submit Form', 'frontend-admin' ),
															'style' => 'primary'
														],
													],
												],
											],
										],
									],
									[
										'name'  => 'toggle',
										'label' => esc_html__( 'Toggle', 'bricks' ) . ' (' . esc_html__( 'Close', 'bricks' ) . ')',
									],
								],
								'settings'  => [
									'_hidden' => [
										'_cssClasses' => 'brx-offcanvas-inner',
									],
								],
							],
							// Backdrop (delete to disable)
							[
								'name'     => 'block',
								'label'    => esc_html__( 'Backdrop', 'bricks' ),
								'children' => [],
								'settings' => [
									'_hidden' => [
										'_cssClasses' => 'brx-offcanvas-backdrop',
									],
								],
							],	
						],
						
					],	
						
		];
	}

	public function render() {
		// Render children elements (= individual items)
		echo \Bricks\Frontend::render_children( $this );
	}
}
