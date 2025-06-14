<?php
namespace Frontend_Admin\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Elementor\Plugin;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Nested_Form extends Widget_Nested_Base {
	public $form_defaults;

	public function get_name() {
		return 'nested-frontend-form';
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

	
	
	public function get_title() {
		return esc_html__( 'Frontend Form (Nestable)', 'elementor' );
	}

	public function get_icon() {
		return 'eicon-tabs frontend-icon';
	}

	public function get_keywords() {
		return [ 'nested', 'tabs', 'accordion', 'toggle' ];
	}

	public function get_default_widgets(){
		return [
			[
				'widgetType' => 'heading',
				'settings' => [ 'title' => __( 'Frontend Form', 'frontend-admin' ), ],
			],
			[
				'widgetType' => 'fea_text_field',
				'settings' => [
					'field_label' => __( 'Text Field', 'frontend-admin' ),
					'field_placeholder' => __( 'Enter your text here', 'frontend-admin' ),
				]
			],
			[
				'widgetType' => 'submit_button',
				'settings' => [ 'text' => __( 'Submit Button', 'frontend-admin' ), ],
			],
		];
	}

	protected function tab_content_container( int $index ) {
		$children = $this->get_default_widgets();

		$elements = [];
		foreach( $children as $child ){
			$elements[] = array_merge(
				$child,
				[
					'id' => \Elementor\Utils::generate_random_string(),
					'elType' => 'widget'
				]
			);
		}

		return [
			'elType' => 'container',
			'settings' => [
				'_title' => sprintf( __( 'Step #%s', 'elementor' ), $index ),
				'content_width' => 'full',
			],
			'elements' => $elements,
		];
	}

	protected function get_default_children_elements() {
		return [
			$this->tab_content_container( 1 ),
		];
	}

	protected function get_default_repeater_title_setting_key() {
		return 'step_title';
	}

	protected function get_default_children_title() {
		return esc_html__( 'Step #%d', 'elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.e-n-tabs-content';
	}

	protected function get_html_wrapper_class() {
		return 'elementor-widget-n-tabs';
	}

	protected function register_controls() {
		
		$this->start_controls_section( 'section_form_submissions', [
			'label' => esc_html__( 'Submissions', 'frontend-admin' ),
			'tab' => Controls_Manager::TAB_CONTENT
		] );

		$this->add_control(
			'admin_forms_select',
			array(
				'label'       => __( 'Choose Form...', 'acf-frontend-form-element' ),
				'type'        => Controls_Manager::HIDDEN,
				'default'     => '',
			)
		);

		$defaults = $this->get_form_defaults();

		$cf_save = $defaults['custom_fields_save'] ?? 'post';
		$this->add_control( 'custom_fields_save', 
			[
				'label' => __( 'Save custom fields to...', 'frontend-admin' ),
				'type' => Controls_Manager::SELECT,
				'default' => $cf_save,
				'options' => [
					'submission' => __( 'Submission', 'acf-frontend-form-element' ),
					'post' => __( 'Post', 'frontend-admin' ),
					'user' => __( 'User', 'frontend-admin' ),
					'term' => __( 'Term', 'frontend-admin' ),
					'product' => __( 'Product', 'frontend-admin' ),
					'options' => __( 'Options', 'frontend-admin' ),
				],
			]
		);

		$this->add_control(
			'save_all_data',
			array(
				'label'     => __( 'Save Data After...', 'acf-frontend-form-element' ),
				'type'      => Controls_Manager::SELECT2,
				'multiple'  => true,
				'default'   => '',
				'options'   => array(
					'require_approval' => __( 'Admin Approval', 'acf-frontend-form-element' ),
					'verify_email'     => __( 'Email is Verified', 'acf-frontend-form-element' ),
				),
				'condition' => array(
					'admin_forms_select'    => '',
				),
			)
		);

		$this->end_controls_section();

		$this->form_defaults = $this->get_form_defaults();

		do_action( 'frontend_admin/elementor/action_controls', $this, true );
		do_action( 'frontend_admin/elementor/actions_controls', $this, true );
		do_action( 'frontend_admin/elementor/permissions_controls', $this, true );
		do_action( 'frontend_admin/elementor_widget/content_controls', $this );


		$this->start_controls_section( 'section_nested_form', [
			'label' => esc_html__( 'Nested Form', 'elementor' ),
		] );

		
		$repeater = new Repeater();

		$repeater->add_control( 'step_title', [
			'label' => esc_html__( 'Title', 'elementor' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Step Title', 'elementor' ),
			'placeholder' => esc_html__( 'Step Title', 'elementor' ),
			'label_block' => true,
			'dynamic' => [
				'active' => true,
			],
		] );

		$repeater->add_control(
			'tab_icon',
			[
				'label' => esc_html__( 'Icon', 'elementor' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin' => 'inline',
				'label_block' => false,
			]
		);

		$repeater->add_control(
			'tab_icon_active',
			[
				'label' => esc_html__( 'Active Icon', 'elementor' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin' => 'inline',
				'label_block' => false,
				'condition' => [
					'tab_icon[value]!' => '',
				],
			]
		);

		$repeater->add_control(
			'element_id',
			[
				'label' => esc_html__( 'CSS ID', 'elementor' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'ai' => [
					'active' => false,
				],
				'dynamic' => [
					'active' => true,
				],
				'title' => esc_html__( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'elementor' ),
				'style_transfer' => false,
				'classes' => 'elementor-control-direction-ltr',
			]
		);

		$this->add_control( 'tabs', [
			'label' => esc_html__( 'Form Steps', 'elementor' ),
			'type' => Control_Nested_Repeater::CONTROL_TYPE,
			'fields' => $repeater->get_controls(),
			'description' => __( 'Multi Step Forms coming soon to Frontend Admin PRO!', 'frontend-admin' ),
			'default' => [
				[
					'step_title' => esc_html__( 'Single Step Form', 'elementor' ),
				],
			],
			'title_field' => '{{{ step_title }}}',
			'item_actions' => [
				'add' => false,
				'remove' => true,
				'duplicate' => false,
			],
			'button_text' => 'Add Step',
		] );

		$this->end_controls_section();
		
	}

	public function get_form_defaults(){
		return [];
	}

	protected function render_step_titles_html( $item_settings ): string {
		$setting_key = $this->get_repeater_setting_key( 'step_title', 'tabs', $item_settings['index'] );
		$title = $item_settings['item']['step_title'];
		$css_classes = [ 'e-n-tab-title' ];

		if ( $item_settings['settings']['hover_animation'] ) {
			$css_classes[] = 'elementor-animation-' . $item_settings['settings']['hover_animation'];
		}

		$this->add_render_attribute( $setting_key, [
			'id' => $item_settings['tab_id'],
			'class' => $css_classes,
			'aria-selected' => 1 === $item_settings['tab_count'] ? 'true' : 'false',
			'data-tab-index' => $item_settings['tab_count'],
			'role' => 'tab',
			'tabindex' => 1 === $item_settings['tab_count'] ? '0' : '-1',
			'aria-controls' => $item_settings['container_id'],
			'style' => '--n-tabs-title-order: ' . $item_settings['tab_count'] . ';',
		] );

		$render_attributes = $this->get_render_attribute_string( $setting_key );
		$text_class = $this->get_render_attribute_string( 'tab-title-text' );
		$icon_class = $this->get_render_attribute_string( 'tab-icon' );

		$icon_html = Icons_Manager::try_get_icon_html( $item_settings['item']['tab_icon'], [ 'aria-hidden' => 'true' ] );
		$icon_active_html = $icon_html;

		if ( $this->is_active_icon_exist( $item_settings['item'] ) ) {
			$icon_active_html = Icons_Manager::try_get_icon_html( $item_settings['item']['tab_icon_active'], [ 'aria-hidden' => 'true' ] );
		}

		ob_start();
		?>
			<button <?php echo $render_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span <?php echo $icon_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $icon_active_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<span <?php echo $text_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo wp_kses_post( $title ); ?>
				</span>
			</button>
		<?php
		return ob_get_clean();
	}

	protected function render_tab_containers_html( $item_settings ): string {
		//error_log( print_r( $item_settings, true ) );
		ob_start();
		$this->print_child( $item_settings['index'], $item_settings );
		return ob_get_clean();
	}


	/**
	 * Print the content area.
	 *
	 * @param int $index
	 * @param array $item_settings
	 */
	public function print_child( $index, $item_settings = [] ) {
		$children = $this->get_children();
		if( empty( $children ) ) return;
		$child_ids = [];

		foreach ( $children as $child ) {
			$child_ids[] = $child->get_id();
		}

		// Add data-tab-index attribute to the content area.
		$add_attribute_to_container = function ( $should_render, $container ) use ( $item_settings, $child_ids ) {
			if ( in_array( $container->get_id(), $child_ids ) ) {
				$this->add_attributes_to_container( $container, $item_settings );
			}

			return $should_render;
		};

		add_filter( 'elementor/frontend/container/should_render', $add_attribute_to_container, 10, 3 );
		if( isset( $children[ $index ] ) ) $children[ $index ]->print_element();
		remove_filter( 'elementor/frontend/container/should_render', $add_attribute_to_container );
	}

	protected function add_attributes_to_container( $container, $item_settings ) {
		$container->add_render_attribute( '_wrapper', [
			'id' => $item_settings['container_id'],
			'role' => 'tabpanel',
			'aria-labelledby' => $item_settings['tab_id'],
			'data-tab-index' => $item_settings['tab_count'],
			'style' => '--n-tabs-title-order: ' . $item_settings['tab_count'] . ';',
			'class' => [
				0 === $item_settings['index'] ? 'e-active' : '',
			]
		] );
	}

	protected function render() {		
		$settings = $this->get_settings_for_display();

		$widget_number = substr( $this->get_id_int(), 0, 3 );

		if ( ! empty( $settings['link'] ) ) {
			$this->add_link_attributes( 'elementor-tabs', $settings['link'] );
		}

		global $fea_instance, $fea_form, $fea_scripts, $fea_limit_visibility;
		$form = $this->prepare_form( $settings );
		if( ! $form ) return;

	
		$form = apply_filters( 'frontend_admin/show_form', $form );

		if( ! $form ){
			$fea_form = null;
			return;
		} 

		do_action( 'frontend_admin/elementor/before_render', $form );

		$fea_instance->frontend->enqueue_scripts( 'frontend_admin_form' );
		$fea_scripts = true;

		echo '<form '. feadmin_get_esc_attrs( $form['form_attributes'] ) .'>';

		$this->add_render_attribute( 'elementor-tabs', [
			'class' => 'e-n-tabs',
			'data-widget-number' => $widget_number,
			'aria-label' => esc_html__( 'Frontend Form. Open items with Enter or Space, close with Escape and navigate using the Arrow keys.', 'elementor' ),
		] );

		$this->add_render_attribute( 'tab-title-text', 'class', 'e-n-tab-title-text' );
		$this->add_render_attribute( 'tab-icon', 'class', 'e-n-tab-icon' );
		$this->add_render_attribute( 'tab-icon-active', 'class', [ 'e-n-tab-icon' ] );

		$step_titles_html = '';
		$tab_containers_html = '';

		$tabs_count = count( $settings['tabs'] );

		foreach ( $settings['tabs'] as $index => $item ) {
			$tab_count = $index + 1;

			$tab_id = empty( $item['element_id'] )
				? 'e-n-tabs-title-' . $widget_number . $tab_count
				: $item['element_id'];

			$item_settings = [
				'index' => $index,
				'tab_count' => $tab_count,
				'tab_id' => $tab_id,
				'container_id' => 'e-n-tab-content-' . $widget_number . $tab_count,
				'widget_number' => $widget_number,
				'item' => $item,
				'settings' => $settings,
			];

			if( $tabs_count > 1 ) $step_titles_html .= $this->render_step_titles_html( $item_settings );
			$tab_containers_html .= $this->render_tab_containers_html( $item_settings );
		}
		?>
		
		<div <?php $this->print_render_attribute_string( 'elementor-tabs' ); ?>>
			<?php if( $tabs_count > 1 ){ ?>
				<div class="e-n-tabs-heading" role="tablist">
					<?php echo $step_titles_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php } ?>
			<div class="e-n-tabs-content">
				<?php echo $tab_containers_html;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
		$fea_instance->form_display->form_render_data( $form );

		echo '</form>';

		do_action( 'frontend_admin/elementor/after_render', $form );

		$fea_form = null;

	}

	protected function content_template() {
		global $fea_limit_visibility;


		?>
		<# const elementUid = view.getIDInt().toString().substr( 0, 3 ); #>

		<?php
			if( ! $fea_limit_visibility ){
		?>
			<# if ( ! settings['form_conditions'] || settings['form_conditions'].length < 1 ) { #>
				<div class="fea-no-permissions-message">
					<?php echo esc_html__( 'By default, this form is only visible to administrators. To change this, please set the visibilty for this element or the entire page.', 'frontend-admin' ); ?>
				</div>
			<# } #>
		<?php } ?>

		<div class="e-n-tabs" data-widget-number="{{ elementUid }}" aria-label="<?php echo esc_html__( 'Frontend Form. Open items with Enter or Space, close with Escape and navigate using the Arrow keys.', 'elementor' ); ?>">
			<# if ( settings['tabs'] ) { #>
				<# if ( settings['tabs'].length > 1 ) { #>
				<div class="e-n-tabs-heading" role="tablist">
					<# _.each( settings['tabs'], function( item, index ) {
					const tabCount = index + 1,
						tabUid = elementUid + tabCount,
						tabWrapperKey = tabUid,
						tabTitleKey = 'tab-title-' + tabUid,
						tabIconKey = 'tab-icon-' + tabUid,
						tabIcon = elementor.helpers.renderIcon( view, item.tab_icon, { 'aria-hidden': true }, 'i' , 'object' ),
						hoverAnimationClass = settings['hover_animation'] ? `elementor-animation-${ settings['hover_animation'] }` : '';

					let tabActiveIcon = tabIcon,
						tabId = 'e-n-tab-title-' + tabUid;

					if ( '' !== item.tab_icon_active.value ) {
						tabActiveIcon = elementor.helpers.renderIcon( view, item.tab_icon_active, { 'aria-hidden': true }, 'i' , 'object' );
					}

					if ( '' !== item.element_id ) {
						tabId = item.element_id;
					}

					view.addRenderAttribute( tabWrapperKey, {
						'id': tabId,
						'class': [ 'e-n-tab-title',hoverAnimationClass ],
						'data-tab-index': tabCount,
						'role': 'tab',
						'aria-selected': 1 === tabCount ? 'true' : 'false',
						'tabindex': 1 === tabCount ? '0' : '-1',
						'aria-controls': 'e-n-tab-content-' + tabUid,
						'style': '--n-tabs-title-order: ' + tabCount + ';',
					} );

					view.addRenderAttribute( tabTitleKey, {
						'class': [ 'e-n-tab-title-text' ],
						'data-binding-type': 'repeater-item',
						'data-binding-repeater-name': 'tabs',
						'data-binding-setting': [ 'step_title' ],
						'data-binding-index': tabCount,
					} );

					view.addRenderAttribute( tabIconKey, {
						'class': [ 'e-n-tab-icon' ],
						'data-binding-type': 'repeater-item',
						'data-binding-repeater-name': 'tabs',
						'data-binding-setting': [ 'tab_icon.value', 'tab_icon_active.value' ],
						'data-binding-index': tabCount,
					} );
					#>
					<button {{{ view.getRenderAttributeString( tabWrapperKey ) }}}>
						<span {{{ view.getRenderAttributeString( tabIconKey ) }}}>{{{ tabIcon.value }}}{{{ tabActiveIcon.value }}}</span>
						<span {{{ view.getRenderAttributeString( tabTitleKey ) }}}>{{{ item.step_title }}}</span>
					</button>
					<# } ); #>
				</div>
				<# } #>
			<div class="e-n-tabs-content"></div>
			<# } #>
		</div>
		<?php
	}

	/**
	 * @param $item
	 * @return bool
	 */
	private function is_active_icon_exist( $item ) {
		return array_key_exists( 'tab_icon_active', $item ) && ! empty( $item['tab_icon_active'] ) && ! empty( $item['tab_icon_active']['value'] );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}


	protected function step_controls(){
		$start = is_rtl() ? 'right' : 'left';
		$end = is_rtl() ? 'left' : 'right';
		$start_logical = is_rtl() ? 'end' : 'start';
		$end_logical = is_rtl() ? 'start' : 'end';
		$logical_dimensions_inline_start = is_rtl() ? '{{RIGHT}}{{UNIT}}' : '{{LEFT}}{{UNIT}}';
		$logical_dimensions_inline_end = is_rtl() ? '{{LEFT}}{{UNIT}}' : '{{RIGHT}}{{UNIT}}';
		$heading_selector_non_touch_device = '{{WRAPPER}} > .e-n-tabs[data-touch-mode="false"] > .e-n-tabs-heading';
		$heading_selector_touch_device = '{{WRAPPER}} > .e-n-tabs[data-touch-mode="true"] > .e-n-tabs-heading';
		$heading_selector = '{{WRAPPER}} > .e-n-tabs > .e-n-tabs-heading';
		$content_selector = ':where( {{WRAPPER}} > .e-n-tabs > .e-n-tabs-content ) > .e-con';

		$this->start_controls_section( 'section_tabs', [
			'label' => esc_html__( 'Form', 'elementor' ),
		] );

		$styling_block_start = '--n-tabs-direction: column; --n-tabs-heading-direction: row; --n-tabs-heading-width: initial; --n-tabs-title-flex-basis: content; --n-tabs-title-flex-shrink: 0;';
		$styling_block_end = '--n-tabs-direction: column-reverse; --n-tabs-heading-direction: row; --n-tabs-heading-width: initial; --n-tabs-title-flex-basis: content; --n-tabs-title-flex-shrink: 0';
		$styling_inline_end = '--n-tabs-direction: row-reverse; --n-tabs-heading-direction: column; --n-tabs-heading-width: 240px; --n-tabs-title-flex-basis: initial; --n-tabs-title-flex-shrink: initial;';
		$styling_inline_start = '--n-tabs-direction: row; --n-tabs-heading-direction: column; --n-tabs-heading-width: 240px; --n-tabs-title-flex-basis: initial; --n-tabs-title-flex-shrink: initial;';

		$this->add_responsive_control( 'tabs_direction', [
			'label' => esc_html__( 'Direction', 'elementor' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'block-start' => [
					'title' => esc_html__( 'Above', 'elementor' ),
					'icon' => 'eicon-v-align-top',
				],
				'block-end' => [
					'title' => esc_html__( 'Below', 'elementor' ),
					'icon' => 'eicon-v-align-bottom',
				],
				'inline-end' => [
					'title' => esc_html__( 'After', 'elementor' ),
					'icon' => 'eicon-h-align-' . $end,
				],
				'inline-start' => [
					'title' => esc_html__( 'Before', 'elementor' ),
					'icon' => 'eicon-h-align-' . $start,
				],
			],
			'separator' => 'before',
			'selectors_dictionary' => [
				'block-start' => $styling_block_start,
				'block-end' => $styling_block_end,
				'inline-end' => $styling_inline_end,
				'inline-start' => $styling_inline_start,
				// Styling duplication for BC reasons.
				'top' => $styling_block_start,
				'bottom' => $styling_block_end,
				'end' => $styling_inline_end,
				'start' => $styling_inline_start,
			],
			'selectors' => [
				'{{WRAPPER}}' => '{{VALUE}}',
			],
		] );

		$this->add_responsive_control( 'tabs_justify_horizontal', [
			'label' => esc_html__( 'Justify', 'elementor' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'start' => [
					'title' => esc_html__( 'Start', 'elementor' ),
					'icon' => "eicon-align-$start_logical-h",
				],
				'center' => [
					'title' => esc_html__( 'Center', 'elementor' ),
					'icon' => 'eicon-align-center-h',
				],
				'end' => [
					'title' => esc_html__( 'End', 'elementor' ),
					'icon' => "eicon-align-$end_logical-h",
				],
				'stretch' => [
					'title' => esc_html__( 'Stretch', 'elementor' ),
					'icon' => 'eicon-align-stretch-h',
				],
			],
			'selectors_dictionary' => [
				'start' => '--n-tabs-heading-justify-content: flex-start; --n-tabs-title-width: initial; --n-tabs-title-height: initial; --n-tabs-title-align-items: center; --n-tabs-title-flex-grow: 0;',
				'center' => '--n-tabs-heading-justify-content: center; --n-tabs-title-width: initial; --n-tabs-title-height: initial; --n-tabs-title-align-items: center; --n-tabs-title-flex-grow: 0;',
				'end' => '--n-tabs-heading-justify-content: flex-end; --n-tabs-title-width: initial; --n-tabs-title-height: initial; --n-tabs-title-align-items: center; --n-tabs-title-flex-grow: 0;',
				'stretch' => '--n-tabs-heading-justify-content: initial; --n-tabs-title-width: 100%; --n-tabs-title-height: initial; --n-tabs-title-align-items: center; --n-tabs-title-flex-grow: 1;',
			],
			'selectors' => [
				'{{WRAPPER}}' => '{{VALUE}}',
			],
			'condition' => [
				'tabs_direction' => [
					'',
					'block-start',
					'block-end',
					'top',
					'bottom',
				],
			],
			'frontend_available' => true,
		] );

		$this->add_responsive_control( 'tabs_justify_vertical', [
			'label' => esc_html__( 'Justify', 'elementor' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'start' => [
					'title' => esc_html__( 'Start', 'elementor' ),
					'icon' => 'eicon-align-start-v',
				],
				'center' => [
					'title' => esc_html__( 'Center', 'elementor' ),
					'icon' => 'eicon-align-center-v',
				],
				'end' => [
					'title' => esc_html__( 'End', 'elementor' ),
					'icon' => 'eicon-align-end-v',
				],
				'stretch' => [
					'title' => esc_html__( 'Stretch', 'elementor' ),
					'icon' => 'eicon-align-stretch-v',
				],
			],
			'selectors_dictionary' => [
				'start' => '--n-tabs-heading-justify-content: flex-start; --n-tabs-title-width: initial; --n-tabs-title-height: initial; --n-tabs-title-align-items: initial; --n-tabs-heading-wrap: wrap; --n-tabs-title-flex-basis: content',
				'center' => '--n-tabs-heading-justify-content: center; --n-tabs-title-width: initial; --n-tabs-title-height: initial; --n-tabs-title-align-items: initial; --n-tabs-heading-wrap: wrap; --n-tabs-title-flex-basis: content',
				'end' => '--n-tabs-heading-justify-content: flex-end; --n-tabs-title-width: initial; --n-tabs-title-height: initial; --n-tabs-title-align-items: initial; --n-tabs-heading-wrap: wrap; --n-tabs-title-flex-basis: content',
				'stretch' => '--n-tabs-heading-justify-content: flex-start; --n-tabs-title-width: initial; --n-tabs-title-height: 100%; --n-tabs-title-align-items: center; --n-tabs-heading-wrap: nowrap; --n-tabs-title-flex-basis: auto',
			],
			'selectors' => [
				'{{WRAPPER}}' => '{{VALUE}}',
			],
			'condition' => [
				'tabs_direction' => [
					'inline-start',
					'inline-end',
					'start',
					'end',
				],
			],
		] );

		$this->add_responsive_control( 'tabs_width', [
			'label' => esc_html__( 'Width', 'elementor' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [
				'%' => [
					'min' => 10,
					'max' => 50,
				],
				'px' => [
					'min' => 20,
					'max' => 600,
				],
			],
			'default' => [
				'unit' => '%',
			],
			'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-heading-width: {{SIZE}}{{UNIT}}',
			],
			'condition' => [
				'tabs_direction' => [
					'inline-start',
					'inline-end',
					'start',
					'end',
				],
			],
		] );

		$this->add_responsive_control( 'title_alignment', [
			'label' => esc_html__( 'Align Title', 'elementor' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'start' => [
					'title' => esc_html__( 'Start', 'elementor' ),
					'icon' => 'eicon-text-align-left',
				],
				'center' => [
					'title' => esc_html__( 'Center', 'elementor' ),
					'icon' => 'eicon-text-align-center',
				],
				'end' => [
					'title' => esc_html__( 'End', 'elementor' ),
					'icon' => 'eicon-text-align-right',
				],
			],
			'selectors_dictionary' => [
				'start' => '--n-tabs-title-justify-content: flex-start; --n-tabs-title-align-items: flex-start; --n-tabs-title-text-align: start;',
				'center' => '--n-tabs-title-justify-content: center; --n-tabs-title-align-items: center; --n-tabs-title-text-align: center;',
				'end' => '--n-tabs-title-justify-content: flex-end; --n-tabs-title-align-items: flex-end; --n-tabs-title-text-align: end;',
			],
			'selectors' => [
				'{{WRAPPER}}' => '{{VALUE}}',
			],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'section_tabs_responsive', [
			'label' => esc_html__( 'Additional Settings', 'elementor' ),
		] );

		$this->add_responsive_control(
			'horizontal_scroll',
			[
				'label' => esc_html__( 'Horizontal Scroll', 'elementor' ),
				'type' => Controls_Manager::SELECT,
				'description' => esc_html__( 'Note: Scroll tabs if they don’t fit into their parent container.', 'elementor' ),
				'options' => [
					'disable' => esc_html__( 'Disable', 'elementor' ),
					'enable' => esc_html__( 'Enable', 'elementor' ),
				],
				'default' => 'disable',
				'selectors_dictionary' => [
					'disable' => '--n-tabs-heading-wrap: wrap; --n-tabs-heading-overflow-x: initial; --n-tabs-title-white-space: initial;',
					'enable' => '--n-tabs-heading-wrap: nowrap; --n-tabs-heading-overflow-x: scroll; --n-tabs-title-white-space: nowrap;',
				],
				'selectors' => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
				'frontend_available' => true,
				'condition' => [
					'tabs_direction' => [
						'',
						'block-start',
						'block-end',
						'top',
						'bottom',
					],
				],
			]
		);

		$dropdown_options = [
			'none' => esc_html__( 'None', 'elementor' ),
		];
		$excluded_breakpoints = [
			'laptop',
			'tablet_extra',
			'widescreen',
		];

		foreach ( Plugin::$instance->breakpoints->get_active_breakpoints() as $breakpoint_key => $breakpoint_instance ) {
			// Exclude the larger breakpoints from the dropdown selector.
			if ( in_array( $breakpoint_key, $excluded_breakpoints, true ) ) {
				continue;
			}

			$dropdown_options[ $breakpoint_key ] = sprintf(
				/* translators: 1: Breakpoint label, 2: `>` character, 3: Breakpoint value. */
				esc_html__( '%1$s (%2$s %3$dpx)', 'elementor' ),
				$breakpoint_instance->get_label(),
				'>',
				$breakpoint_instance->get_value()
			);
		}

		$this->add_control(
			'breakpoint_selector',
			[
				'label' => esc_html__( 'Breakpoint', 'elementor' ),
				'type' => Controls_Manager::SELECT,
				'description' => esc_html__( 'Note: Choose at which breakpoint tabs will automatically switch to a vertical (“accordion”) layout.', 'elementor' ),
				'options' => $dropdown_options,
				'default' => 'mobile',
				'prefix_class' => 'e-n-tabs-',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_tabs_style', [
			'label' => esc_html__( 'Frontend Form', 'elementor' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'tabs_title_space_between', [
			'label' => esc_html__( 'Gap between tabs', 'elementor' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'range' => [
				'px' => [
					'max' => 400,
				],
				'em' => [
					'max' => 40,
				],
				'rem' => [
					'max' => 40,
				],
			],
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-title-gap: {{SIZE}}{{UNIT}}',
			],
		] );

		$this->add_responsive_control( 'tabs_title_spacing', [
			'label' => esc_html__( 'Distance from content', 'elementor' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'range' => [
				'px' => [
					'max' => 400,
				],
				'em' => [
					'max' => 40,
				],
				'rem' => [
					'max' => 40,
				],
			],
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-gap: {{SIZE}}{{UNIT}}',
			],
		] );

		$this->start_controls_tabs( 'tabs_title_style' );

		$this->start_controls_tab(
			'tabs_title_normal',
			[
				'label' => esc_html__( 'Normal', 'elementor' ),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'tabs_title_background_color',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} > .e-n-tabs > .e-n-tabs-heading > .e-n-tab-title[aria-selected="false"]:not( :hover )',
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Background Color', 'elementor' ),
						'selectors' => [
							'{{SELECTOR}}' => 'background: {{VALUE}}',
						],
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'tabs_title_border',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"false\"]:not( :hover )",
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Border Color', 'elementor' ),
					],
					'width' => [
						'label' => esc_html__( 'Border Width', 'elementor' ),
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'tabs_title_box_shadow',
				'label' => esc_html__( 'Shadow', 'elementor' ),
				'separator' => 'after',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"false\"]:not( :hover )",
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tabs_title_hover',
			[
				'label' => esc_html__( 'Hover', 'elementor' ),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'tabs_title_background_color_hover',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => "{$heading_selector_non_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color' => [
						'global' => [
							'default' => Global_Colors::COLOR_ACCENT,
						],
						'label' => esc_html__( 'Background Color', 'elementor' ),
						'selectors' => [
							'{{SELECTOR}}' => 'background: {{VALUE}};',
						],
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'tabs_title_border_hover',
				'selector' => "{$heading_selector_non_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Border Color', 'elementor' ),
					],
					'width' => [
						'label' => esc_html__( 'Border Width', 'elementor' ),
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'tabs_title_box_shadow_hover',
				'label' => esc_html__( 'Shadow', 'elementor' ),
				'separator' => 'after',
				'selector' => "{$heading_selector_non_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
			]
		);

		$this->add_control(
			'hover_animation',
			[
				'label' => esc_html__( 'Hover Animation', 'elementor' ),
				'type' => Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->add_control(
			'tabs_title_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'elementor' ) . ' (s)',
				'type' => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}}' => '--n-tabs-title-transition: {{SIZE}}s',
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 3,
						'step' => 0.1,
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tabs_title_active',
			[
				'label' => esc_html__( 'Active', 'elementor' ),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'tabs_title_background_color_active',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"true\"], {$heading_selector_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
				'fields_options' => [
					'background' => [
						'default' => 'classic',
					],
					'color' => [
						'global' => [
							'default' => Global_Colors::COLOR_ACCENT,
						],
						'label' => esc_html__( 'Background Color', 'elementor' ),
						'selectors' => [
							'{{SELECTOR}}' => 'background: {{VALUE}};',
						],
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'tabs_title_border_active',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"true\"], {$heading_selector_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Border Color', 'elementor' ),
					],
					'width' => [
						'label' => esc_html__( 'Border Width', 'elementor' ),
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'tabs_title_box_shadow_active',
				'label' => esc_html__( 'Shadow', 'elementor' ),
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"true\"], {$heading_selector_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'tabs_title_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'separator' => 'before',
				'selectors' => [
					'{{WRAPPER}}' => '--n-tabs-title-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'padding',
			[
				'label' => esc_html__( 'Padding', 'elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => "--n-tabs-title-padding-block-start: {{TOP}}{{UNIT}}; --n-tabs-title-padding-inline-end: $logical_dimensions_inline_end; --n-tabs-title-padding-block-end: {{BOTTOM}}{{UNIT}}; --n-tabs-title-padding-inline-start: $logical_dimensions_inline_start;",
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section( 'section_title_style', [
			'label' => esc_html__( 'Titles', 'elementor' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'title_typography',
			'global' => [
				'default' => Global_Typography::TYPOGRAPHY_ACCENT,
			],
			'selector' => "{$heading_selector} > :is( .e-n-tab-title > .e-n-tab-title-text, .e-n-tab-title )",
			'fields_options' => [
				'font_size' => [
					'selectors' => [
						'{{WRAPPER}}' => '--n-tabs-title-font-size: {{SIZE}}{{UNIT}}',
					],
				],
			],
		] );

		$this->start_controls_tabs( 'title_style' );

		$this->start_controls_tab(
			'title_normal',
			[
				'label' => esc_html__( 'Normal', 'elementor' ),
			]
		);

		$this->add_control(
			'title_text_color',
			[
				'label' => esc_html__( 'Color', 'elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--n-tabs-title-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_text_shadow',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"false\"]:not( :hover )",
				'fields_options' => [
					'text_shadow_type' => [
						'label' => esc_html__( 'Shadow', 'elementor' ),
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'title_text_stroke',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"false\"]:not( :hover ) :is( span, a, i )",
				'fields_options' => [
					'text_stroke_type' => [
						'label' => esc_html__( 'Stroke', 'elementor' ),
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'title_hover',
			[
				'label' => esc_html__( 'Hover', 'elementor' ),
			]
		);

		$this->add_control(
			'title_text_color_hover',
			[
				'label' => esc_html__( 'Color', 'elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} [data-touch-mode="false"] .e-n-tab-title[aria-selected="false"]:hover' => '--n-tabs-title-color-hover: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_text_shadow_hover',
				'selector' => "{$heading_selector_non_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
				'fields_options' => [
					'text_shadow_type' => [
						'label' => esc_html__( 'Shadow', 'elementor' ),
					],
				],

			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'title_text_stroke_hover',
				'selector' => "{$heading_selector_non_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover :is( span, a, i )",
				'fields_options' => [
					'text_stroke_type' => [
						'label' => esc_html__( 'Stroke', 'elementor' ),
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'title_active',
			[
				'label' => esc_html__( 'Active', 'elementor' ),
			]
		);

		$this->add_control(
			'title_text_color_active',
			[
				'label' => esc_html__( 'Color', 'elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--n-tabs-title-color-active: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_text_shadow_active',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"true\"], {$heading_selector_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover",
				'fields_options' => [
					'text_shadow_type' => [
						'label' => esc_html__( 'Shadow', 'elementor' ),
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'title_text_stroke_active',
				'selector' => "{$heading_selector} > .e-n-tab-title[aria-selected=\"true\"] :is( span, a, i ), {$heading_selector_touch_device} > .e-n-tab-title[aria-selected=\"false\"]:hover :is( span, a, i )",
				'fields_options' => [
					'text_stroke_type' => [
						'label' => esc_html__( 'Stroke', 'elementor' ),
					],
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section( 'icon_section_style', [
			'label' => esc_html__( 'Icon', 'elementor' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$styling_block_start = '--n-tabs-title-direction: column; --n-tabs-icon-order: initial; --n-tabs-title-justify-content-toggle: center; --n-tabs-title-align-items-toggle: initial;';
		$styling_block_end = '--n-tabs-title-direction: column; --n-tabs-icon-order: 1; --n-tabs-title-justify-content-toggle: center; --n-tabs-title-align-items-toggle: initial;';
		$styling_inline_start = '--n-tabs-title-direction: row; --n-tabs-icon-order: initial; --n-tabs-title-justify-content-toggle: initial; --n-tabs-title-align-items-toggle: center;';
		$styling_inline_end = '--n-tabs-title-direction: row; --n-tabs-icon-order: 1; --n-tabs-title-justify-content-toggle: initial; --n-tabs-title-align-items-toggle: center;';

		$this->add_responsive_control( 'icon_position', [
			'label' => esc_html__( 'Position', 'elementor' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'block-start' => [
					'title' => esc_html__( 'Above', 'elementor' ),
					'icon' => 'eicon-v-align-top',
				],
				'inline-end' => [
					'title' => esc_html__( 'After', 'elementor' ),
					'icon' => 'eicon-h-align-' . $end,
				],
				'block-end' => [
					'title' => esc_html__( 'Below', 'elementor' ),
					'icon' => 'eicon-v-align-bottom',
				],
				'inline-start' => [
					'title' => esc_html__( 'Before', 'elementor' ),
					'icon' => 'eicon-h-align-' . $start,
				],
			],
			'selectors_dictionary' => [
				// The toggle variables for 'align items' and 'justify content' have been added to separate the styling of the two 'flex direction' modes.
				'block-start' => $styling_block_start,
				'inline-end' => $styling_inline_end,
				'block-end' => $styling_block_end,
				'inline-start' => $styling_inline_start,
				// Styling duplication for BC reasons.
				'top' => $styling_block_start,
				'bottom' => $styling_block_end,
				'start' => $styling_inline_start,
				'end' => $styling_inline_end,
			],
			'selectors' => [
				'{{WRAPPER}}' => '{{VALUE}}',
			],
		] );

		$this->add_responsive_control( 'icon_size', [
			'label' => esc_html__( 'Size', 'elementor' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [
				'px' => [
					'max' => 100,
				],
				'em' => [
					'max' => 10,
				],
				'rem' => [
					'max' => 10,
				],
			],
			'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-icon-size: {{SIZE}}{{UNIT}}',
			],
		] );

		$this->add_responsive_control( 'icon_spacing', [
			'label' => esc_html__( 'Spacing', 'elementor' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [
				'px' => [
					'max' => 400,
				],
				'vw' => [
					'max' => 50,
					'step' => 0.1,
				],
			],
			'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-icon-gap: {{SIZE}}{{UNIT}}',
			],
		] );

		$this->start_controls_tabs( 'icon_style_states' );

		$this->start_controls_tab(
			'icon_section_normal',
			[
				'label' => esc_html__( 'Normal', 'elementor' ),
			]
		);

		$this->add_control( 'icon_color', [
			'label' => esc_html__( 'Color', 'elementor' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-icon-color: {{VALUE}};',
			],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_section_hover',
			[
				'label' => esc_html__( 'Hover', 'elementor' ),
			]
		);

		$this->add_control( 'icon_color_hover', [
			'label' => esc_html__( 'Color', 'elementor' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} [data-touch-mode="false"] .e-n-tab-title[aria-selected="false"]:hover' => '--n-tabs-icon-color-hover: {{VALUE}};',
			],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_section_active',
			[
				'label' => esc_html__( 'Active', 'elementor' ),
			]
		);

		$this->add_control( 'icon_color_active', [
			'label' => esc_html__( 'Color', 'elementor' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}}' => '--n-tabs-icon-color-active: {{VALUE}};',
			],
		] );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		$this->start_controls_section( 'section_box_style', [
			'label' => esc_html__( 'Content', 'elementor' ),
			'tab' => Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'box_background_color',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => $content_selector,
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Background Color', 'elementor' ),
					],
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'box_border',
				'selector' => $content_selector,
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Border Color', 'elementor' ),
					],
					'width' => [
						'label' => esc_html__( 'Border Width', 'elementor' ),
					],
				],
			]
		);

		$this->add_responsive_control(
			'box_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					$content_selector => '--border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'box_shadow_box_shadow',
				'selector' => $content_selector,
				'condition' => [
					'box_height!' => 'height',
				],
			]
		);

		$this->add_responsive_control(
			'box_padding',
			[
				'label' => esc_html__( 'Padding', 'elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					$content_selector => "--padding-block-start: {{TOP}}{{UNIT}}; --padding-inline-end: $logical_dimensions_inline_end; --padding-block-end: {{BOTTOM}}{{UNIT}}; --padding-inline-start: $logical_dimensions_inline_start;",
				],
			]
		);
		

		$this->end_controls_section();
	}

	
	public function prepare_form( $form = array() ){
		global $fea_instance, $fea_form;
		$wg_id = $this->get_id();
		$settings = $this->get_settings_for_display();
		$current_id = $fea_instance->elementor->get_current_post_id();

		$form_id = $current_id . '_elementor_' .$wg_id;

		if( ! empty( $fea_form['id'] ) && $fea_form['id'] == $form_id ) return $fea_form;	
	
		$instructions = isset( $settings['field_instruction_position'] ) ? $settings['field_instruction_position'] : '';
		$form_args = array_merge( $form, array(
			'id'                    => $form_id,
			'ID'					=> $current_id,
			'form_attributes'       => [],
			'default_submit_button' => 1,
			'submit_value'          => $settings['submit_button_text'] ?? 'Submit',
			'instruction_placement' => $instructions,
			'html_submit_spinner'   => '',
			'label_placement'       => 'top',
			'field_el'              => 'div',
			'kses'                  => empty( $settings['allow_unfiltered_html'] ),
			'html_after_fields'     => '',
		) );
		$form_args = $fea_instance->elementor->get_settings_to_pass( $form_args, $settings );

		if ( isset( $fea_instance->remote_actions ) ) {
			foreach ( $fea_instance->remote_actions as $name => $action ) {
				if ( ! empty( $settings['more_actions'] ) && in_array( $name, $settings['more_actions'] ) && ! empty( $settings[ "{$name}s_to_send" ] ) ) {
					$form_args[ "{$name}s" ] = $settings[ "{$name}s_to_send" ];
				}
			}
		}

		if ( empty( $settings['hide_field_labels'] ) && isset( $settings['field_label_position'] ) ) {
			$form_args['label_placement'] = $settings['field_label_position'];
		}

		if ( isset( $settings['show_in_modal'] ) ) {
			$form_args['_in_modal'] = true;
		}

		$fea_form = $fea_instance->form_display->validate_form( $form_args );



		return $fea_form;

		
	}


	
}
