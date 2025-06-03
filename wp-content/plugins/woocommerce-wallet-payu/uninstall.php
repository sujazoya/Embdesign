<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Remove wallet balance data from users
$users = get_users(array(
    'meta_key' => 'wallet_balance',
    'fields' => 'ids'
));

foreach ($users as $user_id) {
    delete_user_meta($user_id, 'wallet_balance');
    delete_user_meta($user_id, 'wallet_transactions');
}

// Remove plugin options
delete_option('wc_wallet_payu_installed');

// Flush rewrite rules
flush_rewrite_rules();