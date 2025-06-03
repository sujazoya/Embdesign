<?php
if (!defined('ABSPATH')) exit;

// Wallet menu item
function wc_wallet_payu_menu_item($items) {
    $items['wallet'] = __('Wallet', 'woocommerce-wallet-payu');
    return $items;
}

// Wallet content
function wc_wallet_payu_content() {
    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
    
    wc_get_template('myaccount/wallet.php', array(
        'balance' => $balance,
        'user_id' => $user_id
    ), '', WC_WALLET_PAYU_PLUGIN_DIR . 'templates/');
}

// Display wallet transactions
function wc_wallet_payu_display_transactions($user_id) {
    $transactions = get_user_meta($user_id, 'wallet_transactions', true) ?: array();
    
    if (!empty($transactions)) {
        echo '<div class="wallet-transactions">';
        echo '<h3>'.__('Transaction History', 'woocommerce-wallet-payu').'</h3>';
        echo '<table class="woocommerce-orders-table">';
        echo '<thead><tr>
                <th>'.__('Date', 'woocommerce-wallet-payu').'</th>
                <th>'.__('Type', 'woocommerce-wallet-payu').'</th>
                <th>'.__('Amount', 'woocommerce-wallet-payu').'</th>
                <th>'.__('Order', 'woocommerce-wallet-payu').'</th>
                <th>'.__('Balance', 'woocommerce-wallet-payu').'</th>
              </tr></thead>';
        echo '<tbody>';
        
        foreach (array_reverse($transactions) as $transaction) {
            echo '<tr>';
            echo '<td>'.date('M d, Y H:i', strtotime($transaction['date'])).'</td>';
            echo '<td class="'.esc_attr($transaction['type']).'">'.ucfirst($transaction['type']).'</td>';
            echo '<td>'.($transaction['type'] == 'credit' ? '+' : '-').wc_price($transaction['amount']).'</td>';
            echo '<td>'.($transaction['order_id'] ? '<a href="'.wc_get_order($transaction['order_id'])->get_view_order_url().'">#'.$transaction['order_id'].'</a>' : 'N/A').'</td>';
            echo '<td>'.wc_price($transaction['balance']).'</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
}

// Wallet payment option at checkout
function wc_wallet_payu_payment_option() {
    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
    $cart_total = WC()->cart->total;
    
    if ($balance > 0) {
        echo '<div class="use-wallet-option">';
        echo '<input type="checkbox" id="use_wallet" name="use_wallet" '.($balance >= $cart_total ? 'checked' : '').'>';
        echo '<label for="use_wallet">'.sprintf(__('Use wallet balance (%s available)', 'woocommerce-wallet-payu'), wc_price($balance)).'</label>';
        
        if ($balance < $cart_total) {
            echo '<div class="wallet-partial-payment">';
            echo '<label>'.__('Wallet Payment Amount:', 'woocommerce-wallet-payu').'</label>';
            echo '<input type="number" name="wallet_payment_amount" min="0" max="'.esc_attr($balance).'" step="0.01" value="'.esc_attr($balance).'">';
            echo '</div>';
        }
        echo '</div>';
    }
}

// Process wallet payment selection at checkout
function wc_wallet_payu_process_payment() {
    if (isset($_POST['use_wallet']) && $_POST['use_wallet']) {
        $user_id = get_current_user_id();
        $balance = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
        $cart_total = WC()->cart->total;
        $payment_amount = $balance;
        
        if (isset($_POST['wallet_payment_amount']) && $_POST['wallet_payment_amount'] < $balance) {
            $payment_amount = floatval($_POST['wallet_payment_amount']);
        }
        
        if ($payment_amount > $balance) {
            wc_add_notice(__('Insufficient wallet balance', 'woocommerce-wallet-payu'), 'error');
            return;
        }
        
        WC()->session->set('wallet_payment_amount', $payment_amount);
    }
}

// Adjust order total display
function wc_wallet_payu_adjust_order_total() {
    if ($wallet_amount = WC()->session->get('wallet_payment_amount')) {
        echo '<tr class="wallet-payment">
                <th>'.__('Wallet Payment', 'woocommerce-wallet-payu').'</th>
                <td>-'.wc_price($wallet_amount).'</td>
              </tr>';
    }
}

// Process wallet payment at checkout completion
function wc_wallet_payu_process_at_checkout($order_id, $posted_data, $order) {
    if ($wallet_amount = WC()->session->get('wallet_payment_amount')) {
        $user_id = $order->get_user_id();
        $balance = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
        
        if ($wallet_amount > $balance) {
            throw new Exception(__('Insufficient wallet balance', 'woocommerce-wallet-payu'));
        }
        
        $new_balance = $balance - $wallet_amount;
        update_user_meta($user_id, 'wallet_balance', $new_balance);
        
        $transactions = get_user_meta($user_id, 'wallet_transactions', true) ?: array();
        $transactions[] = array(
            'type' => 'debit',
            'amount' => $wallet_amount,
            'date' => current_time('mysql'),
            'order_id' => $order_id,
            'balance' => $new_balance
        );
        update_user_meta($user_id, 'wallet_transactions', $transactions);
        
        $order->add_order_note(sprintf(
            __('%s paid via wallet. New wallet balance: %s', 'woocommerce-wallet-payu'),
            wc_price($wallet_amount),
            wc_price($new_balance)
        ));
        
        WC()->session->set('wallet_payment_amount', null);
    }
}

// Handle wallet top-up request (REVISED FOR PROPER PAYU PROCESSING)
function wc_wallet_payu_topup_request() {
    if (isset($_POST['topup_wallet']) && !empty($_POST['topup_amount'])) {
        $amount = floatval($_POST['topup_amount']);
        $user_id = get_current_user_id();
        
        // Validate minimum amount
        if ($amount < 10) {
            wc_add_notice(__('Minimum top-up amount is 10', 'woocommerce-wallet-payu'), 'error');
            wp_redirect(wc_get_account_endpoint_url('wallet'));
            exit;
        }
        
        // Create order
        $order = wc_create_order(array(
            'customer_id' => $user_id,
            'created_via' => 'wallet_topup',
            'status' => 'pending'
        ));
        
        // Add wallet metadata
        $order->add_meta_data('_is_wallet_topup', true);
        $order->add_meta_data('_wallet_user_id', $user_id);
        $order->add_meta_data('_wallet_amount', $amount);
        
        // Create virtual product
        $product = new WC_Product_Simple();
        $product->set_name('Wallet Top-Up');
        $product->set_price($amount);
        $product->set_regular_price($amount);
        $product->set_virtual(true);
        $product->save();
        
        // Add product to order
        $order->add_product($product);
        $order->set_total($amount);
        $order->set_payment_method('payu');
        $order->save();
        
        // Process payment through WooCommerce's gateway system
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        
        if (isset($available_gateways['payu'])) {
            $result = $available_gateways['payu']->process_payment($order->get_id());
            
            if (isset($result['result']) && $result['result'] === 'success') {
                wp_redirect($result['redirect']);
                exit;
            }
        }
        
        // If we reach here, payment failed
        wc_add_notice(__('Payment initiation failed. Please try again.', 'woocommerce-wallet-payu'), 'error');
        wp_redirect(wc_get_account_endpoint_url('wallet'));
        exit;
    }
}

// Handle wallet top-up after payment (REVISED FOR BETTER RELIABILITY)
function wc_wallet_payu_handle_topup($order_id) {
    $order = wc_get_order($order_id);
    
    if ($order->get_meta('_is_wallet_topup') && $order->get_status() === 'completed') {
        $user_id = $order->get_meta('_wallet_user_id');
        $amount = $order->get_meta('_wallet_amount');
        
        $current_balance = get_user_meta($user_id, 'wallet_balance', true) ?: 0;
        $new_balance = $current_balance + $amount;
        
        update_user_meta($user_id, 'wallet_balance', $new_balance);
        
        $transactions = get_user_meta($user_id, 'wallet_transactions', true) ?: array();
        $transactions[] = array(
            'type' => 'credit',
            'amount' => $amount,
            'date' => current_time('mysql'),
            'order_id' => $order_id,
            'balance' => $new_balance
        );
        update_user_meta($user_id, 'wallet_transactions', $transactions);
        
        // Send email notification
        $user = get_user_by('id', $user_id);
        $mailer = WC()->mailer();
        $subject = sprintf(__('Wallet top-up successful - %s', 'woocommerce-wallet-payu'), wc_price($amount));
        $message = sprintf(
            __('Hello %s,<br><br>Your wallet has been credited with %s. New balance: %s.<br><br>Thank you!', 'woocommerce-wallet-payu'),
            $user->display_name,
            wc_price($amount),
            wc_price($new_balance)
        );
        
        $mailer->send($user->user_email, $subject, $mailer->wrap_message($subject, $message));
    }
}

// Hook everything up
add_action('init', function() {
    // Add wallet endpoint
    add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
    
    // Add menu item
    add_filter('woocommerce_account_menu_items', 'wc_wallet_payu_menu_item');
    
    // Add content
    add_action('woocommerce_account_wallet_endpoint', 'wc_wallet_payu_content');
    
    // Checkout integration
    add_action('woocommerce_review_order_before_payment', 'wc_wallet_payu_payment_option');
    add_action('woocommerce_checkout_process', 'wc_wallet_payu_process_payment');
    add_action('woocommerce_review_order_after_order_total', 'wc_wallet_payu_adjust_order_total');
    add_action('woocommerce_checkout_order_processed', 'wc_wallet_payu_process_at_checkout', 20, 3);
    
    // Top-up handling
    add_action('template_redirect', 'wc_wallet_payu_topup_request');
    add_action('woocommerce_order_status_completed', 'wc_wallet_payu_handle_topup');
});

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});