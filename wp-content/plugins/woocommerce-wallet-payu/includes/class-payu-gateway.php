<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WC_Payment_Gateway')) return;

class WC_Payu_Gateway extends WC_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'payu';
        $this->method_title = 'PayU Money';
        $this->method_description = 'Pay securely via PayU Money';
        $this->has_fields = false;
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->merchant_key = $this->get_option('merchant_key');
        $this->merchant_salt = $this->get_option('merchant_salt');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->base_url = $this->testmode ? 'https://test.payu.in' : 'https://secure.payu.in';
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_payu_gateway', array($this, 'check_payu_response'));
        add_action('init', array($this, 'handle_payu_submission'));
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable PayU Payment',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'default' => 'PayU Money',
                'desc_tip' => true
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'default' => 'Pay securely via PayU Money'
            ),
            'merchant_key' => array(
                'title' => 'Merchant Key',
                'type' => 'text'
            ),
            'merchant_salt' => array(
                'title' => 'Merchant Salt',
                'type' => 'password'
            ),
            'testmode' => array(
                'title' => 'Test Mode',
                'type' => 'checkbox',
                'label' => 'Enable Test Mode',
                'default' => 'yes'
            )
        );
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $is_wallet_topup = $order->get_meta('_is_wallet_topup');
        
        $txnid = $order_id . '_' . time();
        $amount = $order->get_total();
        $productinfo = $is_wallet_topup ? 'Wallet Top-Up' : 'Order #' . $order_id;
        $firstname = $order->get_billing_first_name();
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();
        $surl = WC()->api_request_url('WC_Payu_Gateway');
        $furl = wc_get_checkout_url();
        $curl = WC()->api_request_url('WC_Payu_Gateway');
        
        $hash_string = $this->merchant_key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|||||||||||' . $this->merchant_salt;
        $hash = strtolower(hash('sha512', $hash_string));
        
        $payu_args = array(
            'key' => $this->merchant_key,
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productinfo,
            'firstname' => $firstname,
            'email' => $email,
            'phone' => $phone,
            'surl' => $surl,
            'furl' => $furl,
            'curl' => $curl,
            'hash' => $hash,
            'service_provider' => 'payu_paisa'
        );
        
        $order->update_meta_data('_payu_txnid', $txnid);
        $order->save();
        
        return array(
            'result' => 'success',
            'redirect' => add_query_arg(
                array(
                    'payu_submit' => '1',
                    'parameters' => base64_encode(json_encode($payu_args))
                ),
                home_url('/')
            )
        );
    }
    
    public function check_payu_response() {
        global $woocommerce;
        
        if (isset($_POST['status'])) {
            $status = $_POST['status'];
            $txnid = $_POST['txnid'];
            $order_id = explode('_', $txnid)[0];
            $order = wc_get_order($order_id);
            
            $posted_hash = $_POST['hash'];
            $key = $_POST['key'];
            $amount = $_POST['amount'];
            $productinfo = $_POST['productinfo'];
            $firstname = $_POST['firstname'];
            $email = $_POST['email'];
            $salt = $this->merchant_salt;
            
            $hash_string = $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
            $calculated_hash = strtolower(hash('sha512', $hash_string));
            
            if ($calculated_hash == $posted_hash) {
                if ($status == 'success') {
                    $order->payment_complete();
                    $order->add_order_note('PayU payment successful. Transaction ID: ' . $txnid);
                    $woocommerce->cart->empty_cart();
                    wp_redirect($this->get_return_url($order));
                    exit;
                } else {
                    $order->update_status('failed', 'PayU payment failed. Transaction ID: ' . $txnid);
                    wc_add_notice(__('Payment failed. Please try again.', 'woocommerce-wallet-payu'), 'error');
                    wp_redirect(wc_get_checkout_url());
                    exit;
                }
            } else {
                $order->update_status('failed', 'Hash verification failed for PayU payment.');
                wc_add_notice(__('Payment verification failed. Please contact support.', 'woocommerce-wallet-payu'), 'error');
                wp_redirect(wc_get_checkout_url());
                exit;
            }
        }
    }
    
    public function handle_payu_submission() {
        if (isset($_GET['payu_submit']) && $_GET['payu_submit'] == '1' && isset($_GET['parameters'])) {
            $parameters = json_decode(base64_decode($_GET['parameters']), true);
            
            echo '<html><head><title>Redirecting to PayU...</title></head><body>';
            echo '<form id="payu_form" action="' . ($parameters['service_provider'] == 'payu_paisa' ? 'https://secure.payu.in/_payment' : 'https://test.payu.in/_payment') . '" method="post">';
            
            foreach ($parameters as $name => $value) {
                echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
            }
            
            echo '</form>';
            echo '<script>document.getElementById("payu_form").submit();</script>';
            echo '</body></html>';
            exit;
        }
    }
}

function add_payu_gateway($methods) {
    $methods[] = 'WC_Payu_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_payu_gateway');