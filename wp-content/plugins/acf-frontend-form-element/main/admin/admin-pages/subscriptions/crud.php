<?php
namespace Frontend_Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if( ! class_exists( 'Frontend_Admin\Admin\Subscriptions_Crud' ) ) :

	class Subscriptions_Crud{
        public function create_subscriptions() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'fea_subscriptions';
			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				external_id text NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				expires_at datetime DEFAULT '0000-00-00 00:00:00' NULL,
				status text NOT NULL,
				user text NOT NULL,
				plan int NOT NULL,
				gross text NOT NULL,
				payment_token text NULL,
				recurring_amount text NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            maybe_create_table( $table_name, $sql );
		}

		public function insert_subscription( $args ){
			if( empty( $args['created_at'] ) ){
				$args['created_at'] = current_time( 'mysql' );
			}
			if( empty( $args['status'] ) ){
				$args['status'] = 'active';
			}
			if( empty( $args['user'] ) ){
				$args['user'] = get_current_user_id();
			}
			if( empty( $args['plan'] ) ){
				$args['plan'] = 0;
			}else{
				$plan = fea_instance()->plans_handler->get_plan( $args['plan'] );
				if( $plan ){
					$args['gross'] = $args['gross'] ?? $plan['price'];
					
					//get expiration date in timestamp
					$expires_at = strtotime( '+' . $plan['duration'] . ' ' . $plan['duration_unit'] );
					$args['expires_at'] = $expires_at;
					
				}
			}
			if( empty( $args['gross'] ) ){
				$args['gross'] = 0;
			}

			global $wpdb;
			$wpdb->insert( $wpdb->prefix . 'fea_subscriptions', $args );
			return $wpdb->insert_id;
		}

		public function update_subscription( $id, $args ){
			global $wpdb;
			$wpdb->update( 
				$wpdb->prefix . 'fea_subscriptions', 
				$args,		
				array( 'id' => $id )			
			);
		}


		public function get_subscription( $id = 0 ){
			if( ! $id ) return $id;

			global $wpdb;
			$subscription = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}fea_subscriptions WHERE id = %d", $id ) );

            if( $subscription->$by == $id ) return $subscription;

            return false;
		}

		/**
		 * Retrieve subscriptions data from the database
		 *
		 * @param array $args query arguments
		 *
		 * @return mixed
		 */
		public static function get_subscriptions( $args = array() ) {
			global $wpdb;

			$args = feadmin_parse_args( $args, array(
				'per_page' => 20,
				'current_page' => 1,
			) );

			$sql = "SELECT * FROM {$wpdb->prefix}fea_subscriptions";

			if( ! empty( $args['user'] ) ){
				$sql .= $wpdb->prepare( ' WHERE user LIKE %s', intval( $args['user'] ) );
			}

			if( ! empty( $_REQUEST['s'] ) ){
				$value = $_REQUEST['s'] . '%';
				$sql .= $wpdb->prepare( ' WHERE title LIKE %s', $value );
			}

			$allowed_orderby = [ 'created_at', 'expires_at' ]; // Modify this list to match your DB columns
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

			$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}fea_subscriptions";

			return $wpdb->get_var( $sql );
		}

		public function delete_subscription( $id = 0 ){
			if( $id == 0 ) return $id;
			global $wpdb;
			$wpdb->delete( $wpdb->prefix.'fea_subscriptions', array( 'id' => $id ) );
			return 1;
		}

        public function subscriptions_page_options(){
			if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'fea-subscriptions' ){
				$option = 'per_page';
				$args   = [
					'label'   => 'Subscriptions',
					'default' => 20,
					'option'  => 'subscriptions_per_page'
				];
				add_screen_option( $option, $args );
			}
		}	
		function set_subscriptions_per_page($status, $option, $value) {
			if ( 'subscriptions_per_page' == $option ) return $value;
            return $status;
		}
        public function subscriptions_list(){
            global $fa_subscriptions_page;
            $fa_subscriptions_page = add_submenu_page( 'fea-settings', __( 'Subscriptions', 'acf-frontend-form-element' ), __( 'Subscriptions', 'acf-frontend-form-element' ), 'manage_options', 'frontend-admin-subscriptions', [ $this, 'admin_subscriptions_page'], 82 );
            add_action( "load-$fa_subscriptions_page", array( $this, 'subscriptions_page_options' ) );
        }

        public function admin_subscriptions_page(){ 
			require_once( 'list.php');
			$option = 'per_page';
			$args   = [
				'label'   => 'Subscriptions',
				'default' => 20,
				'option'  => 'subscriptions_per_page'
			];

			add_screen_option( $option, $args );

			?>
				<h2><?php echo __( 'Subscriptions', 'acf-frontend-form-element' ) ?></h2>
				<?php
				fea_instance()->subscriptions_list->prepare_items();
				fea_instance()->subscriptions_list->display();
		}
       
        public function __construct() {
            $this->create_subscriptions();	
           //add_action( 'admin_menu', array( $this, 'subscriptions_list' ), 20 );	
			//add_filter( 'set-screen-option', array( $this, 'set_subscriptions_per_page' ), 11, 3 );
        }
    }
    fea_instance()->subscriptions_handler = new Subscriptions_Crud;

endif;