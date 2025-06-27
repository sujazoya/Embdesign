<?php
/**
 * Thank you template after design order submission
 */

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    wp_die(__('Invalid order ID.', 'the-design-order'));
}

$order_id = intval($_GET['order_id']);
$order = get_post($order_id);

if (!$order || $order->post_type !== 'design_order') {
    wp_die(__('Order not found.', 'the-design-order'));
}

// Check if current user is the order author
if (get_current_user_id() != $order->post_author) {
    wp_die(__('You are not authorized to view this order.', 'the-design-order'));
}

$order_status = wp_get_object_terms($order_id, 'design_order_status', ['fields' => 'names']);
$order_status = !empty($order_status) ? $order_status[0] : __('Unknown', 'the-design-order');
?>

<div class="the-design-order-thankyou">
    <h2><?php _e('Thank You for Your Design Order!', 'the-design-order'); ?></h2>
    
    <div class="the-design-order-summary">
        <h3><?php _e('Order Summary', 'the-design-order'); ?></h3>
        
        <div class="the-design-order-summary-row">
            <span class="the-design-order-summary-label"><?php _e('Order ID:', 'the-design-order'); ?></span>
            <span class="the-design-order-summary-value">#<?php echo esc_html($order_id); ?></span>
        </div>
        
        <div class="the-design-order-summary-row">
            <span class="the-design-order-summary-label"><?php _e('Status:', 'the-design-order'); ?></span>
            <span class="the-design-order-summary-value the-design-order-status the-design-order-status-<?php echo sanitize_html_class(strtolower($order_status)); ?>">
                <?php echo esc_html($order_status); ?>
            </span>
        </div>
        
        <div class="the-design-order-summary-row">
            <span class="the-design-order-summary-label"><?php _e('Date:', 'the-design-order'); ?></span>
            <span class="the-design-order-summary-value"><?php echo get_the_date('', $order); ?></span>
        </div>
    </div>
    
    <div class="the-design-order-next-steps">
        <h3><?php _e('Next Steps', 'the-design-order'); ?></h3>
        
        <?php if ($order_status === __('New', 'the-design-order')) : ?>
            <p><?php _e('Our team will review your design request and get back to you soon with proposals.', 'the-design-order'); ?></p>
        <?php elseif ($order_status === __('In Progress', 'the-design-order')) : ?>
            <p><?php _e('Your design is currently being worked on by our team.', 'the-design-order'); ?></p>
        <?php elseif ($order_status === __('Design Received', 'the-design-order')) : ?>
            <p><?php _e('Your design is ready! Please complete the payment to download your files.', 'the-design-order'); ?></p>
            <?php
            $product_id = get_post_meta($order_id, '_design_order_product_id', true);
            if ($product_id) :
            ?>
                <a href="<?php echo get_permalink($product_id); ?>" class="button">
                    <?php _e('Complete Payment', 'the-design-order'); ?>
                </a>
            <?php endif; ?>
        <?php elseif ($order_status === __('Completed', 'the-design-order')) : ?>
            <p><?php _e('Your design order has been completed. Thank you for your business!', 'the-design-order'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="the-design-order-actions">
        <a href="<?php echo get_permalink(get_option('the_design_order_list_page_id')); ?>" class="button">
            <?php _e('View All Orders', 'the-design-order'); ?>
        </a>
        <a href="<?php echo home_url(); ?>" class="button alt">
            <?php _e('Return to Home', 'the-design-order'); ?>
        </a>
    </div>
</div>