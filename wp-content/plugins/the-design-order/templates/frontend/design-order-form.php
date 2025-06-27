<div class="the-design-order-form">
    <?php if (isset($_GET['error']) && $_GET['error'] === 'required_fields') : ?>
        <div class="the-design-order-notice error">
            <?php _e('Please fill in all required fields.', 'the-design-order'); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'creation_failed') : ?>
        <div class="the-design-order-notice error">
            <?php _e('Failed to create design order. Please try again.', 'the-design-order'); ?>
        </div>
    <?php endif; ?>
    
    <form id="the-design-order-form" method="post" enctype="multipart/form-data">
        <div class="the-design-order-form-group">
            <label for="design_order_name"><?php _e('Name', 'the-design-order'); ?> *</label>
            <input type="text" id="design_order_name" name="design_order_name" required>
        </div>
        
        <div class="the-design-order-form-group">
            <label for="design_order_mobile"><?php _e('Mobile Number', 'the-design-order'); ?> *</label>
            <input type="tel" id="design_order_mobile" name="design_order_mobile" required>
        </div>
        
        <div class="the-design-order-form-group">
            <label for="design_order_email"><?php _e('Email Address', 'the-design-order'); ?></label>
            <input type="email" id="design_order_email" name="design_order_email">
        </div>
        
        <div class="the-design-order-form-group">
            <label for="design_order_description"><?php _e('Design Description', 'the-design-order'); ?> *</label>
            <textarea id="design_order_description" name="design_order_description" rows="5" required></textarea>
        </div>
        
        <div class="the-design-order-form-group">
            <label><?php _e('Upload Documents', 'the-design-order'); ?></label>
            <div class="the-design-order-file-upload">
                <input type="file" name="design_order_files[]" id="design_order_files" multiple style="display: none;">
                <button type="button" class="the-design-order-upload-button">
                    <?php _e('Select Files', 'the-design-order'); ?>
                </button>
                <span class="the-design-order-file-info">
                    <?php _e('No files selected', 'the-design-order'); ?>
                </span>
            </div>
            <div class="the-design-order-file-list"></div>
            <small class="the-design-order-file-hint">
                <?php 
                printf(
                    __('Maximum file size: %s. Allowed file types: %s', 'the-design-order'),
                    size_format(wp_max_upload_size()),
                    'JPG, PNG, GIF, PDF, DOC, XLS, PPT, PSD, AI, EPS, ZIP, RAR'
                );
                ?>
            </small>
        </div>
        
        <?php wp_nonce_field('the_design_order_submit', 'the_design_order_nonce'); ?>
        
        <div class="the-design-order-form-submit">
            <button type="submit" name="the_design_order_submit" class="button">
                <?php _e('Submit Design Order', 'the-design-order'); ?>
            </button>
        </div>
    </form>
</div>