<?php
/**
 * Plugin Name: WooCommerce Wallet with PayU Integration
 * Plugin URI: https://yourwebsite.com/
 * Description: Adds wallet functionality with PayU payment gateway to WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * Text Domain: woocommerce-wallet-payu
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.0
 * WC requires at least: 4.0
 * WC tested up to: 6.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WC_WALLET_PAYU_VERSION', '1.0.0');
define('WC_WALLET_PAYU_PLUGIN_FILE', __FILE__);
define('WC_WALLET_PAYU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_WALLET_PAYU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_WALLET_PAYU_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        _e('WooCommerce Wallet with PayU requires WooCommerce to be installed and active!', 'woocommerce-wallet-payu');
        echo '</p></div>';
    });
    return;
}

// Include the main plugin class
require_once WC_WALLET_PAYU_PLUGIN_DIR . 'includes/class-wallet.php';

// Initialize the plugin
function init_woocommerce_wallet_payu() {
    return WC_Wallet_Payu::instance();
}
add_action('plugins_loaded', 'init_woocommerce_wallet_payu');

// Register activation hook
register_activation_hook(__FILE__, array('WC_Wallet_Payu', 'activate'));

// Register deactivation hook
register_deactivation_hook(__FILE__, array('WC_Wallet_Payu', 'deactivate'));