<?php

namespace Frontend_Admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'Frontend_Admin\Bricks' ) ) :

	class Bricks {
		public $form_elements         = array();
		public $bricks_categories = array();

	
		/* public function elements() {
			 $element_list      = array(
				 'general' => array(
					'acf-form' => 'ACF_Form',
					'edit_button'   => 'Edit_Button_Widget',
				 ),
			 );

			$element_list = array_merge(
				$element_list,
				array(
					'posts' => array(
						'edit_post'      => 'Edit_Post_Widget',
						'new_post'       => 'New_Post_Widget',
						'duplicate_post' => 'Duplicate_Post_Widget',
						'delete_post'    => 'Delete_Post_Widget',
					),
					'terms' => array(
						'edit_term'   => 'Edit_Term_Widget',
						'new_term'    => 'New_Term_Widget',
						'delete_term' => 'Delete_Term_Widget',
					),
					'users' => array(
						'edit_user'   => 'Edit_User_Widget',
						'new_user'    => 'New_User_Widget',
						'delete_user' => 'Delete_User_Widget',
					),
				)
			);

			$element_list = apply_filters( 'frontend_admin/bricks/element_types', $element_list );

			 $bricks = $this->get_bricks_instance();

			 foreach ( $element_list as $folder => $elements ) {
				 foreach ( $elements as $filename => $classname ) {
					 include_once __DIR__ . "/elements/$folder/$filename.php";
					 $classname = 'Frontend_Admin\Bricks\Widgets\\' . $classname;
					 $bricks->elements_manager->register( new $classname() );
				 }
			 }
		

			 do_action( 'frontend_admin/bricks/element_loaded' );

		} */




		public function element_categories( $elements_manager ) {
			$categories = array(
				'frontend-admin-general' => array(
					'title' => __( 'FRONTEND SITE MANAGEMENT', 'acf-frontend-form-element' ),
					'icon'  => 'fa fa-plug',
				),
			);

			foreach ( $categories as $name => $args ) {
				$this->elementor_categories[ $name ] = $args;
				$elements_manager->add_category( $name, $args );
			}

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

			wp_enqueue_script( 'fea-editor', FEA_URL . 'assets/js/editor' . $min . '.js', array(), FEA_VERSION, true );

			wp_enqueue_style( 'acf-global' );
		}

	
		function get_current_post_id() {
			return \Bricks\Database::$page_data['preview_or_post_id'] ?? get_the_ID();
		}

	

	

		public function dynamic_elements(){
			$elements = [
				'edit-post-form' => 'Nested_Edit_Post',
				'new-post-form' => 'Nested_New_Post',
			];

			$post_types = get_post_types( [ 'public' => true, 'publicly_queryable' => true, 'exclude_from_search' => false ], 'objects' );
			$group = 'post';
			$exclude_types = [
				'admin_form', 'attachment', 'bricks_template'
			];
			foreach ( $elements as $file => $class ) {
				//check if file exists
				foreach( $post_types as $post_type ){
					if( in_array( $post_type->name, $exclude_types ) ) continue;
					require_once __DIR__ . '/elements/post/' . $file .'.php';
					$class_name = 'Frontend_Admin\Bricks\Elements\\' . $class;
					$instance = new $class_name( [ 
						'post_type' => $post_type->name, 
						'label' => $post_type->labels->singular_name
					] );
					$this->load_element( $instance, $class_name );
				}

			  }
		}

		public static function load_element( $element_instance, $element_class_name = null ) {
		
	
			// Set controls
			$element_instance->load();
	
			$controls = $element_instance->controls;
	
			// Control 'tab' not defined: Set to 'content' (@since 1.5)
			foreach ( $controls as $index => $control ) {
				if ( empty( $controls[ $index ]['tab'] ) ) {
					$controls[ $index ]['tab'] = 'content';
				}
			}
	
			$control_groups = $element_instance->control_groups;
	
			// Control group 'tab' not defined: Set to 'content' (@since 1.5)
			foreach ( $control_groups as $index => $control ) {
				if ( empty( $control_groups[ $index ]['tab'] ) ) {
					$control_groups[ $index ]['tab'] = 'content';
				}
			}
	
			\Bricks\Elements::$elements[ $element_instance->name ] = [
				'class'            => $element_class_name,
				'name'             => $element_instance->name,
				'icon'             => $element_instance->icon,
				'category'         => $element_instance->category,
				'label'            => $element_instance->label,
				'keywords'         => $element_instance->keywords,
				'tag'              => $element_instance->tag,
				'controls'         => $controls,
				'controlGroups'    => $control_groups,
				'scripts'          => $element_instance->scripts,
				'block'            => $element_instance->block ? $element_instance->block : null,
				'draggable'        => $element_instance->draggable,
				'deprecated'       => $element_instance->deprecated,
				'panelCondition'   => $element_instance->panel_condition,
	
				// @since 1.5 (= Nestable element)
				'nestable'         => $element_instance->nestable,
				'nestableItem'     => $element_instance->nestable_item,
				'nestableChildren' => $element_instance->nestable_children,
	
				// @since 1.11.1 (Masonry layout element)
				'supportMasonry'   => $element_instance->support_masonry,
			];
	
			/**
			 * Rendered HTML output for nestable non-layout elements (slider, accordion, tabs, etc.)
			 *
			 * To use inside BricksNestable.vue on mount()
			 *
			 * @since 1.5
			 */
	
			// Use specific Vue component to render element on canvas (@since 1.5)
			if ( $element_instance->vue_component ) {
				\Bricks\Elements::$elements[ $element_instance->name ]['component'] = $element_instance->vue_component;
			}
	
			// To distinguish non-layout nestables (slider-nested, etc.) in Vue render (@since 1.5)
			if ( ! $element_instance->is_layout_element() ) {
				\Bricks\Elements::$elements[ $element_instance->name ]['nestableHtml'] = $element_instance->nestable_html;
			}
	
			// Nestable element (@since 1.5)
			if ( $element_instance->nestable ) {
				// Always run certain scripts
				\Bricks\Elements::$elements[ $element_instance->name ]['scripts'][] = 'bricksBackgroundVideoInit';
			}
	
			// Provide 'attributes' data in builder
			if ( count( $element_instance->attributes ) ) {
				\Bricks\Elements::$elements[ $element_instance->name ]['attributes'] = $element_instance->attributes;
			}
	
			// Enqueue elements scripts in the builder iframe
			if ( bricks_is_builder_iframe() ) {
				$element_instance->enqueue_scripts();
			}
		}
	


		public function elements(){
				require_once __DIR__ . '/elements/traits/controls.php';

			

            $element_files = [
				'general' => [
					'form-nested',
					'submit-button',  
					'base-field',
					 'text-field',
					 'acf-fields',
					/*'number-field',
					'email-field',
					'textarea-field',
					'image-field',
					'file-field',
					'checkbox-field',
					'radio-field',
					'select-field',
					'hidden-field',
					'date-field',
					'time-field',
					'gallery-field',					
					 */
				],
				'post' => [
					'delete-post-button',  
					'post-title-field',
					'post-content-field',
					'post-excerpt-field',
					'featured-image-field',
					/* 'category-field',
					'tag-field',  */
				]

              ];


			  
            
              foreach ( $element_files as $group => $files ) {
				foreach ( $files as $file ) {
				
						//check if file exists
						if( file_exists( __DIR__ . '/elements/'. $group . '/' . $file .'.php' ) )
						\Bricks\Elements::register_element( __DIR__ . '/elements/'. $group . '/' . $file .'.php' );
				}
              }


			  $elements = [
				'edit-post-form' => 'Nested_Edit_Post',
				'new-post-form' => 'Nested_New_Post',
			];

			$post_types = get_post_types( [ 'public' => true, 'publicly_queryable' => true, 'exclude_from_search' => false ], 'objects' );
			$group = 'post';
			$exclude_types = [
				'admin_form', 'attachment', 'bricks_template'
			];

			foreach ( $elements as $file => $class ) {
				//check if file exists
				foreach( $post_types as $post_type ){
					if( in_array( $post_type->name, $exclude_types ) ) continue;
					$name = str_replace( 'post', $post_type->name, $file );
					\Bricks\Elements::register_element(
						 __DIR__ . '/elements/post/' . $file .'.php',
						$name,
						'Frontend_Admin\Bricks\Elements\\' . $class	
					);
			
					
				}

			  }

		}


	



		public function __construct() {

			add_action( 'init', array( $this, 'elements' ) );
			add_action( 'wp', [ $this, 'dynamic_elements' ] );


		}
	}

	fea_instance()->bricks = new Bricks();

endif;
