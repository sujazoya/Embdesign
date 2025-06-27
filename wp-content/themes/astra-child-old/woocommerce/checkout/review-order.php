<?php
defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table">
    <thead>
        <tr>
            <th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
            <th class="product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
            $_product = $cart_item['data'];
            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) :
        ?>
            <tr class="cart_item">
                <td class="product-name" style="display: flex; align-items: center; gap: 10px;">
                    <?php echo $_product->get_image( [80, 80] ); ?>
                    <span>
                        <?php echo wp_kses_post( $_product->get_name() ); ?>
                        <strong class="product-quantity"> × <?php echo esc_html( $cart_item['quantity'] ); ?></strong>
                    </span>
                </td>
                <td class="product-total">
                    <?php echo WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ); ?>
                </td>
            </tr>
        <?php endif; endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="cart-subtotal">
            <th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
            <td><?php wc_cart_totals_subtotal_html(); ?></td>
        </tr>

        <tr class="order-total">
            <th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
            <td><?php wc_cart_totals_order_total_html(); ?></td>
        </tr>
    </tfoot>
</table>

<div id="payment" class="woocommerce-checkout-payment">
    <?php if ( WC()->cart->needs_payment() ) : ?>
        <?php
        do_action( 'woocommerce_review_order_before_payment' );
        wc_get_template( 'checkout/payment.php' );
        do_action( 'woocommerce_review_order_after_payment' );
        ?>
    <?php endif; ?>
</div>
