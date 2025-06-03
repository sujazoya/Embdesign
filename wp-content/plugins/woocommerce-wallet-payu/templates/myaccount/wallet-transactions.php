<div class="wallet-transactions">
    <h4><?php _e('Transaction History', 'woocommerce-wallet-payu'); ?></h4>
    <table class="woocommerce-orders-table">
        <thead>
            <tr>
                <th><?php _e('Date', 'woocommerce-wallet-payu'); ?></th>
                <th><?php _e('Type', 'woocommerce-wallet-payu'); ?></th>
                <th><?php _e('Amount', 'woocommerce-wallet-payu'); ?></th>
                <th><?php _e('Order', 'woocommerce-wallet-payu'); ?></th>
                <th><?php _e('Balance', 'woocommerce-wallet-payu'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo date('M d, Y H:i', strtotime($transaction['date'])); ?></td>
                    <td><?php echo ucfirst($transaction['type']); ?></td>
                    <td><?php echo ($transaction['type'] == 'credit' ? '+' : '-') . wc_price($transaction['amount']); ?></td>
                    <td>
                        <?php if ($transaction['order_id']): ?>
                            <a href="<?php echo wc_get_order($transaction['order_id'])->get_view_order_url(); ?>">
                                #<?php echo $transaction['order_id']; ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo wc_price($transaction['balance']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>