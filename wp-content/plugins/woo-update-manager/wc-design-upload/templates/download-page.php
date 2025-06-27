<?php
/**
 * Download Page Template
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wcdu-download-container">
    <h2><?php echo esc_html($product->get_name()); ?></h2>
    <?php foreach ($downloads as $file_id => $file_data) : ?>
        <div class="wcdu-download-item">
            <span><?php echo esc_html($file_data['name']); ?></span>
            <a href="<?php echo esc_url($file_data['file']); ?>" download class="button">
                <?php _e('Download', 'wc-design-upload'); ?>
            </a>
        </div>
    <?php endforeach; ?>
</div>