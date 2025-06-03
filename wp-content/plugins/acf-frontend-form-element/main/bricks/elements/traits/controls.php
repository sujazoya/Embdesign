<?php 
namespace Frontend_Admin\Bricks\Elements\Traits;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


trait Controls {
    	/**
	 * Add Control group
	 * 
	 * Add a control group to the widget
	 * 
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Name of the control group.
	 * @param array  $args Arguments for the control group.
	 */
	public function add_control_group( $name, $args, $place = null ) {
		$this->current_control_group = $name;

		if ( $place === 'first' ) {
			$this->control_groups = array_merge( [ $name => $args ], $this->control_groups );
		}else{
			$this->control_groups[ $name ] = $args;
		}
	}

	public function custom_fields_control( $repeater = false ) {
		$cf_save = $this->custom_fields_save ?? 'post';
		
		$controls_settings = array(
			'label'     => __( 'Save Custom Fields to...', 'acf-frontend-form-element' ),
			'type'      => 'select',
			'default'   => $cf_save,

		);

		$custom_fields_options = array(
			'post' => __( 'Post', 'acf-frontend-form-element' ),
			'user' => __( 'User', 'acf-frontend-form-element' ),
			'term' => __( 'Term', 'acf-frontend-form-element' ),
		);
		if ( isset( fea_instance()->pro_features ) ) {
			$custom_fields_options['options'] = __( 'Site Options', 'acf-frontend-form-element' );
			if ( class_exists( 'woocommerce' ) ) {
				$custom_fields_options['product'] = __( 'Product', 'acf-frontend-form-element' );
			}
		}
		$controls_settings['options'] = $custom_fields_options;
		$this->add_control( 'custom_fields_save', $controls_settings );

	}

	/**
	 * Add Control
	 * 
	 * Add a control to the widget
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $name Name of the control.
	 * @param array  $args Arguments for the control.
	 */
	public function add_control( $name, $args ) {
		$args['group'] = $this->current_control_group;
		$this->controls[ $name ] = $args;
	}
}