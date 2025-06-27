<?php
/**
 * Plugin Name: Ultimate Auth System
 * Description: Complete login and registration system with Google Sign-In
 * Version: 1.0.0
 * Author: Your Name
 */

defined('ABSPATH') || exit;

// Define constants
define('UAS_PATH', plugin_dir_path(__FILE__));
define('UAS_URL', plugin_dir_url(__FILE__));
define('UAS_VERSION', '1.0.0');

// Load required files
require_once UAS_PATH . 'includes/class-auth-handler.php';
require_once UAS_PATH . 'includes/class-google-auth.php';
require_once UAS_PATH . 'includes/class-shortcodes.php';
require_once UAS_PATH . 'includes/class-styles-scripts.php';

class Ultimate_Auth_System {
    
    private static $instance = null;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize components
        UAS_Auth_Handler::init();
        UAS_Google_Auth::init();
        UAS_Shortcodes::init();
        UAS_Styles_Scripts::init();
        
        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('ultimate-auth', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// Initialize the plugin
Ultimate_Auth_System::init();