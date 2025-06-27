<?php
namespace TheDesignOrder;

class Design_Order_Frontend {
    private static $instance = null;
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('design_order_form', [$this, 'design_order_form_shortcode']);
        add_shortcode('design_orders_list', [$this, 'design_orders_list_shortcode']);
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('template_redirect', [$this, 'handle_form_submission']);
        
        // Add to WooCommerce my account
        add_filter('woocommerce_account_menu_items', [$this, 'add_my_account_menu_item']);
        add_action('woocommerce_account_design-orders_endpoint', [$this, 'my_account_design_orders_content']);
        add_action('init', [$this, 'add_endpoint']);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'the-design-order-frontend',
            THE_DESIGN_ORDER_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            THE_DESIGN_ORDER_VERSION
        );
        
        wp_enqueue_script(
            'the-design-order-frontend',
            THE_DESIGN_ORDER_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'jquery-ui-sortable'],
            THE_DESIGN_ORDER_VERSION,
            true
        );
        
        wp_localize_script('the-design-order-frontend', 'theDesignOrder', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('the-design-order-nonce'),
            'max_files' => 10,
            'max_file_size' => wp_max_upload_size(),
            'allowed_file_types' => $this->get_allowed_file_types(),
            'i18n' => [
                'file_too_large' => __('File is too large', 'the-design-order'),
                'invalid_file_type' => __('Invalid file type', 'the-design-order'),
                'max_files_reached' => __('Maximum number of files reached', 'the-design-order'),
                'remove_file' => __('Remove file', 'the-design-order'),
                'add_more_files' => __('Add more files', 'the-design-order')
            ]
        ]);
    }
    
    public function design_order_form_shortcode() {
        ob_start();
        include THE_DESIGN_ORDER_PLUGIN_DIR . 'templates/frontend/design-order-form.php';
        return ob_get_clean();
    }
    
    public function design_orders_list_shortcode() {
        if (!is_user_logged_in()) {
            return '<div class="the-design-order-notice">' . __('Please log in to view your design orders.', 'the-design-order') . '</div>';
        }
        
        ob_start();
        $this->display_user_design_orders();
        return ob_get_clean();
    }
    
    private function display_user_design_orders() {
        $user_id = get_current_user_id();
        $orders = get_posts([
            'post_type' => 'design_order',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        include THE_DESIGN_ORDER_PLUGIN_DIR . 'templates/frontend/design-order-list.php';
    }
    
    public function handle_form_submission() {
        if (!isset($_POST['the_design_order_submit']) || !wp_verify_nonce($_POST['the_design_order_nonce'], 'the_design_order_submit')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $name = sanitize_text_field($_POST['design_order_name']);
        $mobile = sanitize_text_field($_POST['design_order_mobile']);
        $email = sanitize_email($_POST['design_order_email']);
        $description = sanitize_textarea_field($_POST['design_order_description']);
        
        if (empty($name) || empty($mobile) || empty($description)) {
            wp_redirect(add_query_arg('error', 'required_fields', wp_get_referer()));
            exit;
        }
        
        // Create design order post
        $order_id = wp_insert_post([
            'post_type' => 'design_order',
            'post_title' => 'Design Order #' . uniqid(),
            'post_content' => $description,
            'post_status' => 'publish',
            'post_author' => $user_id
        ]);
        
        if (is_wp_error($order_id)) {
            wp_redirect(add_query_arg('error', 'creation_failed', wp_get_referer()));
            exit;
        }
        
        // Save meta data
        update_post_meta($order_id, '_design_order_name', $name);
        update_post_meta($order_id, '_design_order_mobile', $mobile);
        update_post_meta($order_id, '_design_order_email', $email);
        
        // Set status to "new"
        wp_set_object_terms($order_id, 'new', 'design_order_status', false);
        
        // Handle file uploads
        if (!empty($_FILES['design_order_files'])) {
            $this->handle_order_files_upload($order_id);
        }
        
        // Redirect to thank you page
        $thank_you_page = get_permalink(get_option('the_design_order_thank_you_page_id'));
        wp_redirect(add_query_arg('order_id', $order_id, $thank_you_page));
        exit;
    }
    
    private function handle_order_files_upload($order_id) {
        $files = $_FILES['design_order_files'];
        
        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
                
                $attachment_id = Design_Order::handle_file_upload($file, $order_id);
                
                if ($attachment_id) {
                    add_post_meta($order_id, '_design_order_files', $attachment_id);
                }
            }
        }
    }
    
    private function get_allowed_file_types() {
        return [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc|docx' => 'application/msword',
            'xls|xlsx' => 'application/vnd.ms-excel',
            'ppt|pptx' => 'application/vnd.ms-powerpoint',
            'psd' => 'application/octet-stream',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];
    }
    
    public function add_my_account_menu_item($items) {
        $items['design-orders'] = __('Design Orders', 'the-design-order');
        return $items;
    }
    
    public function add_endpoint() {
        add_rewrite_endpoint('design-orders', EP_PAGES);
    }
    
    public function my_account_design_orders_content() {
        $this->display_user_design_orders();
    }
}