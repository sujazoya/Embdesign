<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// Check if checkout is allowed
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <div style="display: flex; gap: 30px; align-items: flex-start; flex-wrap: wrap;">
        
        <!-- Left Column: Product Details -->
        <div style="flex: 1; min-width: 300px;">
            <?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
            <h3 id="order_review_heading"><?php esc_html_e( 'Your Product', 'woocommerce' ); ?></h3>
            <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php woocommerce_order_review(); ?>
            </div>
            <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
        </div>

        <!-- Right Column: Checkout Fields (Only Email and Pay) -->
        <div style="flex: 1; min-width: 300px;">
            <?php if ( $checkout->get_checkout_fields() ) : ?>
                <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
                <div class="col2-set" id="customer_details">
                    <div class="col-1">
                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                    </div>
                </div>
                <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
