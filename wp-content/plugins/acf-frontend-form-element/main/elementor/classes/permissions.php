<?php
namespace Frontend_Admin\Elementor\Classes;

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use ElementorPro\Modules\QueryControl\Module as Query_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Permissions {

	public function elementor_permissions_controls( $widget ) {


		$section_settings = array(
			'label' => __( 'Permissions', 'acf-frontend-form-element' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		);

		//if the widget is an instance of ACF_Form, show the condition
		if ( $widget instanceof \Frontend_Admin\Elementor\Widgets\ACF_Form ) {
			$section_settings['condition'] = array(
				'admin_forms_select' => '',
			);
		}
		
		$widget->start_controls_section( 'permissions_section', $section_settings );

		$this->elementor_repeater( $widget );

		$widget->end_controls_section();

		//old permissions section for backwards compatibility
		$widget->start_controls_section(
			'old_permissions',
			array(
				'label' => __( 'Permissions', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->elementor_controls( $widget );
		$widget->end_controls_section();

	}

	public function elementor_controls( $widget ){
		$condition = array();

		$widget->add_control(
			'not_allowed',
			array(
				'label'       => __( 'No Permissions Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => 'show_nothing',
				'options'     => array(
					'show_nothing'   => __( 'None', 'acf-frontend-form-element' ),
					'show_message'   => __( 'Message', 'acf-frontend-form-element' ),
					'custom_content' => __( 'Custom Content', 'acf-frontend-form-element' ),
				),
			)
		);
		$condition['not_allowed'] = 'show_message';
		$widget->add_control(
			'not_allowed_message',
			array(
				'label'       => __( 'Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXTAREA,
				'label_block' => true,
				'rows'        => 4,
				'default'     => __( 'You do not have the proper permissions to view this form', 'acf-frontend-form-element' ),
				'placeholder' => __( 'You do not have the proper permissions to view this form', 'acf-frontend-form-element' ),
				'condition'   => $condition,
			)
		);
		$condition['not_allowed'] = 'custom_content';
		$widget->add_control(
			'not_allowed_content',
			array(
				'label'       => __( 'Content', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::WYSIWYG,
				'label_block' => true,
				'render_type' => 'none',
				'condition'   => $condition,
			)
		);
		unset( $condition['not_allowed'] );
		$who_can_see = array(
			'logged_in'  => __( 'Only Logged In Users', 'acf-frontend-form-element' ),
			'logged_out' => __( 'Only Logged Out', 'acf-frontend-form-element' ),
			'all'        => __( 'All Users', 'acf-frontend-form-element' ),
		);
		// get all user role choices
		$user_roles = feadmin_get_user_roles( array(), true );
		$user_caps  = feadmin_get_user_caps( array(), true );

		$widget->add_control(
			'who_can_see',
			array(
				'label'       => __( 'Who Can See This...', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => 'logged_in',
				'options'     => $who_can_see,
				'condition'   => $condition,
			)
		);
		$condition['who_can_see'] = 'logged_in';
		$widget->add_control(
			'by_role',
			array(
				'label'       => __( 'Select By Role', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'default'     => array( 'administrator' ),
				'options'     => $user_roles,
				'condition'   => $condition,
			)
		);
		$widget->add_control(
			'by_cap',
			array(
				'label'       => __( 'Select By Capabilities', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $user_caps,
				'condition'   => $condition,
			)
		);
		if ( ! class_exists( 'ElementorPro\\Modules\\QueryControl\\Module' ) ) {
			$widget->add_control(
				'by_user_id',
				array(
					'label'       => __( 'Select By User', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => __( '18, 12, 11', 'acf-frontend-form-element' ),
					'description' => __( 'Enter the a comma-seperated list of user ids', 'acf-frontend-form-element' ),
					'condition'   => $condition,
				)
			);
		} else {
			$widget->add_control(
				'by_user_id',
				array(
					'label'        => __( 'Select By User', 'acf-frontend-form-element' ),
					'label_block'  => true,
					'type'         => Query_Module::QUERY_CONTROL_ID,
					'autocomplete' => array(
						'object'  => Query_Module::QUERY_OBJECT_USER,
						'display' => 'detailed',
					),
					'multiple'     => true,
					'condition'    => $condition,
				)
			);
		}

		$widget->add_control(
			'wp_uploader',
			array(
				'label'        => __( 'WP Media Library', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'description'  => 'Whether to use the WordPress media library for file fields or just a basic upload button',
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'default'      => 'true',
				'return_value' => 'true',
			)
		);
		$widget->add_control(
			'media_privacy_note',
			array(
				'label'           => __( '<h3>Media Privacy</h3>', 'acf-frontend-form-element' ),
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( '<p align="left">Click <a target="_blank" href="' . admin_url( '?page=' .  'fea-settings&tab=uploads-privacy' ) . '">here</a> to limit the files displayed in the media library to the user who uploaded them.</p>', 'acf-frontend-form-element' ),
				'content_classes' => 'media-privacy-note',
			)
		);

		$widget->add_control(
			'special_permissions',
			array(
				'label'       => __( 'Special Permissions', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => array(
					'edit_posts' => __( 'Edit Other\'s Posts', 'acf-frontend-form-element' ),
					'edit_users' => __( 'Edit Users', 'acf-frontend-form-element' ),
				),
				'description' => __( 'Allow special permssions. For example, allow authors to edit other user\'s posts or other users', 'acf-frontend-form-element' ),
			)
		);

		//pro only features
		$fea_instance = fea_instance();
		if ( ! empty ( $fea_instance->pro_features ) ) {
			$widget->add_control(
				'allowed_submits',
				array(
					'label'   => __( 'Allowed Submissions', 'acf-frontend-form-element' ),
					'type'    => Controls_Manager::NUMBER,
					'default' => '',
				)
			);

			//limit reached 
			$widget->add_control(
				'limit_reached',
				array(
					'label'        => __( 'Limit Reached', 'acf-frontend-form-element' ),
					'type'         => Controls_Manager::SELECT,
					'label_block'  => true,
					'default'      => 'show_nothing',
					'options'      => array(
						'show_nothing'   => __( 'Show Nothing', 'acf-frontend-form-element' ),
						'show_message'   => __( 'Message', 'acf-frontend-form-element' ),
						'custom_content' => __( 'Custom Content', 'acf-frontend-form-element' ),
					),
				)
			);
			$widget->add_control(
				'limit_reached_message',
				array(
					'label'       => __( 'Limit Reached Message', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXTAREA,
					'label_block' => true,
					'rows'        => 4,
					'default'     => __( 'You have reached the limit of allowed submissions', 'acf-frontend-form-element' ),
					'placeholder' => __( 'You have reached the limit of allowed submissions', 'acf-frontend-form-element' ),
					'condition'   => array(
						'limit_reached' => 'show_message',
					),
				)
			);
			$widget->add_control(
				'limit_reached_content',
				array(
					'label'       => __( 'Limit Reached Content', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::WYSIWYG,
					'label_block' => true,
					'render_type' => 'none',
					'condition'   => array(
						'limit_reached' => 'custom_content',
					),
				)
			);
	
		}
	}

	public function elementor_repeater( $widget ){
		$repeater = new \Elementor\Repeater();

		//rule name
		$repeater->add_control(
			'rule_name',
			array(
				'label'       => __( 'Rule Name', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'render_type' => 'none',
				'default'     => 'Rule Name',
			)
		);

		$this->elementor_controls( $repeater );
		$widget->add_control(
			'form_conditions',
			array(
				'label'       => __( 'Form Permissions', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ rule_name }}}',
			)
		);

	}

	public function __construct() {
		add_action( 'frontend_admin/elementor/permissions_controls', array( $this, 'elementor_permissions_controls' ), 10, 2 );
	}

}

new Permissions();
