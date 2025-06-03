<?php
if (!defined('ABSPATH')) exit;

// AJAX handler for wallet balance check
add_action('wp_ajax_wc_wallet_check_balance', 'wc_wallet_check_balance');
add_action('wp_ajax_nopriv_wc_wallet_check_balance', 'wc_wallet_check_balance');

function wc_wallet_check_balance() {
    check_ajax_referer('wc-wallet-payu-security', 'security');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in to check wallet balance', 'woocommerce-wallet-payu'));
    }
    
    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
    
    wp_send_json_success(array(
        'balance' => wc_price($balance)
    ));
}