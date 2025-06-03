<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if( ! class_exists( 'FEA_Emails_Crud' ) ) :

	class FEA_Emails_Crud{
        public function create_emails() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'fea_emails';
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				user mediumint(9) NOT NULL,
				status varchar(20) DEFAULT 'pending' NOT NULL,
				address text NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			maybe_create_table( $table_name, $sql );

		}

		public function insert_email( $args ){
			if( empty( $args['created_at'] ) ){
				$args['created_at'] = current_time( 'mysql' );
			}
			if( empty( $args['user'] ) ){
				$args['user'] = get_current_user_id();
			}
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . 'fea_emails', $args );
			return $wpdb->insert_id;
		}

		public function update_email( $id, $args ){
			global $wpdb;
			$wpdb->update( 
				$wpdb->prefix . 'fea_emails', 
				$args,		
				array( 'id' => $id )			
			);
		}

		/**
		 * Email Verified
		 * 
		 * @param int|string $id email ID or email address
		 * @param bool $user
		 * 
		 */
		public function is_email_verified( $id ){
			if ( email_exists( $id ) ) {
				$user = get_user_by( 'email', $id );

				$verified = false;
				if( isset( $user->ID ) ){
					$verified = get_user_meta( $user->ID, 'frontend_admin_email_verified', 1 );
				}

				if ( $verified ) {
					$this->approve_email( $user->user_email, $user->ID );
					delete_user_meta( $current_user->ID, 'frontend_admin_email_verified' );
				}

			} 

			$email = $this->get_email( $id );
			if( ! $email ) return false;

			if( 'approved' == $email->status ) return true;
			return false;
		}

		/**
		 * Approve email
		 *
		 * @param int|string $id email ID or email address
		 */
		public function approve_email( $id, $user = false ){
			global $wpdb;
			$email = $this->get_email( $id );

			//if false, insert email and aprrove
			if( ! $email ){
				$this->insert_email( array( 'address' => $id, 'status' => 'approved' ) );
				return;
			}

			if( 'pending' == $email->status ){
				$this->update_email( $email->id, array( 'status' => 'approved' ) );
			}
		}

		public function get_email( $id = 0, $by = 'id' ){
			if( ! $id ) return $id;

			global $wpdb;

			if( is_numeric( $id ) ){
				$email = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fea_emails WHERE %s = %d", $by, $id ) );
			}elseif( is_string( $id ) ){
				$email = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fea_emails WHERE address = %s", $id ) );
			}else{
				return false;
			}
			

            if( isset( $email ) && $email->$by == $id ) return $email;

            return false;
		}

	

		/**
		 * Retrieve emails data from the database
		 *
		 * @param array $args query arguments
		 *
		 * @return mixed
		 */
		public static function get_emails( $args = array() ) {
			global $wpdb;

			$args = feadmin_parse_args( $args, array(
				'per_page' => 20,
				'current_page' => 1,
			) );

			$sql = "SELECT * FROM {$wpdb->prefix}fea_emails";

			if( ! empty( $_REQUEST['s'] ) ){
				$value = $_REQUEST['s'] . '%';
				$sql .= $wpdb->prepare( ' WHERE address LIKE %s', $value );
			}

			$allowed_orderby = [ 'created_at', 'status' ]; // Modify this list to match your DB columns
			$allowed_order   = [ 'ASC', 'DESC' ];

			$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
			$order   = in_array( $args['order'], $allowed_order, true ) ? $args['order'] : 'DESC';

			$sql .= " ORDER BY `$orderby` $order";

			$sql .= $wpdb->prepare( " LIMIT %d", $args['per_page'] );
			$sql .= $wpdb->prepare( " OFFSET %d", ( $args['current_page'] - 1 ) * $args['per_page'] );	


			$result = $wpdb->get_results( $sql, 'ARRAY_A' );

			return $result;
		}

		/**
		 * Returns the count of records in the database.
		 *
		 * @return null|string
		 */
		public static function record_count() {
			global $wpdb;

			$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}fea_emails";

			return $wpdb->get_var( $sql );
		}

		public function delete_email( $id = 0 ){
			if( $id == 0 ) return $id;
			global $wpdb;
			$wpdb->delete( $wpdb->prefix.'fea_emails', array( 'id' => $id ) );
			return 1;
		}

		public function __construct() {
            $this->create_emails();	
        }

    }
    fea_instance()->emails_handler = new FEA_Emails_Crud;

endif;