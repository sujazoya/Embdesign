<?php
/**
 * Login Required Template
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wcdu-login-required">
    <p><?php _e('Please log in to submit a design.', 'wc-design-upload'); ?></p>
    <a href="<?php echo esc_url(wp_login_url()); ?>" class="button">
        <?php _e('Login', 'wc-design-upload'); ?>
    </a>
</div>