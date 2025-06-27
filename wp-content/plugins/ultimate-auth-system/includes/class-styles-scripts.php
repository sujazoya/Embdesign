<?php
class UAS_Styles_Scripts {

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }

    public static function enqueue_assets() {
        // CSS
        wp_register_style(
            'uas-styles',
            UAS_URL . 'assets/css/style.css',
            [],
            UAS_VERSION
        );

        // JS
        wp_register_script(
            'uas-scripts',
            UAS_URL . 'assets/js/script.js',
            ['jquery'],
            UAS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('uas-scripts', 'uas_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'auth_nonce' => wp_create_nonce('uas-auth-nonce'),
            'google_client_id' => get_option('uas_google_client_id'),
            'login_error' => __('Login failed. Please try again.', 'ultimate-auth'),
            'register_error' => __('Registration failed. Please try again.', 'ultimate-auth'),
            'google_error' => __('Google authentication failed.', 'ultimate-auth'),
            'processing_text' => __('Processing...', 'ultimate-auth'),
            'password_mismatch' => __('Passwords do not match.', 'ultimate-auth')
        ]);

        wp_enqueue_style('uas-styles');
        wp_enqueue_script('uas-scripts');
    }

    public static function enqueue_admin_assets($hook) {
        if ($hook === 'settings_page_uas-google-settings') {
            wp_enqueue_style(
                'uas-admin-styles',
                UAS_URL . 'assets/css/admin.css',
                [],
                UAS_VERSION
            );
        }
    }
}