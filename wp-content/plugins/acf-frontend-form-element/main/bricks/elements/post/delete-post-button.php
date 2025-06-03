<?php 
namespace Frontend_Admin\Bricks\Elements;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DeletePostButton extends \Bricks\Element_Button {
public $current_control_group = null;
  use Traits\Controls;

  /** 
   * How to create custom elements in Bricks
   * 
   * https://academy.bricksbuilder.io/article/create-your-own-elements
   */
  public $category     = 'fea-post';
  public $name         = 'fea-delete-post';
  public $icon         = 'fas fa-trash'; // FontAwesome 5 icon in builder (https://fontawesome.com/icons)
  // public $scripts      = []; // Enqueue registered scripts by their handle

  public function get_label() {
    return esc_html__( 'Delete Post', 'bricks' );
  }


  public function get_keywords() {
	return [ 'delete', 'post', 'remove', 'trash', 'frontend dashboard' ];
  }

  public function set_controls() {
		parent::set_controls();

		$this->controls['style']['default'] = 'danger';
		$this->controls['icon']['default']['library'] = 'fontawesomeRegular';
		$this->controls['icon']['default']['icon'] = 'fa fa-trash-can';
		$this->controls['text'] = [
			'type'        => 'text',
			'default'     => esc_html__( 'Delete Post', 'bricks' ),
			'placeholder' => esc_html__( 'Delete Post', 'bricks' ),
		];

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

		$this->add_control_group(
			'delete_button_section',
			array(
				'title' => __( 'Delete Post', 'acf-frontend-form-element' ),
				'tab'   => 'content',
			)
		);

		$this->add_control(
			'confirm_delete_message',
			array(
				'label'       => __( 'Confirm Delete Message', 'acf-frontend-form-element' ),
				'type'        => 'text',
				'default'     => __( 'The post will be deleted. Are you sure?', 'acf-frontend-form-element' ),
				'placeholder' => __( 'The post will be deleted. Are you sure?', 'acf-frontend-form-element' ),
			)
		);

		$this->add_control(
			'show_delete_message',
			array(
				'label'        => __( 'Show Success Message', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'default'      => true,
			)
		);
		$this->add_control(
			'delete_message',
			array(
				'label'       => __( 'Success Message', 'acf-frontend-form-element' ),
				'type'        => 'textarea',
				'default'     => __( 'You have deleted this post', 'acf-frontend-form-element' ),
				'placeholder' => __( 'You have deleted this post', 'acf-frontend-form-element' ),
				'dynamic'     => array(
					'active'    => true,
					'condition' => array(
						'show_delete_message' => 'true',
					),
				),
			)
		);
		$this->add_control(
			'force_delete',
			array(
				'label'        => __( 'Force Delete', 'acf-frontend-form-element' ),
				'type'         => 'checkbox',
				'default'      => false,
				'description'  => __( 'Whether or not to completely delete the posts right away.' ),
			)
		);

		$this->add_control(
			'redirect',
			array(
				'label'   => __( 'Redirect After Delete', 'acf-frontend-form-element' ),
				'type'    => 'select',
				'default' => 'current',
				'options' => array(
					'current'     => __( 'Reload Current Url', 'acf-frontend-form-element' ),
					'custom_url'  => __( 'Custom Url', 'acf-frontend-form-element' ),
					'referer_url' => __( 'Referer', 'acf-frontend-form-element' ),
				),
			)
		);

		$this->add_control(
			'custom_url',
			array(
				'label'         => __( 'Custom URL', 'acf-frontend-form-element' ),
				'type'          => 'text',
				'placeholder'   => __( 'Enter Url Here', 'acf-frontend-form-element' ),
				'show_external' => false,
				'dynamic'       => array(
					'active' => true,
				),
				'condition'     => array(
					'delete_redirect' => 'custom_url',
				),
			)
		);
		

		global $fea_instance;
		/* if ( isset( $fea_instance->remote_actions ) ) {
			$remote_actions = $fea_instance->remote_actions;
			foreach ( $remote_actions as $action ) {
				$action->bricks_settings_section( $this );
			}
		} */
		
		$local_actions = $fea_instance->local_actions;
		
		foreach ( $local_actions as $name => $action ) {			
			$action->bricks_settings_section( $this );
		}
		

	
  }


  public function render(){
	global $fea_form, $fea_instance, $wp_query;
	$form_display = $fea_instance->form_display;
	$current_post_id = $wp_query->get_queried_object_id();


	$this->set_attribute( '_root', 'class', 'fea-delete-button' );

	$this->tag = 'button';
	$this->set_attribute( '_root', 'type', 'button' );
	$this->set_attribute( '_root', 'data-key', $current_post_id . '_bricks_' . $this->id );


	if( ! $fea_form ){
		$reset = true;
		$form_data = $this->settings;
		$form_data['id'] = $form_data['ID'] = $current_post_id . '_bricks_' . $this->id;
		$fea_form =  $form_display->validate_form( $form_data );
	}



    $this->render_button();


	
	if( ! empty( $reset ) ){
		echo '<form >';
		$fea_instance->form_display->form_render_data( $fea_form );
		echo '</form>';
		$fea_form = null;

	}


  }

  public function render_button() {
	$settings = $this->settings;

	$this->set_attribute( '_root', 'class', 'bricks-button' );
	$this->set_attribute( '_root', 'class', 'brxe-button' );

	if ( ! empty( $settings['size'] ) ) {
		$this->set_attribute( '_root', 'class', $settings['size'] );
	}

	// Outline
	if ( isset( $settings['outline'] ) ) {
		$this->set_attribute( '_root', 'class', 'outline' );
	}

	if ( ! empty( $settings['style'] ) ) {
		// Outline (border)
		if ( isset( $settings['outline'] ) ) {
			$this->set_attribute( '_root', 'class', "bricks-color-{$settings['style']}" );
		}

		// Background (= default)
		else {
			$this->set_attribute( '_root', 'class', "bricks-background-{$settings['style']}" );
		}
	}

	// Button circle
	if ( isset( $settings['circle'] ) ) {
		$this->set_attribute( '_root', 'class', 'circle' );
	}

	if ( isset( $settings['block'] ) ) {
		$this->set_attribute( '_root', 'class', 'block' );
	}

	// Link
	if ( ! empty( $settings['link'] ) ) {
		$this->tag = 'a';

		$this->set_link_attributes( '_root', $settings['link'] );
	}

	$output = "<{$this->tag} {$this->render_attributes( '_root' )}>";

	$icon          = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;
	$icon_position = ! empty( $settings['iconPosition'] ) ? $settings['iconPosition'] : 'right';

	if ( $icon && $icon_position === 'left' ) {
		$output .= $icon;
	}

	if ( isset( $settings['text'] ) ) {
		$output .= trim( $settings['text'] );
	}

	if ( $icon && $icon_position === 'right' ) {
		$output .= $icon;
	}

	$output .= "</{$this->tag}>";

	echo $output;
}


  public function get_element( $field, $key ){

		if ( strpos( $key, '_bricks_' ) === false ) {
			return false;
		}

		// Get Template/page id and element id
		$ids = explode( '_bricks_', $key );

		// If there is no element id, there is no reason to continue 
		if( empty( $ids[1] ) ) return false; 

		$element = \Bricks\Helpers::get_element_data( $ids[0], $ids[1] );
		if( $element ){	
			$form_data = $element['element']['settings'];
			$form_data['key'] = $key;	
			$form_data['type'] = 'delete_post';		
			return $form_data;
		}
		return false;

	}


	public function __construct( $settings = [] ) {
		parent::__construct( $settings );

		add_filter( 'frontend_admin/forms/get_delete_button', [ $this, 'get_element' ], 10, 2 );

	}

}
  