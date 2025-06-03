<?php
/*
Plugin Name: Advanced Product Upload
Description: Allow sellers to upload products with drag & drop, multiple files, and AJAX handling.
Version: 1.0.0
Author: Sujauddin Sekh
Text Domain: advanced-product-upload
Domain Path: /languages
*/

defined('ABSPATH') || exit;

// Define plugin constants
define('APU_VERSION', '1.0.0');
define('APU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APU_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/apu-uploads/');
define('APU_MAX_FILES', 10);
define('APU_MAX_FILE_SIZE', wp_max_upload_size());

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'apu_activate_plugin');
register_deactivation_hook(__FILE__, 'apu_deactivate_plugin');

function apu_activate_plugin() {
    // Create upload directory if it doesn't exist
    if (!file_exists(APU_UPLOAD_DIR)) {
        wp_mkdir_p(APU_UPLOAD_DIR);
    }
    
    // Add default options
    add_option('apu_allowed_file_types', [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg'],
        'design' => ['emb', 'dst', 'pes', 'svg', 'eps'],
        'archive' => ['zip', 'rar']
    ]);
}

function apu_deactivate_plugin() {
    // Cleanup if needed
}

// Load required files
require_once APU_PLUGIN_DIR . 'includes/class-file-uploader.php';
require_once APU_PLUGIN_DIR . 'includes/class-product-handler.php';

// Initialize classes
APU_File_Uploader::init();
APU_Product_Handler::init();

// Load text domain
add_action('plugins_loaded', function() {
    load_plugin_textdomain('advanced-product-upload', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=advanced-product-upload') . '">' . __('Settings', 'advanced-product-upload') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});