<?php
/**
 * Download Error Template
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wcdu-error-message">
    <h3><?php _e('Download Error', 'wc-design-upload'); ?></h3>
    <p><?php echo esc_html($message); ?></p>
    <a href="<?php echo esc_url(home_url()); ?>" class="button">
        <?php _e('Return Home', 'wc-design-upload'); ?>
    </a>
</div>