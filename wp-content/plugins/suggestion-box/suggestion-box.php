<?php
/**
 * Plugin Name: Suggestion Box
 * Description: Advanced suggestion system for WordPress
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SUGGESTION_BOX_VERSION', '1.0.0');
define('SUGGESTION_BOX_PATH', plugin_dir_path(__FILE__));
define('SUGGESTION_BOX_URL', plugin_dir_url(__FILE__));
define('SUGGESTION_BOX_TABLE', $wpdb->prefix . 'suggestion_box');  // Fixed constant name

// Check if classes exist before loading
if (!class_exists('Suggestion_Box_DB')) {
    require_once SUGGESTION_BOX_PATH . 'includes/class-suggestion-db.php';
}

if (!class_exists('Suggestion_Box_Frontend')) {
    require_once SUGGESTION_BOX_PATH . 'includes/class-suggestion-frontend.php';
}

if (!class_exists('Suggestion_Box_Admin')) {
    require_once SUGGESTION_BOX_PATH . 'includes/class-suggestion-admin.php';
}

// Initialize the plugin
function suggestion_box_init() {
    global $wpdb;
    
    // Initialize database handler
    $db = new Suggestion_Box_DB();
    $db->create_table();
    
    // Initialize frontend
    new Suggestion_Box_Frontend();
    
    // Initialize admin
    if (is_admin()) {
        new Suggestion_Box_Admin();
    }
}
add_action('plugins_loaded', 'suggestion_box_init');

// Activation and deactivation hooks
register_activation_hook(__FILE__, ['Suggestion_Box_DB', 'activate']);
register_deactivation_hook(__FILE__, ['Suggestion_Box_DB', 'deactivate']);