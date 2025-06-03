<div class="apu-form-container">
    <form id="apu-product-form" method="post" enctype="multipart/form-data">
        <div class="apu-form-header">
            <h2><?php esc_html_e('✨ Submit Your Design ✨', 'advanced-product-upload'); ?></h2>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('how-to-upload'))); ?>" class="apu-help-button" target="_blank">
                <span class="apu-help-icon">?</span>
                <span class="apu-help-text"><?php esc_html_e('How to Upload', 'advanced-product-upload'); ?></span>
            </a>
        </div>

        <div class="apu-form-section">
            <h3><?php esc_html_e('Basic Information', 'advanced-product-upload'); ?></h3>
            
            <div class="apu-form-group">
                <label for="product-title"><?php esc_html_e('Product Title', 'advanced-product-upload'); ?>*</label>
                <input type="text" id="product-title" name="product_title" required>
            </div>
            
            <div class="apu-form-group">
                <label for="product-description"><?php esc_html_e('Description', 'advanced-product-upload'); ?>*</label>
                <textarea id="product-description" name="product_description" rows="5" required></textarea>
            </div>
            
            <div class="apu-form-group">
                <label for="product-price"><?php esc_html_e('Price', 'advanced-product-upload'); ?>*</label>
                <input type="number" id="product-price" name="product_price" min="0" step="0.01" required>
            </div>
        </div>

        <div class="apu-form-section">
            <h3><?php esc_html_e('Design Specifications', 'advanced-product-upload'); ?></h3>
            
            <div class="apu-specs-grid">
                <div class="apu-form-group">
                    <label for="design-code"><?php esc_html_e('Design Code', 'advanced-product-upload'); ?></label>
                    <input type="text" id="design-code" name="design_code">
                </div>
                
                <div class="apu-form-group">
                    <label for="stitches"><?php esc_html_e('Stitches', 'advanced-product-upload'); ?></label>
                    <input type="number" id="stitches" name="stitches" min="0">
                </div>
                
                <div class="apu-form-group">
                    <label for="area"><?php esc_html_e('Area (cm²)', 'advanced-product-upload'); ?></label>
                    <input type="number" id="area" name="area" min="0" step="0.1">
                </div>
                
                <div class="apu-form-group">
                    <label for="height"><?php esc_html_e('Height (mm)', 'advanced-product-upload'); ?></label>
                    <input type="number" id="height" name="height" min="0" step="0.1">
                </div>
                
                <div class="apu-form-group">
                    <label for="width"><?php esc_html_e('Width (mm)', 'advanced-product-upload'); ?></label>
                    <input type="number" id="width" name="width" min="0" step="0.1">
                </div>
                
                <div class="apu-form-group">
                    <label for="formats"><?php esc_html_e('Formats', 'advanced-product-upload'); ?></label>
                    <input type="text" id="formats" name="formats">
                </div>
                
                <div class="apu-form-group">
                    <label for="needle"><?php esc_html_e('Needle', 'advanced-product-upload'); ?></label>
                    <input type="text" id="needle" name="needle">
                </div>
            </div>
        </div>

        <div class="apu-form-section">
            <h3><?php esc_html_e('Visual Assets', 'advanced-product-upload'); ?></h3>
            
            <div class="apu-upload-area">
                <div class="apu-dropzone" id="gallery-dropzone">
                    <div class="dz-message">
                        <?php esc_html_e('Drop design preview images here or click to upload', 'advanced-product-upload'); ?><br>
                        <small><?php esc_html_e('(JPG, PNG, GIF, SVG - Max 5MB each)', 'advanced-product-upload'); ?></small>
                    </div>
                </div>
                <div class="apu-upload-preview" id="gallery-preview"></div>
            </div>
        </div>

        <div class="apu-form-section">
            <h3><?php esc_html_e('Design Files', 'advanced-product-upload'); ?></h3>
            
            <div class="apu-upload-area">
                <div class="apu-dropzone" id="design-dropzone">
                    <div class="dz-message">
                        <?php esc_html_e('Drop design files here or click to upload', 'advanced-product-upload'); ?><br>
                        <small><?php esc_html_e('(EMB, DST, PES, SVG, EPS, ZIP - Max 20MB each)', 'advanced-product-upload'); ?></small>
                    </div>
                </div>
                <div class="apu-upload-preview" id="design-preview"></div>
            </div>
        </div>

        <div class="apu-form-section">
            <h3><?php esc_html_e('Categories & Tags', 'advanced-product-upload'); ?></h3>
            
            <div class="apu-form-group">
                <label for="product-category"><?php esc_html_e('Category', 'advanced-product-upload'); ?></label>
                <select id="product-category" name="product_category">
                    <?php foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) as $term): ?>
                        <option value="<?php echo esc_attr($term->term_id); ?>">
                            <?php echo esc_html($term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="apu-form-group">
                <label><?php esc_html_e('Tags', 'advanced-product-upload'); ?></label>
                <div class="apu-tags-container">
                    <?php foreach (get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]) as $tag): ?>
                        <div class="apu-tag-item">
                            <input type="checkbox" id="tag-<?php echo esc_attr($tag->term_id); ?>" 
                                   name="product_tags[]" value="<?php echo esc_attr($tag->term_id); ?>">
                            <label for="tag-<?php echo esc_attr($tag->term_id); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="apu-form-footer">
            <button type="submit" class="apu-submit-button">
                <?php esc_html_e('🚀 Submit Product', 'advanced-product-upload'); ?>
            </button>
            <div class="apu-status-message" id="apu-status"></div>
        </div>

        <input type="hidden" name="action" value="apu_submit_product">
        <?php wp_nonce_field('apu_product_submission', 'apu_nonce'); ?>
    </form>
</div>