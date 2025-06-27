<div class="the-design-order-proposals">
    <?php if (empty($proposals)) : ?>
        <p><?php _e('No proposals submitted yet.', 'the-design-order'); ?></p>
    <?php else : ?>
        <table class="the-design-order-proposal-table">
            <thead>
                <tr>
                    <th><?php _e('Designer', 'the-design-order'); ?></th>
                    <th><?php _e('Amount', 'the-design-order'); ?></th>
                    <th><?php _e('Description', 'the-design-order'); ?></th>
                    <th><?php _e('Date', 'the-design-order'); ?></th>
                    <th><?php _e('Actions', 'the-design-order'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proposals as $index => $proposal) : ?>
                    <tr <?php echo (isset($approved_proposal['user_id']) && $approved_proposal['user_id'] === $proposal['user_id']) ? 'class="approved"' : ''; ?>>
                        <td><?php echo esc_html($proposal['user_name']); ?></td>
                        <td><?php echo wc_price($proposal['amount']); ?></td>
                        <td><?php echo esc_html($proposal['description']); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($proposal['date'])); ?></td>
                        <td>
                            <?php if (!isset($approved_proposal['user_id'])) : ?>
                                <button type="button" class="button the-design-order-approve-proposal" data-proposal-index="<?php echo esc_attr($index); ?>">
                                    <?php _e('Approve', 'the-design-order'); ?>
                                </button>
                            <?php elseif ($approved_proposal['user_id'] === $proposal['user_id']) : ?>
                                <span class="the-design-order-approved-label">
                                    <?php _e('Approved', 'the-design-order'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="the-design-order-add-proposal">
        <h3><?php _e('Add Proposal', 'the-design-order'); ?></h3>
        
        <div class="the-design-order-proposal-form">
            <div class="the-design-order-form-group">
                <label for="design_order_proposal_amount"><?php _e('Amount', 'the-design-order'); ?></label>
                <input type="number" step="0.01" id="design_order_proposal_amount" name="design_order_proposal_amount" min="0">
            </div>
            
            <div class="the-design-order-form-group">
                <label for="design_order_proposal_description"><?php _e('Description', 'the-design-order'); ?></label>
                <textarea id="design_order_proposal_description" name="design_order_proposal_description" rows="3"></textarea>
            </div>
            
            <button type="submit" class="button"><?php _e('Submit Proposal', 'the-design-order'); ?></button>
        </div>
    </div>
    
    <input type="hidden" name="design_order_approve_proposal" id="design_order_approve_proposal" value="">
</div>