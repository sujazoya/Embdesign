<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$redirect_options = array(
	'current'    => __( 'Reload Current Page', 'acf-frontend-form-element' ),
	'custom_url' => __( 'Custom URL', 'acf-frontend-form-element' ),
	'referer'    => __( 'Referer', 'acf-frontend-form-element' ),
	'post_url'   => __( 'Post URL', 'acf-frontend-form-element' ),
	'none'       => __( 'None', 'acf-frontend-form-element' ),
);

$redirect_options = apply_filters( 'frontend_admin/forms/redirect_options', $redirect_options );

$fields = array(
	array(
		'key'           => 'redirect',
		'label'         => __( 'Redirect After Submit', 'acf-frontend-form-element' ),
		'type'          => 'select',
		'instructions'  => '',
		'required'      => 0,
		'wrapper'       => array(
			'width' => '',
			'class' => '',
			'id'    => '',
		),
		'choices'       => $redirect_options,
		'allow_null'    => 0,
		'multiple'      => 0,
		'ui'            => 0,
		'return_format' => 'value',
		'ajax'          => 0,
		'placeholder'   => '',
	),
	array( 
		'key'               => 'redirect_action',
		'label'             => __( 'After Reload', 'acf-frontend-form-element' ),
		'type'              => 'select',
		'instructions'      => '',
		'required'          => 0,
		'choices'           => array(
			'none' => __( 'None', 'acf-frontend-form-element' ),
			'clear' => __( 'Clear Form', 'acf-frontend-form-element' ),
			'edit' => __( 'Edit Content', 'acf-frontend-form-element' ),
		),
	),
	array(
		'key'               => 'custom_url',
		'label'             => __( 'Custom Url', 'acf-frontend-form-element' ),
		'type'              => 'url',
		'instructions'      => '',
		'required'          => 0,
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
	),
	array(
		'key'               => 'show_update_message',
		'label'             => __( 'Success Message', 'acf-frontend-form-element' ),
		'type'              => 'true_false',
		'instructions'      => '',
		'required'          => 0,
		'conditional_logic' => 0,
		'message'           => '',
		'ui'                => 1,
		'ui_on_text'        => '',
		'ui_off_text'       => '',
	),
	array(
		'key'               => 'update_message',
		'label'             => '',
		'field_label_hide'  => true,
		'type'              => 'textarea',
		'instructions'      => '',
		'required'          => 0,
		'conditional_logic' => array(
			array(
				array(
					'field'    => 'show_update_message',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),
		'placeholder'       => '',
		'maxlength'         => '',
		'rows'              => '2',
		'new_lines'         => '',
	),
	array(
		'key'              => 'error_message',
		'label'            => __( 'Error Message', 'acf-frontend-form-element' ),
		'type'             => 'textarea',
		'instructions'     => __( 'Add a custom validation error message', 'acf-frontend-form-element' ),
		'required'         => 0,
		'placeholder'      => __( 'There has been an error. Please fix the fields that need attention', 'acf-frontend-form-element' ),
		'maxlength'        => '',
		'rows'             => '2',
		'new_lines'        => '',
	),
	array(
		'key'			   => 'default_required_message',
		'label'			   => __( 'Default Required Message', 'acf-frontend-form-element' ),
		'type'			   => 'text',
		'instructions'	   => __( 'This message will be used for all required fields if a custom message is not set', 'acf-frontend-form-element' ),
		'required'		   => 0,
		'placeholder'	   => __( 'This field is required', 'acf-frontend-form-element' ),
	),
	//email verified message if email verification is enabled
	array(
		'key'              => 'email_verified_message',
		'label'            => __( 'Email Verified Message', 'acf-frontend-form-element' ),
		'type'             => 'textarea',
		'instructions'     => __( 'Add a custom message for email verification', 'acf-frontend-form-element' ),
		'required'         => 0,
		'placeholder'      => __( 'Your email has been verified', 'acf-frontend-form-element' ),
		'default_value'    => __( 'Your email has been verified', 'acf-frontend-form-element' ),
		'conditional_logic' => array(
			array(
				array(
					'field'    => 'save_all_data',
					'operator' => '==contains',
					'value'    => 'verify_email',
				),
			),
		),
		'maxlength'        => '',
		'rows'             => '2',
		'new_lines'        => '',
	),
);

$fields = apply_filters( 'frontend_admin/forms/settings/submit_actions', $fields );

return $fields;
