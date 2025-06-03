<?php
if (!defined('ABSPATH')) exit;

class WC_Wallet_Payu {
    
    private static $instance;
    
    public static function instance() {
        if (!isset(self::$instance) && !(self::$instance instanceof WC_Wallet_Payu)) {
            self::$instance = new WC_Wallet_Payu();
            self::$instance->init();
        }
        return self::$instance;
    }
    
    private function init() {
        // Include required files
        $this->includes();
        
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Initialize wallet system
        add_action('init', array($this, 'init_wallet_system'));
    }
    
    public function includes() {
        require_once WC_WALLET_PAYU_PLUGIN_DIR . 'includes/wallet-functions.php';
        require_once WC_WALLET_PAYU_PLUGIN_DIR . 'includes/class-payu-gateway.php';
        require_once WC_WALLET_PAYU_PLUGIN_DIR . 'includes/wallet-ajax.php';
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('woocommerce-wallet-payu', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('wc-wallet-payu', WC_WALLET_PAYU_PLUGIN_URL . 'assets/css/wallet.css', array(), WC_WALLET_PAYU_VERSION);
        wp_enqueue_script('wc-wallet-payu', WC_WALLET_PAYU_PLUGIN_URL . 'assets/js/wallet.js', array('jquery'), WC_WALLET_PAYU_VERSION, true);
        
        wp_localize_script('wc-wallet-payu', 'wc_wallet_payu_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('wc-wallet-payu-security')
        ));
    }
    
    public function init_wallet_system() {
        // Wallet endpoint and menu item
        add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
        add_filter('woocommerce_account_menu_items', 'wc_wallet_payu_menu_item');
        
        // Wallet content
        add_action('woocommerce_account_wallet_endpoint', 'wc_wallet_payu_content');
        
        // Wallet payment options
        add_action('woocommerce_review_order_before_payment', 'wc_wallet_payu_payment_option');
        add_action('woocommerce_checkout_process', 'wc_wallet_payu_process_payment');
        add_action('woocommerce_review_order_after_order_total', 'wc_wallet_payu_adjust_order_total');
        add_action('woocommerce_checkout_order_processed', 'wc_wallet_payu_process_at_checkout', 20, 3);
        
        // Wallet top-up
        add_action('template_redirect', 'wc_wallet_payu_topup_request');
        add_action('woocommerce_order_status_completed', 'wc_wallet_payu_handle_topup');
    }
    
    public static function activate() {
        // Add wallet balance field to users
        if (!get_option('wc_wallet_payu_installed')) {
            global $wpdb;
            $wpdb->query("ALTER TABLE {$wpdb->usermeta} ADD INDEX user_meta_wallet_balance (meta_value(191)) WHERE meta_key = 'wallet_balance'");
            update_option('wc_wallet_payu_installed', '1');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
}