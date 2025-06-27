<?php
if (!defined('ABSPATH')) {
    exit;
}

class ACD_Assets {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'acd-styles',
            ACD_PLUGIN_URL . 'assets/css/acd-styles.css',
            array(),
            ACD_VERSION
        );

        // JS
        wp_enqueue_script(
            'acd-scripts',
            ACD_PLUGIN_URL . 'assets/js/acd-scripts.js',
            array('jquery'),
            ACD_VERSION,
            true
        );
    }
}