<?php
/*
Plugin Name: Advanced Categories Display
Description: Displays product categories in list and grid views with shortcode
Version: 1.0
Author: Sujauddin Sekh
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ACD_VERSION', '1.0');
define('ACD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once ACD_PLUGIN_DIR . 'includes/class-acd-init.php';

// Initialize the plugin
function acd_initialize_plugin() {
    new ACD_Init();
}
add_action('plugins_loaded', 'acd_initialize_plugin');

// Activation hook
register_activation_hook(__FILE__, array('ACD_Init', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('ACD_Init', 'deactivate'));