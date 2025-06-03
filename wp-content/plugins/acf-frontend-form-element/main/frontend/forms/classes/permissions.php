<?php
namespace Frontend_Admin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class PermissionsTab {


	public function conditions_logic( $settings, $type = 'form' ) {
		if ( ! empty( $settings['display'] ) ) {
			return $settings;
		}

		if( 'field' == $type ){
			$type = 'form';
			$parent = $settings['parent'] ?? 0;
			if( $parent ){
				$settings = fea_instance()->form_display->get_form( $parent );
			}else{
				global $fea_form;
				if( $fea_form ){
					$settings = $fea_form;
				}
			}

			if( ! $settings ){
				return $settings;
			}
		}

		global $post, $fea_limit_visibility;

		if ( ! empty( $settings['form_conditions'] ) ) {
			$conditions = $settings['form_conditions'];
		} else {
			$conditions = array();	
			
			$values     = array(
				'who_can_see'         => $fea_limit_visibility['who_can_see'] ?? 'logged_in',
				'not_allowed'         => 'show_nothing',
				'not_allowed_message' => '',
				'not_allowed_content' => '',
				'email_verification'  => 'all',
				'by_role'             => $fea_limit_visibility['by_role'] ?? array( 'administrator' ),
				'by_user_id'          => '',
				'wp_uploader'		  => '',
				'special_permissions' => []
			);

			foreach ( $values as $key => $value ) {
				if ( isset( $settings[ $key ] ) && ! $fea_limit_visibility ) {
					$conditions[0][ $key ] = $settings[ $key ];
				} else {
					$conditions[0][ $key ] = $value;
				}
			}
		}

		if ( empty( $conditions ) ) {
			return $settings;
		}


		$active_user = wp_get_current_user();
		foreach ( $conditions as $condition ) {
			$condition = wp_parse_args( $condition, [
				'special_permissions' => [],
				'who_can_see'         => 'logged_in',
				'not_allowed'         => 'show_nothing',
				'not_allowed_message' => '',
				'not_allowed_content' => '',
				'email_verification'  => 'all',
				'by_role'             => array( 'administrator' ),
				'by_user_id'          => '',
				'applies_to'          => [ 'form' ],	
				'wp_uploader'		  => '',	
			] );

			if( ! $condition['wp_uploader'] ){
				$uploader = 'basic';
			}else{
				if ( ! feadmin_edit_mode() ) {
					acf_enqueue_uploader();
				}
				$uploader = 'wp';
			}
			acf_update_setting( 'uploader', $uploader );

			if ( empty( $condition['applies_to'] ) || ! in_array( $type, $condition['applies_to'] ) ) {
				continue;
			}

			if ( 'all' == $condition['who_can_see'] ) {
				$settings['display'] = true;

				return $settings;
			}
			if ( 'logged_out' == $condition['who_can_see'] ) {
				$settings['display'] = ! (bool) $active_user->ID;
			}
			if ( 'logged_in' == $condition['who_can_see'] ) {
				if ( empty( $active_user->ID ) ) {
					$settings['display'] = false;
				} else {
					$by_role = true; 
					$by_cap = $specific_user = $dynamic = false;
					$user_roles = $condition['by_role'];

					if ( $user_roles ) {
						if ( is_array( $condition['by_role'] ) ) {
							if ( count( array_intersect( $condition['by_role'], (array) $active_user->roles ) ) != false || in_array( 'all', $condition['by_role'] ) ) {
								$by_role = true;
							}else{
								$by_role = false;
							}
						}
					}

					if ( ! empty( $condition['by_cap'] ) ) {
						foreach ( $condition['by_cap'] as $cap ) {
							if ( current_user_can( $cap ) ) {
								$by_cap = true;
							}
						}
					}

					if ( ! empty( $condition['by_user_id'] ) ) {
						$user_ids = $condition['by_user_id'];
						if ( ! is_array( $user_ids ) ) {
							$user_ids = explode( ',', $user_ids );
						}
						if ( is_array( $user_ids ) ) {
							if ( in_array( $active_user->ID, $user_ids ) ) {
								$specific_user = true;
							}
						}
					}

					if ( $by_role || $by_cap || $specific_user ) {
						if ( isset( $condition['email_verification'] ) && $condition['email_verification'] != 'all' ) {
							$required       = $condition['email_verification'] == 'verified' ? 1 : 0;
							$email_verified = get_user_meta( $active_user, 'frontend_admin_email_verified', true );

							if ( ( $email_verified == $required ) ) {
								$settings['display'] = true;
							} else {
								$settings['display'] = false;
							}
						} else {
							$settings['display'] = true;
						}
						if ( ! empty( $condition['allowed_submits'] ) ) {
							$submits = (int) $condition['allowed_submits'];
							$submitted = get_user_meta( $active_user->ID, 'submitted::' . $settings['id'], true );

							if ( $submits - (int) $submitted <= 0 ) {
								$settings['display'] = false;
								if ( $condition['limit_reached'] == 'show_message' ) {
									echo '<div class="acf-notice -limit frontend-admin-limit-message"><p>' . $condition['limit_reached_message'] . '</p></div>';
								} elseif ( $condition['limit_reached'] == 'custom_content' ) {
									echo wp_kses_post( $condition['limit_reached_content'] );
								} 
							}
						}

						if( empty( $condition['special_permissions'] ) || ! is_array( $condition['special_permissions'] ) ){
							$condition['special_permissions'] = [];
						}	
						$settings = apply_filters( 'frontend_admin/special_permissions', $settings, $condition, $active_user );
						
						return $settings;
					}

					$settings['display'] = false;

				}
			}
			if ( $condition['not_allowed'] == 'show_message' ) {
				echo '<div class="acf-notice -limit frontend-admin-limit-message"><p>' . esc_html( $condition['not_allowed_message'] ) . '</p></div>';
			} elseif ( $condition['not_allowed'] == 'custom_content' ) {
				echo wp_kses_post( $condition['not_allowed_content'] );
			}

			if ( $settings['display'] ) {
				break;
			}
		}
		if ( empty( $settings['display'] ) ) {
			$settings = false;
		}

		return $settings;
	}

	public function __construct() {
		add_filter( 'frontend_admin/show_form', array( $this, 'conditions_logic' ), 10, 2 );
	}

}

new PermissionsTab();
