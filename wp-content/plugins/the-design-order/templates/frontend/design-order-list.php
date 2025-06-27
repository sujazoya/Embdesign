<div class="the-design-order-list">
    <h2><?php _e('My Design Orders', 'the-design-order'); ?></h2>
    
    <?php if (empty($orders)) : ?>
        <p><?php _e('You have no design orders yet.', 'the-design-order'); ?></p>
    <?php else : ?>
        <table class="the-design-order-table">
            <thead>
                <tr>
                    <th><?php _e('Order', 'the-design-order'); ?></th>
                    <th><?php _e('Date', 'the-design-order'); ?></th>
                    <th><?php _e('Status', 'the-design-order'); ?></th>
                    <th><?php _e('Actions', 'the-design-order'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : 
                    $status = wp_get_object_terms($order->ID, 'design_order_status', ['fields' => 'names']);
                    $status = !empty($status) ? $status[0] : __('Unknown', 'the-design-order');
                    $order_date = get_the_date('', $order);
                    $product_id = get_post_meta($order->ID, '_design_order_product_id', true);
                ?>
                    <tr>
                        <td><?php echo esc_html($order->post_title); ?></td>
                        <td><?php echo esc_html($order_date); ?></td>
                        <td>
                            <span class="the-design-order-status the-design-order-status-<?php echo sanitize_html_class(strtolower($status)); ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo get_permalink($order->ID); ?>" class="button">
                                <?php _e('View', 'the-design-order'); ?>
                            </a>
                            
                            <?php if ($product_id && $status === __('Design Received', 'the-design-order')) : ?>
                                <a href="<?php echo get_permalink($product_id); ?>" class="button alt">
                                    <?php _e('Purchase', 'the-design-order'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>