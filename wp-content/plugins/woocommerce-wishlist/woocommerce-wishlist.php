<?php
/**
 * Plugin Name: WooCommerce Wishlist
 * Plugin URI: https://yourwebsite.com
 * Description: Adds wishlist functionality to WooCommerce products
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woocommerce-wishlist
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 7.0.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WCWL_VERSION', '1.0.0');
define('WCWL_PLUGIN_FILE', __FILE__);
define('WCWL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WCWL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
if (
    !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) &&
    !defined('WCWL_TESTING')
) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>' . esc_html__('WooCommerce Wishlist requires WooCommerce to be installed and active.', 'woocommerce-wishlist') . '</p></div>';
    });
    return;
}

// Autoload classes
spl_autoload_register(function($class) {
    $prefix = 'WC_Wishlist_';
    
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $file = WCWL_PLUGIN_PATH . 'includes/' . strtolower(str_replace(array($prefix, '_'), array('', '-'), $class)) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include required files
require_once WCWL_PLUGIN_PATH . 'includes/class-wishlist.php';
require_once WCWL_PLUGIN_PATH . 'includes/wishlist-ajax.php';
require_once WCWL_PLUGIN_PATH . 'includes/wishlist-shortcodes.php';
require_once WCWL_PLUGIN_PATH . 'includes/wishlist-template.php';

// Initialize the plugin
function wcwl_init() {
    WC_Wishlist::instance();
}
add_action('plugins_loaded', 'wcwl_init');

// Load text domain
function wcwl_load_textdomain() {
    load_plugin_textdomain('woocommerce-wishlist', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'wcwl_load_textdomain');

// Activation hook
register_activation_hook(__FILE__, array('WC_Wishlist', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('WC_Wishlist', 'deactivate'));