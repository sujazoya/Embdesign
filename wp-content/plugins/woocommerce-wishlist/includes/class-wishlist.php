<?php
class WC_Wishlist {
    private static $instance = null;
    private $wishlist = array();
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Load assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add button to product page
        add_action('woocommerce_after_add_to_cart_button', array($this, 'add_to_wishlist_button'), 20);
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_to_wishlist_button_loop'), 20);
        
        // Register shortcode
        add_shortcode('woocommerce_wishlist', array($this, 'wishlist_shortcode'));
        
        // Add endpoint for wishlist page
        add_action('init', array($this, 'add_wishlist_endpoint'));
        add_filter('query_vars', array($this, 'add_wishlist_query_var'));
        add_filter('woocommerce_get_query_vars', array($this, 'add_wishlist_query_var'));
        add_filter('woocommerce_account_menu_items', array($this, 'add_wishlist_link_my_account'));
        add_action('woocommerce_account_wishlist_endpoint', array($this, 'wishlist_content'));
        
        // Initialize wishlist
        add_action('init', array($this, 'init_wishlist'));
    }
    
    public static function activate() {
        // Add wishlist endpoint on activation
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('wcwl-style', WCWL_PLUGIN_URL . 'assets/css/wishlist.css', array(), WCWL_VERSION);
        wp_enqueue_script('wcwl-script', WCWL_PLUGIN_URL . 'assets/js/wishlist.js', array('jquery'), WCWL_VERSION, true);
        
        wp_localize_script('wcwl-script', 'wcwl_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcwl-nonce'),
            'added_to_wishlist' => __('Product added to wishlist', 'woocommerce-wishlist'),
            'removed_from_wishlist' => __('Product removed from wishlist', 'woocommerce-wishlist'),
            'error' => __('Error occurred', 'woocommerce-wishlist')
        ));
    }
    
    public function init_wishlist() {
        if (is_user_logged_in()) {
            $this->wishlist = get_user_meta(get_current_user_id(), 'wcwl_wishlist', true);
            
            if (!is_array($this->wishlist)) {
                $this->wishlist = array();
            }
        } else {
            $this->wishlist = isset($_COOKIE['wcwl_wishlist']) ? json_decode(wp_unslash($_COOKIE['wcwl_wishlist']), true) : array();
        }
    }
    
    public function add_to_wishlist_button() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $is_in_wishlist = $this->is_product_in_wishlist($product->get_id());
        
        echo sprintf(
            '<a href="%s" class="button wcwl-add-to-wishlist %s" data-product-id="%d" data-nonce="%s">%s</a>',
            esc_url('#'),
            $is_in_wishlist ? 'added' : '',
            esc_attr($product->get_id()),
            wp_create_nonce('wcwl-add-' . $product->get_id()),
            $is_in_wishlist ? __('Remove from Wishlist', 'woocommerce-wishlist') : __('Add to Wishlist', 'woocommerce-wishlist')
        );
    }
    
    public function add_to_wishlist_button_loop() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $is_in_wishlist = $this->is_product_in_wishlist($product->get_id());
        
        echo sprintf(
            '<a href="%s" class="wcwl-add-to-wishlist-loop %s" data-product-id="%d" data-nonce="%s" title="%s">%s</a>',
            esc_url('#'),
            $is_in_wishlist ? 'added' : '',
            esc_attr($product->get_id()),
            wp_create_nonce('wcwl-add-' . $product->get_id()),
            $is_in_wishlist ? __('Remove from Wishlist', 'woocommerce-wishlist') : __('Add to Wishlist', 'woocommerce-wishlist'),
            $is_in_wishlist ? '♥' : '♡'
        );
    }
    
    public function is_product_in_wishlist($product_id) {
        return in_array($product_id, $this->wishlist);
    }
    
    public function add_to_wishlist($product_id) {
        if (!in_array($product_id, $this->wishlist)) {
            $this->wishlist[] = $product_id;
            $this->save_wishlist();
            return true;
        }
        return false;
    }
    
    public function remove_from_wishlist($product_id) {
        $key = array_search($product_id, $this->wishlist);
        
        if ($key !== false) {
            unset($this->wishlist[$key]);
            $this->save_wishlist();
            return true;
        }
        return false;
    }
    
    private function save_wishlist() {
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'wcwl_wishlist', $this->wishlist);
        } else {
            setcookie('wcwl_wishlist', json_encode($this->wishlist), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }
    }
    
    public function get_wishlist() {
        return $this->wishlist;
    }
    
    public function add_wishlist_endpoint() {
        add_rewrite_endpoint('wishlist', EP_ROOT | EP_PAGES);
    }
    
    public function add_wishlist_query_var($vars) {
        $vars[] = 'wishlist';
        return $vars;
    }
    
    public function add_wishlist_link_my_account($items) {
        $items['wishlist'] = __('Wishlist', 'woocommerce-wishlist');
        return $items;
    }
    
    public function wishlist_content() {
        wc_get_template('wishlist-view.php', array(), '', WCWL_PLUGIN_PATH . 'templates/');
    }
    
    public function wishlist_shortcode($atts) {
        ob_start();
        $this->wishlist_content();
        return ob_get_clean();
    }
}