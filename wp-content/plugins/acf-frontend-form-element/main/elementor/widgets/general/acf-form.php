<?php

namespace Frontend_Admin\Elementor\Widgets;

use  Frontend_Admin\Plugin;
use  Frontend_Admin\FEA_Module;
use  Frontend_Admin\Classes;
use  Elementor\Controls_Manager;
use  Elementor\Controls_Stack;
use  Elementor\Widget_Base;
use  ElementorPro\Modules\QueryControl\Module as Query_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly
}

/**

 *
 * @since 1.0.0
 */
class ACF_Form extends Widget_Base {

	public $form_defaults;
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
		return 'acf_ele_form';
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
	 * Get widget defaults.
	 *
	 * Retrieve acf form widget defaults.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string Widget defaults.
	 */
	public function get_form_defaults() {
		return array(
			'custom_fields_save' => 'all',
			'form_title'         => '',
			'submit'             => __( 'Update', 'acf-frontend-form-element' ),
			'success_message'    => __( 'Your site has been updated successfully.', 'acf-frontend-form-element' ),
			'field_type'         => 'ACF_fields',
			'fields'             => array(
				array( 'ACF_fields' ),
			),
		);
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
		return __( 'ACF Form', 'acf-frontend-form-element' );
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
			'edit post',
			'add post',
			'add user',
			'edit user',
			'edit site',
			'acf',
			'acf form',
		);
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
		return array( 'frontend-admin-general' );
	}

	/**
	 * Register acf ele form widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function register_controls() {
		$this->register_form_structure_controls();
		
			do_action( 'frontend_admin/elementor/action_controls', $this, true );
			do_action( 'frontend_admin/elementor/actions_controls', $this, true );
			do_action( 'frontend_admin/multi_step_settings', $this, true );
			do_action( 'frontend_admin/elementor/permissions_controls', $this, true );

			do_action( 'frontend_admin/elementor_widget/content_controls', $this );

		$this->register_style_tab_controls();

		do_action( 'frontend_admin/styles_controls', $this );

	}

	protected function register_form_structure_controls() {
		$this->start_controls_section(
			'fields_section',
			array(
				'label' => __( 'Form', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$default = array( '' => __( 'Build in Elementor', 'acf-frontend-form-element' ) );
		
		$form_choices = feadmin_form_choices( $default );

		if ( $form_choices ) {
			$this->add_control(
				'admin_forms_select',
				array(
					'label'       => __( 'Choose Form...', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::SELECT,
					'label_block' => true,
					'options'     => $form_choices,
					'default'     => '',
				)
			);
			$this->add_control(
				'edit_form_button',
				array(
					'show_label' => false,
					'type'       => Controls_Manager::RAW_HTML,
					'raw'        => '<button class="edit-fea-form" type="button" data-link="' . admin_url( 'post.php' ) . '">
                        <span class="eicon-pencil">' . __( 'Edit Form', 'acf-frontend-form-element' ) . '</span>
                    </button>',
					'condition'  => array(
						'admin_forms_select!' => '',
					),
				)
			);
		}

		$this->add_control(
			'create_form_button',
			array(
				'show_label' => false,
				'type'       => Controls_Manager::RAW_HTML,
				'raw'        => '<button class="new-fea-form" type="button" data-link="' . admin_url( 'post-new.php?post_type=admin_form' ) . '">
                    <span class="eicon-plus"></span>' . __( 'Create New Form', 'acf-frontend-form-element' ) . '
                </button>',
			)
		);

			$this->custom_fields_control();
			do_action( 'frontend_admin/fields_controls', $this );
			$this->add_control(
				'submit_button_text',
				array(
					'label'       => __( 'Submit Button Text', 'acf-frontend-form-element' ),
					'type'        => Controls_Manager::TEXT,
					'label_block' => true,
					'default'     => $this->form_defaults['submit'],
					'placeholder' => $this->form_defaults['submit'],
					'condition'   => array(
						'admin_forms_select' => '',
					),
					'dynamic'     => array(
						'active' => true,
					),
				)
			);

			$this->add_control(
				'allow_unfiltered_html',
				array(
					'label'        => __( 'Allow Unfiltered HTML', 'acf-frontend-form-element' ),
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'true',
					'condition'    => array(
						'admin_forms_select' => '',
					),
				)
			);

		$this->end_controls_section();

	}

	

	public function custom_fields_control() {
		$cf_save = 'post';
		if ( $this->get_name() != 'acf_ele_form' ) {

			$cf_save = str_replace( array( 'new_', 'edit_', 'duplicate_' ), '', $this->get_name() );
		}
		$continue_action   = array();
		$controls_settings = array(
			'label'     => __( 'Save Custom Fields to...', 'acf-frontend-form-element' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => $cf_save,
			'condition' => array(
				'admin_forms_select' => '',
			),

		);

		$custom_fields_options = array(
			'submission' => __( 'Submission', 'acf-frontend-form-element' ),
			'post' => __( 'Post', 'acf-frontend-form-element' ),
			'user' => __( 'User', 'acf-frontend-form-element' ),
			'term' => __( 'Term', 'acf-frontend-form-element' ),
		);
		if ( ! empty( fea_instance()->pro_features ) ) {
			$custom_fields_options['options'] = __( 'Site Options', 'acf-frontend-form-element' );
			if ( class_exists( 'woocommerce' ) ) {
				$custom_fields_options['product'] = __( 'Product', 'acf-frontend-form-element' );
			}
		}
		$controls_settings['options'] = $custom_fields_options;
		$this->add_control( 'custom_fields_save', $controls_settings );
	} 

	


	public function register_style_tab_controls() {
		$this->start_controls_section(
			'display_section',
			array(
				'label' => __( 'Display Options', 'acf-frontend-form-element' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'fields_spacing',
			array(
				'label'     => __( 'Spacing Between Fields', 'elementor-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 15,
				),
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .acf-fields' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
			)
		);

		//margin around fields
		$this->add_control(
			'fields_margin',
			array(
				'label'      => __( 'Fields Margin', 'acf-frontend-form-element' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'    => '0',
					'right'  => '0',
					'bottom' => '0',
					'left'   => '0',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .acf-fields' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		//padding around fields
		$this->add_control(
			'fields_padding',
			array(
				'label'      => __( 'Fields Padding', 'acf-frontend-form-element' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .acf-fields' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'hide_field_labels',
			array(
				'label'        => __( 'Hide Field Labels', 'acf-frontend-form-element' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Hide', 'acf-frontend-form-element' ),
				'label_off'    => __( 'Show', 'acf-frontend-form-element' ),
				'return_value' => 'true',
				'separator'    => 'before',
				'selectors'    => array(
					'{{WRAPPER}} .acf-label' => 'display: none',
				),
			)
		);
		$this->add_control(
			'field_label_position',
			array(
				'label'     => __( 'Label Position', 'elementor-pro' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'top'  => __( 'Above', 'elementor-pro' ),
					'left' => __( 'Inline', 'elementor-pro' ),
				),
				'default'   => 'top',
				'condition' => array(
					'hide_field_labels!' => 'true',
				),
			)
		);
		$this->add_control(
			'hide_mark_required',
			array(
				'label'        => __( 'Hide Required Mark', 'elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Hide', 'elementor-pro' ),
				'label_off'    => __( 'Show', 'elementor-pro' ),
				'return_value' => 'true',
				'condition'    => array(
					'hide_field_labels!' => 'true',
				),
				'selectors'    => array(
					'{{WRAPPER}} .acf-required' => 'display: none',
				),
			)
		);

		$this->add_control(
			'field_instruction_position',
			array(
				'label'     => __( 'Instruction Position', 'elementor-pro' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'label' => __( 'Above Field', 'elementor-pro' ),
					'field' => __( 'Below Field', 'elementor-pro' ),
				),
				'default'   => 'label',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'field_seperator',
			array(
				'label'        => __( 'Field Seperator', 'elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Hide', 'elementor-pro' ),
				'label_off'    => __( 'Show', 'elementor-pro' ),
				'default'      => 'true',
				'return_value' => 'true',
				'separator'    => 'before',
				'selectors'    => array(
					'{{WRAPPER}} .acf-fields>.acf-field' => 'border-top: none',
					'{{WRAPPER}} .acf-field[data-width]+.acf-field[data-width]' => 'border-left: none',
				),
			)
		);

		$this->end_controls_section();
		

		if ( empty( fea_instance()->pro_features ) ) {

			$this->start_controls_section(
				'style_promo_section',
				array(
					'label' => __( 'Styles', 'acf-frontend-form-element' ),
					'tab'   => Controls_Manager::TAB_STYLE,
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
			do_action( 'frontend_admin/style_tab_settings', $this );
		}
	}

	public function get_field_type_options() {
		$groups = feadmin_get_field_type_groups();
		$fields = array(
			'acf'    => $groups['acf'],
			'layout' => $groups['layout'],
		);

		
		$fields = array_merge(
			$fields,
			array(
				'post' => $groups['post'],
				'user' => $groups['user'],
				'term' => $groups['term'],
			)
		);
		if ( ! empty( fea_instance()->pro_features ) ) {
			$fields['options'] = $groups['options'];
			// $fields['comment'] = $groups['comment'];
			if ( class_exists( 'woocommerce' ) ) {
				$fields['product_type']                  = $groups['product_type'];
				$fields['product']                       = $groups['product'];
				$fields['product_inventory']             = $groups['product_inventory'];
				$fields['product_downloadable'] = $groups['product_downloadable'];
				$fields['product_shipping']     = $groups['product_shipping'];
				$fields['product_external']              = $groups['product_external'];
				$fields['product_linked']              = $groups['product_linked'];
				$fields['product_attributes']              = $groups['product_attributes'];
				$fields['product_advanced']              = $groups['product_advanced'];
				
			}
		}
		if( isset( $groups['security'] ) ){
			$fields['security'] = $groups['security'];
		}
		return $fields;
	}


	public function prepare_form(){
		global $fea_instance, $fea_form;
		$current_id = $fea_instance->elementor->get_current_post_id();
		$wg_id = $this->get_id();
		$settings = $this->get_settings_for_display();

		if ( ! empty( $settings['admin_forms_select'] ) ) {
			$form_id = $settings['admin_forms_select'];

			if( ! empty( $fea_form['id'] ) && $fea_form['id'] == $form_id ){
				$form_args = $fea_form;
			}else{
				$form_args = $fea_instance->form_display->get_form( $form_id );
			}
		} else {
			$form_id = $current_id . '_elementor_' . $wg_id;

			if ( ! empty( $fea_form['id'] ) && $fea_form['id'] == $form_id ) {
				$form_args = $fea_form;
			}else{

				$fields = false;

				$instructions = isset( $settings['field_instruction_position'] ) ? $settings['field_instruction_position'] : '';
				$form_args = array(
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
				);
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
				
				$form_args = $this->get_form_structure( $form_args, $wg_id );
				$form_args = $fea_instance->form_display->validate_form( $form_args );
			}

		}

		if ( isset( $settings['show_in_modal'] ) ) {
			$form_args['_in_modal'] = true;
		}

		return $form_args;

		
	}

	public function parse_tags( $settings ) {
		$dynamic_tags = $settings['__dynamic__'];
		foreach ( $dynamic_tags as $control_name => $tag ) {
			$settings[ $control_name ] = $tag;
		}
		return $settings;
	}

	public function get_form_structure( $form, $wg_id ) {	
		$wg_id = $this->get_id();

		$form['fields'] = array();

			if ( empty( $form['fields_selection'] ) ) {
				return $form;
			}
			$fields_selection = $form['fields_selection'];

			foreach ( $fields_selection as $ind => $form_field ) {
				$form_field = wp_parse_args( $form_field, [
					'field_label_on' => 1,
					'field_label' => '',
					'field_type' => 'acf_fields',
					'field_instruction'  => '',
					'field_required'      => 0,
					'field_placeholder'   => '',
					'field_hidden'		  => 0,
					'field_default_value' => '',
					'field_disabled'      => 0,
					'field_readonly'      => 0,
					'minimum'           => 0,
					'maximum'           => 0,
					'prepend'       => '',
					'append'        => '',
					'fields_select' => [],
					'fields_select_exclude' => [],	
				] );

				$local_field = false;
				$acf_field_groups = false;
				$acf_fields = false;

				if( 'ACF_fields' == $form_field['field_type'] ){
					if( $form_field['fields_select'] ){
						foreach( $form_field['fields_select'] as $selected ){
							$selected_field = fea_instance()->frontend->get_field( $selected );

							if( $selected_field ){
								$selected_field['class'] .= 'elementor-repeater-item-' . $form_field['_id'];
								$form['fields'][$selected] = $selected_field;
							}
						}
					}
				}elseif( 'ACF_field_groups' == $form_field['field_type'] ){
					if( $form_field['field_groups_select'] ){
						if( empty( $form_field['fields_select_exclude'] ) ){
							$form_field['fields_select_exclude'] = [];
						}
						foreach( $form_field['field_groups_select'] as $group ){
							$selected_fields = acf_get_fields( $group );
							if( $selected_fields ){
								foreach( $selected_fields as $selected ){
									if( in_array( $selected['key'], $form_field['fields_select_exclude'], true ) ) continue;
	
									$selected['class'] .= 'elementor-repeater-item-' . $form_field['_id'];
									$form['fields'][$selected['key']] = $selected;
								}
							}
						}
					}
				}else{
					switch ( $form_field['field_type'] ) {							
						case 'tab':
							if ( $form_field['endpoint'] == 'true' ) {
								$fields[] = array(
									'tab' => 'endpoint',
								);
							} else {
								$tab      = array(
									'tab' => $form_field['_id'],
								);
								$fields[] = $tab;
							}
							break;
						case 'recaptcha':
							$local_field = array(
								'key'          => $wg_id . '_' . $form_field['field_type'] . '_' . $form_field['_id'],
								'type'         => 'recaptcha',
								'wrapper'      => array(
									'class' => '',
									'id'    => '',
									'width' => '',
								),
								'required'     => 0,
								'version'      => $form_field['recaptcha_version'],
								'v2_theme'     => $form_field['recaptcha_theme'],
								'v2_size'      => $form_field['recaptcha_size'],
								'site_key'     => $form_field['recaptcha_site_key'],
								'secret_key'   => $form_field['recaptcha_secret_key'],
								'disabled'     => 0,
								'readonly'     => 0,
								'v3_hide_logo' => $form_field['recaptcha_hide_logo'],
							);
							break;
						case 'step':
							$local_field         = acf_get_valid_field( $form_field );
							$local_field['type'] = 'form_step';
							$local_field['key']  = $local_field['name'] = $wg_id . '_' . $form_field['field_type'] . '_' . $form_field['_id'];
							break;
						default:
							if ( isset( $form_field['__dynamic__'] ) ) {
								$form_field = $this->parse_tags( $form_field );
							}
							$default_value = $form_field['field_default_value'];
							$local_field   = array(
								'label'         => '',
								'wrapper'       => array(
									'class' => '',
									'id'    => '',
									'width' => '',
								),
								'instructions'  => $form_field['field_instruction'],
								'required'      => ( $form_field['field_required'] ? 1 : 0 ),
								'placeholder'   => $form_field['field_placeholder'],
								'default_value' => $default_value,
								'disabled'      => $form_field['field_disabled'],
								'readonly'      => $form_field['field_readonly'],
								'min'           => $form_field['minimum'],
								'max'           => $form_field['maximum'],
								'prepend'       => $form_field['prepend'],
								'append'        => $form_field['append'],
							);
	
							if ( isset( $data_default ) ) {
								$local_field['wrapper']['data-default']       = $data_default;
								$local_field['wrapper']['data-dynamic_value'] = $default_value;
							}
	
							if ( $form_field['field_hidden'] ) {
								$local_field['frontend_admin_display_mode'] = 'hidden';
							}
	
							if ( $form_field['field_type'] == 'message' ) {
								$local_field['type']    = 'message';
								$local_field['message'] = $form_field['field_message'];
							}
	
							break;
					}
	
					if ( isset( $local_field ) ) {
						foreach ( feadmin_get_field_type_groups() as $name => $group ) {
							if ( in_array( $form_field['field_type'], array_keys( $group['options'] ) ) ) {
								$action_name = explode( '_', $name )[0];
								if ( isset( fea_instance()->local_actions[ $action_name ] ) ) {
									$action   = fea_instance()->local_actions[ $action_name ];
									$sub_fields = null;
									if( 'attributes' == $form_field['field_type'] ){
										$sub_fields = $form['attribute_fields'];
									}
									if( 'variables' == $form_field['field_type'] ){
										$sub_fields = $form['variable_fields'];
									}
									$local_field = $action->get_fields_display(
										$form_field,
										$local_field,
										$wg_id,
										$sub_fields
									);
	
									if ( isset( $form_field['field_label_on'] ) ) {
										$field_label          = ucwords( str_replace( '_', ' ', $form_field['field_type'] ) );
										$local_field['label'] = ( $form_field['field_label'] ? $form_field['field_label'] : $field_label );
									}
	
									if ( isset( $local_field['type'] ) ) {
										if ( $local_field['type'] == 'number' ) {
											$local_field['placeholder']   = $form_field['number_placeholder'];
											$local_field['default_value'] = $form_field['number_default_value'];
										}
	
									
									}
	
									if ( ! empty( $form_field['default_terms'] ) ) {
										$local_field['default_terms'] = $form_field['default_terms'];
									}
								}
								break;
							}
						}
					}
					if ( isset( $local_field['label'] ) ) {
	
						if ( empty( $form_field['field_label_on'] ) ) {
							$local_field['field_label_hide'] = 1;
						} else {
							$local_field['field_label_hide'] = 0;
						}
					}
	
					if ( isset( $form_field['button_text'] ) && $form_field['button_text'] ) {
						$local_field['button_text'] = $form_field['button_text'];
					}
	
					$current_id = fea_instance()->elementor->get_current_post_id();

					$local_field['key'] = $current_id . '_elementor_' . $wg_id . '_' . $form_field['_id'];
					$local_field['name'] = $local_field['key'];
					$local_field['wrapper']['class'] = ' elementor-repeater-item-' . $form_field['_id'];
	
					$form['fields'][ $local_field['key'] ] = $local_field;
						
				}
			}
		unset( $form['fields_selection'] );

		return $form;
	}


	/**
	 * Render acf ele form widget output on the frontend.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function render() {
		global $fea_form, $fea_instance, $fea_limit_visibility;

		$wg_id = $this->get_id();
		$settings = $this->get_settings_for_display();

		$form_args = $this->prepare_form();
		$fea_form = $form_args;

		if( ! $form_args ){
			$fea_form = null;
			return;
		} 

		$form_args = apply_filters( 'frontend_admin/show_form', $form_args );

		if( ! $form_args ){
			$fea_form = null;
			return;
		} 

		if( $settings['admin_forms_select'] ) {
			$fea_instance->form_display->render_form( $form_args );
		}else{
			$form_args['page_builder'] = 'elementor';
			do_action( 'frontend_admin/elementor/before_render', $form_args );

			$fea_instance->form_display->render_form( $form_args );

			do_action( 'frontend_admin/elementor/after_render', $form_args );
		}

		$fea_form = null;
	}

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			fea_instance()->frontend->enqueue_scripts( 'frontend_admin_form' );
		}
		$this->form_defaults = $this->get_form_defaults();

		fea_instance()->elementor->form_widgets[] = $this->get_name();

	}

}
