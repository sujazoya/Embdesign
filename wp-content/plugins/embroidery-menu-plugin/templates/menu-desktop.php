<?php
/**
 * Desktop Menu Template
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<nav class="embroidery-desktop-nav">
    <div class="embroidery-desktop-nav-container">
        <?php
        // Main navigation menu
        do_action('embroidery_desktop_menu');
        
        // Additional elements like search or logo can be added here
        ?>
        
        <?php if (class_exists('WooCommerce')) : ?>
        <div class="desktop-account-section">
            <?php
            // Display cart icon with count
            $cart_count = WC()->cart->get_cart_contents_count();
            ?>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="desktop-cart-link">
                <span class="cart-icon"></span>
                <?php if ($cart_count > 0) : ?>
                    <span class="cart-count"><?php echo esc_html($cart_count); ?></span>
                <?php endif; ?>
            </a>
            
            <?php
            // Display account link
            $account_url = wc_get_page_permalink('myaccount');
            $account_text = is_user_logged_in() ? __('My Account', 'embroidery-menu') : __('Login/Register', 'embroidery-menu');
            ?>
            <a href="<?php echo esc_url($account_url); ?>" class="desktop-account-link">
                <?php echo esc_html($account_text); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>