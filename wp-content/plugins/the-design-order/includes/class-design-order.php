<?php
namespace TheDesignOrder;

class Design_Order {
    private static $instance = null;
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Core functionality
    }
    
    public static function activate() {
        // Create necessary pages
        self::create_pages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_pages() {
        $pages = [
            'new-design-order' => [
                'title' => __('New Design Order', 'the-design-order'),
                'content' => '[design_order_form]',
                'option' => 'the_design_order_form_page_id'
            ],
            'design-orders' => [
                'title' => __('My Design Orders', 'the-design-order'),
                'content' => '[design_orders_list]',
                'option' => 'the_design_order_list_page_id'
            ]
        ];
        
        foreach ($pages as $slug => $page) {
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                $page_id = wp_insert_post([
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ]);
                
                if ($page_id && isset($page['option'])) {
                    update_option($page['option'], $page_id);
                }
            }
        }
    }
    
    public static function get_upload_dir() {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/the-design-order';
        
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        
        return $dir;
    }
    
    public static function get_upload_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/the-design-order';
    }
    
    public static function handle_file_upload($file, $order_id) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_dir = self::get_upload_dir();
        $upload_url = self::get_upload_url();
        
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
            // Create attachment post
            $attachment = [
                'post_mime_type' => $movefile['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($movefile['file'])),
                'post_content' => '',
                'post_status' => 'inherit',
                'guid' => $movefile['url']
            ];
            
            $attach_id = wp_insert_attachment($attachment, $movefile['file'], $order_id);
            
            if (!is_wp_error($attach_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);
                
                return $attach_id;
            }
        }
        
        return false;
    }
}