<?php
/**
 * Plugin Name: Seller Profile
 * Description: A complete seller profile system for WordPress/WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: seller-profile
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('SELLER_PROFILE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SELLER_PROFILE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SELLER_PROFILE_VERSION', '1.0.0');

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        _e('Seller Profile plugin requires WooCommerce to be installed and active!', 'seller-profile');
        echo '</p></div>';
    });
    return;
}

// Load plugin files
require_once SELLER_PROFILE_PLUGIN_PATH . 'includes/shortcodes.php';
require_once SELLER_PROFILE_PLUGIN_PATH . 'includes/ajax-handlers.php';
require_once SELLER_PROFILE_PLUGIN_PATH . 'includes/template.php';
require_once SELLER_PROFILE_PLUGIN_PATH . 'includes/enqueue.php';

// Load text domain
add_action('plugins_loaded', function() {
    load_plugin_textdomain('seller-profile', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Register activation hook
register_activation_hook(__FILE__, function() {
    // Create seller profile page if it doesn't exist
    if (!get_page_by_path('seller-profile')) {
        $page = array(
            'post_title'    => __('Seller Profile', 'seller-profile'),
            'post_name'     => 'seller-profile',
            'post_content'  => '[seller_profile]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1
        );
        wp_insert_post($page);
    }
});