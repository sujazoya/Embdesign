<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WFFN_REST_Tools
 *
 * * @extends WP_REST_Controller
 */
if ( ! class_exists( 'WFFN_REST_Tools' ) ) {
	#[AllowDynamicProperties]
	class WFFN_REST_Tools extends WP_REST_Controller {

		public static $_instance = null;

		/**
		 * Route base.
		 *
		 * @var string
		 */

		protected $namespace = 'funnelkit-app';
		protected $rest_base = 'funnels/tools';

		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Register the routes for taxes.
		 */
		public function register_routes() {
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_tools' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),

				),
			) );
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'tools_action' ),
					'permission_callback' => array( $this, 'get_write_api_permission_check' ),
					'args'                => array(
						'woofunnels_transient' => array(
							'description'       => __( 'Clear woofunnels transient', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'woofunnels_tracking'  => array(
							'description'       => __( 'Clear woofunnels tracking', 'funnel-builder' ),
							'type'              => 'boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'index_past_order'     => array(
							'description'       => __( 'Clear woofunnels tracking', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			) );

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/get-all-log-files', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_log_files' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),

				),
			) );

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/view-log-file', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'view_log_files' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
					'args'                => array(
						'log_selected' => array(
							'description'       => __( 'Selected log file for view', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			) );

			register_rest_route( $this->namespace, '/' . $this->rest_base . '/delete-log-file', array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_log_files' ),
					'permission_callback' => array( $this, 'get_write_api_permission_check' ),
					'args'                => array(
						'log_selected' => array(
							'description'       => __( 'Selected log file for view', 'funnel-builder' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			) );

		}

		public function get_read_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'funnel', 'read' );
		}

		public function get_write_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'funnel', 'write' );
		}

		public function get_index_orders() {

			$threshold_order     = defined( 'BWF_THRESHOLD_ORDERS' ) ? BWF_THRESHOLD_ORDERS : 0;
			$get_threshold_order = get_option( '_bwf_order_threshold', $threshold_order );
			$bwf_db_upgrade      = WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state();
			$index_orders        = [];
			global $wpdb;
			if ( ! class_exists( 'WooCommerce' ) ) {
				return $index_orders;
			}

			if ( '3' !== $bwf_db_upgrade || $get_threshold_order < 1 ) {

				$paid_statuses = implode( ',', array_map( function ( $status ) {
					return "'wc-$status'";
				}, wc_get_is_paid_statuses() ) );

				if ( ! BWF_WC_Compatibility::is_hpos_enabled() ) {

					$query = $wpdb->prepare( "SELECT COUNT(p.ID) FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} AS pm ON ( p.ID = pm.post_id AND pm.meta_key = '_woofunnel_cid') LEFT JOIN {$wpdb->postmeta} AS pm2 ON (p.ID = pm2.post_id) WHERE 1=1 AND pm.post_id IS NULL AND ( pm2.meta_key = '_billing_email' AND pm2.meta_value != '' ) AND p.post_type = %s AND p.post_status IN ({$paid_statuses}) ORDER BY p.post_date DESC", 'shop_order' ); //phpcs:ignore

				} else {
					$order_table      = $wpdb->prefix . 'wc_orders';
					$order_meta_table = $wpdb->prefix . 'wc_orders_meta';
					$query            = $wpdb->prepare( "SELECT COUNT(p.id) FROM {$order_table} AS p LEFT JOIN {$order_meta_table} AS pm ON ( p.id = pm.order_id AND pm.meta_key = '_woofunnel_cid') WHERE 1=1 AND pm.order_id IS NULL AND p.billing_email != '' AND  p.type = %s  AND p.status IN ({$paid_statuses}) ORDER BY p.date_created_gmt DESC", 'shop_order' ); //phpcs:ignore


				}


				$query_results = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$get_threshold_order = $query_results;


			}


			if ( 0 === $get_threshold_order && 0 === absint( $bwf_db_upgrade ) ) {
				WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->set_upgrade_state( '5' );
				$bwf_db_upgrade = '5';
			}

			$description = __( 'This tool will scan all the previous orders and create an optimized index to run efficient queries. <a href="https://funnelkit.com/docs/upstroke/miscellaneous/index-past-order/?utm_source=WordPress&utm_medium=Index+Past+Orders&utm_campaign=fb+lite+plugin">Learn more</a>', 'funnel-builder' );

			if ( '1' === $bwf_db_upgrade || '6' === $bwf_db_upgrade ) {
				$description .= esc_html__( ' Unable to complete indexing of orders.', 'funnel-builder' );

				$description .= ' <a target="_blank" href="https://funnelkit.com/support/?utm_source=WordPress&utm_medium=Indexing+Failed+Support&utm_campaign=fb+lite+plugin">Contact support to get the issue resolved.</a>';

			}
			if ( true === apply_filters( 'bwf_needs_order_indexing', false ) ) {
				$index_orders = array(
					'title' => __( 'Index Past Orders', 'funnel-builder' ),
					'desc'  => $description,

				);


				if ( '3' === $bwf_db_upgrade ) {

					$index_orders['cta'] = array(
						'type' => 'button',
						'text' => __( 'Running', 'funnel-builder' ),
						'slug' => 'index_past_order',
						'prop' => 'disabled',
					);
				} elseif ( '4' === $bwf_db_upgrade || '5' === $bwf_db_upgrade ) {
					$index_orders['cta'] = array(
						'type' => 'button',
						'text' => __( 'Completed', 'funnel-builder' ),
						'slug' => 'index_past_order',
						'prop' => 'disabled',
					);
				} else {
					$index_orders['cta'] = array(
						'type' => 'button',
						'text' => __( 'Start', 'funnel-builder' ),
						'slug' => 'index_past_order',
						'prop' => ( $get_threshold_order > 0 ) ? '' : 'disabled',
					);
				}

				if ( '3' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					$index_orders['notice'] = array(
						'type' => 'success',
						'text' => __( 'Indexing of orders has started. It may take sometime to finish the process. We will update this notice once the process completes.', 'funnel-builder' )
					);
				}


			}

			return $index_orders;
		}

		public function get_all_tools() {
			return rest_ensure_response( $this->get_all_tools_array() );
		}

		public function get_all_tools_array() {
			$tools_array = array(

				array(
					'title' => __( 'FunnelKit transients', 'funnel-builder' ),
					'desc'  => __( 'This tool will clear all the FunnelKit plugins transients cache.', 'funnel-builder' ),
					'cta'   => array(
						'type' => 'button',
						'text' => __( 'Clear Transients', 'funnel-builder' ),
						'slug' => 'woofunnels_transient',
					),
				),
				array(
					'title' => __( 'Verify Base Tables', 'funnel-builder' ),
					'desc'  => __( 'This will verify FunnelKit all the base tables which are required for smooth functioning.', 'funnel-builder' ),
					'cta'   => array(
						'type' => 'button',
						'text' => __( 'Verify', 'funnel-builder' ),
						'slug' => 'woofunnels_verify',
					),
				),
				array(
					'title' => __( 'Usage Tracking', 'funnel-builder' ),
					'desc'  => __( 'This action controls Usage Tracking', 'funnel-builder' ),
					'cta'   => array(
						'type'         => 'toggle',
						'value'        => wffn_string_to_bool( WooFunnels_OptIn_Manager::get_optIn_state() ),
						'text_enable'  => __( 'User Tracking Enabled', 'funnel-builder' ),
						'text_disable' => __( 'User Tracking Disabled', 'funnel-builder' ),
						'slug'         => 'woofunnels_tracking',
					),
				),

			);
			$index       = $this->get_index_orders();
			if ( count( $index ) > 0 ) {
				return array_merge( [ $index ], $tools_array );
			} else {
				return $tools_array;
			}

		}

		public function get_all_log_files() {

			$file_list   = array();
			$file_list[] = array(
				'label' => __( 'Select Log File', 'funnel-builder' ),
				'value' => '',
				'key'   => ''
			);

			if ( ! class_exists( 'BWF_Logger' ) ) {
				return rest_ensure_response( $file_list );
			}

			$logger_obj        = BWF_Logger::get_instance();
			$final_logs_result = $logger_obj->get_log_options();

			foreach ( $final_logs_result as $plugin_folder => $plugin_log_files ) {
				foreach ( $plugin_log_files as $file_slug => $file_name ) {
					$option_value = $plugin_folder . '/' . $file_slug;
					$file_list[]  = array(
						'label' => $this->extractFilename( $file_name ),
						'value' => $option_value,
						'key'   => $option_value
					);

				}
			}

			return rest_ensure_response( $file_list );
		}

		/**
		 * Extract the filename correct
		 *
		 * @param $filename
		 *
		 * @return string
		 */
		public function extractFilename( $filename ) {
			$pattern = '/^(.*?-\d{4}-\d{2}-\d{2})(?:-[a-f0-9]{32})?$/';

			if ( preg_match( $pattern, $filename, $matches ) ) {
				return $matches[1];
			}

			return $filename; // Return original if no match
		}

		public function view_log_files( $request ) {

			$resp = array(
				'status' => false,
				'msg'    => __( 'No log file found', 'funnel-builder' )
			);

			$selected_log_file = isset( $request['log_selected'] ) ? $request['log_selected'] : '';

			if ( empty( $selected_log_file ) ) {
				return rest_ensure_response( $resp );
			}

			$folder_prefix    = explode( '/', $selected_log_file );
			$folder_file_name = $folder_prefix[1];
			$folder_prefix    = $folder_prefix[0];
			$file_api         = new WooFunnels_File_Api( $folder_prefix );

			// View log submit is clicked, get the content from the selected file
			$content = $file_api->get_contents( $folder_file_name );

			if ( $content !== false ) {
				return rest_ensure_response( $content );
			}

			return rest_ensure_response( $resp );
		}

		public function delete_log_files( $request ) {

			$resp = array(
				'status' => false,
				'msg'    => __( 'No log file found', 'funnel-builder' )
			);

			$selected_log_file = isset( $request['log_selected'] ) ? $request['log_selected'] : '';

			if ( empty( $selected_log_file ) ) {
				return rest_ensure_response( $resp );
			}

			$folder_prefix    = explode( '/', $selected_log_file );
			$folder_file_name = $folder_prefix[1];
			$folder_prefix    = $folder_prefix[0];
			$file_api         = new WooFunnels_File_Api( $folder_prefix );

			// View log submit is clicked, get the content from the selected file
			$delete = $file_api->delete_file( $folder_file_name );

			if ( $delete ) {
				$resp = array(
					'status' => true,
					'msg'    => __( 'Successfully delete log file', 'funnel-builder' )
				);
			}

			return rest_ensure_response( $resp );
		}

		public function tools_action( $request ) {
			$resp = array(
				'status' => false,
			);

			$transient         = ( isset( $request['woofunnels_transient'] ) && $request['woofunnels_transient'] === 'yes' ) ? $request['woofunnels_transient'] : '';
			$woofunnels_verify = ( isset( $request['woofunnels_verify'] ) && $request['woofunnels_verify'] === 'yes' ) ? $request['woofunnels_verify'] : '';
			$tracking          = ( isset( $request['woofunnels_tracking'] ) ) ? $request['woofunnels_tracking'] : '';
			$index_orders      = ( isset( $request['index_past_order'] ) && $request['index_past_order'] === 'yes' ) ? $request['index_past_order'] : '';

			if ( $transient !== '' ) {
				$woofunnels_transient_obj = WooFunnels_Transient::get_instance();
				$woofunnels_transient_obj->delete_force_transients();
				$resp['status'] = true;
				$resp['tool']   = $this->get_index_orders();
				$resp['msg']    = __( 'All Plugins transients cleared.', 'funnel-builder' );

				return rest_ensure_response( $resp );
			}
			if ( $woofunnels_verify !== '' ) {
				global $wpdb;
				$tables          = $this->get_tables_list();
				$filtered_tables = $this->get_filtered_tables( $tables );
				$missing_tables  = $this->check_missing_tables( $filtered_tables );

				if ( empty( array_filter( $missing_tables ) ) ) {
					return rest_ensure_response( [ 'status' => true, 'msg' => 'All required tables are present in the database.' ] );
				}

				$missing_table_names             = array_merge( ...array_values( array_filter( $missing_tables ) ) );
				$missing_table_names_with_prefix = array_map( function ( $table ) use ( $wpdb ) {
					return $wpdb->prefix . $table;
				}, $missing_table_names );

				return rest_ensure_response( [
					'status' => true,
					'msg'    => 'The following tables were created: ' . implode( ', ', $missing_table_names_with_prefix )
				] );

			}

			if ( $tracking !== '' ) {

				if ( true === $tracking ) {
					WooFunnels_OptIn_Manager::Allow_optin();
				} else {
					delete_option( 'bwf_is_opted' );
				}

				if ( false !== wp_next_scheduled( 'bwf_maybe_track_usage_scheduled' ) ) {
					wp_clear_scheduled_hook( 'bwf_maybe_track_usage_scheduled' );
				}
				$resp['status'] = true;
				$resp['msg']    = __( sprintf( 'Usage tracking successfully %s.', true === $tracking ? 'enabled' : 'disabled' ), 'funnel-builder' );

				return rest_ensure_response( $resp );
			}

			if ( $index_orders !== '' ) {

				if ( 'yes' === $index_orders && '0' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->set_upgrade_state( '2' );
				}
				if ( 'yes' === $index_orders && '1' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->reset_indexing_data( true );
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->set_upgrade_state( '2' );
				}
				if ( '2' === WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->get_upgrade_state() ) {
					WooFunnels_Dashboard::$classes['WooFunnels_DB_Updater']->bwf_start_indexing();
				}

				$get_index_array = $this->get_all_tools_array();
				$resp['tool']    = $get_index_array[0];
				$resp['status']  = true;
				$resp['msg']     = __( 'Indexing started successfully', 'funnel-builder' );

				return rest_ensure_response( $resp );
			}

			return rest_ensure_response( $resp );

		}


		public function check_missing_tables( $tables ) {
			$missing_tables = [];

			foreach ( $tables as $version_key => $table_list ) {
				$missing_tables[ $version_key ] = $this->wffn_validate_db_tables( $table_list );
			}

			return $missing_tables;
		}

		public function get_filtered_tables( array $tables ): array {
			$is_funnel_pro_active = WFFN_Common::wffn_is_funnel_pro_active();

			return array_filter( $tables, function ( $table_list, $version_key ) use ( $is_funnel_pro_active ) {

				if ( $is_funnel_pro_active ) {
					return true;
				}

				return ! in_array( $version_key, [ '_wfocu_db_version', 'wfob_db_ver_3_0', 'wfacp_db_ver_2_1' ] ); // @codingStandardsIgnoreLine
			}, ARRAY_FILTER_USE_BOTH );
		}


		public function wffn_validate_db_tables( $tables ) {
			global $wpdb;

			$table_names = array_map( function ( $table ) use ( $wpdb ) {
				return $wpdb->prefix . $table;
			}, $tables );

			$placeholders = implode( ',', array_fill( 0, count( $table_names ), '%s' ) );
			$query        = $wpdb->prepare( "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME IN ($placeholders)", array_merge( [ $wpdb->dbname ], $table_names ) ); // @codingStandardsIgnoreLine

			$existing_tables = $wpdb->get_col( $query ); // @codingStandardsIgnoreLine

			$missing_tables = array_diff( $table_names, $existing_tables );

			if ( ! empty( $missing_tables ) ) {

				$missing_tables = array_map( function ( $table ) use ( $wpdb ) {
					return str_replace( $wpdb->prefix, '', $table );
				}, $missing_tables );

				$option_keys_to_delete = $this->get_option_keys_for_missing_tables( $tables );
				$option_key_map        = [
					'_wfocu_db_version'  => [ 'class' => WFOCU_Admin::class, 'method' => 'check_db_version', 'update_method' => 'maybe_update_database_update' ],
					'_wffn_db_version'   => [ 'class' => WFFN_ADMIN::class, 'method' => 'check_db_version', 'update_method' => 'maybe_update_database_update' ],
					'_wfopp_db_version'  => [ 'class' => WFOPP_DB_Tables::class, 'method' => 'add_if_needed' ],
					'wfob_db_ver_3_0'    => [ 'class' => WFOB_Reporting::class, 'method' => 'create_table' ],
					'wfacp_db_ver_2_1'   => [ 'class' => WFACP_Reporting::class, 'method' => 'create_table' ],
					'_bwf_db_table_list' => [ 'class' => WooFunnels_Create_DB_Tables::class, 'method' => 'create' ]
				];

				if ( ! empty( $option_keys_to_delete ) ) {
					foreach ( $option_keys_to_delete as $option_key ) {
						delete_option( $option_key );
						if ( isset( $option_key_map[ $option_key ] ) ) {
							$table_handler = $option_key_map[ $option_key ];
							$tables        = $table_handler['class']::get_instance();
							if ( isset( $table_handler['method'] ) ) {
								$tables->{$table_handler['method']}();
							}
							if ( isset( $table_handler['update_method'] ) ) {
								$tables->{$table_handler['update_method']}();
							}
						}
					}
				}
			}

			return ! empty( $missing_tables ) ? $missing_tables : [];
		}

		public function get_option_keys_for_missing_tables( array $tables ): array {
			$table_to_option_map = $this->get_tables_list();

			$option_keys = array_keys( array_filter( $table_to_option_map, function ( $required_tables ) use ( $tables ) {
				return ! empty( array_intersect( $tables, $required_tables ) );
			} ) );

			return $option_keys;
		}

		private function get_tables_list(): array {
			return [
				'_wfocu_db_version'  => [ 'wfocu_session', 'wfocu_event', 'wfocu_event_meta' ],
				'_wffn_db_version'   => [ 'bwf_funnels', 'bwf_funnelmeta' ],
				'_wfopp_db_version'  => [ 'bwf_optin_entries' ],
				'wfob_db_ver_3_0'    => [ 'wfob_stats' ],
				'wfacp_db_ver_2_1'   => [ 'wfacp_stats' ],
				'_bwf_db_table_list' => [ 'bwf_contact', 'bwf_contact_meta', 'bwf_wc_customers', 'wfco_report_views', 'bwf_conversion_tracking' ],
			];
		}
	}


	return WFFN_REST_Tools::get_instance();


}
