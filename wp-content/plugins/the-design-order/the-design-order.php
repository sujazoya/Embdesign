<?php
/**
 * Plugin Name: The Design Order
 * Plugin URI: https://example.com/the-design-order
 * Description: A WooCommerce extension for custom design orders with document uploads and designer proposals.
 * Version: 1.0.0
 * Author: sahir
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: the-design-order
 * Domain Path: /languages
 * WC requires at least: 5.0.0
 * WC tested up to: 7.0.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('THE_DESIGN_ORDER_VERSION', '1.0.0');
define('THE_DESIGN_ORDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THE_DESIGN_ORDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THE_DESIGN_ORDER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        ?>
        <div class="error notice">
            <p><?php _e('The Design Order requires WooCommerce to be installed and active!', 'the-design-order'); ?></p>
        </div>
        <?php
    });
    return;
}

// Manually include class files instead of autoloader
require_once THE_DESIGN_ORDER_PLUGIN_DIR . 'includes/class-design-order.php';
require_once THE_DESIGN_ORDER_PLUGIN_DIR . 'includes/class-design-order-post-types.php';
require_once THE_DESIGN_ORDER_PLUGIN_DIR . 'includes/class-design-order-admin.php';
require_once THE_DESIGN_ORDER_PLUGIN_DIR . 'includes/class-design-order-frontend.php';
require_once THE_DESIGN_ORDER_PLUGIN_DIR . 'includes/class-design-order-ajax.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Load textdomain
    load_plugin_textdomain('the-design-order', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    
    // Initialize classes
    TheDesignOrder\Design_Order_Post_Types::init();
    TheDesignOrder\Design_Order::init();
    TheDesignOrder\Design_Order_Admin::init();
    TheDesignOrder\Design_Order_Frontend::init();
    TheDesignOrder\Design_Order_Ajax::init();
});

// Activation and deactivation hooks
register_activation_hook(__FILE__, ['TheDesignOrder\\Design_Order', 'activate']);
register_deactivation_hook(__FILE__, ['TheDesignOrder\\Design_Order', 'deactivate']);