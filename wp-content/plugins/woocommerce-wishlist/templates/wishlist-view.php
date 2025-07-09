<?php
/**
 * Wishlist page template
 *
 * @package WooCommerce Wishlist
 */

if (!defined('ABSPATH')) {
    exit;
}

$wishlist = WC_Wishlist::instance()->get_wishlist();
?>

<div class="woocommerce wcwl-wishlist">
    <h2><?php esc_html_e('Your Wishlist', 'woocommerce-wishlist'); ?></h2>
    
    <?php if (empty($wishlist)) : ?>
        <p class="wcwl-empty-wishlist"><?php esc_html_e('Your wishlist is currently empty.', 'woocommerce-wishlist'); ?></p>
    <?php else : ?>
        <table class="shop_table shop_table_responsive cart wcwl-wishlist-table">
            <thead>
                <tr>
                    <th class="product-remove">&nbsp;</th>
                    <th class="product-thumbnail">&nbsp;</th>
                    <th class="product-name"><?php esc_html_e('Product', 'woocommerce-wishlist'); ?></th>
                    <th class="product-price"><?php esc_html_e('Price', 'woocommerce-wishlist'); ?></th>
                    <th class="product-stock-status"><?php esc_html_e('Stock Status', 'woocommerce-wishlist'); ?></th>
                    <th class="product-add-to-cart">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($wishlist as $product_id) {
                    $product = wc_get_product($product_id);
                    
                    if (!$product) {
                        continue;
                    }
                    
                    $availability = $product->get_availability();
                    $stock_status = isset($availability['class']) ? $availability['class'] : '';
                    ?>
                    <tr class="wcwl-wishlist-item">
                        <td class="product-remove">
                            <a href="#" class="wcwl-remove-from-wishlist" data-product-id="<?php echo esc_attr($product_id); ?>" data-nonce="<?php echo wp_create_nonce('wcwl-add-' . $product_id); ?>">
                                &times;
                            </a>
                        </td>
                        <td class="product-thumbnail">
                            <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                <?php echo $product->get_image(); ?>
                            </a>
                        </td>
                        <td class="product-name" data-title="<?php esc_attr_e('Product', 'woocommerce-wishlist'); ?>">
                            <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                <?php echo $product->get_name(); ?>
                            </a>
                        </td>
                        <td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce-wishlist'); ?>">
                            <?php echo $product->get_price_html(); ?>
                        </td>
                        <td class="product-stock-status" data-title="<?php esc_attr_e('Stock Status', 'woocommerce-wishlist'); ?>">
                            <span class="wcwl-stock-status <?php echo esc_attr($stock_status); ?>">
                                <?php
                                if ($product->is_in_stock()) {
                                    esc_html_e('In Stock', 'woocommerce-wishlist');
                                } else {
                                    esc_html_e('Out of Stock', 'woocommerce-wishlist');
                                }
                                ?>
                            </span>
                        </td>
                        <td class="product-add-to-cart">
                            <?php
                            if ($product->is_type('variable')) {
                                echo sprintf(
                                    '<a href="%s" class="button">%s</a>',
                                    esc_url($product->get_permalink()),
                                    esc_html__('Select Options', 'woocommerce-wishlist')
                                );
                            } elseif ($product->is_in_stock()) {
                                echo sprintf(
                                    '<a href="%s" data-quantity="1" class="button add_to_cart_button ajax_add_to_cart" data-product_id="%d" data-product_sku="%s" aria-label="%s" rel="nofollow">%s</a>',
                                    esc_url($product->add_to_cart_url()),
                                    esc_attr($product->get_id()),
                                    esc_attr($product->get_sku()),
                                    esc_attr__('Add to cart', 'woocommerce-wishlist'),
                                    esc_html__('Add to Cart', 'woocommerce-wishlist')
                                );
                            } else {
                                echo sprintf(
                                    '<a href="%s" class="button disabled">%s</a>',
                                    esc_url($product->get_permalink()),
                                    esc_html__('Read More', 'woocommerce-wishlist')
                                );
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>