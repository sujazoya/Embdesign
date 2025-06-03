<?php
defined( 'ABSPATH' ) || exit; //Exit if accessed directly

/**
 * Class WFOB_Contacts_Analytics
 */
if ( ! class_exists( 'WFOB_Contacts_Analytics' ) ) {

	class WFOB_Contacts_Analytics {

		/**
		 * instance of class
		 * @var null
		 */
		private static $ins = null;

		/**
		 * WFOB_Contacts_Analytics constructor.
		 */
		public function __construct() {
		}

		/**
		 * @return WFOB_Contacts_Analytics|null
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		/**
		 * @param $funnel_id
		 * @param $cid
		 *
		 * @return array|object|null
		 */
		public function get_all_contacts_records( $funnel_id, $cid ) {
			global $wpdb;
			$item_data = [];
			$query     = "SELECT bump.oid as order_id, bump.bid as 'object_id', bump.iid as 'item_ids', bump.total as 'total_revenue',p.post_title as 'object_name', bump.converted as 'is_converted',DATE_FORMAT(bump.date, '%Y-%m-%dT%TZ') as 'date','bump' as 'type' FROM " . $wpdb->prefix . 'wfob_stats' . " AS bump LEFT JOIN " . $wpdb->prefix . 'posts' . " as p ON bump.bid  = p.id  WHERE bump.converted = 1 AND bump.fid=$funnel_id AND bump.cid=$cid order by bump.date asc";

			$order_data = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error   = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			if ( ! is_array( $order_data ) || count( $order_data ) === 0 ) {
				return $item_data;
			}

			$all_item_ids = [];

			/** merge all items ids in one array */
			if ( is_array( $order_data ) && count( $order_data ) > 0 ) {
				foreach ( $order_data as &$i_array ) {
					$i_array->item_ids = ( isset( $i_array->item_ids ) && '' != $i_array->item_ids ) ? json_decode( $i_array->item_ids ) : [];
					if ( is_array( $i_array->item_ids ) && count( $i_array->item_ids ) > 0 ) {
						$all_item_ids = array_merge( $all_item_ids, $i_array->item_ids );
					}
				}
			}

			if ( is_array( $all_item_ids ) && count( $all_item_ids ) > 0 ) {
				/**
				 * get order item product name and quantity by items ids
				 */
				$item_query = "SELECT oi.order_item_id as 'item_id', oi.order_item_name as 'product_name', oim.meta_value as 'qty' FROM " . $wpdb->prefix . "woocommerce_order_items as oi LEFT JOIN " . $wpdb->prefix . "woocommerce_order_itemmeta as oim ON oi.order_item_id = oim.order_item_id WHERE oi.order_item_id IN (" . implode( ',', $all_item_ids ) . ") AND oi.order_item_type = 'line_item' AND oim.meta_key = '_qty' GROUP BY oi.order_item_id";
				$item_data  = $wpdb->get_results( $item_query ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$db_error   = WFFN_Common::maybe_wpdb_error( $wpdb );
				if ( true === $db_error['db_error'] ) {
					return $db_error;
				}
			}

			foreach ( $order_data as &$order ) {
				$product_titles = [];
				$qty            = 0;
				if ( is_array( $order->item_ids ) && count( $order->item_ids ) > 0 && is_array( $item_data ) && count( $item_data ) > 0 ) {
					foreach ( $order->item_ids as $item_id ) {
						$search = array_search( intval( $item_id ), array_map( 'intval', wp_list_pluck( $item_data, 'item_id' ) ), true );
						if ( false !== $search && isset( $item_data[ $search ] ) ) {
							$product_titles[] = $item_data[ $search ]->product_name;
							$qty              += absint( $item_data[ $search ]->qty );
						}
					}

				}
				unset( $order->item_ids );
				$order->product_name = implode( ', ', $product_titles );
				$order->product_qty  = $qty;
			}

			return $order_data;
		}

		public function get_contacts_revenue_records( $cid, $order_ids ) {
			global $wpdb;
			$query = "SELECT bump.fid as fid, bump.oid as order_id, bump.bid as 'object_id',bump.total as 'total_revenue',p.post_title as 'object_name', bump.converted as 'is_converted',DATE_FORMAT(bump.date, '%Y-%m-%d %T') as 'date','bump' as 'type' FROM " . $wpdb->prefix . 'wfob_stats' . " AS bump LEFT JOIN " . $wpdb->prefix . 'posts' . " as p ON bump.bid  = p.id  WHERE bump.converted = 1 AND bump.oid IN ( $order_ids ) AND bump.cid=$cid order by bump.date asc";

			$data     = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;

		}

		public function get_bumps_by_order_id( $order_id ) {
			global $wpdb;

			$query    = "SELECT bump.bid as 'id', p.post_title as 'bump_name', '' as 'bump_products', if(bump.converted=1, 'Yes', 'No') as 'bump_converted', bump.oid as 'bump_order_id', bump.total as 'bump_total' FROM " . $wpdb->prefix . 'wfob_stats' . " AS bump LEFT JOIN " . $wpdb->prefix . 'posts' . " as p ON bump.bid  = p.id WHERE  bump.oid='{$order_id}' order by bump.date asc";
			$data     = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;

		}


		/**
		 * @param $cid
		 *
		 * @return array|object|null
		 */
		public function get_all_contact_record_by_cid( $cid ) {
			global $wpdb;
			$query = "SELECT stats.oid as order_id, bump.bid as 'object_id',bump.total as 'total_revenue',p.post_title as 'object_name', bump.converted as 'is_converted',DATE_FORMAT(bump.date, '%Y-%m-%dT%TZ') as 'date','bump' as 'type' FROM " . $wpdb->prefix . 'wfob_stats' . " AS bump LEFT JOIN " . $wpdb->prefix . 'posts' . " as p ON bump.bid  = p.id LEFT JOIN " . $wpdb->prefix . 'wfob_stats' . " as stats on stats.bid = bump.bid WHERE bump.converted = 1 AND bump.cid=$cid order by bump.date asc";

			$data     = $wpdb->get_results( $query ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			if ( ! empty( $data[0]->order_id ) ) {
				$order_products = ! empty( wc_get_order( $data[0]->order_id ) ) ? wffn_rest_funnel_modules()->get_first_item( $data[0]->order_id ) : [];
				if ( ! empty( $order_products ) ) {
					$data[0]->product_name = $order_products['title'];
					$data[0]->product_qty  = $order_products['more'];
				}
			} else if ( ! empty( $data[0] ) ) {
				$data[0]->product_name = '';
				$data[0]->product_qty  = '';
			}

			return $data;

		}



		/**
		 * @param $limit
		 * @param string $order
		 * @param string $order_by
		 *
		 * @return string
		 */
		public function get_timeline_data_query( $limit, $order = 'DESC', $order_by = 'date' ) {
			global $wpdb;
			$limit = ( $limit !== '' ) ? " LIMIT " . $limit : '';

			return "SELECT stats.bid as id, stats.fid as 'fid', stats.cid as 'cid', oid as 'order_id', CONVERT( stats.total USING utf8) as 'total_revenue', 'bump' as 'type', posts.post_title as 'post_title', stats.date as date FROM " . $wpdb->prefix . "wfob_stats AS stats LEFT JOIN " . $wpdb->prefix . "posts AS posts ON stats.bid=posts.ID WHERE stats.converted = 1 ORDER BY " . $order_by . " " . $order . " " . $limit;

		}

		/**
		 * @param $limit
		 * @param $date_query
		 *
		 * @return array|false[]|object|stdClass[]|null
		 */
		public function get_top_funnels( $limit = '', $date_query = '' ) {
			global $wpdb;
			$limit      = ( $limit !== '' ) ? " LIMIT " . $limit : '';
			$date_query = str_replace( '{{COLUMN}}', 'wfob_stats.date', $date_query );
			$query      = "SELECT funnel.id as fid, funnel.title as title, stats.total as total FROM " . $wpdb->prefix . "bwf_funnels AS funnel 
    				JOIN ( SELECT fid, SUM( total ) as total FROM " . $wpdb->prefix . "wfob_stats as wfob_stats 
    				WHERE fid != 0 AND converted = 1 AND " . $date_query . "  GROUP BY fid ) as stats ON funnel.id = stats.fid WHERE 1 = 1 GROUP BY funnel.id ORDER BY total DESC " . $limit;

			$data     = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return $data;
		}

		/**
		 * @param $cids
		 * @param $funnel_id
		 *
		 * @return array|false[]|true
		 */
		public function delete_contact( $cids, $funnel_id = 0 ) {
			global $wpdb;
			$cid_count                = count( $cids );
			$stringPlaceholders       = array_fill( 0, $cid_count, '%s' );
			$placeholdersForFavFruits = implode( ',', $stringPlaceholders );

			$funnel_query = ( absint( $funnel_id ) > 0 ) ? " AND fid = " . $funnel_id . " " : '';
			$query        = "DELETE FROM " . $wpdb->prefix . "wfob_stats WHERE cid IN (" . $placeholdersForFavFruits . ") " . $funnel_query;

			$wpdb->query( $wpdb->prepare( $query, $cids ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$db_error = WFFN_Common::maybe_wpdb_error( $wpdb );
			if ( true === $db_error['db_error'] ) {
				return $db_error;
			}

			return true;

		}

		/**
		 * @param $funnel_id
		 */
		public function reset_analytics( $funnel_id ) {
			global $wpdb;
			$query = "DELETE FROM " . $wpdb->prefix . "wfob_stats WHERE fid=" . $funnel_id;
			$wpdb->query( $query );
		}
	}
}