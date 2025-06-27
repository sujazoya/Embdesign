<?php
if (!defined('ABSPATH')) exit;

// Function to get seller products
function seller_profile_get_products($user_id, $search_term = '') {
    $args = array(
        'post_type'   => 'product',
        'post_status' => array('publish', 'private'),
        'author'      => $user_id,
        'numberposts' => -1
    );
    
    if (!empty($search_term)) {
        $args['s'] = $search_term;
    }
    
    return get_posts($args);
}

// Function to calculate seller earnings
function seller_profile_calculate_earnings($user_id) {
    global $wpdb;
    $earnings = 0;
    
    $order_items = $wpdb->get_results(
        "SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE oi.order_item_type = 'line_item'"
    );
    
    foreach ($order_items as $item) {
        $order_item_id = $item->order_item_id;
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta
            WHERE order_item_id = %d AND meta_key = '_product_id'", $order_item_id
        ));
        
        if ($product_id && get_post_field('post_author', $product_id) == $user_id) {
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items
                WHERE order_item_id = %d", $order_item_id
            ));
            
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order && in_array($order->get_status(), array('completed', 'processing'))) {
                    $item_total = $wpdb->get_var($wpdb->prepare(
                        "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta
                        WHERE order_item_id = %d AND meta_key = '_line_total'", $order_item_id
                    ));
                    $earnings += floatval($item_total);
                }
            }
        }
    }
    
    return $earnings;
}