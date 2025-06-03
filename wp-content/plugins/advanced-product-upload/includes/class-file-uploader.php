<?php
class APU_File_Uploader {
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_apu_upload_file', [__CLASS__, 'handle_ajax_upload']);
        add_action('wp_ajax_nopriv_apu_upload_file', [__CLASS__, 'handle_ajax_upload']);
        add_filter('upload_mimes', [__CLASS__, 'add_custom_mime_types']);
    }

    public static function enqueue_assets() {
    global $post;
    
    // Check if the current post contains either the new or old shortcode
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'advance-product_submission_form') || 
         has_shortcode($post->post_content, 'product_submission_form'))) {
        
        wp_enqueue_style(
            'apu-frontend-css',
            APU_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            filemtime(APU_PLUGIN_DIR . 'assets/css/frontend.css')
        );

        wp_enqueue_script(
            'dropzone',
            'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js',
            [],
            '5.9.2',
            true
        );

        wp_enqueue_script(
            'apu-frontend-js',
            APU_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'dropzone'],
            filemtime(APU_PLUGIN_DIR . 'assets/js/frontend.js'),
            true
        );

        wp_localize_script('apu-frontend-js', 'apu_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'max_files' => APU_MAX_FILES,
            'max_file_size' => APU_MAX_FILE_SIZE,
            'allowed_file_types' => get_option('apu_allowed_file_types'),
            'i18n' => [
                'file_too_big' => __('File is too big ({{filesize}}MB). Max filesize: {{maxFilesize}}MB.', 'advanced-product-upload'),
                'invalid_type' => __('Invalid file type. Allowed: {{acceptedFiles}}', 'advanced-product-upload'),
                'upload_failed' => __('Upload failed', 'advanced-product-upload'),
                'drop_files_here' => __('Drop files here or click to upload', 'advanced-product-upload'),
                'remove_file' => __('Remove file', 'advanced-product-upload'),
                'cancel_upload' => __('Cancel upload', 'advanced-product-upload')
            ],
            'upload_nonce' => wp_create_nonce('apu_upload_nonce')
        ]);
    }
}

    public static function handle_ajax_upload() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in to upload files.', 'advanced-product-upload'), 403);
            }

            if (!wp_verify_nonce($_REQUEST['nonce'], 'apu_upload_nonce')) {
                throw new Exception(__('Security check failed.', 'advanced-product-upload'), 403);
            }

            if (!isset($_FILES['file'])) {
                throw new Exception(__('No file was uploaded.', 'advanced-product-upload'), 400);
            }

            $file = $_FILES['file'];
            $file_type = sanitize_text_field($_POST['file_type']);
            $allowed_types = get_option('apu_allowed_file_types');

            // Check file type
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_types[$file_type])) {
                throw new Exception(
                    sprintf(__('Invalid file type for %s. Allowed: %s', 'advanced-product-upload'), 
                    $file_type, 
                    implode(', ', $allowed_types[$file_type])),
                    400
                );
            }

            // Check file size
            if ($file['size'] > APU_MAX_FILE_SIZE) {
                throw new Exception(
                    sprintf(__('File is too large. Maximum size: %s', 'advanced-product-upload'), 
                    size_format(APU_MAX_FILE_SIZE)),
                    400
                );
            }

            // Process upload
            $upload_dir = APU_UPLOAD_DIR . date('Y/m/');
            if (!file_exists($upload_dir)) {
                wp_mkdir_p($upload_dir);
            }

            $filename = wp_unique_filename($upload_dir, $file['name']);
            $destination = $upload_dir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception(__('Error moving uploaded file.', 'advanced-product-upload'), 500);
            }

            // Add to media library
            $attachment = [
                'post_mime_type' => $file['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            $attach_id = wp_insert_attachment($attachment, $destination);
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $destination);
            wp_update_attachment_metadata($attach_id, $attach_data);

            wp_send_json_success([
                'id' => $attach_id,
                'url' => wp_get_attachment_url($attach_id),
                'name' => $filename
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }

    public static function add_custom_mime_types($mimes) {
        $mimes['emb'] = 'application/octet-stream';
        $mimes['dst'] = 'application/octet-stream';
        $mimes['svg'] = 'image/svg+xml';
        $mimes['eps'] = 'application/postscript';
        return $mimes;
    }
}