<?php
namespace Frontend_Admin\Elementor\Widgets;

use Frontend_Admin\Plugin;

use Frontend_Admin\Classes;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use ElementorPro\Modules\QueryControl\Module as Query_Module;

/**

 *
 * @since 1.0.0
 */
class Delete_Post_Widget extends Widget_Base {


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
		return 'delete_post';
	}

	/**
	 * Check if the widget is dynamic.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @return bool True if the widget is dynamic, false otherwise.
	 */
	protected function is_dynamic_content(): bool {
		return true;
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
		return __( 'Trash/Delete Post', 'acf-frontend-form-element' );
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
		return 'eicon-trash frontend-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the acf ele form widget belongs to.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'frontend-admin-posts' );
	}

	/**
	 * Register acf ele form widget controls.
	 *
	 * Adds different input fields to allow the post to change and customize the widget settings.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'delete_button_section',
			array(
				'label' => __( 'Trash Button', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'delete_button_text',
			array(
				'label'       => __( 'Delete Button Text', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Delete', 'acf-frontend-form-element' ),
				'placeholder' => __( 'Delete', 'acf-frontend-form-element' ),
			)
		);
		$this->add_control(
			'delete_button_icon',
			array(
				'label' => __( 'Delete Button Icon', 'acf-frontend-form-element' ),
				'type'  => Controls_Manager::ICONS,
			)
		);

		$this->add_control(
			'confirm_delete_message',
			array(
				'label'       => __( 'Confirm Delete Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'The post will be deleted. Are you sure?', 'acf-frontend-form-element' ),
				'placeholder' => __( 'The post will be deleted. Are you sure?', 'acf-frontend-form-element' ),
			)
		);

		$this->add_control(
			'show_delete_message',
			array(
				'label'        => __( 'Show Success Message', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'acf-frontend-form-element' ),
				'label_off'    => __( 'No', 'acf-frontend-form-element' ),
				'default'      => 'true',
				'return_value' => 'true',
			)
		);
		$this->add_control(
			'delete_message',
			array(
				'label'       => __( 'Success Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXTAREA,
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
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'true',
				'return_value' => 'true',
				'description'  => __( 'Whether or not to completely delete the posts right away.' ),
			)
		);

		$this->add_control(
			'delete_redirect',
			array(
				'label'   => __( 'Redirect After Delete', 'acf-frontend-form-element' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current'     => __( 'Reload Current Url', 'acf-frontend-form-element' ),
					'custom_url'  => __( 'Custom Url', 'acf-frontend-form-element' ),
					'referer_url' => __( 'Referer', 'acf-frontend-form-element' ),
				),
			)
		);

		$this->add_control(
			'redirect_after_delete',
			array(
				'label'         => __( 'Custom URL', 'acf-frontend-form-element' ),
				'type'          => Controls_Manager::URL,
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

		$this->end_controls_section();

		$this->start_controls_section(
			'post_section',
			array(
				'label' => __( 'Post', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		fea_instance()->local_actions['post']->action_controls( $this, false, 'delete_post' );

		$this->end_controls_section();

		do_action( 'frontend_admin/elementor/permissions_controls', $this );

		if ( empty( fea_instance()->pro_features ) ) {

			$this->start_controls_section(
				'style_promo_section',
				array(
					'label' => __( 'Styles', 'acf-frontend-form-element' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'styles_promo',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => __( '<p><a target="_blank" href="https://www.dynamiapps.com/"><b>Go Pro</b></a> to unlock styles.</p>', 'acf-frontend-form-element' ),
					'content_classes' => 'acf-fields-note',
				)
			);

			$this->end_controls_section();

		} else {
			do_action( 'frontend_admin/delete_button_styles', $this );
		}

	}

	/**
	 * Render acf ele form widget output on the frontend.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function render() {
		global $fea_form, $fea_instance, $fea_limit_visibility;
		$reset = $fea_form ? false : true;	
		$button_args = $this->prepare_field();

		if( ! $button_args ){
			return;
		}
		$settings = $this->get_settings_for_display();
		

		$fea_instance->form_display->render_field_wrap( $button_args );


		if( $reset ){
			$fea_form = null;
		}
		

	}


	public function prepare_field(){
		global $post, $fea_form, $fea_instance;
		$wg_id = $this->get_id();
		$current_id = $fea_instance->elementor->get_current_post_id();
		$settings = $this->get_settings_for_display();
		

		$custom_url = '';
		if ( isset( $settings['redirect_after_delete']['url'] ) ) {
			$custom_url = $settings['redirect_after_delete']['url'];
		}

		$args = array(
			'id'                  => $current_id . '_elementor_' .$wg_id,
			'key'                 => $current_id . '_elementor_' .$wg_id,
			'name'                => $current_id . '_elementor_' .$wg_id,
			'type'                => 'delete_post',
			'label'               => '',
			'field_label_hide'    => 1,
			'button_text'         => $settings['delete_button_text'],
			'button_icon'         => $settings['delete_button_icon']['value'],
			'confirmation_text'   => $settings['confirm_delete_message'],
			'show_delete_message' => $settings['show_delete_message'],
			'delete_message'      => $settings['delete_message'],
			'force_delete'        => ! empty( $settings['force_delete'] ) && 'true' == $settings['force_delete'],
			'wrapper'             => array(
				'class' => '',
				'id'    => '',
				'width' => '',
			),
			'instructions'        => '',
			'required'            => '',
			'redirect'            => $settings['delete_redirect'],
			'custom_url'          => $custom_url ?? home_url(),
		);

		$args = $this->get_settings_to_pass( $args, $settings );
		

		if( ! $fea_form ){
			$args = $fea_instance->form_display->validate_form( $args );

			$args = apply_filters( 'frontend_admin/show_form', $args );

			if( ! $args && ! feadmin_edit_mode() ){
				$fea_form = null;
				return false;
			} 

			$fea_form = $args;

		}else{
			$key = $current_id . '_elementor_' . $this->get_id();
			$fea_form['fields'][$key] = $args;
		}

		return $args;

	}

	public function get_settings_to_pass( $form_args, $settings ) {
		 $settings_to_pass = array( 'form_conditions', 'who_can_see', 'by_role', 'by_user_id', 'dynamic', 'dynamic_manager', 'not_allowed', 'not_allowed_message', 'not_allowed_content', 'save_all_data' );

		 $current = str_replace( 'delete_', '', $this->get_name() );

		$types = array( $current );
		foreach ( $types as $type ) {
			$settings_to_pass[] = "save_to_{$type}";
			$settings_to_pass[] = "{$type}_to_edit";
			$settings_to_pass[] = "url_query_{$type}";
			$settings_to_pass[] = "{$type}_select";

			if( 'post' == $type ){
				$settings_to_pass[] = 'post_type'; 
			}
		}



		foreach ( $settings_to_pass as $setting ) {
			if ( isset( $settings[ $setting ] ) ) {
				$form_args[ $setting ] = $settings[ $setting ];
			}
		}

		return $form_args;
	}


}
