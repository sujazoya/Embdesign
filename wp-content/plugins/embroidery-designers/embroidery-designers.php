<?php
/**
 * Plugin Name: Embroidery Designers
 * Plugin URI: https://yourwebsite.com/embroidery-designers
 * Description: A complete solution for displaying embroidery designers with search functionality and detailed profiles.
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: embroidery-designers
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ED_VERSION', '1.1.0');
define('ED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ED_TEMPLATE_PATH', ED_PLUGIN_DIR . 'templates/');

// Load required files
require_once ED_PLUGIN_DIR . 'includes/functions.php';
require_once ED_PLUGIN_DIR . 'includes/shortcodes.php';
require_once ED_PLUGIN_DIR . 'includes/user-fields.php';
require_once ED_PLUGIN_DIR . 'includes/query-filters.php';

class Embroidery_Designers {

    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Register custom rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));

        // Initialize plugin components
        $this->init();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('embroidery-designers', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Add custom rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^designers/?$', 'index.php?designers_page=1', 'top');
        add_rewrite_rule('^designers/search/?$', 'index.php?designers_search=1', 'top');
        add_rewrite_tag('%designers_page%', '([^&]+)');
        add_rewrite_tag('%designers_search%', '([^&]+)');
    }

    /**
     * Enqueue plugin assets
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'ed-styles',
            ED_PLUGIN_URL . 'assets/css/designers.css',
            array(),
            ED_VERSION
        );

        // Font Awesome for icons
        wp_enqueue_style(
            'ed-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            array(),
            '5.15.4'
        );

        // JavaScript
        wp_enqueue_script(
            'ed-scripts',
            ED_PLUGIN_URL . 'assets/js/designers.js',
            array('jquery'),
            ED_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('ed-scripts', 'ed_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ed-search-nonce')
        ));
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Register custom user fields
        ED_User_Fields::init();

        // Register shortcodes
        ED_Shortcodes::init();

        // Register query filters
        ED_Query_Filters::init();

        // Register custom templates
        add_filter('template_include', array($this, 'designers_templates'));
    }

    /**
     * Load custom templates
     */
    public function designers_templates($template) {
        if (get_query_var('designers_page')) {
            return ED_TEMPLATE_PATH . 'designers-list.php';
        } elseif (is_author()) {
            return ED_TEMPLATE_PATH . 'designer-profile.php';
        }
        return $template;
    }
}

// Initialize the plugin
new Embroidery_Designers();