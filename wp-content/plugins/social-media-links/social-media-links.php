<?php
/**
 * Plugin Name: Social Media Links
 * Plugin URI: https://yourwebsite.com/social-media-links
 * Description: Display beautiful social media links with customizable shortcode
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: social-media-links
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Social_Media_Links class
 */
class Social_Media_Links {

    /**
     * Constructor
     */
    public function __construct() {
        // Define constants
        $this->define_constants();

        // Initialize plugin features
        $this->init();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('SML_VERSION', '1.0.0');
        define('SML_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('SML_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('SML_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    /**
     * Initialize plugin features
     */
    private function init() {
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Register shortcode
        add_shortcode('social_media_links', array($this, 'shortcode_output'));

        // Add admin settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'social-media-links',
            false,
            dirname(SML_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Enqueue plugin assets
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'sml-styles',
            SML_PLUGIN_URL . 'css/sml-styles.css',
            array(),
            SML_VERSION
        );

        // JS
        wp_enqueue_script(
            'sml-scripts',
            SML_PLUGIN_URL . 'js/sml-scripts.js',
            array('jquery'),
            SML_VERSION,
            true
        );
    }

    /**
     * Shortcode output
     */
    public function shortcode_output($atts) {
        // Default attributes
        $defaults = array(
            'title'     => get_option('sml_default_title', __('Let\'s Connect!', 'social-media-links')),
            'subtitle'  => get_option('sml_default_subtitle', __('Join our community across these platforms', 'social-media-links')),
            'layout'    => 'grid',
            'columns'   => 3,
            'facebook'  => get_option('sml_facebook_url', ''),
            'instagram' => get_option('sml_instagram_url', ''),
            'twitter'   => get_option('sml_twitter_url', ''),
            'youtube'   => get_option('sml_youtube_url', ''),
            'linkedin'  => get_option('sml_linkedin_url', ''),
            'pinterest' => get_option('sml_pinterest_url', ''),
            'whatsapp'  => get_option('sml_whatsapp_url', ''),
            'telegram'  => get_option('sml_telegram_url', ''),
            'tiktok'    => get_option('sml_tiktok_url', ''),
            'show_labels' => 'true'
        );

        // Parse shortcode attributes
        $atts = shortcode_atts($defaults, $atts, 'social_media_links');

        // Sanitize attributes
        $atts['columns'] = absint($atts['columns']);
        $atts['show_labels'] = filter_var($atts['show_labels'], FILTER_VALIDATE_BOOLEAN);

        // Start output buffering
        ob_start();

        // Include template file
        include SML_PLUGIN_DIR . 'templates/shortcode-template.php';

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Social Media Links Settings', 'social-media-links'),
            __('Social Media Links', 'social-media-links'),
            'manage_options',
            'social-media-links',
            array($this, 'settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Default settings section
        add_settings_section(
            'sml_settings_section',
            __('Default Social Media Links', 'social-media-links'),
            array($this, 'settings_section_callback'),
            'social-media-links'
        );

        // Register fields
        $fields = array(
            'sml_default_title' => __('Default Title', 'social-media-links'),
            'sml_default_subtitle' => __('Default Subtitle', 'social-media-links'),
            'sml_facebook_url' => __('Facebook URL', 'social-media-links'),
            'sml_instagram_url' => __('Instagram URL', 'social-media-links'),
            'sml_twitter_url' => __('Twitter URL', 'social-media-links'),
            'sml_youtube_url' => __('YouTube URL', 'social-media-links'),
            'sml_linkedin_url' => __('LinkedIn URL', 'social-media-links'),
            'sml_pinterest_url' => __('Pinterest URL', 'social-media-links'),
            'sml_whatsapp_url' => __('WhatsApp URL', 'social-media-links'),
            'sml_telegram_url' => __('Telegram URL', 'social-media-links'),
            'sml_tiktok_url' => __('TikTok URL', 'social-media-links')
        );

        foreach ($fields as $field => $label) {
            register_setting('sml_settings_group', $field);
            add_settings_field(
                $field,
                $label,
                array($this, 'settings_field_callback'),
                'social-media-links',
                'sml_settings_section',
                array('field' => $field)
            );
        }
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Set default values for your social media links. These will be used when not specified in the shortcode.', 'social-media-links') . '</p>';
    }

    /**
     * Settings field callback
     */
    public function settings_field_callback($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        echo '<input type="text" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('sml_settings_group');
                do_settings_sections('social-media-links');
                submit_button(__('Save Settings', 'social-media-links'));
                ?>
            </form>
            
            <div class="sml-shortcode-help">
                <h2><?php _e('Shortcode Usage', 'social-media-links'); ?></h2>
                <p><?php _e('Use the following shortcode to display your social media links:', 'social-media-links'); ?></p>
                <code>[social_media_links]</code>
                
                <h3><?php _e('Available Attributes', 'social-media-links'); ?></h3>
                <ul>
                    <li><code>title</code>: <?php _e('Custom title text', 'social-media-links'); ?></li>
                    <li><code>subtitle</code>: <?php _e('Custom subtitle text', 'social-media-links'); ?></li>
                    <li><code>layout</code>: <?php _e('Layout type (grid or list)', 'social-media-links'); ?></li>
                    <li><code>columns</code>: <?php _e('Number of columns (1-4)', 'social-media-links'); ?></li>
                    <li><code>show_labels</code>: <?php _e('Show/hide platform labels (true/false)', 'social-media-links'); ?></li>
                    <li><?php _e('Platform URLs (facebook, instagram, etc.)', 'social-media-links'); ?></li>
                </ul>
                
                <h3><?php _e('Example', 'social-media-links'); ?></h3>
                <code>[social_media_links title="Follow Us" subtitle="Stay connected" columns="4" show_labels="false"]</code>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new Social_Media_Links();