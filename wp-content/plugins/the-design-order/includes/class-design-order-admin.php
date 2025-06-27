<?php
namespace TheDesignOrder;

class Design_Order_Admin {
    private static $instance = null;
    
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_design_order', [$this, 'save_meta_boxes'], 10, 2);
        add_filter('manage_design_order_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_design_order_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_filter('manage_edit-design_order_sortable_columns', [$this, 'add_sortable_columns']);
        add_action('pre_get_posts', [$this, 'handle_custom_orderby']);
        add_action('restrict_manage_posts', [$this, 'add_status_filter']);
        add_filter('parse_query', [$this, 'handle_status_filter']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    public function enqueue_scripts($hook) {
        global $post_type;
        
        if ('design_order' !== $post_type && 'edit.php' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'the-design-order-admin',
            THE_DESIGN_ORDER_PLUGIN_URL . 'assets/css/admin.css',
            [],
            THE_DESIGN_ORDER_VERSION
        );
        
        wp_enqueue_script(
            'the-design-order-admin',
            THE_DESIGN_ORDER_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable', 'wp-util'],
            THE_DESIGN_ORDER_VERSION,
            true
        );
        
        wp_localize_script('the-design-order-admin', 'theDesignOrderAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('the-design-order-admin-nonce'),
            'i18n' => [
                'confirm_delete' => __('Are you sure you want to delete this file?', 'the-design-order'),
                'error' => __('Error occurred', 'the-design-order')
            ]
        ]);
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'design_order_details',
            __('Design Order Details', 'the-design-order'),
            [$this, 'render_details_meta_box'],
            'design_order',
            'normal',
            'high'
        );
        
        add_meta_box(
            'design_order_files',
            __('Customer Files', 'the-design-order'),
            [$this, 'render_files_meta_box'],
            'design_order',
            'normal',
            'high'
        );
        
        add_meta_box(
            'design_order_proposals',
            __('Design Proposals', 'the-design-order'),
            [$this, 'render_proposals_meta_box'],
            'design_order',
            'side',
            'default'
        );
        
        add_meta_box(
            'design_order_deliverables',
            __('Design Deliverables', 'the-design-order'),
            [$this, 'render_deliverables_meta_box'],
            'design_order',
            'normal',
            'high'
        );
    }
    
    public function render_details_meta_box($post) {
        $name = get_post_meta($post->ID, '_design_order_name', true);
        $mobile = get_post_meta($post->ID, '_design_order_mobile', true);
        $email = get_post_meta($post->ID, '_design_order_email', true);
        $status = wp_get_object_terms($post->ID, 'design_order_status', ['fields' => 'slugs']);
        $status = !empty($status) ? $status[0] : 'new';
        
        wp_nonce_field('the_design_order_save', 'the_design_order_nonce');
        
        include THE_DESIGN_ORDER_PLUGIN_DIR . 'templates/admin/design-order-details.php';
    }
    
    public function render_files_meta_box($post) {
        $files = get_post_meta($post->ID, '_design_order_files', false);
        
        include THE_DESIGN_ORDER_PLUGIN_DIR . 'templates/admin/design-order-files.php';
    }
    
    public function render_proposals_meta_box($post) {
        $proposals = get_post_meta($post->ID, '_design_order_proposals', true);
        $proposals = is_array($proposals) ? $proposals : [];
        $approved_proposal = get_post_meta($post->ID, '_design_order_approved_proposal', true);
        
        include THE_DESIGN_ORDER_PLUGIN_DIR . 'templates/admin/design-order-proposals.php';
    }
    
    public function render_deliverables_meta_box($post) {
        $deliverables = get_post_meta($post->ID, '_design_order_deliverables', false);
        $status = wp_get_object_terms($post->ID, 'design_order_status', ['fields' => 'slugs']);
        $status = !empty($status) ? $status[0] : 'new';
        
        include THE_DESIGN_ORDER_PLUGIN_DIR . 'templates/admin/design-order-deliverables.php';
    }
    
