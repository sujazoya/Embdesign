<?php
/**
 * Plugin Name: Embroidery Designs Menu
 * Plugin URI: https://embdesign.shop/
 * Description: Custom menu system for embroidery designs website
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://embdesign.shop/
 * License: GPL2
 */

defined('ABSPATH') or die('No direct access allowed!');

// Define plugin constants
define('EMBROIDERY_MENU_VERSION', '1.0.0');
define('EMBROIDERY_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EMBROIDERY_MENU_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once EMBROIDERY_MENU_PLUGIN_DIR . 'includes/class-menu-builder.php';
require_once EMBROIDERY_MENU_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once EMBROIDERY_MENU_PLUGIN_DIR . 'includes/class-mobile-menu.php';
require_once EMBROIDERY_MENU_PLUGIN_DIR . 'includes/class-styles-scripts.php';

// Initialize classes
Embroidery_Menu_Builder::init();
Embroidery_Shortcodes::init();
Embroidery_Mobile_Menu::init();
Embroidery_Styles_Scripts::init();