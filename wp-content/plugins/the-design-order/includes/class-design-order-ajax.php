<?php
namespace TheDesignOrder;

class Design_Order_Ajax {
    private static $instance = null;
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Frontend AJAX
        add_action('wp_ajax_the_design_order_add_file', [$this, 'add_file']);
        add_action('wp_ajax_the_design_order_remove_file', [$this, 'remove_file']);
        
        // Admin AJAX
        add_action('wp_ajax_the_design_order_admin_remove_file', [$this, 'admin_remove_file']);
        add_action('wp_ajax_the_design_order_admin_remove_deliverable', [$this, 'admin_remove_deliverable']);
        add_action('wp_ajax_the_design_order_admin_complete_order', [$this, 'admin_complete_order']);
    }
    
    public function add_file() {
        check_ajax_referer('the-design-order-nonce', 'nonce');
        
        if (empty($_FILES['file'])) {
            wp_send_json_error(__('No file uploaded', 'the-design-order'));
        }
        
        $file = $_FILES['file'];
        $allowed_types = $this->get_allowed_file_types();
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type', 'the-design-order'));
        }
        
        if ($file['size'] > wp_max_upload_size()) {
            wp_send_json_error(__('File is too large', 'the-design-order'));
        }
        
        $upload_dir = Design_Order::get_upload_dir();
        $upload_url = Design_Order::get_upload_url();
        
        $file_data = [
            'name' => sanitize_file_name($file['name']),
            'type' => $file['type'],
            'tmp_name' => $file['tmp_name'],
            'error' => $file['error'],
            'size' => $file['size']
        ];
        
        $overrides = [
            'test_form' => false,
            'test_type' => false,
            'upload_dir' => $upload_dir,
            'upload_url' => $upload_url
        ];
        
        $movefile = wp_handle_upload($file_data, $overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success([
                'name' => $file['name'],
                'url' => $movefile['url'],
                'size' => size_format($file['size'])
            ]);
        } else {
            wp_send_json_error($movefile['error']);
        }
    }
    
    public function remove_file() {
        check_ajax_referer('the-design-order-nonce', 'nonce');
        
        if (empty($_POST['file_name'])) {
            wp_send_json_error(__('No file specified', 'the-design-order'));
        }
        
        $file_name = sanitize_text_field($_POST['file_name']);
        $upload_dir = Design_Order::get_upload_dir();
        $file_path = $upload_dir . '/' . $file_name;
        
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                wp_send_json_success();
            } else {
                wp_send_json_error(__('Could not delete file', 'the-design-order'));
            }
        } else {
            wp_send_json_error(__('File not found', 'the-design-order'));
        }
    }
    
    public function admin_remove_file() {
        check_ajax_referer('the-design-order-admin-nonce', 'nonce');
        
        if (empty($_POST['post_id']) || empty($_POST['file_id'])) {
            wp_send_json_error(__('Invalid request', 'the-design-order'));
        }
        
        $post_id = intval($_POST['post_id']);
        $file_id = intval($_POST['file_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Permission denied', 'the-design-order'));
        }
        
        // Remove from post meta
        delete_post_meta($post_id, '_design_order_files', $file_id);
        
        // Delete attachment
        wp_delete_attachment($file_id, true);
        
        wp_send_json_success();
    }
    
    public function admin_remove_deliverable() {
        check_ajax_referer('the-design-order-admin-nonce', 'nonce');
        
        if (empty($_POST['post_id']) || empty($_POST['file_id'])) {
            wp_send_json_error(__('Invalid request', 'the-design-order'));
        }
        
        $post_id = intval($_POST['post_id']);
        $file_id = intval($_POST['file_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Permission denied', 'the-design-order'));
        }
        
        // Remove from post meta
        delete_post_meta($post_id, '_design_order_deliverables', $file_id);
        
        // Remove from product downloads if exists
        $product_id = get_post_meta($post_id, '_design_order_product_id', true);
        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $downloads = $product->get_downloads();
                $file_url = wp_get_attachment_url($file_id);
                
                foreach ($downloads as $key => $download) {
                    if ($download->get_file() === $file_url) {
                        unset($downloads[$key]);
                        break;
                    }
                }
                
                $product->set_downloads($downloads);
                $product->save();
            }
        }
        
        // Delete attachment
        wp_delete_attachment($file_id, true);
        
        wp_send_json_success();
    }
    
    public function admin_complete_order() {
        check_ajax_referer('the-design-order-admin-nonce', 'nonce');
        
        if (empty($_POST['post_id'])) {
            wp_send_json_error(__('Invalid request', 'the-design-order'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Permission denied', 'the-design-order'));
        }
        
        // Change status to "completed"
        wp_set_object_terms($post_id, 'completed', 'design_order_status', false);
        
        wp_send_json_success();
    }
    
    private function get_allowed_file_types() {
        return [
            'image/jpeg',
            'image/gif',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/octet-stream',
            'application/postscript',
            'application/zip',
            'application/x-rar-compressed'
        ];
    }
}