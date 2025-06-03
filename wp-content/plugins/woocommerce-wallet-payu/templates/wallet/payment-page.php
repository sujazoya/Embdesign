<?php
/**
 * Wallet Payment Page
 */
if (!defined('ABSPATH')) exit;

global $current_user;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Wallet Top-Up', 'woocommerce-wallet-payu'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="wallet-payment-page">
    <div class="wallet-payment-container">
        <div class="wallet-payment-card">
            <div class="payment-header">
                <h2><?php _e('Add Funds to Wallet', 'woocommerce-wallet-payu'); ?></h2>
                <div class="payment-amount">
                    <?php echo wc_price($amount); ?>
                </div>
            </div>

            <div class="payment-methods">
                <h3><?php _e('Select Payment Method', 'woocommerce-wallet-payu'); ?></h3>
                
                <?php do_action('woocommerce_wallet_before_payment_methods'); ?>
                
                <div class="payment-method payu-method active">
                    <input type="radio" name="payment_method" id="payu_method" value="payu" checked>
                    <label for="payu_method">
                        <img src="<?php echo WC_WALLET_PAYU_PLUGIN_URL; ?>assets/images/payu-logo.png" alt="PayU">
                        <span><?php _e('PayU Payment Gateway', 'woocommerce-wallet-payu'); ?></span>
                    </label>
                </div>
                
                <?php do_action('woocommerce_wallet_after_payment_methods'); ?>
            </div>

            <form method="post" id="wallet-payment-form">
                <input type="hidden" name="amount" value="<?php echo esc_attr($amount); ?>">
                <?php wp_nonce_field('wc_wallet_topup_payment', 'wc_wallet_topup_nonce'); ?>
                <button type="submit" class="btn-pay-now">
                    <?php _e('Pay Now', 'woocommerce-wallet-payu'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>