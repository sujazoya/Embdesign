<?php
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', 'seller_profile_enqueue_scripts');

function seller_profile_enqueue_scripts() {
    global $post;
    
    // Only load on pages with the shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'seller_profile')) {
        // CSS
        wp_enqueue_style(
            'seller-profile-css',
            SELLER_PROFILE_PLUGIN_URL . 'assets/css/seller-profile.css',
            array(),
            SELLER_PROFILE_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'seller-profile-js',
            SELLER_PROFILE_PLUGIN_URL . 'assets/js/seller-profile.js',
            array('jquery'),
            SELLER_PROFILE_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script(
            'seller-profile-js',
            'sellerProfile',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('username_check_nonce'),
                'i18n' => array(
                    'newProfilePreview' => __('New Profile Picture Preview:', 'seller-profile'),
                    'usernameMinLength' => __('Username must be at least 3 characters', 'seller-profile'),
                    'checkingUsername' => __('Checking availability...', 'seller-profile'),
                    'usernameAvailable' => __('Username available', 'seller-profile'),
                    'usernameTaken' => __('Username already taken', 'seller-profile'),
                    'usernameCheckError' => __('Error checking username', 'seller-profile'),
                    'chooseAvailableUsername' => __('Please choose an available username', 'seller-profile')
                )
            )
        );
    }
}