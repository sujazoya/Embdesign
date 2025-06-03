<?php
/**
 * Plugin Name: Frontend Admin
 * Plugin URI: https://wordpress.org/plugins/acf-frontend-form-element/
 * Description: This awesome plugin allows you to easily display admin forms to the frontend of your site so your clients can easily edit content on their own from the frontend.
 * Version:     3.28.8
 * Author:      Shabti Kaplan
 * Author URI:  https://www.dynamiapps.com/
 * Text Domain: acf-frontend-form-element
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'feap_fs' ) ) {
	function feap_fs() {
		return false;
	}
}

if ( ! class_exists( 'Front_End_Admin' ) ) {
	if ( ! function_exists( 'fea_freemius' ) ) {
		function fea_freemius() {
			global $fea_freemius, $feap_freemius;

			if ( isset( $fea_freemius ) || isset( $feap_freemius ) ) {
				return $fea_freemius;
			}

			if ( ! defined( 'WP_FS__PRODUCT_5212_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_5212_MULTISITE', true );
			}

			require_once dirname( __FILE__ ) . '/main/freemius/start.php';
			$fea_freemius = fs_dynamic_init(
				array(
					'id'              => '5212',
					'slug'            => 'acf-frontend-form-element',
					'premium_slug'    => 'frontend-admin-pro',
					'type'            => 'plugin',
					'public_key'      => 'pk_771aff8259bcf0305b376eceb7637',
					'is_premium'      => false,
					'premium_suffix'  => 'Pro',
					'has_addons'      => false,
					'has_paid_plans'  => true,
					'trial'           => array(
						'days'               => 7,
						'is_require_payment' => true,
					),
					'has_affiliation' => false,
					'menu'            => array(
						'slug'        => 'fea-settings',
						'contact'     => false,
						'support'     => false,
						'affiliation' => false,
					),
				)
			);
			return $fea_freemius;
		}
		fea_freemius();
	}

	/**
	 * Main Frontend Admin Class
	 *
	 * The main class that initiates and runs the plugin.
	 *
	 * @since 1.0.0
	 */
	final class Front_End_Admin {


		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 */
		public function __construct() {
			add_action( 'after_setup_theme', array( $this, 'init' ), 11 );
		}



		/**
		 * Initialize the plugin
		 *
		 * Load the plugin only after ACF is loaded.
		 * Checks for basic plugin requirements, if one check fail don't continue,
		 * If all checks have passed load the files required to run the plugin.
		 *
		 * Fired by `plugins_loaded` action hook.
		 *
		 * @since 1.0.0
		 *
		 * @access public
		 */
		public function init() {
			if ( did_action( 'front_end_admin_pro_loaded' ) ) {
				return;
			}

			global $fea_instance;

			if ( isset( $fea_instance ) ) {
				return;
			}

			include_once 'main/plugin.php';
			$fea_instance = new \Frontend_Admin\Plugin(
				[
					'basename' => plugin_basename( __FILE__ ),
					'plugin_dir' => plugin_dir_path( __FILE__ ),
					'plugin_url' => plugin_dir_url( __FILE__ ),
					'plugin' => 'Free Version',
				]
			);

		}

	}
	new Front_End_Admin();

}
