<?php

namespace Frontend_Admin\Elementor\Widgets;

use  Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**

 *
 * @since 1.0.0
 */
class Image_Field extends Base_Field {

	/**
	 * Get widget name.
	 *
	 * Retrieve acf ele form widget name.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'fea_image_field';
	}


	/**
	 * Get widget defaults.
	 *
	 * Retrieve field widget defaults.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget defaults.
	 */
	public function get_field_defaults() {
		return array(
			'field_label_on'     => 'true',
			'field_label'        => '',
			'field_name'         => '',
			'field_placeholder'  => '',
			'field_default_value' => '',
			'field_instruction'  => '',
			'prepend'            => '',
			'append'             => '',
			'custom_fields_save' => 'post',
		);
	
	}

	/**
	 * Is meta field.
	 * 
	 * Check if the field is a meta field.
	 * 
	 * @since 1.0.0
	 */
	public function is_meta_field(){
		return true;
	}


	/**
	 * 
	 * Get meta name.
	 * 
	 * Retrieve the meta name of the field.
	 * 
	 * @since 1.0.0
	 */

	public function get_meta_name(){
		return 'image_field';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve acf ele form widget title.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Image Field', 'acf-frontend-form-element' );
	}

	 /**
	  * Get widget icon.
	  *
	  * Retrieve acf ele form widget icon.
	  *
	  * @since  1.0.0
	  * @access public
	  *
	  * @return string Widget icon.
	  */
	public function get_icon() {
		return 'eicon-form-horizontal frontend-icon';
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
		return array(
			'frontend editing',
			'fields',
			'acf',
			'acf form',
		);
	}


	public function field_specific_controls() {
	
		/* $this->add_control(
			'placeholder_image',
			[
				'label' => esc_html__( 'Placeholder Image', 'frontend-admin' ),
				'type' => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		); */

        $this->add_control(
			'default_image',
			[
				'label' => esc_html__( 'Default Image', 'frontend-admin' ),
				'type' => Controls_Manager::MEDIA,
				'dynamic' => [
					'active' => true,
				],
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
        );

		$this->add_control(
			'button_text',
			[
				'label' => esc_html__( 'Button Text', 'frontend-admin' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Upload Image', 'frontend-admin' ),
			]
		);

		//button text icon
		$this->add_control(
			'button_icon',
			[
				'label' => esc_html__( 'Button Icon', 'frontend-admin' ),
				'type' => Controls_Manager::ICONS,
				'label_block' => true,
			]
		);

		//no image message
		$this->add_control(
			'no_file_text',
			[
				'label' => esc_html__( 'No File Text', 'frontend-admin' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'No File Selected', 'frontend-admin' ),
			]
		);

		//Show preview
		$this->add_control(
			'show_preview',
			[
				'label' => esc_html__( 'Show Preview', 'frontend-admin' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'label_on' => esc_html__( 'Yes', 'frontend-admin' ),
				'label_off' => esc_html__( 'No', 'frontend-admin' ),
			]
		);

		//Preview size
		$this->add_control(
			'preview_size',
			[
				'label' => esc_html__( 'Preview Size', 'frontend-admin' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'thumbnail',
				'options' => [
					'thumbnail' => esc_html__( 'Thumbnail', 'frontend-admin' ),
					'medium' => esc_html__( 'Medium', 'frontend-admin' ),
					'large' => esc_html__( 'Large', 'frontend-admin' ),
					'full' => esc_html__( 'Full', 'frontend-admin' ),
				],
			]
		);

		//Preview image in selector
		$this->add_control(
			'show_preview_in',
			[
				'label' => esc_html__( 'Preview Image In...', 'frontend-admin' ),
				'type' => Controls_Manager::TEXT,
				'description' => esc_html__( 'CSS selector where the preview image will be displayed.', 'frontend-admin' ),
				'placeholder' => '.preview-image',
			]

		);


		//img tag or background image
		$this->add_control(
			'preview_type',
			[
				'label' => esc_html__( 'Preview Type', 'frontend-admin' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'img',
				'options' => [
					'img' => esc_html__( 'Image Tag', 'frontend-admin' ),
					'background' => esc_html__( 'Background Image', 'frontend-admin' ),
				],
				'return_type' => 'none',
				'condition' => [
					'show_preview_in!' => '',
				],
			]
		);
		
		
		$this->add_control(
			'library',
			[
				'label' => esc_html__( 'Library', 'frontend-admin' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => [
					'all' => esc_html__( 'All', 'frontend-admin' ),
					'uploadedTo' => esc_html__( 'Uploaded to post', 'frontend-admin' ),
					//'uploadedUser' => esc_html__( 'Uploaded by current user', 'frontend-admin' ),
				],
			]
		);
	
	}

	public function field_specific_validation(){
		$this->add_control(
			'mime_types',
			[
				'label' => esc_html__( 'Mime Types', 'frontend-admin' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'jpeg, jpg, png',
				'description' => esc_html__( 'Comma separated list of mime types.', 'frontend-admin' ),
			]
		);



		$this->add_control(
			'min_size',
			[
				'label' => esc_html__( 'Minimum Size', 'frontend-admin' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'description' => esc_html__( 'Minimum file size in bytes.', 'frontend-admin' ),
			]
		);

		$this->add_control(
			'max_size',
			[
				'label' => esc_html__( 'Maximum Size', 'frontend-admin' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'description' => esc_html__( 'Maximum file size in bytes.', 'frontend-admin' ),
			]
		);

		$this->add_control(
			'min_width',
			[
				'label' => esc_html__( 'Minimum Width', 'frontend-admin' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'description' => esc_html__( 'Minimum image width in pixels.', 'frontend-admin' ),
			]
		);

		$this->add_control(
			'max_width',
			[
				'label' => esc_html__( 'Maximum Width', 'frontend-admin' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'description' => esc_html__( 'Maximum image width in pixels.', 'frontend-admin' ),
			]
		);

		$this->add_control(
			'min_height',
			[
				'label' => esc_html__( 'Minimum Height', 'frontend-admin' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'description' => esc_html__( 'Minimum image height in pixels.', 'frontend-admin' ),
			]
		);

		$this->add_control(
			'max_height',
			[
				'label' => esc_html__( 'Maximum Height', 'frontend-admin' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '',
				'description' => esc_html__( 'Maximum image height in pixels.', 'frontend-admin' ),
			]
		);

	}

	
	/**
	 * Get field data.
	 *
	 * Retrieve the field data.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @param array $field Field data.
	 *
	 * @return array Field data.
	 */
	protected function get_field_data( $field ) {
		$settings = $this->get_settings();
		$_field = [
			'type' => 'upload_image',
			//'placeholder' => $settings['placeholder_image']['url'],
			'default_value' => $settings['default_image']['id'],
			
		];

		$pass = [
			'button_text',
			'no_file_text',
			'show_preview',
			'preview_size',
			'show_preview_in',
			'preview_type',
			'library',
			'mime_types',
			'min_size',
			'max_size',
			'min_width',
			'max_width',
			'min_height',
			'max_height',
		];

		
		foreach ( $pass as $key ) {
			$_field[ $key ] = $settings[ $key ];
		}

		if( $settings['button_icon']['value'] ){
			$_field['button_icon'] = $settings['button_icon']['value'];
	   }


		$field = array_merge( $field, $_field );
		return $field;
	}


}
