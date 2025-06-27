<div class="the-design-order-files">
    <?php if (empty($files)) : ?>
        <p><?php _e('No files uploaded by customer.', 'the-design-order'); ?></p>
    <?php else : ?>
        <ul class="the-design-order-file-list">
            <?php foreach ($files as $file_id) : 
                $file_url = wp_get_attachment_url($file_id);
                $file_name = basename($file_url);
            ?>
                <li>
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file_name); ?></a>
                    <button type="button" class="the-design-order-remove-file" data-file-id="<?php echo esc_attr($file_id); ?>">
                        <?php _e('Remove', 'the-design-order'); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>