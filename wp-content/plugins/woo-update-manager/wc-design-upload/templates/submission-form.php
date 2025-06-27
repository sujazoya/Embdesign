<?php
/**
 * Design Submission Form
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wcdu-form-container">
    <h2><?php _e('Submit Your Design', 'wc-design-upload'); ?></h2>
    <?php if (isset($error)) : ?>
        <div class="wcdu-error-message"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="wcdu-form">
        <label><?php _e('Design Title', 'wc-design-upload'); ?></label>
        <input type="text" name="product_title" required value="<?php echo esc_attr($form_data['product_title'] ?? ''); ?>">
        
        <label><?php _e('Description', 'wc-design-upload'); ?></label>
        <textarea name="product_desc" required><?php echo esc_textarea($form_data['product_desc'] ?? ''); ?></textarea>
        
        <label><?php _e('Design Files (DST, PES, ZIP)', 'wc-design-upload'); ?></label>
        <input type="file" name="design_files[]" multiple accept=".dst,.pes,.zip" required>
        
        <button type="submit" name="wcdu_submit"><?php _e('Submit Design', 'wc-design-upload'); ?></button>
    </form>
</div>