<?php
namespace Frontend_Admin\Elementor\Widgets;

use Frontend_Admin\Plugin;

use Frontend_Admin\Classes;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;
use ElementorPro\Modules\QueryControl\Module as Query_Module;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
/**

 *
 * @since 1.0.0
 */
class Submit_Button_Widget extends Widget_Base {


	/**
	 * Get widget name.
	 *
	 * Retrieve widget name.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'submit_button';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve widget title.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Submit Button', 'acf-frontend-form-element' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve widget icon.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-pencil frontend-icon';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories widget belongs to.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'frontend-admin-general' );
	}


	/**
	 * Get button sizes.
	 *
	 * Retrieve an array of button sizes for the button widget.
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return array An array containing button sizes.
	 */
	public static function get_button_sizes() {
		 return array(
			 'xs' => __( 'Extra Small', 'elementor' ),
			 'sm' => __( 'Small', 'elementor' ),
			 'md' => __( 'Medium', 'elementor' ),
			 'lg' => __( 'Large', 'elementor' ),
			 'xl' => __( 'Extra Large', 'elementor' ),
		 );
	}

	/**
	 * Register button widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_button',
			array(
				'label' => __( 'Button', 'elementor' ),
			)
		);
	
		$this->add_control(
			'button_type',
			array(
				'label'        => __( 'Type', 'elementor' ),
				'type'         => Controls_Manager::HIDDEN,
				'default'      => '',
				'prefix_class' => 'elementor-button-',
			)
		);

		$this->add_control(
			'text',
			array(
				'label'       => __( 'Text', 'elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'default'     => __( 'Submit', 'acf-frontend-form-element' ),
				'placeholder' => __( 'Submit', 'acf-frontend-form-element' ),
			)
		);

		$redirect_options = array(
			''			 => __( 'Form Default', 'acf-frontend-form-element' ),
			'current'    => __( 'Reload Current Page', 'acf-frontend-form-element' ),
			'custom_url' => __( 'Custom URL', 'acf-frontend-form-element' ),
			'referer'    => __( 'Referer', 'acf-frontend-form-element' ),
			'post_url'   => __( 'Post URL', 'acf-frontend-form-element' ),
			'none'       => __( 'None', 'acf-frontend-form-element' ),
		);
		$redirect_options = apply_filters( 'frontend_admin/forms/redirect_options', $redirect_options );

		//type: save progress or submit
		$this->add_control(
			'submit_type',
			array(
				'label'        => __( 'Submit Type', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SELECT,
				'options'      => array(
					'submit' => __( 'Submit', 'acf-frontend-form-element' ),
					'save'   => __( 'Save Progress', 'acf-frontend-form-element' ),
				),
				'default'      => 'submit',
			)
		);

		//Success Message
		$this->add_control(
			'success_message',
			array(
				'label'       => __( 'Success Message', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::TEXTAREA,
				'dynamic'     => array(
					'active' => true,
				),
				'placeholder' => __( 'Success', 'acf-frontend-form-element' ),
			)
		);

		//redirect
		$this->add_control(
			'redirect',
			array(
				'label'         => __( 'Redirect After Submit', 'acf-frontend-form-element' ),
				'type'          => Controls_Manager::SELECT,
				'options'       => $redirect_options,
				'a,llow_null'    => 0,
				'multiple'      => 0,
				'ui'            => 0,
				'return_format' => 'value',
				'ajax'          => 0,
				'placeholder'   => '',
			)
		);

		//custom url
		$this->add_control(
			'custom_url',
			array(
				'label'             => __( 'Custom Url', 'acf-frontend-form-element' ),
				'type'              => 'url',
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'redirect',
							'operator' => '==',
							'value'    => 'custom_url',
						),
					),
				),
				'placeholder'       => '',
			)
		);


		$this->add_responsive_control(
			'align',
			array(
				'label'        => __( 'Alignment', 'elementor' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'left'    => array(
						'title' => __( 'Left', 'elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => __( 'Center', 'elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => __( 'Right', 'elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => __( 'Justified', 'elementor' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'prefix_class' => 'elementor%s-align-',
				'default'      => '',
			)
		);

		$this->add_control(
			'size',
			array(
				'label'          => __( 'Size', 'elementor' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => 'sm',
				'options'        => self::get_button_sizes(),
				'style_transfer' => true,
			)
		);

		$this->add_control(
			'selected_icon',
			array(
				'label'            => __( 'Icon', 'elementor' ),
				'type'             => Controls_Manager::ICONS,
				'default'          => array(
					'value'   => 'fas fa-edit',
					'library' => 'solid',
				),
				'fa4compatibility' => 'icon',
			)
		);

		$this->add_control(
			'icon_align',
			array(
				'label'     => __( 'Icon Position', 'elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'left',
				'options'   => array(
					'left'  => __( 'Before', 'elementor' ),
					'right' => __( 'After', 'elementor' ),
				),
				'condition' => array(
					'selected_icon[value]!' => '',
				),
			)
		);

		$this->add_control(
			'icon_indent',
			array(
				'label'     => __( 'Icon Spacing', 'elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'max' => 50,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .elementor-button .elementor-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .elementor-button .elementor-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'view',
			array(
				'label'   => __( 'View', 'elementor' ),
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'traditional',
			)
		);

		$this->add_control(
			'button_css_id',
			array(
				'label'       => __( 'Button ID', 'elementor' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array(
					'active' => true,
				),
				'default'     => '',
				'title'       => __( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'elementor' ),
				'description' => __( 'Please make sure the ID is unique and not used elsewhere on the page this form is displayed. This field allows <code>A-z 0-9</code> & underscore chars without spaces.', 'elementor' ),
				'separator'   => 'before',

			)
		);

		$this->end_controls_section();

		
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Button', 'elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'global'    => array(
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				),
				'selector' => '{{WRAPPER}} .elementor-button',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .elementor-button',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .elementor-button',
			)
		);

		$this->add_responsive_control(
			'text_padding',
			array(
				'label'      => __( 'Padding', 'elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		// Start Normal tab
		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => __( 'Normal', 'elementor' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .elementor-button' => 'fill: {{VALUE}}; color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'     => __( 'Background Color', 'elementor' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default'  => Global_Colors::COLOR_ACCENT,
				),
				'selectors' => array(
					'{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_color',
			array(
				'label'     => __( 'Border Color', 'elementor' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'border_border!' => '',
				),
				'selectors' => array(
					'{{WRAPPER}} .elementor-button, {{WRAPPER}} .elementor-button' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'border',
				'selector' => '{{WRAPPER}} .elementor-button',
			)
		);
		// End Normal tab
		$this->end_controls_tab();

		// Start Hover tab
		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => __( 'Hover', 'elementor' ),
			)
		);

		$this->add_control(
			'hover_color',
			array(
				'label'     => __( 'Text Color', 'elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .elementor-button:hover, {{WRAPPER}} .elementor-button:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button:hover svg, {{WRAPPER}} .elementor-button:focus svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_background_hover_color',
			array(
				'label'       => __( 'Background Color', 'elementor' ),
				'type'        => Controls_Manager::COLOR,
				'description' => 'To add a different border for On Hover change this 
					setting. To make the border disappear completely on hover make 
					the width zero.',
				'selectors'   => array(
					'{{WRAPPER}} .elementor-button:hover, {{WRAPPER}} .elementor-button:focus' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => __( 'Border Color', 'elementor' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'border_border!' => '',
				),
				'selectors' => array(
					'{{WRAPPER}} .elementor-button:hover, {{WRAPPER}} .elementor-button:focus' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'border_hover',
				'selector' => '{{WRAPPER}} .elementor-button:hover',
			)
		);

		$this->add_control(
			'hover_animation',
			array(
				'label' => __( 'Hover Animation', 'elementor' ),
				'type'  => Controls_Manager::HOVER_ANIMATION,
			)
		);

		$this->end_controls_tab();
		// End Hover tab

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/**
	 * Render button widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function render() {
		global $fea_form;

		if( ! feadmin_edit_mode() && ( ! $fea_form || empty( $fea_form['rendered_field'] ) ) ){
			return;
		}

		$settings = $this->get_settings_for_display();

		$this->add_render_attribute( 'wrapper', 'class', 'elementor-button-wrapper' );

		$this->add_render_attribute( 'button', 'class', 'elementor-button' );
		$this->add_render_attribute( 'button', 'role', 'button' );
		$this->add_render_attribute( 'button', 'class', 'fea-submit-button' );
		$this->add_render_attribute( 'button', 'data-state', $settings['submit_type'] ?? 'publish' );

		if( ! empty( $settings['success_message'] ) ){
			$this->add_render_attribute( 'button', 'data-success', $settings['success_message'] );
		}

		if ( ! empty( $settings['redirect'] ) ) {
			$redirect = $settings['redirect'];

			if( 'custom_url' == $redirect ){
				$redirect = $settings['custom_url'];
			}
			$this->add_render_attribute( 'button', 'data-redirect', $redirect );
		}

		if ( ! empty( $settings['button_css_id'] ) ) {
			$this->add_render_attribute( 'button', 'id', $settings['button_css_id'] );
		}

		if ( ! empty( $settings['size'] ) ) {
			$this->add_render_attribute( 'button', 'class', 'elementor-size-' . $settings['size'] );
		}

		if ( $settings['hover_animation'] ) {
			$this->add_render_attribute( 'button', 'class', 'elementor-animation-' . $settings['hover_animation'] );
		}

		$wg_id           = $this->get_id();
		$current_post_id = fea_instance()->elementor->get_current_post_id();


			?>
			<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
				<button <?php echo $this->get_render_attribute_string( 'button' ); ?>>
					<?php $this->render_text(); ?>		
				</button>
				</a>
			</div>
			<?php

	}


	/**
	 * Render button text.
	 *
	 * Render button widget text.
	 *
	 * @since  1.5.0
	 * @access protected
	 */
	protected function render_text() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute(
			array(
				'content-wrapper' => array(
					'class' => 'elementor-button-content-wrapper',
				),
				'icon-align'      => array(
					'class' => array(
						'elementor-button-icon',
						'elementor-align-icon-' . $settings['icon_align'],
					),
				),
				'text'            => array(
					'class' => 'elementor-button-text',
				),
			)
		);

		$this->add_inline_editing_attributes( 'text', 'none' );
		?>
		<span <?php echo $this->get_render_attribute_string( 'content-wrapper' ); ?>>
		<?php if ( ! empty( $settings['selected_icon']['value'] ) ) : ?>
			<span <?php echo $this->get_render_attribute_string( 'icon-align' ); ?>>
			<?php
				Icons_Manager::render_icon( $settings['selected_icon'], array( 'aria-hidden' => 'true' ) );
			?>
			</span>
		<?php endif; ?>
			<span <?php echo $this->get_render_attribute_string( 'text' ); ?>><?php esc_html_e( $settings['text'] ); ?></span>
		</span>
		<?php
	}

	public function on_import( $element ) {
		 return Icons_Manager::on_import_migration( $element, 'icon', 'selected_icon' );
	}



}
