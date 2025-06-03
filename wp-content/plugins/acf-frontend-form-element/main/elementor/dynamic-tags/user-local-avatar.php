<?php
namespace Frontend_Admin\DynamicTags;

use Frontend_Admin\Plugin;
use ElementorPro\Modules\DynamicTags\Module;
use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class User_Local_Avatar_Tag extends Data_Tag {


	/**
	 * Get Name
	 *
	 * Returns the Name of the tag
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_name() {
		return 'local-avatar';
	}

	/**
	 * Get Title
	 *
	 * Returns the title of the Tag
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Local Avatar', 'acf-frontend-form-element' );
	}

	/**
	 * Get Group
	 *
	 * Returns the Group of the tag
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_group() {
		return 'frontend-admin-user-data';
	}

	/**
	 * Get Categories
	 *
	 * Returns an array of tag categories
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY );
	}

	protected function register_controls() {
		$this->add_control(
			'fallback',
			array(
				'label' => __( 'Fallback', 'acf-frontend-form-element' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);
	}


	/**
	 * Render
	 *
	 * Prints out the value of the Dynamic tag
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function get_value( array $options = array() ) {
		$value = $this->get_settings( 'fallback' );
		$user_id = get_current_user_id();

		 $image_data = array(
			 'id'  => null,
			 'url' => get_avatar_url( $user_id, ['size' => 150] ) ?? $value['url'],
			);

		 if( ! $user_id ) return $image_data;


		 $img_field_key = get_option( 'local_avatar' );

		if ( $img_field_key == 'none' ) {
			return $image_data;
		}

		$field = fea_instance()->frontend->get_field( $img_field_key );

		if ( empty( $field['name'] ) ) {
			return $image_data;
		}

		$meta_key = $field['name'];

		// Get the file id
		$image_id = get_user_meta( $user_id, $meta_key, true );

		if ( ! $image_id ) {
			return $image_data;
		}

		
		$image_url = wp_get_attachment_image_src( $image_id, 'thumbnail' );
			if ( ! $image_url ) {
				return $image_data;
			}
			$avatar_url = $image_url[0];
		return [
			'id' => $image_id,
			'url' => $image_url
		];

	}
}
