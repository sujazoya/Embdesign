<?php
class UAS_Auth_Handler {

    public static function init() {
        add_action('wp_ajax_uas_login', [__CLASS__, 'handle_login']);
        add_action('wp_ajax_nopriv_uas_login', [__CLASS__, 'handle_login']);
        
        add_action('wp_ajax_uas_register', [__CLASS__, 'handle_register']);
        add_action('wp_ajax_nopriv_uas_register', [__CLASS__, 'handle_register']);
        
        add_action('wp_ajax_uas_check_password', [__CLASS__, 'check_password_strength']);
        add_action('wp_ajax_nopriv_uas_check_password', [__CLASS__, 'check_password_strength']);
    }

    public static function handle_login() {
        try {
            check_ajax_referer('uas-auth-nonce', 'security');

            $creds = [
                'user_login'    => sanitize_user($_POST['username']),
                'user_password' => $_POST['password'],
                'remember'     => isset($_POST['rememberme'])
            ];

            $user = wp_signon($creds, false);

            if (is_wp_error($user)) {
                throw new Exception($user->get_error_message());
            }

            wp_send_json_success([
                'redirect' => self::get_redirect_url('login'),
                'message'  => __('Login successful!', 'ultimate-auth')
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public static function handle_register() {
        try {
            check_ajax_referer('uas-auth-nonce', 'security');

            $data = [
                'user_login' => sanitize_user($_POST['username']),
                'user_email' => sanitize_email($_POST['email']),
                'user_pass'  => $_POST['password'],
                'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
                'last_name'  => sanitize_text_field($_POST['last_name'] ?? ''),
            ];

            // Validation
            if (empty($data['user_login'])) {
                throw new Exception(__('Username is required.', 'ultimate-auth'));
            }
            if (empty($data['user_email'])) {
                throw new Exception(__('Email is required.', 'ultimate-auth'));
            }
            if (!is_email($data['user_email'])) {
                throw new Exception(__('Invalid email address.', 'ultimate-auth'));
            }
            if (username_exists($data['user_login'])) {
                throw new Exception(__('Username already exists.', 'ultimate-auth'));
            }
            if (email_exists($data['user_email'])) {
                throw new Exception(__('Email already registered.', 'ultimate-auth'));
            }

            $user_id = wp_insert_user($data);

            if (is_wp_error($user_id)) {
                throw new Exception($user_id->get_error_message());
            }

            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);

            // WooCommerce integration
            if (function_exists('wc_create_new_customer')) {
                do_action('woocommerce_created_customer', $user_id, $data, true);
            }

            wp_send_json_success([
                'redirect' => self::get_redirect_url('register'),
                'message'  => __('Registration successful!', 'ultimate-auth')
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public static function check_password_strength() {
        $password = $_POST['password'] ?? '';
        $username = sanitize_user($_POST['username'] ?? '');
        $email    = sanitize_email($_POST['email'] ?? '');

        $strength = self::calculate_password_strength($password, $username, $email);

        wp_send_json_success([
            'strength' => $strength,
            'message'  => self::get_password_strength_message($strength)
        ]);
    }

    private static function calculate_password_strength($password, $username, $email) {
        $score = 0;
        if (strlen($password) < 1) return 0;
        if (strlen($password) < 4) return 1;
        
        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;
        
        if (!empty($username) && stripos($password, $username) !== false) $score--;
        if (!empty($email) && stripos($password, $email) !== false) $score--;
        
        return min(max($score, 0), 4);
    }

    private static function get_password_strength_message($strength) {
        $messages = [
            __('Very weak', 'ultimate-auth'),
            __('Weak', 'ultimate-auth'),
            __('Medium', 'ultimate-auth'),
            __('Strong', 'ultimate-auth'),
            __('Very strong', 'ultimate-auth')
        ];
        return $messages[$strength] ?? '';
    }

    public static function get_redirect_url($context = 'login') {
        if (!empty($_POST['redirect'])) {
            return esc_url_raw($_POST['redirect']);
        }
        
        if (function_exists('wc_get_page_id')) {
            return get_permalink(wc_get_page_id('myaccount'));
        }
        
        return home_url();
    }
}