<?php
namespace TheDesignOrder;

class Design_Order_Post_Types {
    private static $instance = null;
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
    }
    
    public function register_post_types() {
        // Design Order CPT
        register_post_type('design_order',
            [
                'labels' => [
                    'name' => __('Design Orders', 'the-design-order'),
                    'singular_name' => __('Design Order', 'the-design-order'),
                    'add_new' => __('Add New', 'the-design-order'),
                    'add_new_item' => __('Add New Design Order', 'the-design-order'),
                    'edit_item' => __('Edit Design Order', 'the-design-order'),
                    'new_item' => __('New Design Order', 'the-design-order'),
                    'view_item' => __('View Design Order', 'the-design-order'),
                    'search_items' => __('Search Design Orders', 'the-design-order'),
                    'not_found' => __('No design orders found', 'the-design-order'),
                    'not_found_in_trash' => __('No design orders found in Trash', 'the-design-order'),
                    'menu_name' => __('Design Orders', 'the-design-order')
                ],
                'public' => true,
                'has_archive' => false,
                'rewrite' => ['slug' => 'design-orders'],
                'supports' => ['title', 'editor', 'author', 'custom-fields'],
                'show_in_menu' => 'woocommerce',
                'show_in_rest' => true,
                'capability_type' => 'post',
                'capabilities' => [
                    'create_posts' => 'do_not_allow' // Disable direct creation
                ],
                'map_meta_cap' => true
            ]
        );
    }
    
    public function register_taxonomies() {
        // Design Order Status
        register_taxonomy('design_order_status', 'design_order',
            [
                'labels' => [
                    'name' => __('Statuses', 'the-design-order'),
                    'singular_name' => __('Status', 'the-design-order'),
                    'search_items' => __('Search Statuses', 'the-design-order'),
                    'all_items' => __('All Statuses', 'the-design-order'),
                    'edit_item' => __('Edit Status', 'the-design-order'),
                    'update_item' => __('Update Status', 'the-design-order'),
                    'add_new_item' => __('Add New Status', 'the-design-order'),
                    'new_item_name' => __('New Status Name', 'the-design-order'),
                    'menu_name' => __('Statuses', 'the-design-order')
                ],
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'design-order-status'],
                'show_in_rest' => true
            ]
        );
        
        // Add default terms
        $this->add_default_status_terms();
    }
    
    private function add_default_status_terms() {
        $terms = [
            'new' => __('New', 'the-design-order'),
            'in_progress' => __('In Progress', 'the-design-order'),
            'design_received' => __('Design Received', 'the-design-order'),
            'completed' => __('Completed', 'the-design-order'),
            'cancelled' => __('Cancelled', 'the-design-order')
        ];
        
        foreach ($terms as $slug => $name) {
            if (!term_exists($slug, 'design_order_status')) {
                wp_insert_term($name, 'design_order_status', ['slug' => $slug]);
            }
        }
    }
}