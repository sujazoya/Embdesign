<?php
/**
 * Submission Success Template
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wcdu-success-message">
    <h3><?php _e('Design Submitted Successfully!', 'wc-design-upload'); ?></h3>
    <p><?php _e('Your design is now available in the store.', 'wc-design-upload'); ?></p>
    <a href="<?php echo esc_url($redirect); ?>" class="button">
        <?php _e('View Design', 'wc-design-upload'); ?>
    </a>
</div>