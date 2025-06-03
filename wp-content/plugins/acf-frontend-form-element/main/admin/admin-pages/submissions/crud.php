<?php
namespace Frontend_Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'Submissions_Crud' ) ) :

	class Submissions_Crud {

		public function create_submissions() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name      = $wpdb->prefix . 'fea_submissions';
			$sql             = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				title text NOT NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				user int NOT NULL,
				fields longtext NOT NULL,
				form text NOT NULL,
				status text NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			maybe_create_table( $table_name, $sql );
		}

		public function insert_submission( $args ) {
			global $wpdb;

			if( empty( $args['created_at'] ) ){
				$args['created_at'] = current_time( 'mysql' );
			}
	
			if ( empty( $args['title'] ) ) {
				$args['title'] = '(no name)';
			}
			$wpdb->insert( $wpdb->prefix . 'fea_submissions', $args );

			$submits_count = get_option( 'frontend_admin_submissions_all_time', 0 );
			$submits_count++;
			update_option( 'frontend_admin_submissions_all_time', $submits_count );

			return $wpdb->insert_id;
		}

		public function update_submission( $id, $args ) {
			if ( isset( $args['fields'] ) && is_array( $args['fields'] ) ) {
				$args['fields'] = fea_encrypt( json_encode( $args['fields'] ) );
			}

			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'fea_submissions',
				$args,
				array( 'id' => $id )
			);
		}

		public function approve_submission( $id ) {
			 global $wpdb;
			$submission = $this->get_submission( $id );

			if ( $submission->status == 'pending' ) {
				$form = $this->get_form( $submission );
				foreach ( fea_instance()->local_actions as $action ) {
					$name = $action->get_name();
					$form = $action->run( $form );
				}
				$wpdb->update(
					$wpdb->prefix . 'fea_submissions',
					array( 'status' => 'approved' ),
					array( 'id' => $id )
				);
			}
		}

		public function get_submission( $id = 0 ) {
			if ( ! $id ) {
				return $id;
			}

			global $wpdb;
			$id         = (int) $id;
			$submission = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fea_submissions WHERE id = %d", $id ) );

			$submission = maybe_unserialize( $submission );
			$submission = wp_unslash( $submission );


			return $submission;
		}

		public function get_status_label( $slug ) {
			switch ( $slug ) {
				case 'in_progress':
					return __( 'In Progress', 'acf-frontend-form-element' );
				case 'require_approval':
					return __( 'Pending Approval', 'acf-frontend-form-element' );
				case 'verify_email':
					return __( 'Pending Email Verification', 'acf-frontend-form-element' );
				case 'email_verified':
					return __( 'Email Verified', 'acf-frontend-form-element' );
				case 'pending_payment':
					return __( 'Pending Payment', 'acf-frontend-form-element' );
				case 'payment_received':
					return __( 'Payment Received', 'acf-frontend-form-element' );
				case 'approved':
					return __( 'Approved', 'acf-frontend-form-element' );
				default:
					return $slug;
			}
		}

		/**
		 * Retrieve submissions data from the database
		 *
		 * @param array $args query arguments
		 *
		 * @return mixed
		 */
		
		/**
		 * Retrieve submissions data from the database
		 *
		 * @param array $args query arguments
		 *
		 * @return mixed
		 */
		public static function get_submissions( $args = array() ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				return false;
			}

			// Parse and sanitize request parameters
			$args = wp_parse_args( $args, [
				'orderby'      => isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : '',
				'order'        => isset( $_REQUEST['order'] ) ? strtoupper( sanitize_text_field( $_REQUEST['order'] ) ) : '',
				'per_page'     => isset( $_REQUEST['per_page'] ) ? (int) $_REQUEST['per_page'] : 10,
				'paged' => isset( $_REQUEST['paged'] ) ? max( 1, (int) $_REQUEST['paged'] ) : 1,
			] );

			// Allowlisted columns and order directions
			$allowed_orderby = [ 'created_at', 'title', 'status' ]; // Modify this list to match your DB columns
			$allowed_order   = [ 'ASC', 'DESC' ];

			$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
			$order   = in_array( $args['order'], $allowed_order, true ) ? $args['order'] : 'DESC';

			$per_page	 = (int) $args['per_page'];
			$current_page = (int) $args['paged'];
			$offset		 = ( $current_page - 1 ) * $per_page;

			// Base query - update table name accordingly
			global $wpdb;
			$table = $wpdb->prefix . 'fea_submissions';
			$sql   = "SELECT * FROM `$table`";

			// Append search query if any
			if ( ! empty( $_REQUEST['s'] ) ) {
				$search = sanitize_text_field( $_REQUEST['s'] );
				$sql    .= $wpdb->prepare( ' WHERE title LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
			}

			// Append ORDER BY
			$sql .= " ORDER BY `$orderby` $order";

			// Pagination
			if ( $per_page !== -1 ) {
				$sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
			}

			// Append WHERE clause if any filters are applied
			if ( ! empty( $args['form'] ) ) {
				$sql .= $wpdb->prepare( ' WHERE form = %s', $args['form'] );
			}

			global $current_screen;

			// Execute query
			$results = $wpdb->get_results( $sql, ARRAY_A );

			// Send response
			return $results;
		}


		/**
		 * Returns the count of records in the database.
		 *
		 * @return null|string
		 */
		public static function record_count( $args = array() ) {
			global $wpdb;

			$sql = "SELECT COUNT(form) FROM {$wpdb->prefix}fea_submissions";

			if ( ! empty( $args['form'] ) ) {
				$form = true;
				$sql .= $wpdb->prepare( ' WHERE form = %s', $args['form'] );
			}
			if ( ! empty( $args['form_key'] ) ) {
				if ( isset( $form ) ) {
					$sql .= ' OR';
				} else {
					$sql .= ' WHERE';
				}
				$sql .= $wpdb->prepare( ' form = %s', $args['form_key'] );

				$form = true;
			}
			if ( ! empty( $args['form_id'] ) ) {
				if ( isset( $form ) ) {
					$sql .= ' OR';
				} else {
					$sql .= ' WHERE';
				}
				$sql .= $wpdb->prepare( ' form = %d', $args['form_id'] );
			}

			return $wpdb->get_var( $sql );
		}

		public function delete_submission( $id = 0 ) {
			if ( $id == 0 ) {
				return $id;
			}
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'fea_submissions', array( 'id' => $id ) );
			return 1;
		}

		public function display_submissions() {
			 include_once __DIR__ . '/list.php';
			$display_list = true;

			if ( isset( $_REQUEST['id'] ) ) {
				$submission_id = absint( $_REQUEST['id'] );
				$submission    = $this->get_submission( $submission_id );

				if ( $submission ) {
					$user = get_user_by( 'ID', $submission->user );
					if ( is_object( $user ) ) {
						$title = $user->display_name . ' (' . $user->user_login . ')';
					} else {
						$title = '--';
					}
					?>
					<h2><?php echo sprintf( 'Submission #%d: %s', esc_html( $submission_id ), esc_html( $title ) ); ?></h2>
					<?php

					$action = fea_instance()->submissions_list->current_action();
					if ( $action == 'edit' ) {
						$form = $this->get_form( $submission, array(), true, true );

						if( ! $form ){
							echo '<div class="notice notice-error"><p>'. esc_html__( 'Form not found.', 'acf-frontend-form-element' ) .'</p></div>';
							return;
						}
						$form['wp_uploader'] = 1;

						if( empty( $form['object'] ) ){
					
			

							fea_instance()->form_display->render_form( $form );


						}else{
						
							$form['object']->print_element();
						}
						if ( ! empty( $form['record']['emails_to_verify'] ) ) {
							 echo '<div class="emails-to-verify">';
							 echo '<h3>'. esc_html( __( 'Pending Verification:', 'acf-frontend-form-element' ) ) . '</h3><ul>';
							foreach ( $form['record']['emails_to_verify'] as $address ) {
								echo '<li>' . esc_html( $address ) . '</li>';
							}
							echo '</ul></div>';
						}
						if ( ! empty( $form['record']['verified_emails'] ) ) {
							echo '<div class="verified-emails">';
							echo '<h3>'. esc_html( __( 'Verified Emails:', 'acf-frontend-form-element' ) ) . '</h3><ul>';
							foreach ( $form['record']['verified_emails'] as $address ) {
								echo '<li>' . esc_html( $address ) . '</li>';
							}
							echo '</ul></div>';
						}
						$display_list = false;
					} elseif ( $action == 'delete' ) {
						if ( empty( $_REQUEST['nonce'] ) ) {
							die( 'Authentication Error. Please try refreshing the page.' );
						}
						$nonce = esc_attr( wp_kses( $_REQUEST['nonce'], 'strip' ) );

						if ( ! wp_verify_nonce( $nonce, 'frontend_admin_delete_submission' ) ) {
							die( 'Go get a life script kiddies' );
						}

						$this->delete_submission( $submission_id );
					}
				} else {
					$display_list = true;
				}
			}
			if ( $display_list ) {
				fea_instance()->submissions_list->prepare_items();
				?>
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Submissions', 'acf-frontend-form-element' ); ?></h1>
				<form method="post">
				<?php
				fea_instance()->submissions_list->search_box( 'search', 'search_id' );
				fea_instance()->submissions_list->display();
				?>
				 </form> 
				 <?php
			}
		}

		public function get_form( $submission, $form = array(), $approval = false, $element = false ) {
			if ( is_numeric( $submission ) ) {
				$submission = $this->get_submission( $submission );
				if ( ! $submission ) {
					esc_html_e( 'Submission not found. Did you erase it?', 'acf-frontend-form-element' );
					return false;
				}
			}

			if ( empty( $form ) ) {
				
				$form = fea_instance()->form_display->get_form( $submission->form, $element );

				if( ! $form ){ 
					$form_id = explode( ':', $submission->form );
					$form_id = $form_id[0];
					$form = fea_instance()->form_display->get_form( $form_id, $element );
					if( ! $form ){
						esc_html_e( 'Form not found. Did you erase it?', 'acf-frontend-form-element' );
						return false;
					}
				
				}
				$current_user = wp_get_current_user();
				if ( $current_user && $current_user->ID == $submission->user ) {
					$form['display'] = true;
				}

				$fields = json_decode( fea_decrypt( $submission->fields ), true );
				$fields = wp_unslash( $fields );

				if ( ! isset( $fields['record'] ) ) {
					$form['record'] = $fields;
				} else {
					$form['record'] = $fields['record'];
				}
			}

			$fields = array();
			if ( ! empty( $form['record']['fields'] ) ) {
				foreach ( $form['record']['fields'] as $type ) {
					if ( is_array( $type ) ) {
						$fields = array_merge( $fields, $type );
					}
				}
			}


			$approval_form                     = $form;
			$approval_form['submission']       = $submission->id;
			$approval_form['submission_title'] = $submission->title;

			if ( $approval ) {
				if ( $submission->status == 'approved' ) {
					$submit_value    = __( 'Update', 'acf-frontend-form-element' );
					$success_message = __( 'Data has been updated.', 'acf-frontend-form-element' );
					$update          = true;
				} else {
					$submit_value    = __( 'Approve', 'acf-frontend-form-element' );
					$success_message = __( 'Submission has been approved. Data has been saved.', 'acf-frontend-form-element' );
				}

				$approval_form = array_merge(
					$approval_form,
					array(
						'submit_value'        => $submit_value,
						'kses'                => 0,
						'no_cookies'          => 1,
						'approval'            => 1,
						'submitted_by'        => $submission->user,
						'submitted_on'        => $submission->created_at,
						'return'              => admin_url( 'admin.php?page=' .  'fea-settings-submissions&action=edit&id=' . $submission->id ),
						'update_message'      => $success_message,
						'show_update_message' => 1,
						'hidden_fields' => [
							'submission' => $submission->id,
							'approval_nonce' => wp_create_nonce( 'frontend_admin_approve_submission' ),
						]
					)
				);
			}

			$data_types = array( 'post', 'user', 'term', 'product' );

			foreach ( $data_types as $type ) {
				if ( isset( $form['record'][ $type ] ) ) {
					$approval_form[ "{$type}_id" ] = $form['record'][ $type ];
					if ( isset( $update ) && is_numeric( $form['record'][ $type ] ) ) {
						$approval_form[ "save_to_{$type}" ] = 'edit_' . $type;
					} else {
						$approval_form[ "save_to_{$type}" ] = $form[ "save_to_{$type}" ];
					}
				}
			}
			global $ajax_render_form, $fea_form;
			if( $ajax_render_form ) {
				$form['ajax_submit'] = 'submission_form';
				$form['close_modal'] = 1;
			}
			$fea_form = $approval_form;

			$approval_form['fields'] = apply_filters( 'frontend_admin/submissions/form_fields', $approval_form['fields'], $approval_form, $approval_form['id'] );

			return $approval_form;
		}

		public function submissions_page_options() {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] ==  'fea-settings-submissions' ) {
				$option = 'per_page';
				$args   = array(
					'label'   => 'Submissions',
					'default' => 20,
					'option'  => 'submissions_per_page',
				);
				add_screen_option( $option, $args );
			}
		}
		function set_submissions_per_page( $status, $option, $value ) {
			if ( 'submissions_per_page' == $option ) {
				return $value;
			}
			return $status;
		}
		public function custom_admin_pages() {
			if ( ! get_option( 'frontend_admin_save_submissions' ) ) {
				return;
			}

			global $fea_submissions_page;
			$fea_submissions_page = add_submenu_page(  'fea-settings', __( 'Submissions', 'acf-frontend-form-element' ), __( 'Submissions', 'acf-frontend-form-element' ), 'manage_options',  'fea-settings-submissions', array( $this, 'display_submissions' ), 81 );
			add_action( "load-$fea_submissions_page", array( $this, 'submissions_page_options' ) );
		}

		public function transfer_acff_rows_to_new_table() {
			 global $wpdb;

			$old_name = $wpdb->prefix . 'acff_submissions';
			$query    = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old_name ) );

			if ( $wpdb->get_var( $query ) !== $old_name ) {
				return;
			}

			$sql     = "SELECT * FROM $old_name";
			$results = $wpdb->get_results( $sql, 'ARRAY_A' );

			if ( $results ) {
				foreach ( $results as $result ) {
					$submission = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $old_name WHERE id = %d", $result['id'] ) );
					if ( $submission ) {
						$this->insert_submission( $result );
						$wpdb->delete( $old_name, array( 'id' => $result['id'] ) );
					}
				}
			} else {
				$wpdb->query( "DROP TABLE IF EXISTS $old_name" );
			}

			$sql     = "SELECT * FROM $old_name";
			$results = $wpdb->get_results( $sql, 'ARRAY_A' );

			if ( $results ) {
				foreach ( $results as $result ) {
					$submission = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $old_name WHERE id = %d", $result['id'] ) );
					if ( $submission ) {
						$this->insert_submission( $result );
						$wpdb->delete( $old_name, array( 'id' => $result['id'] ) );
					}
				}
			}

		}
	

		public function render_form( $args ) {
			if ( 'submission' != $args['data_type'] ) return;
				
			$form = $this->get_form( sanitize_text_field( $args['form_action'] ) );

			if ( $form ) {
				fea_instance()->form_display->render_form( $form );
			}
			die();
		}

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'custom_admin_pages' ), 19 );
			$this->create_submissions();
			add_filter( 'set-screen-option', array( $this, 'set_submissions_per_page' ), 11, 3 );

			add_action( 'frontend_admin/ajax_add_form', array( $this, 'render_form' ) );
			add_filter( 'frontend_admin/forms/get_submission', function( $form, $submission ){
				if( empty( $submission ) ){
					return $form;
				}
				
				//check for approval nonce
				$nonce = $_POST['_acf_approval_nonce'] ?? '';
				if( ! $nonce || ! wp_verify_nonce( $nonce, 'frontend_admin_approve_submission' ) ){
					$approval = false;
				}else{
					$approval = true;
				}
				
				$form = $this->get_form( $submission, $form, $approval, true );

				return $form;
			}, 20, 3 );

		}
	}
	fea_instance()->submissions_handler = new Submissions_Crud();

endif;
