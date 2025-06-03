<div class="woocommerce-wallet">
    <div class="wallet-header">
        <div class="wallet-balance-card">
            <h3><?php _e('Wallet Balance', 'woocommerce-wallet-payu'); ?></h3>
            <div class="balance-amount"><?php echo wc_price($balance); ?></div>
        </div>
    </div>

    <div class="wallet-topup-card">
        <h3><?php _e('Add Funds', 'woocommerce-wallet-payu'); ?></h3>
        <form method="post" class="wallet-topup-form">
            <div class="form-group">
                <label for="topup_amount"><?php _e('Enter Amount', 'woocommerce-wallet-payu'); ?></label>
                <div class="input-with-currency">
                    <span class="currency-symbol"><?php echo get_woocommerce_currency_symbol(); ?></span>
                    <input type="number" id="topup_amount" name="topup_amount" min="10" step="1" placeholder="100" required>
                </div>
            </div>
            <button type="submit" name="topup_wallet" class="wallet-topup-button">
                <?php _e('Continue to Payment', 'woocommerce-wallet-payu'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
            </button>
        </form>
    </div>

    <?php wc_wallet_payu_display_transactions($user_id); ?>
</div>