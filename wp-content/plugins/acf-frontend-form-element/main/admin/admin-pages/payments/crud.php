<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if( ! class_exists( 'FEA_Payments_Crud' ) ) :

	class FEA_Payments_Crud{
        public function create_payments() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'fea_payments';
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				description text NOT NULL,
				external_id text NOT NULL,
				user int NOT NULL,
				amount int NOT NULL,
				subscription int NULL,
				currency text NOT NULL,
				method text NOT NULL, 
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			maybe_create_table( $table_name, $sql );


			$acff_table = $wpdb->prefix . 'acff_payments';

			if($wpdb->get_var("SHOW TABLES LIKE '$acff_table'") == $acff_table) {
				$entries = $wpdb->get_results("SELECT * FROM $acff_table", ARRAY_A);
				foreach($entries as $entry) {
					$wpdb->insert($table_name, $entry);
				}
				$wpdb->query("TRUNCATE TABLE $acff_table");
				$wpdb->query("DROP TABLE $acff_table");
			}
		}

		public function insert_payment( $args ){
			if( empty( $args['created_at'] ) ){
				$args['created_at'] = current_time( 'mysql' );
			}
			if( empty( $args['user'] ) ){
				$args['user'] = get_current_user_id();
			}
			global $wpdb;
			$wpdb->insert( $wpdb->prefix . 'fea_payments', $args );
			return $wpdb->insert_id;
		}

		public function update_payment( $id, $args ){
			global $wpdb;
			$wpdb->update( 
				$wpdb->prefix . 'fea_payments', 
				$args,		
				array( 'id' => $id )			
			);
		}

		public function approve_payment( $id ){
			global $wpdb;
			$payment = $this->get_payment( $id );

			if( $payment->status == 'pending' ){
				$form = $this->get_form( $payment );
				foreach( fea_instance()->local_actions as $action ){
					$name = $action->get_name();
					$form = $action->run( $form );
				}
				$wpdb->update( 
					$wpdb->prefix . 'fea_payments', 
					array( 'status' => 'approved' ),		
					array( 'id' => $id )			
				);
			}
		}

		public function get_payment( $id = 0, $by = 'id' ){
			if( ! $id ) return $id;

			global $wpdb;

			if( is_numeric( $id ) ){
				$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fea_payments WHERE id = %d", $id ) );
			}elseif( is_string( $id ) ){
				$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fea_payments WHERE external_id = %s", $id ) );
			}else{
				return false;
			}
			

            if( isset( $payment ) && $payment->$by == $id ) return $payment;

            return false;
		}

		/**
		 * Retrieve payments data from the database
		 *
		 * @param array $args query arguments
		 *
		 * @return mixed
		 */
		public static function get_payments( $args = array() ) {
			global $wpdb;

			$args = feadmin_parse_args( $args, array(
				'per_page' => 20,
				'current_page' => 1,
			) );

			$sql = "SELECT * FROM {$wpdb->prefix}fea_payments";

			if( ! empty( $_REQUEST['s'] ) ){
				$value = $_REQUEST['s'] . '%';
				$sql .= $wpdb->prepare( ' WHERE title LIKE %s', $value );
			}

			// Allowlisted columns and order directions
			$allowed_orderby = [ 'created_at', 'title', 'status' ]; // Modify this list to match your DB columns
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

			$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}fea_payments";

			return $wpdb->get_var( $sql );
		}

		public function delete_payment( $id = 0 ){
			if( $id == 0 ) return $id;
			global $wpdb;
			$wpdb->delete( $wpdb->prefix.'fea_payments', array( 'id' => $id ) );
			return 1;
		}

		

		public function get_form( $payment, $form = array() ){		
			if( empty( $form ) ) $form = json_decode( acf_decrypt( $payment->fields ), true );
			$approval_form = array(
				'id' => $form['id'],
				'field_objects' => call_user_func_array( 'array_merge', $form['record']['fields'] ),
				'submit_value' => __( 'Approve', 'acf-frontend-form-element' ),
				'redirect' => 'custom_url',
				'kses' => 0,
				'no_cookies' => 1,
				'payment' => $payment->id,
				'approval' => 1,
				'custom_url' => admin_url( 'admin.php?page=fea-payments&action=edit&id=' .$_REQUEST['id'] ),
			);
			$data_types = array( 'post', 'user', 'term' );
			if( fea_instance()->is_license_active() ){
				if ( class_exists( 'woocommerce' ) ){
					$data_types[] = 'product';
				}
			}			
			foreach( $data_types as $type ){
				if( isset( $form['record'][$type] ) ){
					$approval_form["{$type}_id"] = $form['record'][$type];
					$approval_form["save_to_{$type}"] = $form["save_to_{$type}"];
				}
			}
			return $approval_form;
		}

        public function payments_page_options(){
			if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'fea-payments' ){
				$option = 'per_page';
				$args   = [
					'label'   => 'Payments',
					'default' => 20,
					'option'  => 'payments_per_page'
				];
				add_screen_option( $option, $args );
			}
		}	
		function set_payments_per_page($status, $option, $value) {
			if ( 'payments_per_page' == $option ) return $value;
            return $status;
		}
        public function payments_list(){
            global $fa_payments_page;
            $fa_payments_page = add_submenu_page( 'fea-settings', __( 'Payments', 'acf-frontend-form-element' ), __( 'Payments', 'acf-frontend-form-element' ), 'manage_options', 'frontend-admin-payments', [ $this, 'admin_payments_page'], 82 );
            add_action( "load-$fa_payments_page", array( $this, 'payments_page_options' ) );
        }

        public function admin_payments_page(){ 
			require_once( 'list.php');
			$option = 'per_page';
			$args   = [
				'label'   => 'Payments',
				'default' => 20,
				'option'  => 'payments_per_page'
			];

			add_screen_option( $option, $args );

			?>
				<h2><?php echo __( 'Payments', 'acf-frontend-form-element' ) ?></h2>
				<?php
				fea_instance()->payments_list->prepare_items();
				fea_instance()->payments_list->display();
		}
       
        public function __construct() {
            $this->create_payments();	
            add_action( 'admin_menu', array( $this, 'payments_list' ), 20 );	
			add_filter( 'set-screen-option', array( $this, 'set_payments_per_page' ), 11, 3 );
        }
    }
    fea_instance()->payments_handler = new FEA_Payments_Crud;

endif;