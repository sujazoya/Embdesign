<?php 
	if ( ! defined( 'ABSPATH' ) ) 
		exit;
	
function xyz_ihs_network_uninstall($networkwide) {
	global $wpdb;

	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			$old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				xyz_ihs_uninstall();
			}
			switch_to_blog($old_blog);
			return;
		}
	}
	xyz_ihs_uninstall();
}

function xyz_ihs_uninstall(){

global $wpdb;
delete_option("xyz_ihs_sort_order");
delete_option("xyz_ihs_sort_field_name");
delete_option("xyz_ihs_limit");
delete_option("xyz_ihs_installed_date");
delete_option('xyz_ihs_credit_dismiss');
delete_option('xyz_ihs_premium_version_ads');
delete_option('xyz_ihs_exec_in_editor');
delete_option('xyz_ihs_free_version');
/* table delete*/
$wpdb->query("DROP TABLE ".$wpdb->prefix."xyz_ihs_short_code");

}
register_uninstall_hook( XYZ_INSERT_HTML_PLUGIN_FILE, 'xyz_ihs_network_uninstall' );
?>
