<?php
class APU_Product_Handler {
     public static function init() {
        // Changed shortcode tag
        add_shortcode('advance-product_submission_form', [__CLASS__, 'render_submission_form']);
        add_action('wp_ajax_apu_submit_product', [__CLASS__, 'handle_product_submission']);
        add_action('wp_ajax_nopriv_apu_submit_product', [__CLASS__, 'handle_product_submission']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }

    public static function render_submission_form() {
        if (!is_user_logged_in()) {
            return '<div class="apu-login-required">' . 
                   __('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to submit a product.', 'advanced-product-upload') . 
                   '</div>';
        }

        ob_start();
        include APU_PLUGIN_DIR . 'templates/product-submission-form.php';
        return ob_get_clean();
    }

    public static function handle_product_submission() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in to submit products.', 'advanced-product-upload'), 403);
            }

            if (!wp_verify_nonce($_POST['nonce'], 'apu_product_submission')) {
                throw new Exception(__('Security check failed.', 'advanced-product-upload'), 403);
            }

            $user_id = get_current_user_id();
            $title = sanitize_text_field($_POST['product_title']);
            $desc = wp_kses_post($_POST['product_description']);
            $price = floatval($_POST['product_price']);

            // Create product
            $product_id = wp_insert_post([
                'post_title' => $title,
                'post_content' => $desc,
                'post_status' => 'pending',
                'post_type' => 'product',
                'post_author' => $user_id,
            ]);

            if (is_wp_error($product_id)) {
                throw new Exception($product_id->get_error_message(), 500);
            }

            // Set product meta
            update_post_meta($product_id, '_price', $price);
            update_post_meta($product_id, '_regular_price', $price);
            wp_set_object_terms($product_id, 'simple', 'product_type');

            // Process specifications
            $specs = [
                'design_code' => sanitize_text_field($_POST['design_code'] ?? ''),
                'stitches' => intval($_POST['stitches'] ?? 0),
                'area' => floatval($_POST['area'] ?? 0),
                'height' => floatval($_POST['height'] ?? 0),
                'width' => floatval($_POST['width'] ?? 0),
                'formats' => sanitize_text_field($_POST['formats'] ?? ''),
                'needle' => sanitize_text_field($_POST['needle'] ?? '')
            ];

            foreach ($specs as $key => $value) {
                if (!empty($value)) {
                    update_post_meta($product_id, $key, $value);
                }
            }

            // Process category
            if (!empty($_POST['product_category'])) {
                $cat_id = (int) $_POST['product_category'];
                wp_set_post_terms($product_id, [$cat_id], 'product_cat');
            }

            // Process tags
            if (!empty($_POST['product_tags'])) {
                $tags = array_map('intval', (array) $_POST['product_tags']);
                $valid_tags = array_filter($tags, function($tag_id) {
                    return term_exists($tag_id, 'product_tag');
                });
                
                if (!empty($valid_tags)) {
                    wp_set_object_terms($product_id, $valid_tags, 'product_tag');
                }
            }

            // Process files
            $gallery_images = array_map('intval', (array) ($_POST['gallery_images'] ?? []));
            $design_files = array_map('intval', (array) ($_POST['design_files'] ?? []));

            // Set featured image
            if (!empty($gallery_images)) {
                set_post_thumbnail($product_id, $gallery_images[0]);
                
                // Add remaining images to gallery
                if (count($gallery_images) > 1) {
                    update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($gallery_images, 1)));
                }
            }

            // Set downloadable files
            if (!empty($design_files)) {
                $downloads = [];
                
                foreach ($design_files as $file_id) {
                    $file_url = wp_get_attachment_url($file_id);
                    $file_name = basename(get_attached_file($file_id));
                    
                    $downloads[md5($file_url)] = [
                        'name' => $file_name,
                        'file' => $file_url
                    ];
                }
                
                if (!empty($downloads)) {
                    $product = wc_get_product($product_id);
                    $product->set_downloads($downloads);
                    $product->set_downloadable('yes');
                    $product->save();
                }
            }

            // Notify admin
            wp_new_product_notification($product_id);

            wp_send_json_success([
                'message' => __('Product submitted successfully!', 'advanced-product-upload'),
                'redirect' => $price == 0 ? 
                    add_query_arg('product_id', $product_id, site_url('/download-product/')) : 
                    get_permalink($product_id)
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public static function enqueue_admin_assets() {
        wp_enqueue_style(
            'apu-admin-css',
            APU_PLUGIN_URL . 'assets/css/admin.css',
            [],
            filemtime(APU_PLUGIN_DIR . 'assets/css/admin.css')
        );

        wp_enqueue_script(
            'apu-admin-js',
            APU_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            filemtime(APU_PLUGIN_DIR . 'assets/js/admin.js'),
            true
        );
    }
}