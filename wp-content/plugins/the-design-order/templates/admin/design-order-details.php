<div class="the-design-order-details">
    <div class="the-design-order-detail-row">
        <label for="design_order_name"><?php _e('Customer Name', 'the-design-order'); ?></label>
        <input type="text" id="design_order_name" name="design_order_name" value="<?php echo esc_attr($name); ?>">
    </div>
    
    <div class="the-design-order-detail-row">
        <label for="design_order_mobile"><?php _e('Mobile Number', 'the-design-order'); ?></label>
        <input type="text" id="design_order_mobile" name="design_order_mobile" value="<?php echo esc_attr($mobile); ?>">
    </div>
    
    <div class="the-design-order-detail-row">
        <label for="design_order_email"><?php _e('Email Address', 'the-design-order'); ?></label>
        <input type="email" id="design_order_email" name="design_order_email" value="<?php echo esc_attr($email); ?>">
    </div>
    
    <div class="the-design-order-detail-row">
        <label for="design_order_status"><?php _e('Status', 'the-design-order'); ?></label>
        <select id="design_order_status" name="design_order_status">
            <?php
            $terms = get_terms([
                'taxonomy' => 'design_order_status',
                'hide_empty' => false
            ]);
            
            foreach ($terms as $term) {
                echo '<option value="' . esc_attr($term->slug) . '" ' . selected($status, $term->slug, false) . '>' . esc_html($term->name) . '</option>';
            }
            ?>
        </select>
    </div>
</div>