    public function save_meta_boxes($post_id, $post) {
        if (!isset($_POST['the_design_order_nonce']) || !wp_verify_nonce($_POST['the_design_order_nonce'], 'the_design_order_save')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save basic details
        if (isset($_POST['design_order_name'])) {
            update_post_meta($post_id, '_design_order_name', sanitize_text_field($_POST['design_order_name']));
        }
        
        if (isset($_POST['design_order_mobile'])) {
            update_post_meta($post_id, '_design_order_mobile', sanitize_text_field($_POST['design_order_mobile']));
        }
        
        if (isset($_POST['design_order_email'])) {
            update_post_meta($post_id, '_design_order_email', sanitize_email($_POST['design_order_email']));
        }
        
        // Save status
        if (isset($_POST['design_order_status'])) {
            wp_set_object_terms($post_id, sanitize_text_field($_POST['design_order_status']), 'design_order_status', false);
        }
        
        // Save proposals
        if (isset($_POST['design_order_proposal_amount']) && isset($_POST['design_order_proposal_description'])) {
            $current_user = wp_get_current_user();
            $proposal = [
                'user_id' => $current_user->ID,
                'user_name' => $current_user->display_name,
                'amount' => floatval($_POST['design_order_proposal_amount']),
                'description' => sanitize_textarea_field($_POST['design_order_proposal_description']),
                'date' => current_time('mysql')
            ];
            
            $proposals = get_post_meta($post_id, '_design_order_proposals', true);
            $proposals = is_array($proposals) ? $proposals : [];
            $proposals[] = $proposal;
            
            update_post_meta($post_id, '_design_order_proposals', $proposals);
        }
        
        // Save approved proposal
        if (isset($_POST['design_order_approve_proposal']) && is_numeric($_POST['design_order_approve_proposal'])) {
            $proposal_index = intval($_POST['design_order_approve_proposal']);
            $proposals = get_post_meta($post_id, '_design_order_proposals', true);
            
            if (isset($proposals[$proposal_index])) {
                update_post_meta($post_id, '_design_order_approved_proposal', $proposals[$proposal_index]);
                
                // Create WooCommerce product for payment
                $this->create_design_product($post_id, $proposals[$proposal_index]);
                
                // Change status to "in_progress"
                wp_set_object_terms($post_id, 'in_progress', 'design_order_status', false);
            }
        }
        
        // Handle deliverables upload
        if (!empty($_FILES['design_order_deliverables'])) {
            $this->handle_deliverables_upload($post_id);
        }
    }
    
    private function create_design_product($order_id, $proposal) {
        $product = new \WC_Product();
        $product->set_name(sprintf(__('Design Order #%d', 'the-design-order'), $order_id));
        $product->set_status('private');
        $product->set_catalog_visibility('hidden');
        $product->set_price($proposal['amount'] * 1.1); // Add 10%
        $product->set_regular_price($proposal['amount'] * 1.1);
        $product->set_virtual(true);
        $product->set_downloadable(true);
        $product->set_sold_individually(true);
        
        $product_id = $product->save();
        
        if ($product_id) {
            update_post_meta($order_id, '_design_order_product_id', $product_id);
            return $product_id;
        }
        
        return false;
    }
    
    private function handle_deliverables_upload($order_id) {
        $files = $_FILES['design_order_deliverables'];
        
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
                    add_post_meta($order_id, '_design_order_deliverables', $attachment_id);
                    
                    // Add to product downloads if product exists
                    $product_id = get_post_meta($order_id, '_design_order_product_id', true);
                    if ($product_id) {
                        $this->add_file_to_product($product_id, $attachment_id);
                    }
                }
            }
        }
        
        // Change status to "design_received"
        wp_set_object_terms($order_id, 'design_received', 'design_order_status', false);
    }
    
    private function add_file_to_product($product_id, $file_id) {
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $file_url = wp_get_attachment_url($file_id);
        $file_name = basename($file_url);
        
        $downloads = $product->get_downloads();
        
        $download = new \WC_Product_Download();
        $download->set_name($file_name);
        $download->set_id(wp_generate_uuid4());
        $download->set_file($file_url);
        
        $downloads[$download->get_id()] = $download;
        
        $product->set_downloads($downloads);
        $product->save();
    }
    
    public function add_custom_columns($columns) {
        $columns = [
            'cb' => $columns['cb'],
            'title' => __('Order', 'the-design-order'),
            'customer' => __('Customer', 'the-design-order'),
            'status' => __('Status', 'the-design-order'),
            'date' => __('Date', 'the-design-order')
        ];
        
        return $columns;
    }
    
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'customer':
                $name = get_post_meta($post_id, '_design_order_name', true);
                $email = get_post_meta($post_id, '_design_order_email', true);
                
                echo esc_html($name);
                if ($email) {
                    echo '<br><small>' . esc_html($email) . '</small>';
                }
                break;
                
            case 'status':
                $status = wp_get_object_terms($post_id, 'design_order_status', ['fields' => 'names']);
                if (!empty($status)) {
                    echo esc_html($status[0]);
                }
                break;
        }
    }
    
    public function add_sortable_columns($columns) {
        $columns['customer'] = 'customer';
        $columns['status'] = 'status';
        return $columns;
    }
    
    public function handle_custom_orderby($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'design_order') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'customer') {
            $query->set('meta_key', '_design_order_name');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'status') {
            $query->set('orderby', 'tax_query');
            $query->set('tax_query', [
                [
                    'taxonomy' => 'design_order_status',
                    'field' => 'slug'
                ]
            ]);
        }
    }
    
    public function add_status_filter() {
        global $typenow;
        
        if ($typenow === 'design_order') {
            $taxonomy = 'design_order_status';
            $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
            $info_taxonomy = get_taxonomy($taxonomy);
            
            wp_dropdown_categories([
                'show_option_all' => __("Show All Statuses", 'the-design-order'),
                'taxonomy' => $taxonomy,
                'name' => $taxonomy,
                'orderby' => 'name',
                'selected' => $selected,
                'show_count' => true,
                'hide_empty' => false,
                'value_field' => 'slug'
            ]);
        }
    }
    
    public function handle_status_filter($query) {
        global $pagenow;
        $type = 'design_order';
        
        if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === $type && isset($_GET['design_order_status']) && $_GET['design_order_status'] !== '') {
            $query->query_vars['tax_query'] = [
                [
                    'taxonomy' => 'design_order_status',
                    'field' => 'slug',
                    'terms' => $_GET['design_order_status']
                ]
            ];
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Design Orders', 'the-design-order'),
            __('Design Orders', 'the-design-order'),
            'manage_options',
            'edit.php?post_type=design_order'
        );
    }
}