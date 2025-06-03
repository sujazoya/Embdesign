<div class="use-wallet-option">
    <input type="checkbox" id="use_wallet" name="use_wallet" <?php echo ($balance >= $cart_total ? 'checked' : ''); ?>>
    <label for="use_wallet">
        <?php printf(__('Use wallet balance (%s available)', 'woocommerce-wallet-payu'), wc_price($balance)); ?>
    </label>
    
    <?php if ($balance < $cart_total): ?>
        <div class="wallet-partial-payment">
            <label><?php _e('Wallet Payment Amount:', 'woocommerce-wallet-payu'); ?></label>
            <input type="number" name="wallet_payment_amount" min="0" max="<?php echo $balance; ?>" step="0.01" value="<?php echo $balance; ?>">
        </div>
    <?php endif; ?>
</div>