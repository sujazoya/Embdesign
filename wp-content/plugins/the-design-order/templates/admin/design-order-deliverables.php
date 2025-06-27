<div class="the-design-order-deliverables">
    <?php if ($status === 'in_progress' || $status === 'design_received' || $status === 'completed') : ?>
        <?php if (empty($deliverables)) : ?>
            <p><?php _e('No deliverables uploaded yet.', 'the-design-order'); ?></p>
        <?php else : ?>
            <ul class="the-design-order-deliverable-list">
                <?php foreach ($deliverables as $file_id) : 
                    $file_url = wp_get_attachment_url($file_id);
                    $file_name = basename($file_url);
                ?>
                    <li>
                        <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file_name); ?></a>
                        <button type="button" class="the-design-order-remove-deliverable" data-file-id="<?php echo esc_attr($file_id); ?>">
                            <?php _e('Remove', 'the-design-order'); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <?php if ($status === 'in_progress') : ?>
            <div class="the-design-order-upload-deliverables">
                <h3><?php _e('Upload Deliverables', 'the-design-order'); ?></h3>
                
                <input type="file" name="design_order_deliverables[]" id="design_order_deliverables" multiple>
                <p class="description">
                    <?php _e('Upload the final design files for the customer.', 'the-design-order'); ?>
                </p>
            </div>
        <?php elseif ($status === 'design_received') : ?>
            <button type="button" class="button the-design-order-complete-order">
                <?php _e('Mark as Completed', 'the-design-order'); ?>
            </button>
        <?php endif; ?>
    <?php else : ?>
        <p><?php _e('Deliverables will be available after approving a proposal.', 'the-design-order'); ?></p>
    <?php endif; ?>
</div>