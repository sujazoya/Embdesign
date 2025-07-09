<?php
add_action('wp_ajax_wcwl_add_to_wishlist', 'wcwl_add_to_wishlist');
add_action('wp_ajax_nopriv_wcwl_add_to_wishlist', 'wcwl_add_to_wishlist');

function wcwl_add_to_wishlist() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wcwl-nonce')) {
        wp_send_json_error(array('message' => __('Nonce verification failed', 'woocommerce-wishlist')));
    }
    
    if (!isset($_POST['product_id'])) {
        wp_send_json_error(array('message' => __('No product ID provided', 'woocommerce-wishlist')));
    }
    
    $product_id = absint($_POST['product_id']);
    $wishlist = WC_Wishlist::instance();
    
    if ($wishlist->is_product_in_wishlist($product_id)) {
        $wishlist->remove_from_wishlist($product_id);
        wp_send_json_success(array(
            'status' => 'removed',
            'message' => __('Product removed from wishlist', 'woocommerce-wishlist'),
            'count' => count($wishlist->get_wishlist())
        ));
    } else {
        $wishlist->add_to_wishlist($product_id);
        wp_send_json_success(array(
            'status' => 'added',
            'message' => __('Product added to wishlist', 'woocommerce-wishlist'),
            'count' => count($wishlist->get_wishlist())
        ));
    }
}