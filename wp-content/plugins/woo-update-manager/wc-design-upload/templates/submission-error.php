<?php
/**
 * Submission Error Template
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wcdu-error-message">
    <h3><?php _e('Submission Failed', 'wc-design-upload'); ?></h3>
    <p><?php echo esc_html($error); ?></p>
    <a href="#" onclick="window.history.back();" class="button">
        <?php _e('Try Again', 'wc-design-upload'); ?>
    </a>
</div>