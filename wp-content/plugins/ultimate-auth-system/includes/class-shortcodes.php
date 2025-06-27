<?php
class UAS_Shortcodes {

    public static function init() {
        add_shortcode('ultimate_auth', [__CLASS__, 'auth_shortcode']);
    }

    public static function auth_shortcode($atts) {
        $atts = shortcode_atts([
            'redirect' => '',
            'show_google_login' => true,
            'default_view' => 'login',
            'show_lost_password' => true,
            'show_remember_me' => true
        ], $atts);

        wp_enqueue_style('uas-styles');
        wp_enqueue_script('uas-scripts');

        if ($atts['show_google_login'] && get_option('uas_google_client_id')) {
            wp_enqueue_script('google-signin', 'https://accounts.google.com/gsi/client', [], null, true);
        }

        ob_start();
        include UAS_PATH . 'templates/auth-container.php';
        return ob_get_clean();
    }
}