<?php

if ( ! class_exists( 'WFFN_REST_API_Dashboard_EndPoint' ) ) {
	class WFFN_REST_API_Dashboard_EndPoint extends WFFN_REST_Controller {

		private static $ins = null;
		protected $namespace = 'funnelkit-app';
		protected $rest_base = 'funnel-analytics';

		/**
		 * WFFN_REST_API_Dashboard_EndPoint constructor.
		 */
		public function __construct() {

			add_action( 'rest_api_init', [ $this, 'register_endpoint' ], 12 );
		}

		/**
		 * @return WFFN_REST_API_Dashboard_EndPoint|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self;
			}

			return self::$ins;
		}

		public function register_endpoint() {
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/dashboard/stats/', array(
				array(
					'args'                => $this->get_stats_collection(),
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_graph_data' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
				),
			) );
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/dashboard/overview/', array(
				array(
					'args'                => $this->get_stats_collection(),
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_overview_data' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
				),
			) );
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/stream/timeline/', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_timeline_funnels' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
				),
			) );
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/dashboard/', array(
				array(
					'args'                => $this->get_stats_collection(),
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_stats_data' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
				),
			) );
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/dashboard/sources', array(
				array(
					'args'                => $this->get_stats_collection(),
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_source_data' ),
					'permission_callback' => array( $this, 'get_read_api_permission_check' ),
				),
			) );
		}

		public function get_read_api_permission_check() {
			return wffn_rest_api_helpers()->get_api_permission_check( 'analytics', 'read' );
		}

		public function get_overview_data( $request, $is_email_data = false ) {
			if ( isset( $request['overall'] ) ) {
				$start_date = '';
				$end_date   = '';
			} else {
				$start_date = ( isset( $request['after'] ) && '' !== $request['after'] ) ? $request['after'] : self::default_date( WEEK_IN_SECONDS )->format( self::$sql_datetime_format );
				$end_date   = ( isset( $request['before'] ) && '' !== $request['before'] ) ? $request['before'] : self::default_date()->format( self::$sql_datetime_format );

			}

			$funnel_id        = 0;
			$total_revenue    = null;
			$checkout_revenue = 0;
			$upsell_revenue   = 0;
			$bump_revenue     = 0;

			$get_total_revenue = $this->get_total_revenue( $funnel_id, $start_date, $end_date );
			$get_total_orders  = $this->get_total_orders( $funnel_id, $start_date, $end_date );
			$get_total_contact = $this->get_total_contacts( 0, $start_date, $end_date );

			if ( ! isset( $get_total_revenue['db_error'] ) ) {
				if ( is_array( $get_total_revenue ) ) {
					$total_revenue = $checkout_revenue = $get_total_revenue['aero'][0]['total'];
					if ( count( $get_total_revenue['aero'] ) > 0 ) {
						$checkout_revenue = $get_total_revenue['aero'][0]['sum_aero'];
					}
					if ( count( $get_total_revenue['bump'] ) > 0 ) {
						$bump_revenue = $get_total_revenue['bump'][0]['sum_bump'];
					}
					if ( count( $get_total_revenue['upsell'] ) > 0 ) {
						$upsell_revenue = $get_total_revenue['upsell'][0]['sum_upsells'];
					}
				}
			}

			$result = [
				'revenue'          => is_null( $total_revenue ) ? 0 : $total_revenue,
				'total_orders'     => intval( $get_total_orders ),
				'checkout_revenue' => floatval( $checkout_revenue ),
				'upsell_revenue'   => floatval( $upsell_revenue ),
				'bump_revenue'     => floatval( $bump_revenue ),
			];
			if ( $is_email_data === true ) {
				$result['total_contacts'] = is_array( $get_total_contact ) ? $get_total_contact[0]['contacts'] : 0;;
				$result['average_order_value'] = ( absint( $total_revenue ) !== 0 ) ? ( $total_revenue ) / $get_total_orders : 0;
			}
			$resp = array(
				'status' => true,
				'msg'    => __( 'success', 'funnel-builder' ),
				'data'   => $result
			);

			return rest_ensure_response( $resp );

		}

		public function get_graph_data( $request ) {
			$resp = array(
				'status' => false,
				'data'   => []
			);

			$interval_type = '';

			if ( isset( $request['overall'] ) ) {
				global $wpdb;
				$request['after']    = $wpdb->get_var( $wpdb->prepare( "SELECT timestamp as date FROM {$wpdb->prefix}bwf_conversion_tracking WHERE funnel_id != '' AND type = 2 ORDER BY ID ASC LIMIT %d", 1 ) );
				$start_date          = ( isset( $request['after'] ) && '' !== $request['after'] ) ? $request['after'] : self::default_date( WEEK_IN_SECONDS )->format( self::$sql_datetime_format );
				$end_date            = ( isset( $request['before'] ) && '' !== $request['before'] ) ? $request['before'] : self::default_date()->format( self::$sql_datetime_format );
				$request['interval'] = $this->get_two_date_interval( $start_date, $end_date );
				$interval_type       = $request['interval'];
			}

			$totals    = $this->prepare_graph_for_response( $request );
			$intervals = $this->prepare_graph_for_response( $request, 'interval' );

			if ( ! is_array( $totals ) || ! is_array( $intervals ) ) {
				return rest_ensure_response( $resp );
			}

			$resp = array(
				'status' => true,
				'data'   => array(
					'totals'    => $totals,
					'intervals' => $intervals
				)
			);

			if ( isset( $request['overall'] ) ) {
				$resp['data']['interval_type'] = $interval_type;
			}

			return rest_ensure_response( $resp );
		}

		public function prepare_graph_for_response( $request, $is_interval = '' ) {
			$start_date  = ( isset( $request['after'] ) && '' !== $request['after'] ) ? $request['after'] : self::default_date( WEEK_IN_SECONDS )->format( self::$sql_datetime_format );
			$end_date    = ( isset( $request['before'] ) && '' !== $request['before'] ) ? $request['before'] : self::default_date()->format( self::$sql_datetime_format );
			$int_request = ( isset( $request['interval'] ) && '' !== $request['interval'] ) ? $request['interval'] : 'week';


			$funnel_id      = 0;
			$total_revenue  = 0;
			$aero_revenue   = 0;
			$upsell_revenue = 0;
			$bump_revenue   = 0;

			$get_total_orders = $this->get_total_orders( $funnel_id, $start_date, $end_date, $is_interval, $int_request );
			if ( isset( $get_total_orders['db_error'] ) ) {
				$get_total_orders = 0;
			}
			$get_total_revenue = $this->get_total_revenue( $funnel_id, $start_date, $end_date, $is_interval, $int_request );

			$result    = [];
			$intervals = array();
			if ( ! empty( $is_interval ) ) {
				$overall       = isset( $request['overall'] ) ? true : false;
				$intervals_all = $this->intervals_between( $start_date, $end_date, $int_request, $overall );
				foreach ( $intervals_all as $all_interval ) {
					$interval   = $all_interval['time_interval'];
					$start_date = $all_interval['start_date'];
					$end_date   = $all_interval['end_date'];

					$get_total_order = is_array( $get_total_orders ) ? $this->maybe_interval_exists( $get_total_orders, 'time_interval', $interval ) : [];

					if ( ! isset( $get_total_revenue['db_error'] ) ) {
						$get_revenue        = $this->maybe_interval_exists( $get_total_revenue['aero'], 'time_interval', $interval );
						$total_revenue_aero = is_array( $get_revenue ) ? $get_revenue[0]['sum_aero'] : 0;
						$total_revenue      = is_array( $get_revenue ) ? $get_revenue[0]['total'] : 0;


						$total_revenue_bump = $this->maybe_interval_exists( $get_total_revenue['bump'], 'time_interval', $interval );
						$total_revenue_bump = is_array( $total_revenue_bump ) ? $total_revenue_bump[0]['sum_bump'] : 0;

						$total_revenue_upsells = $this->maybe_interval_exists( $get_total_revenue['upsell'], 'time_interval', $interval );
						$total_revenue_upsells = is_array( $total_revenue_upsells ) ? $total_revenue_upsells[0]['sum_upsells'] : 0;
					} else {
						$total_revenue         = 0;
						$total_revenue_aero    = 0;
						$total_revenue_bump    = 0;
						$total_revenue_upsells = 0;
					}

					$get_total_order             = is_array( $get_total_order ) ? $get_total_order[0]['total_orders'] : 0;
					$intervals['interval']       = $interval;
					$intervals['start_date']     = $start_date;
					$intervals['date_start_gmt'] = $this->convert_local_datetime_to_gmt( $start_date )->format( self::$sql_datetime_format );
					$intervals['end_date']       = $end_date;
					$intervals['date_end_gmt']   = $this->convert_local_datetime_to_gmt( $end_date )->format( self::$sql_datetime_format );
					$intervals['subtotals']      = array(
						'orders'           => $get_total_order,
						'revenue'          => $total_revenue,
						'checkout_revenue' => floatval( $total_revenue_aero ),
						'upsell_revenue'   => floatval( $total_revenue_upsells ),
						'bump_revenue'     => floatval( $total_revenue_bump ),
					);

					$result[] = $intervals;

				}

			} else {
				if ( ! isset( $get_total_revenue['db_error'] ) ) {
					if ( count( $get_total_revenue['aero'] ) > 0 ) {
						$aero_revenue  = $get_total_revenue['aero'][0]['sum_aero'];
						$total_revenue += $aero_revenue;
					}
					if ( count( $get_total_revenue['bump'] ) > 0 ) {
						$bump_revenue  = $get_total_revenue['bump'][0]['sum_bump'];
						$total_revenue += $bump_revenue;
					}
					if ( count( $get_total_revenue['upsell'] ) > 0 ) {
						$upsell_revenue = $get_total_revenue['upsell'][0]['sum_upsells'];
						$total_revenue  += $upsell_revenue;
					}
				}

				$result = [
					'orders'           => $get_total_orders,
					'revenue'          => is_null( $total_revenue ) ? 0 : $total_revenue,
					'checkout_revenue' => floatval( $aero_revenue ),
					'upsell_revenue'   => floatval( $upsell_revenue ),
					'bump_revenue'     => floatval( $bump_revenue ),
				];
			}

			return $result;

		}

		public function get_all_stats_data( $request ) {
			$response                = array();
			$response['top_funnels'] = $this->get_top_funnels( $request );
			$top_campaigns           = array(
				'sales' => array(),
				'lead'  => array()
			);

			$top_campaigns = apply_filters( 'wffn_dashboard_top_campaigns', $top_campaigns, $request );

			$response['top_campaigns'] = $top_campaigns;

			return rest_ensure_response( $response );
		}

		public function get_top_funnels( $request ) {

			if ( isset( $request['overall'] ) ) {
				$start_date = '';
				$end_date   = '';
			} else {
				$start_date = ( isset( $request['after'] ) && '' !== $request['after'] ) ? $request['after'] : self::default_date( WEEK_IN_SECONDS )->format( self::$sql_datetime_format );
				$end_date   = ( isset( $request['before'] ) && '' !== $request['before'] ) ? $request['before'] : self::default_date()->format( self::$sql_datetime_format );
			}
			$limit = isset( $request['top_funnels_limit'] ) ? $request['top_funnels_limit'] : ( isset( $request['limit'] ) ? $request['limit'] : 5 );

			$limit_str = " LIMIT 0, " . $limit . ' ';

			global $wpdb;
			$sales_funnels = [];
			$lead_funnels  = [];
			$all_funnels   = array(
				'sales' => array(),
				'lead'  => array()
			);

			/**
			 * get all sales funnel data
			 */

			$funnel_count = "SELECT COUNT( id) AS total_count FROM " . $wpdb->prefix . "bwf_funnels WHERE steps LIKE '%wc_%'";
			$funnel_count = $wpdb->get_var( $funnel_count );//phpcs:ignore

			if ( ! empty( $funnel_count ) && absint( $funnel_count ) > 0 ) {
				/**
				 * get all funnel conversion from conversion table order by top conversion table
				 */
				$report_range = ( '' !== $start_date && '' !== $end_date ) ? " AND conv.timestamp >= '" . $start_date . "' AND conv.timestamp < '" . $end_date . "' " : '';

				$f_query = "SELECT funnel.id as fid, funnel.title as title, SUM( COALESCE(conv.value, 0) ) as total, 0 as views, COUNT(conv.ID) as conversion, 0 as conversion_rate
FROM " . $wpdb->prefix . "bwf_funnels AS funnel LEFT JOIN " . $wpdb->prefix . "bwf_conversion_tracking AS conv ON funnel.id = conv.funnel_id  AND conv.type = 2 " . $report_range . "
WHERE 1=1 AND funnel.steps LIKE '%wc_%' GROUP BY funnel.id ORDER BY SUM( conv.value ) DESC " . $limit_str;

				$get_funnels = $wpdb->get_results( $f_query, ARRAY_A ); //phpcs:ignore
				if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
					$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( false === $db_error['db_error'] ) {
						$sales_funnels = $get_funnels;
					}
				}

				/**
				 * calculate total funnels revenue
				 */
				if ( is_array( $sales_funnels ) && count( $sales_funnels ) > 0 ) {

					/**
					 *  get funnel unique views and conversion rate
					 */
					$sales_funnels = $this->get_funnel_views_data( $sales_funnels, $start_date, $end_date );

					$all_funnels['sales'] = $sales_funnels;
				}
			}


			/**
			 * get all lead funnel data
			 */
			$lead_count = "SELECT COUNT( id) AS total_count FROM " . $wpdb->prefix . "bwf_funnels WHERE steps NOT LIKE '%wc_%'";
			$lead_count = $wpdb->get_var( $lead_count );//phpcs:ignore

			if ( ! empty( $lead_count ) && absint( $lead_count ) > 0 ) {

				/**
				 * get all funnel conversion from conversion table order by top conversion table
				 */
				$report_range = ( '' !== $start_date && '' !== $end_date ) ? " AND conv.timestamp >= '" . $start_date . "' AND conv.timestamp < '" . $end_date . "' " : '';

				$l_query = "SELECT funnel.id as fid, funnel.title as title, 0 as total, 0 as views, COUNT(conv.id) as conversion, 0 as conversion_rate
FROM " . $wpdb->prefix . "bwf_funnels AS funnel LEFT JOIN " . $wpdb->prefix . "bwf_conversion_tracking AS conv ON funnel.id = conv.funnel_id AND conv.type = 1 " . $report_range . "
WHERE 1=1 AND funnel.steps NOT LIKE '%wc_%' GROUP BY funnel.id ORDER BY COUNT(conv.id) DESC " . $limit_str;

				$l_funnels = $wpdb->get_results( $l_query, ARRAY_A ); //phpcs:ignore
				if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
					$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( false === $db_error['db_error'] ) {
						$lead_funnels = $l_funnels;
					}
				}

				/**
				 * get all funnels by optin entries if deleted funnel exists
				 */
				if ( is_array( $lead_funnels ) && count( $lead_funnels ) > 0 ) {

					/**
					 *  get funnel unique views and conversion rate
					 */
					$lead_funnels = $this->get_funnel_views_data( $lead_funnels, $start_date, $end_date );


					$all_funnels['lead'] = $lead_funnels;
				}

			}

			return $all_funnels;

		}

		public function get_funnel_views_data( $funnels, $start_date, $end_date ) {
			global $wpdb;

			$ids = array_unique( wp_list_pluck( $funnels, 'fid' ) );

			$report_range = ( '' !== $start_date && '' !== $end_date ) ? " AND date >= '" . $start_date . "' AND date < '" . $end_date . "' " : '';
			$view_query   = "SELECT object_id as fid , SUM(COALESCE(no_of_sessions, 0)) AS views FROM " . $wpdb->prefix . "wfco_report_views WHERE type = 7 AND object_id IN (" . implode( ',', $ids ) . ") " . $report_range . " GROUP BY object_id";
			$report_data  = $wpdb->get_results( $view_query, ARRAY_A ); //phpcs:ignore
			if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
				$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
				if ( false === $db_error['db_error'] ) {
					if ( is_array( $report_data ) && count( $report_data ) > 0 ) {
						/**
						 * prepare data for sales funnels and add views and conversion
						 */
						$funnels = array_map( function ( $item ) use ( $report_data ) {
							$search_view = array_search( intval( $item['fid'] ), array_map( 'intval', wp_list_pluck( $report_data, 'fid' ) ), true );
							if ( false !== $search_view && isset( $report_data[ $search_view ]['views'] ) && absint( $report_data[ $search_view ]['views'] ) > 0 ) {
								$item['views']           = absint( $report_data[ $search_view ]['views'] );
								$item['conversion_rate'] = $this->get_percentage( absint( $item['views'] ), $item['conversion'] );
							} else {
								$item['views']           = '0';
								$item['conversion']      = '0';
								$item['conversion_rate'] = '0';
							}

							return $item;
						}, $funnels );
					}
				}
			}

			return $funnels;

		}

		public function get_timeline_funnels() {
			global $wpdb;
			$conv_table    = $wpdb->prefix . "bwf_conversion_tracking";
			$contact_table = $wpdb->prefix . "bwf_contact";
			$final_q       = "SELECT conv.*, coalesce(contact.f_name, '') as f_name, coalesce(contact.l_name, '') as l_name FROM " . $conv_table . " as conv LEFT JOIN " . $contact_table . " AS contact ON contact.id=conv.contact_id WHERE contact.id != '' AND conv.funnel_id != '' AND ( conv.type != '' AND conv.type IS NOT NULL ) ORDER BY conv.timestamp DESC LIMIT 20";

			$get_results = $wpdb->get_results( $final_q, ARRAY_A ); //phpcs:ignore
			$steps       = [];
			if ( is_array( $get_results ) && count( $get_results ) > 0 ) {
				foreach ( $get_results as $result ) {
					if ( 20 === count( $steps ) ) {
						break;
					}
					$step = [
						'fid'             => $result['funnel_id'],
						'cid'             => $result['contact_id'],
						'f_name'          => $result['f_name'],
						'l_name'          => $result['l_name'],
						'step_id'         => 0,
						'post_title'      => '',
						'order_id'        => $result['source'],
						'id'              => $result['step_id'],
						'tot'             => $result['value'],
						'type'            => '',
						'date'            => $result['timestamp'],
						'order_edit_link' => '',
						'edit_link'       => '',

					];
					if ( 2 === absint( $result['type'] ) ) {
						if ( empty( $result['checkout_total'] ) && ! empty( $result['offer_accepted'] ) && '[]' !== $result['offer_accepted'] ) {
							$steps = $this->maybe_add_offer( $steps, $result, $step );
							continue;
						}

						$step['type'] = 'aero';
						$step['tot']  = $result['checkout_total'];
						$steps[]      = $step;
						$steps        = $this->maybe_add_offer( $steps, $result, $step );
						$steps        = $this->maybe_add_bump( $steps, $result, $step );

					} else if ( 1 === absint( $result['type'] ) ) {
						$step['tot']  = '';
						$step['type'] = 'optin';
						$steps[]      = $step;
					}


				}
			}
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return rest_ensure_response( $db_error );
			}

			if ( ! is_array( $steps ) || count( $steps ) === 0 ) {
				return rest_ensure_response( [] );
			}

			foreach ( $steps as &$step ) {
				if ( isset( $step['id'] ) && isset( $step['type'] ) ) {
					$step['edit_link'] = WFFN_Common::get_step_edit_link( $step['id'], $step['type'], $step['fid'], true );
				}
				$step['post_title'] = ( isset( $step['id'] ) && absint( $step['id'] ) > 0 ) ? get_the_title( $step['id'] ) : '';

				if ( isset( $step['order_id'] ) ) {
					if ( wffn_is_wc_active() ) {
						$order = wc_get_order( $step['order_id'] );
						if ( $order instanceof WC_Order ) {
							if ( absint( $step['fid'] ) === WFFN_Common::get_store_checkout_id() ) {
								$step['order_edit_link'] = WFFN_Common::get_store_checkout_edit_link( '/orders' );
							} else {
								$step['order_edit_link'] = WFFN_Common::get_funnel_edit_link( $step['fid'], '/orders' );
							}
						} else {
							$step['order_edit_link'] = '';
						}
					} else {
						$step['order_edit_link'] = '';
					}

				}
			}

			return rest_ensure_response( $steps );

		}

		public function maybe_add_offer( $steps, $result, $step ) {
			if ( ! empty( $result['offer_accepted'] ) && '[]' !== $result['offer_accepted'] ) {
				$accepted_offer = json_decode( $result['offer_accepted'], true );
				if ( is_array( $accepted_offer ) && count( $accepted_offer ) > 0 ) {
					foreach ( $accepted_offer as $offer_id ) {
						$step['tot']  = $this->get_single_offer_value( $offer_id, $result['source'] );
						$step['type'] = 'upsell';
						$step['id']   = $offer_id;
						$steps[]      = $step;
					}
				}
			}

			return $steps;
		}

		public function maybe_add_bump( $steps, $result, $step ) {
			if ( ! empty( $result['bump_accepted'] ) && '[]' !== $result['bump_accepted'] ) {
				$accepted_bump = json_decode( $result['bump_accepted'], true );
				if ( is_array( $accepted_bump ) && count( $accepted_bump ) > 0 ) {


					foreach ( $accepted_bump as $bump_id ) {

						$step['tot']  = $this->get_single_bump_value( $bump_id, $result['source'] );
						$step['type'] = 'bump';
						$step['id']   = $bump_id;
						$steps[]      = $step;
					}
				}
			}

			return $steps;
		}


		public function get_single_offer_value( $offer_id, $order_id ) {
			global $wpdb;
			if ( ! class_exists( 'WFOCU_Core' ) ) {
				return 0;
			}
			$get_revenue = $wpdb->get_var( $wpdb->prepare( "SELECT CONVERT( stats.value USING utf8) as 'value' FROM " . $wpdb->prefix . "wfocu_session AS sess LEFT JOIN " . $wpdb->prefix . "wfocu_event AS stats ON stats.sess_id=sess.id where stats.object_id = %d AND stats.action_type_id = %d AND sess.order_id = %s", absint( $offer_id ), 4, $order_id ) );

			if ( ! empty( $get_revenue ) ) {
				return $get_revenue;
			}

			return 0;

		}

		public function get_single_bump_value( $bump_id, $order_id ) {
			global $wpdb;
			if ( ! class_exists( 'WFOB_Core' ) ) {
				return 0;
			}
			$get_revenue = $wpdb->get_var( $wpdb->prepare( "SELECT CONVERT( stats.total USING utf8) as 'value' FROM " . $wpdb->prefix . "wfob_stats AS stats where stats.converted= %d AND stats.bid = %d AND stats.oid = %d ", 1, absint( $bump_id ), $order_id ) );

			if ( ! empty( $get_revenue ) ) {
				return $get_revenue;
			}

			return 0;

		}

		public function get_total_orders( $funnel_id, $start_date, $end_date, $is_interval = '', $int_request = '' ) {
			global $wpdb;


			$funnel_id      = empty( $funnel_id ) ? 0 : (int) $funnel_id;
			$table          = $wpdb->prefix . 'bwf_conversion_tracking';
			$date_col       = "tracking.timestamp";
			$interval_query = '';
			$group_by       = '';
			$limit          = '';
			$intervals      = [];
			$funnel_query   = ( 0 === intval( $funnel_id ) ) ? " AND tracking.funnel_id != " . $funnel_id . " " : " AND tracking.funnel_id = " . $funnel_id . " ";

			if ( 'interval' === $is_interval ) {
				$get_interval   = $this->get_interval_format_query( $int_request, $date_col );
				$interval_query = $get_interval['interval_query'];
				$interval_group = $get_interval['interval_group'];
				$group_by       = " GROUP BY " . $interval_group;

			}

			$date = ( '' !== $start_date && '' !== $end_date ) ? " AND " . $date_col . " >= '" . $start_date . "' AND " . $date_col . " < '" . $end_date . "' " : '';

			$total_orders = $wpdb->get_results( "SELECT count(DISTINCT tracking.source) as total_orders " . $interval_query . "  FROM `" . $table . "` as tracking JOIN `" . $wpdb->prefix . "bwf_contact` as cust ON cust.id=tracking.contact_id WHERE 1=1 AND tracking.type=2 " . $date . $funnel_query . $group_by . " ORDER BY tracking.id ASC $limit", ARRAY_A );//phpcs:ignore
			if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
				$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
				if ( true === $db_error['db_error'] ) {
					WFFN_Core()->logger->log( 'failed fetch data #' . print_r( $db_error, true ), 'wffn-failed-actions', true ); // phpcs:ignore

					return 0;
				}
			}

			if ( is_array( $total_orders ) && count( $total_orders ) > 0 ) {
				if ( 'interval' === $is_interval ) {
					$intervals = ( is_array( $total_orders ) && count( $total_orders ) > 0 ) ? $total_orders : [];
				} else {
					$total_orders = isset( $total_orders[0]['total_orders'] ) ? absint( $total_orders[0]['total_orders'] ) : 0;
				}
			}

			return ( 'interval' === $is_interval ) ? $intervals : $total_orders;
		}


		public function get_total_revenue( $funnel_id, $start_date, $end_date, $is_interval = '', $int_request = '' ) {

			/**
			 * get revenue
			 */ global $wpdb;
			$total_revenue_aero    = [];
			$total_revenue_bump    = [];
			$total_revenue_upsells = [];

			/**
			 * get revenue
			 */
			$table          = $wpdb->prefix . 'bwf_conversion_tracking';
			$date_col       = "conv.timestamp";
			$interval_query = '';
			$group_by       = '';
			$funnel_query   = ( 0 === intval( $funnel_id ) ) ? " AND conv.funnel_id != " . $funnel_id . " " : " AND conv.funnel_id = " . $funnel_id . " ";

			if ( 'interval' === $is_interval ) {
				$get_interval   = $this->get_interval_format_query( $int_request, $date_col );
				$interval_query = $get_interval['interval_query'];
				$interval_group = $get_interval['interval_group'];
				$group_by       = " GROUP BY " . $interval_group;

			}

			$date = ( '' !== $start_date && '' !== $end_date ) ? " AND " . $date_col . " >= '" . $start_date . "' AND " . $date_col . " < '" . $end_date . "' " : '';

			if ( class_exists( 'WFACP_Core' ) ) {
				$query              = "SELECT SUM(conv.value) as total, SUM(conv.checkout_total) as sum_aero " . $interval_query . "  FROM `" . $table . "` as conv WHERE 1=1 " . $date . $funnel_query . $group_by . " ORDER BY conv.id ASC";
				$total_revenue_aero = $wpdb->get_results( $query, ARRAY_A );//phpcs:ignore
				if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
					$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( true === $db_error['db_error'] ) {
						$total_revenue_aero = [];
						WFFN_Core()->logger->log( 'failed fetch data #' . print_r( $db_error, true ), 'wffn-failed-actions', true ); // phpcs:ignore
					}
				}
			}

			if ( class_exists( 'WFOB_Core' ) ) {
				$query              = "SELECT SUM(conv.bump_total) as sum_bump " . $interval_query . "  FROM `" . $table . "` as conv WHERE 1=1 " . $date . $funnel_query . $group_by . " ORDER BY conv.id ASC";
				$total_revenue_bump = $wpdb->get_results( $query, ARRAY_A );//phpcs:ignore
				if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
					$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( true === $db_error['db_error'] ) {
						$total_revenue_bump = [];
						WFFN_Core()->logger->log( 'failed fetch data #' . print_r( $db_error, true ), 'wffn-failed-actions', true ); // phpcs:ignore
					}
				}
			}

			if ( class_exists( 'WFOCU_Core' ) ) {
				$query                 = "SELECT SUM(conv.offer_total) as sum_upsells " . $interval_query . "  FROM `" . $table . "` as conv WHERE 1=1 " . $date . $funnel_query . $group_by . " ORDER BY conv.id ASC";
				$total_revenue_upsells = $wpdb->get_results( $query, ARRAY_A );//phpcs:ignore
				if ( method_exists( 'WFFN_Common', 'maybe_wpdb_error' ) ) {
					$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
					if ( true === $db_error['db_error'] ) {
						$total_revenue_upsells = [];
						WFFN_Core()->logger->log( 'failed fetch data #' . print_r( $db_error, true ), 'wffn-failed-actions', true ); // phpcs:ignore
					}
				}
			}

			return array( 'aero' => $total_revenue_aero, 'bump' => $total_revenue_bump, 'upsell' => $total_revenue_upsells );
		}


		/**
		 * @param $funnel_id
		 * @param $start_date
		 * @param $end_date
		 * @param $is_interval
		 * @param $int_request
		 *
		 * @return array|object|stdClass[]
		 */
		public function get_total_contacts( $funnel_id, $start_date, $end_date, $is_interval = '', $int_request = '' ) {
			global $wpdb;
			$table          = $wpdb->prefix . 'bwf_conversion_tracking';
			$date_col       = "timestamp";
			$interval_query = '';
			$group_by       = '';
			$funnel_query   = ( 0 === intval( $funnel_id ) ) ? " AND funnel_id != " . $funnel_id . " " : " AND funnel_id = " . $funnel_id . " ";

			if ( 'interval' === $is_interval ) {
				$get_interval   = $this->get_interval_format_query( $int_request, $date_col );
				$interval_query = $get_interval['interval_query'];
				$interval_group = $get_interval['interval_group'];
				$group_by       = " GROUP BY " . $interval_group;
			}

			$date = ( '' !== $start_date && '' !== $end_date ) ? " AND `timestamp` >= '" . $start_date . "' AND `timestamp` < '" . $end_date . "' " : '';

			$query        = "SELECT COUNT( DISTINCT contact_id ) as contacts " . $interval_query . " FROM `" . $table . "` WHERE 1=1 " . $date . " " . $funnel_query . " " . $group_by;
			$get_contacts = $wpdb->get_results( $query, ARRAY_A );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( is_array( $get_contacts ) && count( $get_contacts ) > 0 ) {
				return $get_contacts;
			}

			return [];
		}

		public function get_all_source_data( $request ) {
			$resp = array(
				'status' => false,
				'msg'    => __( 'failed', 'funnel-builder' ),
				'data'   => [
					'sales' => [],
					'lead'  => []
				]
			);

			if ( isset( $request['overall'] ) ) {
				$start_date = '';
				$end_date   = '';
			} else {
				$start_date = ( isset( $request['after'] ) && '' !== $request['after'] ) ? $request['after'] : self::default_date( WEEK_IN_SECONDS )->format( self::$sql_datetime_format );
				$end_date   = ( isset( $request['before'] ) && '' !== $request['before'] ) ? $request['before'] : self::default_date()->format( self::$sql_datetime_format );

			}

			$args = [
				'start_date' => $start_date,
				'end_date'   => $end_date,
			];

			$conv_data = apply_filters( 'wffn_source_data_by_conversion_query', [], $args );

			if ( is_array( $conv_data ) && count( $conv_data ) > 0 ) {
				$resp['data']['sales'] = $conv_data['sales'];
				$resp['data']['lead']  = $conv_data['lead'];
			}

			$resp['status'] = true;
			$resp['msg']    = __( 'success', 'funnel-builder' );

			return rest_ensure_response( $resp );
		}

		public function get_stats_collection() {
			$params = array();

			$params['after']  = array(
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'funnel-builder' ),
			);
			$params['before'] = array(
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'funnel-builder' ),
			);
			$params['limit']  = array(
				'type'              => 'integer',
				'default'           => 5,
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'funnel-builder' ),
			);

			return apply_filters( 'wfocu_rest_funnels_dashboard_stats_collection', $params );
		}

	}

	WFFN_REST_API_Dashboard_EndPoint::get_instance();
}
