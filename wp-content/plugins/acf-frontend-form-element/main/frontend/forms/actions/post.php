<?php
namespace Frontend_Admin\Actions;

use Frontend_Admin\Plugin;
use Frontend_Admin\Classes\ActionBase;
use Frontend_Admin\Forms\Actions;
use Elementor\Controls_Manager;
use ElementorPro\Modules\QueryControl\Module as Query_Module;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ActionPost' ) ) :

	class ActionPost extends ActionBase {


		public function get_name() {
			return 'post';
		}

		public function get_label() {
			return __( 'Post', 'acf-frontend-form-element' );
		}

		public function get_fields_display( $form_field, $local_field, $element = '' ) {
			$field_appearance = isset( $form_field['field_taxonomy_appearance'] ) ? $form_field['field_taxonomy_appearance'] : 'checkbox';
			$field_add_term   = isset( $form_field['field_add_term'] ) ? $form_field['field_add_term'] : 0;

			switch ( $form_field['field_type'] ) {
				case 'title':
					$local_field['type'] = 'post_title';
					break;
				case 'slug':
					$local_field['type']              = 'post_slug';
					$local_field['wrapper']['class'] .= ' post-slug-field';
					break;
				case 'content':
					$local_field['type']       = 'post_content';
					$local_field['field_type'] = isset( $form_field['editor_type'] ) ? $form_field['editor_type'] : 'wysiwyg';
					break;
				case 'featured_image':
					$local_field['type']          = 'featured_image';
					$local_field['default_value'] = empty( $form_field['default_featured_image']['id'] ) ? '' : $form_field['default_featured_image']['id'];
					break;
				case 'excerpt':
					$local_field['type'] = 'post_excerpt';
					break;
				case 'author':
					$local_field['type'] = 'post_author';
					break;
				case 'published_on':
					$local_field['type'] = 'post_date';
					break;
				case 'menu_order':
					$local_field['type'] = 'menu_order';
					break;
				case 'taxonomy':
					$taxonomy                       = isset( $form_field['field_taxonomy'] ) ? $form_field['field_taxonomy'] : 'category';
					$local_field['type']            = 'taxonomy';
					$local_field['taxonomy']        = $taxonomy;
					$local_field['field_type']      = $field_appearance;
					$local_field['allow_null']      = 0;
					$local_field['add_term']        = $field_add_term;
					$local_field['load_post_terms'] = 1;
					$local_field['load_terms'] = 1;					
					$local_field['save_terms']      = 1;
					$local_field['custom_taxonomy'] = true;
					break;
				case 'categories':
					$local_field['type']            = 'taxonomy';
					$local_field['taxonomy']        = 'category';
					$local_field['field_type']      = $field_appearance;
					$local_field['allow_null']      = 0;
					$local_field['add_term']        = $field_add_term;
					$local_field['load_post_terms'] = 1;
					$local_field['load_terms'] = 1;
					$local_field['save_terms']      = 1;
					$local_field['custom_taxonomy'] = true;
					break;
				case 'tags':
					$local_field['type']            = 'taxonomy';
					$local_field['taxonomy']        = 'post_tag';
					$local_field['field_type']      = $field_appearance;
					$local_field['allow_null']      = 0;
					$local_field['add_term']        = $field_add_term;
					$local_field['load_post_terms'] = 1;
					$local_field['load_terms'] = 1;
					$local_field['save_terms']      = 1;
					$local_field['custom_taxonomy'] = true;
					break;
				case 'post_type':
					$local_field['type']          = 'post_type';
					$local_field['field_type']    = isset( $form_field['role_appearance'] ) ? $form_field['role_appearance'] : 'select';
					$local_field['layout']        = isset( $form_field['role_radio_layout'] ) ? $form_field['role_radio_layout'] : 'vertical';
					$local_field['default_value'] = isset( $form_field['default_post_type'] ) ? $form_field['default_post_type'] : 'post';
					if ( isset( $form_field['post_type_field_options'] ) ) {
						$local_field['post_type_options'] = $form_field['post_type_field_options'];
					}
					break;
			}
			return $local_field;
		}

		public function get_default_fields( $form, $action = '' ) {
			switch ( $action ) {
				case 'delete':
					$default_fields = array(
						'delete_post',
					);
					break;
				case 'status':
					$default_fields = array(
						'post_status',
						'submit_button',
					);
					break;
				case 'new':
					$default_fields = array(
						'post_title',
						'post_content',
						'post_excerpt',
						'featured_image',
						'post_status',
						'submit_button',
					);
					break;
				default:
					$default_fields = array(
						'post_to_edit',
						'post_title',
						'post_excerpt',
						'featured_image',
						'post_status',
						'submit_button',
					);
					break;
			}
			return $this->get_valid_defaults( $default_fields, $form );
		}

		public function get_form_builder_options( $form ) {
			$post_types = acf_get_pretty_post_types();
			 $options = array(
				 array(
					 'key'               => 'save_to_post',
					 'field_label_hide'  => 1,
					 'type'              => 'select',
					 'instructions'      => __( 'If there is a Post to Edit field in the form, these settings will be overwritten.', 'acf-frontend-form-element' ),
					 'required'			 => 0,           
					 'conditional_logic' => 0,
					 'choices'           => array(
						 'edit_post'      => __( 'Edit Post', 'acf-frontend-form-element' ),
						 'new_post'       => __( 'New Post', 'acf-frontend-form-element' ),
						 'duplicate_post' => __( 'Duplicate Post', 'acf-frontend-form-element' ),
					 ),
					 'allow_null'        => 0,
					 'multiple'          => 0,
					 'ui'                => 0,
					 'return_format'     => 'value',
					 'ajax'              => 0,
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'new_post_type',
					 'label'             => __( 'Post Type', 'acf-frontend-form-element' ),
					 'type'              => 'select',
					 'instructions'      => '',
					 'required'          => 0,
					 'choices'           => $post_types,
					 'default_value'     => false,
					 'conditional_logic' => array(
						array(
							array(
								'field'    => 'save_to_post',
								'operator' => '==',
								'value'    => 'new_post',
							),
						),
					),
					 'allow_null'        => 0,
					 'multiple'          => 0,
					 'ui'                => 0,
					 'return_format'     => 'value',
					 'ajax'              => 0,
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'post_to_edit',
					 'label'             => __( 'Post', 'acf-frontend-form-element' ),
					 'type'              => 'select',
					 'instructions'      => '',
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '!=',
								 'value'    => 'new_post',
							 ),
						 ),
					 ),
					 'choices'           => array(
						 'current_post' => __( 'Current Post', 'acf-frontend-form-element' ),
						 'url_query'    => __( 'URL Query', 'acf-frontend-form-element' ),
						 'select_post'  => __( 'Specific Post', 'acf-frontend-form-element' ),
						 'user_first_post' => __( 'User\'s First Post', 'acf-frontend-form-element' ),
						 'user_last_post' => __( 'User\'s Most Recent Post', 'acf-frontend-form-element' ),
					 ),
					 'default_value'     => false,
					 'allow_null'        => 0,
					 'multiple'          => 0,
					 'ui'                => 0,
					 'return_format'     => 'value',
					 'ajax'              => 0,
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'url_query_post',
					 'label'             => __( 'URL Query Key', 'acf-frontend-form-element' ),
					 'type'              => 'text',
					 'instructions'      => '',
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '!=',
								 'value'    => 'new_post',
							 ),
							 array(
								 'field'    => 'post_to_edit',
								 'operator' => '==',
								 'value'    => 'url_query',
							 ),
						 ),
					 ),
					 'placeholder'       => '',
				 ),
				 array(
					'key'               => 'post_type',
					'label'             => __( 'Post Type', 'acf-frontend-form-element' ),
					'type'              => 'select',
					'instructions'      => '',
					'required'          => 0,
					'choices'           => array_merge(
					   $post_types
					),
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'save_to_post',
								'operator' => '!=',
								'value'    => 'new_post',
							),
						),
					),
					'default_value'     => [],
					'allow_null'        => 0,
					'multiple'          => 1,
					'ui'                => 1,
					'return_format'     => 'value',
					'ajax'              => 0,
					'placeholder'       => __('Any', 'acf-frontend-form-element' ),
				),
				 array(
					 'key'               => 'select_post',
					 'label'             => __( 'Post', 'acf-frontend-form-element' ),
					 'name'              => 'select_post',
					 'prefix'            => 'form',
					 'type'              => 'post_object',
					 'instructions'      => '',
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '!=',
								 'value'    => 'new_post',
							 ),
							 array(
								 'field'    => 'post_to_edit',
								 'operator' => '==',
								 'value'    => 'select_post',
							 ),
						 ),
					 ),
					 'post_type'         => '',
					 'taxonomy'          => '',
					 'allow_null'        => 0,
					 'multiple'          => 0,
					 'return_format'     => 'object',
					 'ui'                => 1,
				 ),

				 array( 
					'key'               => 'hide_if_no_post',
					'label'             => __( 'Hide if no post', 'acf-frontend-form-element' ),
					'type'              => 'true_false',
					'instructions'      => __( 'Hide this form if there is no post to edit', 'acf-frontend-form-element' ),
					'required'          => 0,
				 ),

				 array(
					 'key'               => 'new_post_terms',
					 'label'             => __( 'New Post Terms', 'acf-frontend-form-element' ),
					 'type'              => 'select',
					 'instructions'      => '',
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '==',
								 'value'    => 'new_post',
							 ),
						 ),
					 ),
					 'choices'           => array(
						 'current_term' => __( 'Current Term', 'acf-frontend-form-element' ),
						 'select_terms' => __( 'Specific Term', 'acf-frontend-form-element' ),
					 ),
					 'default_value'     => false,
					 'allow_null'        => 0,
					 'multiple'          => 0,
					 'ui'                => 0,
					 'return_format'     => 'value',
					 'ajax'              => 0,
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'new_terms_select',
					 'label'             => __( 'Terms', 'acf-frontend-form-element' ),
					 'type'              => 'text',
					 'instructions'      => __( 'Comma-seperated list of term ids', 'acf-frontend-form-element' ),
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '==',
								 'value'    => 'new_post',
							 ),
							 array(
								 'field'    => 'new_post_terms',
								 'operator' => '==',
								 'value'    => 'select_terms',
							 ),
						 ),
					 ),
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'new_post_status',
					 'label'             => __( 'Post Status', 'acf-frontend-form-element' ),
					 'type'              => 'select',
					 'instructions'      => '',
					 'required'          => 0,
					 'choices'           => array(
						'no_change' => __( 'No Change', 'acf-frontend-form-element' ),
						 'draft'   => __( 'Draft', 'acf-frontend-form-element' ),
						 'private' => __( 'Private', 'acf-frontend-form-element' ),
						 'pending' => __( 'Pending Review', 'acf-frontend-form-element' ),
						 'publish' => __( 'Published', 'acf-frontend-form-element' ),
					 ),
					 'default_value'     => 'no_change',
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '!=',
								 'value'    => 'delete_post',
							 ),
						 ),
					 ),
					 'allow_null'        => 0,
					 'multiple'          => 0,
					 'ui'                => 0,
					 'return_format'     => 'value',
					 'ajax'              => 0,
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'copy_title_text',
					 'label'             => __( 'Copy Title Text', 'acf-frontend-form-element' ),
					 'type'              => 'text',
					 'instructions'      => '',
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '==',
								 'value'    => 'duplicate_post',
							 ),
						 ),
					 ),
					 'default_value'     => __( 'Copy of', 'acf-frontend-form-element' ),
					 'placeholder'       => '',
				 ),
				 array(
					 'key'               => 'copy_date',
					 'label'             => __( 'Copy Date', 'acf-frontend-form-element' ),
					 'type'              => 'true_false',
					 'instructions'      => '',
					 'required'          => 0,
					 'conditional_logic' => array(
						 array(
							 array(
								 'field'    => 'save_to_post',
								 'operator' => '==',
								 'value'    => 'duplicate_post',
							 ),
						 ),
					 ),
					 'default_value'     => 'yes',
					 'ui'                => 0,
					 'return_format'     => 'value',
				 ),

			 );

			 return $options;
		}

		public function load_data( $form ) {
			if ( empty( $form['save_to_post'] ) ) {
				return $form;
			}

			$user_id = get_current_user_id();

			switch ( $form['save_to_post'] ) {
				case 'new_post':
				
					$form['post_id'] = 'add_post';
				break;
				case 'edit_post':
				case 'duplicate_post':
				case 'delete_post':
					if ( empty( $form['post_to_edit'] ) ) {
						$form['post_to_edit'] = 'current_post';
					}

					global $post;
		
					$post_type = (array) $form['post_type'];

					if( in_array( 'any', $post_type ) ){
						$post_type = 'post';
					}else{
						$post_type = $post_type[0];
					}

					switch ( $form['post_to_edit'] ) {
						case 'user_first_post':
							$user_posts = get_posts(
								array(
									'author' => $user_id,
									'posts_per_page' => 1,
									'post_status' => 'any',
									'orderby' => 'date',
									'order' => 'ASC',
									'post_type' => $post_type,
								)
							);
							if ( ! empty( $user_posts[0] ) ) {
								$form['post_id'] = $user_posts[0]->ID;
							}
							break;
						case 'user_last_post':
							$user_posts = get_posts(
								array(
									'author' => $user_id,
									'posts_per_page' => 1,
									'post_status' => 'any',
									'orderby' => 'date',
									'order' => 'DESC',
									'post_type' => $post_type,
								)
							);
							if ( ! empty( $user_posts[0] ) ) {
								$form['post_id'] = $user_posts[0]->ID;
							}
							break;
						case 'select_post':
							if ( ! empty( $form['select_post'] ) ) {
								$form['post_id'] = $form['select_post'];
							}
							break;
						case 'url_query':
							if ( isset( $_GET[ $form['url_query_post'] ] ) ) {
								$form['post_id'] = absint( $_GET[ $form['url_query_post'] ] );
							}
							break;
						case 'current_post':
							if ( isset( $post->ID ) ) {
								$form['post_id'] = $post->ID;
							}
							break;
					}
					

					if ( empty( $form['post_id'] ) ) {
						$form['post_id'] = 'none';
					}else{
						
						$post = get_post( $form['post_id'] );

						if( ! $post ){
							$form['post_id'] = 'none';
							return $form;
						}

						$post_type = $post->post_type;

						
						if( is_array( $form['post_type'] ) && ! in_array( 'any', $form['post_type'] ) && ! in_array( $post_type, $form['post_type'] ) ){
							$form['post_id'] = 'none';
						}
						
					}


				break;
			}
			return $form;
		}

		public function before_posts_query() {
			$fields = array(
				array(
					'key'        => 'select_post',
					'prefix'     => 'form',
					'type'       => 'post_object',
					'post_type'  => '',
					'taxonomy'   => '',
					'allow_null' => 0,
					'multiple'   => 0,
					'ui'         => 1,
				),
				array(
					'key'        => 'select_product',
					'prefix'     => 'form',
					'type'       => 'post_object',
					'post_type'  => 'product',
					'taxonomy'   => '',
					'allow_null' => 0,
					'multiple'   => 0,
					'ui'         => 1,
				),
			);

			foreach ( $fields as $field ) {
				$field['prefix'] = 'form';
				$field['name']   = $field['key'];
				acf_add_local_field( $field );
			}

		}
		public function bricks_settings_section( $widget ) {
			$widget->add_control_group(
				'post_edit_section',
				array(
					'title' => __( 'Post', 'acf-frontend-form-element' ),
					'tab'   => 'content',
				)
			);

			$type = $widget->settings[ 'save_to_post'] ?? 'edit_post';

			$widget->add_control(
				'save_to_post',
				[
					'label' => esc_html__( 'Post', 'acf-frontend-form-element' ),
					'type' => 'select',
					'default' => $type,
					'options' => [
						'edit_post'      => esc_html__( 'Edit Post', 'acf-frontend-form-element' ),
						'new_post'       => esc_html__( 'New Post', 'acf-frontend-form-element' ),
						'duplicate_post' => esc_html__( 'Duplicate Post', 'acf-frontend-form-element' ),
						'delete_post'    => esc_html__( 'Delete Post', 'acf-frontend-form-element' ),
					],
				]
			);

			$post_type_choices = feadmin_get_post_type_choices();

			$widget->add_control(
				'post_to_edit',
				[
					'label' => esc_html__( 'Post', 'acf-frontend-form-element' ),
					'type' => 'select',
					'default' => 'current_post',
					'options' => [
						'current_post'    => esc_html__( 'Current Post', 'acf-frontend-form-element' ),
						'url_query'       => esc_html__( 'URL Query', 'acf-frontend-form-element' ),
						'select_post'     => esc_html__( 'Specific Post', 'acf-frontend-form-element' ),
						'user_first_post' => esc_html__( 'User\'s First Post', 'acf-frontend-form-element' ),
						'user_last_post'  => esc_html__( 'User\'s Most Recent Post', 'acf-frontend-form-element' ),
					],
					'required' => [
						'save_to_post', '=', [ 'edit_post', 'delete_post', 'duplicate_post' ],
					],
				]
			);

			$widget->add_control(
				'post_type',
				[
					'label' => esc_html__( 'Post Types', 'acf-frontend-form-element' ),
					'type' => 'select',
					'multiple' => true,
					'label_block' => true,
					'default' => ['any'],
					'options' => $post_type_choices,
					'required' => [
						'save_to_post', '=', [ 'edit_post', 'delete_post', 'duplicate_post' ],
					],
				]
			);

			$widget->add_control(
				'url_query_post',
				[
					'label' => esc_html__( 'URL Query', 'acf-frontend-form-element' ),
					'type' => 'text',
					'default' => 'post_id',
					'description' => esc_html__( 'Enter the URL query parameter containing the post ID.', 'acf-frontend-form-element' ),
					'required' => [
						'post_to_edit', '=', 'url_query',
					],
				]
			);

			$widget->add_control(
				'post_select',
				[
					'label' => esc_html__( 'Post', 'acf-frontend-form-element' ),
					'type' => 'text',
					'placeholder' => '18',
					'description' => esc_html__( 'Enter the post ID.', 'acf-frontend-form-element' ),
					'required' => [
						'post_to_edit', '=', 'select_post',
					],
				]
			);

			$widget->add_control(
				'new_post_type',
				[
					'label' => esc_html__( 'New Post Type', 'acf-frontend-form-element' ),
					'type' => 'select',
					'label_block' => true,
					'default' => 'post',
					'options' => $post_type_choices,
					'required' => [
						'save_to_post', '=', 'new_post',
					],
				]
			);

			$widget->add_control(
				'new_post_terms',
				[
					'label' => esc_html__( 'New Post Terms', 'acf-frontend-form-element' ),
					'type' => 'select',
					'label_block' => true,
					'default' => 'post',
					'options' => [
						'current_term' => esc_html__( 'Current Term', 'acf-frontend-form-element' ),
						'select_terms' => esc_html__( 'Specific Term', 'acf-frontend-form-element' ),
					],
					'required' => [
						'save_to_post', '=', 'new_post',
					],
				]
			);

			$widget->add_control(
				'new_terms_select',
				[
					'label' => esc_html__( 'Terms', 'acf-frontend-form-element' ),
					'type' => 'text', // optionally replace with a taxonomy selector
					'placeholder' => '18, 12, 11',
					'description' => esc_html__( 'Enter a comma-separated list of term IDs.', 'acf-frontend-form-element' ),
					'required' => [
						'new_post_terms', '=', 'select_terms',
					],
				]
			);

			$widget->add_control(
				'new_post_status',
				[
					'label' => esc_html__( 'Post Status', 'acf-frontend-form-element' ),
					'type' => 'select',
					'default' => 'no_change',
					'options' => [
						'no_change' => esc_html__( 'No Change', 'acf-frontend-form-element' ),
						'draft'     => esc_html__( 'Draft', 'acf-frontend-form-element' ),
						'private'   => esc_html__( 'Private', 'acf-frontend-form-element' ),
						'pending'   => esc_html__( 'Pending Review', 'acf-frontend-form-element' ),
						'publish'   => esc_html__( 'Published', 'acf-frontend-form-element' ),
					],
					'required' => [
						'save_to_post', '=', [ 'edit_post', 'new_post', 'duplicate_post' ],
					],
				]
			);

			$widget->add_control(
				'copy_title_text',
				[
					'label' => esc_html__( 'Copy Title Text', 'acf-frontend-form-element' ),
					'type' => 'text',
					'default' => esc_html__( 'Copy of', 'acf-frontend-form-element' ),
					'required' => [
						'save_to_post', '=', 'duplicate_post',
					],
				]
			);

			$widget->add_control(
				'copy_date',
				[
					'label' => esc_html__( 'Copy Date', 'acf-frontend-form-element' ),
					'type' => 'checkbox',
					'default' => true,
					'required' => [
						'save_to_post', '=', 'duplicate_post',
					],
				]
			);
		}

		public function register_settings_section( $widget ) {
			$tab = apply_filters( 'frontend_admin/elementor/form_widget/control_tab', Controls_Manager::TAB_CONTENT, $widget );
			$condition = apply_filters( 'frontend_admin/elementor/form_widget/conditions', false, $widget );

			$widget->start_controls_section(
				'section_edit_post',
				array(
					'label'     => $this->get_label(),
					'tab'       => $tab,
					'condition' => $condition,
				)
			);

			$this->action_controls( $widget );

			$widget->end_controls_section();
		}

		public function action_controls( $widget ) {
		
			$type = $widget->form_defaults['save_to_post'] ?? 'edit_post';
			

			$condition = array();			

			if( 'delete_post' !== $widget->get_name() ){
				$args = array(
					'label'   => __( 'Post', 'acf-frontend-form-element' ),
					'show_label' => false,
					'type'    => Controls_Manager::SELECT,
					'options' => array(
						'edit_post'      => __( 'Edit Post', 'acf-frontend-form-element' ),
						'new_post'       => __( 'New Post', 'acf-frontend-form-element' ),
						'duplicate_post' => __( 'Duplicate Post', 'acf-frontend-form-element' ),
					),
					'default' => $type,
				);
				

				$widget->add_control( 'save_to_post', $args );
			}else{
				$condition['save_to_post'] = 'delete_post';
				$widget->add_control(
					'save_to_post',
					array(
						'label'     => __( 'Post', 'acf-frontend-form-element' ),
						'type'      => Controls_Manager::HIDDEN,
						'default'   => 'delete_post',
					)
				);
			}

			$condition['save_to_post'] = array( 'edit_post', 'delete_post', 'duplicate_post' );


			// add option to determine when the post will be save: 1. on form submit
			// 2. when user confirms email  3. when admin approves submission
			// 4. on woocommerce purchase
			$post_type_choices = feadmin_get_post_type_choices();

			
			$widget->add_control(
				'post_to_edit',
				array(
					'label'     => __( 'Post', 'acf-frontend-form-element' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => 'current_post',
					'options'   => array(
						'current_post' => __( 'Current Post', 'acf-frontend-form-element' ),
						'url_query'    => __( 'Url Query', 'acf-frontend-form-element' ),
						'select_post'  => __( 'Specific Post', 'acf-frontend-form-element' ),
						'user_first_post' => __( 'User\'s First Post', 'acf-frontend-form-element' ),
						'user_last_post' => __( 'User\'s Most Recent Post', 'acf-frontend-form-element' ),
					),
					'condition' => $condition,
				)
			);

			$widget->add_control(
				'post_type',
				array(
					'label'       => __( 'Post Types', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => ['any'],
					'multiple'    => true,
					'options'     => array_merge(
						$post_type_choices
					 ),
					'condition'   => $condition,
				)
			);
			$condition['post_to_edit'] = 'url_query';
			$widget->add_control(
				'url_query_post',
				array(
					'label'       => __( 'URL Query', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => __( 'post_id', 'acf-frontend-form-element' ),
					'default'     => __( 'post_id', 'acf-frontend-form-element' ),
					'required'    => true,
					'description' => __( 'Enter the URL query parameter containing the id of the post you want to edit', 'acf-frontend-form-element' ),
					'condition'   => $condition,
				)
			);
			$condition['post_to_edit'] = 'select_post';
			$widget->add_control(
				'post_select',
				array(
					'label'       => __( 'Post', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => __( '18', 'acf-frontend-form-element' ),
					'description' => __( 'Enter the post ID', 'acf-frontend-form-element' ),
					'condition'   => $condition,
				)
			);
			unset( $condition['post_to_edit'] );
			$condition['save_to_post'] = 'new_post';

			$widget->add_control(
				'new_post_type',
				array(
					'label'       => __( 'New Post Type', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => 'post',
					'options'     => $post_type_choices,
					'condition'   => $condition,
				)
			);
		
			$widget->add_control(
				'new_post_terms',
				array(
					'label'       => __( 'New Post Terms', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => 'post',
					'options'     => array(
						'current_term' => __( 'Current Term', 'acf-frontend-form-element' ),
						'select_terms' => __( 'Specific Term', 'acf-frontend-form-element' ),
					),
					'condition'   => $condition,
				)
			);

			$condition['new_post_terms'] = 'select_terms';
			if ( ! class_exists( 'ElementorPro\Modules\QueryControl\Module' ) ) {
				$widget->add_control(
					'new_terms_select',
					array(
						'label'       => __( 'Terms', 'acf-frontend-form-element' ),
						'type'        => Controls_Manager::TEXT,
						'placeholder' => __( '18, 12, 11', 'acf-frontend-form-element' ),
						'description' => __( 'Enter the a comma-seperated list of term ids', 'acf-frontend-form-element' ),
						'condition'   => $condition,
					)
				);
			} else {
				$widget->add_control(
					'new_terms_select',
					array(
						'label'        => __( 'Terms', 'acf-frontend-form-element' ),
						'type'         => Query_Module::QUERY_CONTROL_ID,
						'label_block'  => true,
						'autocomplete' => array(
							'object'  => Query_Module::QUERY_OBJECT_TAX,
							'display' => 'detailed',
						),
						'multiple'     => true,
						'condition'    => $condition,
					)
				);
			}

			unset( $condition['new_post_terms'] );

			$condition['save_to_post'] = array( 'new_post', 'edit_post', 'duplicate_post' );

			$widget->add_control(
				'new_post_status',
				array(
					'label'       => __( 'Post Status', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => 'no_change',
					'options'     => array(
						'no_change' => __( 'No Change', 'acf-frontend-form-element' ),
						'draft'   => __( 'Draft', 'acf-frontend-form-element' ),
						'private' => __( 'Private', 'acf-frontend-form-element' ),
						'pending' => __( 'Pending Review', 'acf-frontend-form-element' ),
						'publish' => __( 'Published', 'acf-frontend-form-element' ),
					),
					'condition'   => $condition,
				)
			);

			//duplicate post title
			$widget->add_control(
				'copy_title_text',
				array(
					'label'       => __( 'Copy Title Text', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXT,
					'label_block' => true,
					'default'     => __( 'Copy of', 'acf-frontend-form-element' ),
					'condition'   => array(
						'save_to_post' => 'duplicate_post',
					),
				)
			);

			//duplicate post date
			$widget->add_control(
				'copy_date',
				array(
					'label'       => __( 'Copy Date', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::SWITCHER,
					'label_block' => true,
					'default'     => 'yes',
					'condition'   => array(
						'save_to_post' => 'duplicate_post',
					),
				)
			);
		}

		public function get_core_fields() {
			 return array(
				 'post_title',
				 'post_slug',
				 'post_status',
				 'post_content',
				 'post_author',
				 'post_excerpt',
				 'post_date',
				 'post_type',
				 'menu_order',
				 'allow_comments',
			 );
		}

		public function run( $form ) {

			$record = $form['record'];


			if ( empty( $record['post'] ) || empty( $record['fields']['post'] ) ) {
				return $form;
			}

			$post_id = $record['post'];

			// allow for custom save
			$post_id = apply_filters( 'acf/pre_save_post', $post_id, $form );
			
			$post_to_edit = array();


			$old_status        = '';
			$post_to_duplicate = false;

			if ( 'add_post' == $post_id ) {
				$post_to_edit['ID']        = 0;
				$post_to_edit['post_type'] = $record['post_type'] ?? $form['new_post_type'] ?? 'post';
				error_log( 'Saving new post with type: ' . $post_to_edit['post_type'] );
		   }else{
				if( 'duplicate_post' == $form['save_to_post'] ){
					$post_to_duplicate           = get_post( $post_id );
					$post_to_edit                = get_object_vars( $post_to_duplicate );
					$post_to_edit['ID']          = 0;
					$post_to_edit['post_author'] = get_current_user_id();
					if ( isset( $form['new_post_status'] ) ) {
						$post_to_edit['post_status'] = $form['new_post_status'];
					}
					//check copy date setting
					if ( ! empty( $form['copy_date'] ) ) {
						$post_to_edit['post_date'] = $post_to_duplicate->post_date;
					}else{
						$post_to_edit['post_date'] = current_time( 'mysql' );
					}
				}else{
					if ( get_post_type( $post_id ) == 'revision' && isset( $record['status'] ) && $record['status'] == 'publish' ) {
						$revision_id = $post_id;
						$post_id     = wp_get_post_parent_id( $revision_id );
						wp_delete_post_revision( $revision_id );
					}
					$old_status         = get_post_field( 'post_status', $post_id );
					$post_to_edit['ID'] = $post_id;
				}
		   }

		

			$metas = array();

			$core_fields = $this->get_core_fields();


			if ( ! empty( $record['fields']['post'] ) ) {
				foreach ( $record['fields']['post'] as $name => $_field ) {
					if ( ! isset( $_field['key'] ) ) {
						continue;
					}
					$field = fea_instance()->frontend->get_field( $_field['key'] );

					

					if ( ! $field ) {
						continue;
					}

					$field_type      = $field['type'];
					$field['value']  = $_field['_input'];
					$field['_input'] = $_field['_input'];
					if ( ! in_array( $field_type, $core_fields ) ) {
						$metas[ $field['key'] ] = $field;
						continue;
					}

					$submit_key = $field_type == 'post_slug' ? 'post_name' : $field_type;

					if ( 'post_title' == $field_type && ! empty( $field['custom_slug'] ) ) {
						$post_to_edit['post_name'] = sanitize_title( $field['value'] );
					}

					$post_to_edit[ $submit_key ] = $field['value'];
				}
			}

			if ( 'duplicate_post' == $form['save_to_post'] ) {
				if ( $post_to_edit['post_name'] == $post_to_duplicate->post_name ) {
					$post_name = sanitize_title( $post_to_edit['post_title'] );
					if ( ! feadmin_slug_exists( $post_name ) ) {
						   $post_to_edit['post_name'] = $post_name;
					} else {
						 $post_to_edit['post_name'] = feadmin_duplicate_slug( $post_to_duplicate->post_name );
					}
				}
			}

			if ( empty( $post_to_edit['post_status'] ) ) {
				
				if ( isset( $record['status'] ) && $record['status'] == 'draft' ) {
					$post_to_edit['post_status'] = 'draft';
				} else {
					$status = $form['new_post_status'] ?? 'no_change';

					if ( $status != 'no_change' ) {
						$post_to_edit['post_status'] = $status;
					} elseif ( empty( $old_status ) || $old_status == 'auto-draft' ) {
						$post_to_edit['post_status'] = 'publish';
					}
				}
				
			}

			$form = $this->save_post( $form, $post_to_edit, $metas, $post_to_duplicate );
			return $form;
		}

		public function save_post( $form, $post_to_edit, $metas, $post_to_duplicate ) {
			if ( $post_to_edit['ID'] == 0 ) {
				$post_to_edit['meta_input'] = array(
					'admin_form_source' => str_replace( '_', '', $form['id'] ),
				);
				if ( empty( $post_to_edit['post_title'] ) ) {
					$post_to_edit['post_title'] = '(no-name)';
				}

				if ( isset( $form['approval'] ) ) {
					if ( empty( $post_to_edit['post_author'] ) ) {
						$post_to_edit['post_author'] = $form['submitted_by'];
					}
					/*
					 if( empty( $post_to_edit['post_date'] ) ){
					$post_to_edit['post_date'] = $form['submitted_on'];
					} */
				}


				$post_id = wp_insert_post( $post_to_edit );

				if ( is_wp_error( $post_id ) ) {
					$error = $post_id->get_error_message();
					wp_send_json_error( $form );
				}

				$GLOBALS['admin_form']['record']['post'] = $post_id;
				$form['record']['post'] = $post_id;
			} else {
				$post_id = $post_to_edit['ID'];
				wp_update_post( $post_to_edit );
				update_metadata( 'post', $post_id, 'admin_form_edited', $form['id'] );
			}

	

				if ( ! empty( $form['new_post_terms'] ) ) {
					$new_terms = [];
					if ( 'select_terms' == $form['new_post_terms']  ) {
						$new_terms = $form['new_terms_select'];
					}
					if ( 'current_term' == $form['new_post_terms'] ) {
						$current_term = $form['term_id'] ?? false;
						if( is_numeric( $current_term ) ){
							$new_terms = $current_term;
						}
					}

					if ( is_string( $new_terms ) ) {
						$new_terms = explode( ',', $new_terms );
					}
					if ( $new_terms ) {
						foreach ( $new_terms as $term_id ) {
							   $term = get_term( $term_id );
							if ( $term ) {
								wp_set_object_terms( $post_id, $term->term_id, $term->taxonomy, true );
							}
						}
					}
				}
				

			if ( 'duplicate_post' == $form['save_to_post'] ) {
				$taxonomies = get_object_taxonomies( $post_to_duplicate->post_type );
				foreach ( $taxonomies as $taxonomy ) {
					$post_terms = wp_get_object_terms( $post_to_duplicate->ID, $taxonomy, array( 'fields' => 'slugs' ) );
					wp_set_object_terms( $post_id, $post_terms, $taxonomy, false );
				}

				global $wpdb;
				$post_meta_infos = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_to_duplicate->ID" );
				if ( count( $post_meta_infos ) != 0 ) {
					$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
					foreach ( $post_meta_infos as $meta_info ) {
						   $meta_key        = $meta_info->meta_key;
						   $meta_value      = addslashes( $meta_info->meta_value );
						   $sql_query_sel[] = "SELECT $post_id, '$meta_key', '$meta_value'";
					}
					$sql_query .= implode( ' UNION ALL ', $sql_query_sel );
					$wpdb->query( $sql_query );
				}
			}

			if ( ! empty( $metas ) ) {
				foreach ( $metas as $meta ) {
					acf_update_value( $meta['_input'], $post_id, $meta );
				}
			}

			do_action( 'frontend_admin/save_post', $form, $post_id );
			do_action( 'acf_frontend/save_post', $form, $post_id );
			return $form;
		}

		public function conditions_logic( $settings, $condition, $user ){
			$post_id = $settings['post_id'] ?? 'none';

			if( ! is_numeric( $post_id ) ){
				return $settings;
			}

			if( ! feadmin_can_edit_post( $post_id, $settings ) ){
				if( ! in_array( 'edit_posts', $condition['special_permissions'] ) ){
					$settings['post_id'] = 'none';
				}
			}			

			return $settings;
		}

		public function __construct() {
			add_filter( 'frontend_admin/special_permissions', array( $this, 'conditions_logic' ), 10, 3 );
			add_action( 'wp_ajax_acf/fields/post_object/query', array( $this, 'before_posts_query' ), 4 );
		}

	}

	fea_instance()->local_actions['post'] = new ActionPost();

endif;


