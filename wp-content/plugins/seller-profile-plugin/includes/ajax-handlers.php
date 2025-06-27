<?php
if (!defined('ABSPATH')) exit;

// Handle username availability check
add_action('wp_ajax_check_username_availability', 'check_username_availability');
add_action('wp_ajax_nopriv_check_username_availability', 'check_username_availability');

function check_username_availability() {
    check_ajax_referer('username_check_nonce', 'security');
    
    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $current_user = wp_get_current_user();
    
    $response = array(
        'available' => false,
        'message' => ''
    );
    
    if (empty($username)) {
        $response['message'] = __('Username cannot be empty', 'seller-profile');
        wp_send_json($response);
    }
    
    if (!validate_username($username)) {
        $response['message'] = __('Username contains invalid characters', 'seller-profile');
        wp_send_json($response);
    }
    
    if (username_exists($username) && $username !== $current_user->user_login) {
        $response['message'] = __('Username already exists', 'seller-profile');
        wp_send_json($response);
    }
    
    $response['available'] = true;
    $response['message'] = __('Username available', 'seller-profile');
    wp_send_json($response);
}