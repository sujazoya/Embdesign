<?php 
/*
Plugin Name: Insert HTML Snippet
Plugin URI: http://xyzscripts.com/wordpress-plugins/insert-html-snippet/
Description: Add HTML code to your pages and posts easily using shortcodes. This plugin lets you create a shortcode corresponding to any random HTML code such as ad codes, javascript, video embedding, etc. and use the same in your posts, pages or widgets.It also includes flexible snippet placement options: Automatic and Manual Shortcode.
Version: 1.4.1
Author: xyzscripts.com
Author URI: http://xyzscripts.com/
Text Domain: insert-html-snippet
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) 
	exit;

 
// error_reporting(E_ALL);

define('XYZ_INSERT_HTML_PLUGIN_FILE',__FILE__);

require( dirname( __FILE__ ) . '/xyz-functions.php' );

require( dirname( __FILE__ ) . '/add_shortcode_tynimce.php' );

require( dirname( __FILE__ ) . '/admin/install.php' );

require( dirname( __FILE__ ) . '/admin/menu.php' );

require( dirname( __FILE__ ) . '/shortcode-handler.php' );

require( dirname( __FILE__ ) . '/ajax-handler.php' );

require( dirname( __FILE__ ) . '/admin/uninstall.php' );

require( dirname( __FILE__ ) . '/widget.php' );

require( dirname( __FILE__ ) . '/direct_call.php' );

require( dirname( __FILE__ ) . '/admin/admin-notices.php' );

if(get_option('xyz_credit_link')=="ihs"){

	add_action('wp_footer', 'xyz_ihs_credit');

}
function xyz_ihs_credit() {	
	$content = '<div style="width:100%;text-align:center; font-size:11px; clear:both"><a target="_blank" title="Insert HTML Snippet Wordpress Plugin" href="http://xyzscripts.com/wordpress-plugins/insert-html-snippet/">HTML Snippets</a> Powered By : <a target="_blank" title="PHP Scripts & Wordpress Plugins" href="http://www.xyzscripts.com" >XYZScripts.com</a></div>';
	echo $content;
}
add_action('admin_init', 'xyz_ihs_check_and_upgrade_plugin_version');

function xyz_ihs_check_and_upgrade_plugin_version() {
	$current_version = xyz_ihs_plugin_get_version();
	$saved_version   = get_option('xyz_ihs_free_version');
	if ($saved_version === false) {
		xyz_ihs_run_upgrade_routines();
		add_option('xyz_ihs_free_version', $current_version);
	} elseif (version_compare($current_version, $saved_version, '>')) {
		xyz_ihs_run_upgrade_routines();
		update_option('xyz_ihs_free_version', $current_version);
	}
}
?>
