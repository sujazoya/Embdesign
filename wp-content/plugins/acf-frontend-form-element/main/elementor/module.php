<?php

namespace Frontend_Admin;

use Elementor\Core\Settings\Manager as SettingsManager;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'Frontend_Admin\Elementor' ) ) :

	class Elementor {
		public $form_widgets         = array();
		public $elementor_categories = array();

		public static function find_element_recursive( $elements, $widget_id ) {
			foreach ( $elements as $element ) {
				if ( $widget_id == $element['id'] ) {
					return $element;
				}

				if ( ! empty( $element['elements'] ) ) {
					$element = self::find_element_recursive( $element['elements'], $widget_id );

					if ( $element ) {
						return $element;
					}
				}
			}
		

			return false;
		}

		public function widgets() {
			include_once __DIR__ . "/widgets/general/base-field.php";
			 $widget_list      = array(
				 'general' => array(
					'acf-form' => 'ACF_Form',
					'field' => 'Field',
					'text-field' => 'Text_Field',
					'textarea-field' => 'Textarea_Field',
					'taxonomy-field' => 'Taxonomy_Field',
					'number-field' => 'Number_Field',
					'email-field' => 'Email_Field',
					'image-field' => 'Image_Field',
					'images-field' => 'Images_Field',
					'text-editor-field' => 'Text_Editor_Field',
					'edit-button'   => 'Edit_Button_Widget',
					'submit-button'   => 'Submit_Button_Widget',
				 ),
			 );

				
			$widget_list = array_merge(
				$widget_list,
				array(
					'post' => array(
						'title-field' => 'Post_Title_Field',
						//'author-field' => 'Post_Author_Field',
						'excerpt-field' => 'Post_Excerpt_Field',
						'featured-image-field' => 'Featured_Image_Field',
						'content-field' => 'Post_Content_Field',
						'edit-post'      => 'Edit_Post_Widget',
						'new-post'       => 'New_Post_Widget',
						'duplicate-post' => 'Duplicate_Post_Widget',
						'delete-post'    => 'Delete_Post_Widget',
					),
					'term' => array(
						'edit-term'   => 'Edit_Term_Widget',
						'new-term'    => 'New_Term_Widget',
						'delete-term' => 'Delete_Term_Widget',
						'term-name-field' => 'Term_Name_Field',
						'term-slug-field' => 'Term_Slug_Field',
					),
					'user' => array(
						'edit-user'   => 'Edit_User_Widget',
						'new-user'    => 'New_User_Widget',
						'delete-user' => 'Delete_User_Widget',
					),
				)
			);

			$widget_list = $this->get_nestable_widgets( $widget_list );
			$widget_list = apply_filters( 'frontend_admin/elementor/widget_types', $widget_list );

			 $elementor = $this->get_elementor_instance();

			 foreach ( $widget_list as $folder => $widgets ) {

				 foreach ( $widgets as $filename => $classname ) {
					if( 'path' == $filename ) continue;
					$folder_path = $widgets['path'] ?? __DIR__ . "/widgets/$folder";

					 include_once "$folder_path/$filename.php";
					 $classname = 'Frontend_Admin\Elementor\Widgets\\' . $classname;

					 /* if( 'nested-new-post' == $filename ){
						global $fea_elementor_post_type;
						$post_types = get_post_types( [ 'public' => true, 'publicly_queryable' => true, 'exclude_from_search' => false ], 'objects' );

						foreach( $post_types as $post_type ){
							$fea_elementor_post_type = $post_type;
							$elementor->widgets_manager->register( new $classname() );
						}
					 }*/
					 $elementor->widgets_manager->register( new $classname() );
					 
				 }
			 }
		

			 do_action( 'frontend_admin/widget_loaded' );

		}

		public function get_nestable_widgets( $widget_list ){
			if( ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'nested-elements' ) ){
				return $widget_list;
			}

			$widget_list['general']['nested-form'] = 'Nested_Form';
			$widget_list['post']['nested-edit-post'] = 'Nested_Edit_Post';
			$widget_list['post']['nested-new-post'] = 'Nested_New_Post';
			$widget_list['term']['nested-edit-term'] = 'Nested_Edit_Term';
			$widget_list['term']['nested-new-term'] = 'Nested_New_Term';
			

			return $widget_list;
		}

		public function documents(){
			$elementor = $this->get_elementor_instance();

			include_once __DIR__ . '/widgets/general/form-container.php';
			$elementor->documents->register_document_type( 'form_container', 'Frontend_Admin\Elementor\Widgets\FormContainer' );
		} 


        public function widget_categories( $elements_manager )
        {
            $categories = array(
                'frontend-admin-general'    => array(
					'title' => __( 'Frontend Admin Forms', 'frontend-admin' ),
					'icon'  => 'eicon-form-horizontal',
				),
				'frontend-admin-fields'    => array(
					'title' => __( 'Frontend Admin Fields', 'frontend-admin' ),
					'icon'  => 'eicon-form-vertical',
				),
                'frontend-admin-posts'      => array(
					'title' => __( 'Frontend Admin Posts', 'frontend-admin' ),
					'icon'  => 'eicon-post',
				),
                'frontend-admin-users'      => array(
					'title' => __( 'Frontend Admin Users', 'frontend-admin' ),
					'icon'  => 'eicon-my-account',
				),
                'frontend-admin-taxonomies' => array(
					'title' => __( 'Frontend Admin Taxonomies', 'frontend-admin' ),
					'icon'  => 'eicon-t-letter',
				),
            );

			if ( class_exists( 'woocommerce' ) ) {
				$categories['frontend-admin-products'] = array(
					'title' => __( 'Frontend Admin Products', 'frontend-admin' ),
					'icon'  => 'eicon-product',
				);
			}

            foreach ( $categories as $name => $args ) {
                $this->elementor_categories[$name] = $args;
                $elements_manager->add_category( $name, $args );
            }
        }
        
		
	public function get_settings_to_pass( $form_args, $settings ) {
		$to_pass = [
			"more_actions",
			"open_modal",
			"redirect",
			"redirect_action",
			"custom_url",
			"show_success_message",
			"update_message",
			"error_message",
			"email_verified_message",
			"save_to_user",
			"user_to_edit",
			"new_user_role",
			"roles",
			"hide_admin_bar",
			"login_user",
			"post_type",
			"new_post_type",
			"new_post_terms",
			"new_post_status",
			"new_terms_select",
			"new_term_taxonomy",
			"new_product_terms",
			"new_product_terms_select",
			"new_product_status",
			"fields_selection",
			"show_in_modal",
			"modal_button_text",
			"modal_button_icon",
			"custom_fields_save",
			"wp_uploader",
			"save_all_data",
			"form_conditions",
			"who_can_see",
			"not_allowed",
			"not_allowed_message",
			"not_allowed_content",
			"email_verification",
			"by_role",
			"by_user_id",
			"special_permissions",
			"no_kses",			
			'copy_title_text',
			'copy_product_title_text',
			'copy_date',
			'copy_product_date',
			'attribute_fields',
			'variable_fields',
			'validate_steps',
			'steps_display',
			'responsive_description',
			'steps_tabs_display',
			'tabs_align',
			'steps_counter_display',
			'counter_prefix',
			'counter_suffix',
			'step_number',
			'tab_links',
			'between_tabs_display'
		];	 

		$types = array( 'post', 'user', 'term', 'product' );
		foreach ( $types as $type ) {
			$to_pass[] = "save_to_{$type}";
			$to_pass[] = "{$type}_to_edit";
			$to_pass[] = "url_query_{$type}";
			$to_pass[] = "{$type}_select";
		}

		foreach( $to_pass as $key ){
			if( isset( $settings[$key] ) ){
				$form_args[$key] = $settings[$key];
			}
		}
		
		if( isset( $settings['show_success_message'] ) ){
			$form_args['show_update_message'] = $settings['show_success_message'];
		}

		if( isset( $settings['required_message'] ) ){
			$form_args['default_required_message'] = $settings['required_message'];
		}

		return $form_args;

	}


		public function dynamic_tags( $dynamic_tags ) {
			\Elementor\Plugin::$instance->dynamic_tags->register_group(
				'frontend-admin-user-data',
				array(
					'title' => 'User',
				)
			);
			include_once __DIR__ . '/dynamic-tags/user-local-avatar.php';
			include_once __DIR__ . '/dynamic-tags/author-local-avatar.php';

			$dynamic_tags->register( new DynamicTags\User_Local_Avatar_Tag() );
			$dynamic_tags->register( new DynamicTags\Author_Local_Avatar_Tag() );
		}

		public function frontend_scripts() {
			wp_enqueue_style( 'fea-modal' );
			wp_enqueue_style( 'acf-global' );
			wp_enqueue_script( 'fea-modal' );
		}
		public function editor_scripts() {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '-min';

			wp_enqueue_style( 'fea-icon', FEA_URL . 'assets/css/icon' . $min . '.css', array(), FEA_VERSION );
			wp_enqueue_style( 'fea-editor', FEA_URL . 'assets/css/editor' . $min . '.css', array(), FEA_VERSION );

			wp_enqueue_script( 'fea-editor', FEA_URL . 'assets/js/editor' . $min . '.js', array( 'elementor-editor' ), FEA_VERSION, true );

			//localize wp-rest 
			wp_localize_script( 'fea-editor', 'feaRestData', array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'url' => rest_url(),
			) );

			wp_enqueue_style( 'acf-global' );

			if( \Elementor\Plugin::$instance->experiments->is_feature_active( \Elementor\Modules\NestedElements\Module::EXPERIMENT_NAME ) ){
				$js_deps = include_once FEA_DIR . '/assets/build/el-nested-form-editor/index.asset.php';
				$js_url = FEA_URL . 'assets/build/el-nested-form-editor/index.js';
				wp_enqueue_script( 'nested-form', $js_url, [
					'nested-elements',
				], $js_deps['version'], true );
			 } 
		}

		public function get_the_widget( $element_ids ) {
			if ( is_array( $element_ids ) ) {
				$widget_id = $element_ids[1];
				$post_id   = $element_ids[0];

				global $post;
				if( ! $post ) $post = get_post( $post_id );
			} else {
				return false;
			}

			if ( isset( $post_id ) ) {
				$elementor = $this->get_elementor_instance();

				$document = $elementor->documents->get( $post_id );

				if ( $document ) {
					$form = $this->find_element_recursive( $document->get_elements_data(), $widget_id );

				}

				if ( ! empty( $form['templateID'] ) ) {
					$template = $elementor->documents->get( $form['templateID'] );

					if ( $template ) {
						$global_meta = $template->get_elements_data();
						$form        = $global_meta[0];
					}
				}

				if ( empty( $form ) ) {
					return false;
				}

				return $elementor->elements_manager->create_element_instance( $form );
			}
		}

		function get_elementor_instance() {
			 return \Elementor\Plugin::$instance;
		}

		function get_current_post_id() {
			global $fea_current_post_id;

			if ( isset( $fea_current_post_id ) ) {
				return $fea_current_post_id;
			}

			$el = $this->get_elementor_instance();
			if ( isset( $el->documents ) ) {
				$current_page = $el->documents->get_current();
				if ( isset( $current_page ) ) {
					return $el->documents->get_current()->get_main_id();
				}
			}
			return get_the_ID();
		}

		public function get_form_widget( $form, $key, $element = false ){
			$key = str_replace( ':elementor_', '_elementor_', $key );

			if ( strpos( $key, '_elementor_' ) === false ) {
				return $form;
			}
	
			// Get Template/page id and widget id
			$ids = explode( '_elementor_', $key );
	
			// If there is no widget id, there is no reason to continue 
			if( empty( $ids[1] ) ) return $form; 
			

			$widget = $this->get_the_widget( $ids );


			if( $widget ){			
				$form = $widget->prepare_form();

				if( $element ){
					$form['object'] = $widget;
				}
				return $form;
			}
			return false;
		}

		public function get_field_widget( $field, $key ){
			if ( $field || strpos( $key, '_elementor_' ) === false ) {
				return $field;
			}
	
			// Get Template/page id and widget id
			$ids = explode( '_elementor_', $key );


			// If there is no widget id, there is no reason to continue 
			if( empty( $ids[1] ) ) return false; 

			$widget_ids = explode( '_', $ids[1] );

			$post_id = $ids[0];

			global $fea_current_post_id;
			$fea_current_post_id = $post_id;	

			$widget_id = $widget_ids[0];
			$field_id = $widget_ids[1] ?? false;

			$widget = $this->get_the_widget( [ $post_id, $widget_id ] );
			

			if( $widget ){	
				
				if( empty( $field_id ) ) return $widget->prepare_field( $key );

				$form = $widget->prepare_form( $key );
			
				if( ! empty( $form['fields'][$key] ) ) return $form['fields'][$key];
			}
			return false;
	
		}

		

		public function delete_ghost_fields(){
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'posts', [ 'post_type' => 'acf-field', 'post_parent' => 0 ] );
		
		}

		public function controls( $controls_manager ) {

			require_once( __DIR__ . '/controls/custom-select.php' );
			require_once( __DIR__ . '/controls/conditions.php' );
		
			$controls_manager->register( new Elementor\Controls\Custom_Select() );
			$controls_manager->register( new Elementor\Controls\Conditions_Control() );
		
		}

		public function __construct() {
			include_once __DIR__ . '/classes/content-tab.php';
			include_once __DIR__ . '/classes/modal.php';
			include_once __DIR__ . '/classes/conditions.php';
			include_once __DIR__ . '/classes/permissions.php';

			add_action( 'elementor/elements/categories_registered', array( $this, 'widget_categories' ) );
			add_action( 'elementor/controls/register', array( $this, 'controls' ) );
			add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
			//add_action( 'elementor/documents/register', array( $this, 'documents' ) );

			add_action( 'elementor/dynamic_tags/register', array( $this, 'dynamic_tags' ) );

			add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'editor_scripts' ) );

			add_filter( 'frontend_admin/forms/get_form', [ $this, 'get_form_widget' ], 10, 3 );

			add_action( 'init', array( $this, 'delete_ghost_fields' ) );

			add_filter( 'frontend_admin/fields/get_field', [ $this, 'get_field_widget' ], 10, 2 );
		}
	}

	fea_instance()->elementor = new Elementor();

endif;
