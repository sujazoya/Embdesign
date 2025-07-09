<?php
if (!function_exists('wcwl_get_wishlist_count')) {
    function wcwl_get_wishlist_count() {
        $wishlist = WC_Wishlist::instance();
        return count($wishlist->get_wishlist());
    }
}

if (!function_exists('wcwl_get_wishlist_url')) {
    function wcwl_get_wishlist_url() {
        if (function_exists('wc_get_endpoint_url')) {
            return wc_get_endpoint_url('wishlist', '', wc_get_page_permalink('myaccount'));
        }
        return '';
    }
}

if (!function_exists('wcwl_wishlist_button')) {
    function wcwl_wishlist_button($product_id = null) {
        if (is_null($product_id)) {
            global $product;
            $product_id = $product->get_id();
        }
        
        $wishlist = WC_Wishlist::instance();
        $is_in_wishlist = $wishlist->is_product_in_wishlist($product_id);
        
        return sprintf(
            '<a href="%s" class="button wcwl-add-to-wishlist %s" data-product-id="%d" data-nonce="%s">%s</a>',
            esc_url('#'),
            $is_in_wishlist ? 'added' : '',
            esc_attr($product_id),
            wp_create_nonce('wcwl-add-' . $product_id),
            $is_in_wishlist ? __('Remove from Wishlist', 'woocommerce-wishlist') : __('Add to Wishlist', 'woocommerce-wishlist')
        );
    }
}