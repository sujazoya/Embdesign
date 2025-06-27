<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class UAS_Google_Auth {

    public static function init() {
        add_action('wp_ajax_uas_google_auth', [__CLASS__, 'handle_google_auth']);
        add_action('wp_ajax_nopriv_uas_google_auth', [__CLASS__, 'handle_google_auth']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
    }

    public static function register_settings() {
        register_setting('uas_google_settings', 'uas_google_settings', [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
            'default' => [
                'client_id' => '',
                'client_secret' => ''
            ]
        ]);

        add_settings_section(
            'uas_google_section',
            __('Google API Credentials', 'ultimate-auth'),
            [__CLASS__, 'render_section_description'],
            'uas-google-settings'
        );

        add_settings_field(
            'uas_google_client_id',
            __('Client ID', 'ultimate-auth'),
            [__CLASS__, 'render_client_id_field'],
            'uas-google-settings',
            'uas_google_section'
        );

        add_settings_field(
            'uas_google_client_secret',
            __('Client Secret', 'ultimate-auth'),
            [__CLASS__, 'render_client_secret_field'],
            'uas-google-settings',
            'uas_google_section'
        );
    }

    public static function sanitize_settings($input) {
        $output = [];
        $output['client_id'] = sanitize_text_field($input['client_id'] ?? '');
        $output['client_secret'] = sanitize_text_field($input['client_secret'] ?? '');
        return $output;
    }

    public static function add_admin_menu() {
        add_options_page(
            __('Google Auth Settings', 'ultimate-auth'),
            __('Google Auth', 'ultimate-auth'),
            'manage_options',
            'uas-google-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function render_section_description() {
        echo '<p>' . sprintf(
            __('Enter your Google API credentials obtained from %sGoogle Cloud Console%s', 'ultimate-auth'),
            '<a href="https://console.cloud.google.com/" target="_blank">',
            '</a>'
        ) . '</p>';
    }

    public static function render_client_id_field() {
        $options = get_option('uas_google_settings');
        echo '<input type="text" id="uas_google_client_id" name="uas_google_settings[client_id]" 
              value="' . esc_attr($options['client_id'] ?? '') . '" class="regular-text">';
    }

    public static function render_client_secret_field() {
        $options = get_option('uas_google_settings');
        echo '<input type="password" id="uas_google_client_secret" name="uas_google_settings[client_secret]" 
              value="' . esc_attr($options['client_secret'] ?? '') . '" class="regular-text">';
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ultimate-auth'));
        }

        $client_status = self::verify_client_installation();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Google Authentication Settings', 'ultimate-auth'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('uas_google_settings');
                do_settings_sections('uas-google-settings');
                submit_button();
                ?>
            </form>

            <div class="uas-system-status">
                <h2><?php esc_html_e('System Status', 'ultimate-auth'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Component', 'ultimate-auth'); ?></th>
                            <th><?php esc_html_e('Status', 'ultimate-auth'); ?></th>
                            <th><?php esc_html_e('Details', 'ultimate-auth'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('Google API Client', 'ultimate-auth'); ?></td>
                            <td>
                                <?php if ($client_status['installed']): ?>
                                    <span style="color:green">✔ <?php esc_html_e('Installed', 'ultimate-auth'); ?></span>
                                <?php else: ?>
                                    <span style="color:red">✖ <?php esc_html_e('Not found', 'ultimate-auth'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($client_status['installed']): ?>
                                    <?php esc_html_e('Version:', 'ultimate-auth'); ?> <?php echo esc_html($client_status['version']); ?><br>
                                    <?php esc_html_e('Install method:', 'ultimate-auth'); ?> <?php echo esc_html($client_status['method']); ?>
                                <?php else: ?>
                                    <p><?php esc_html_e('The Google API client library is required for Google Sign-In.', 'ultimate-auth'); ?></p>
                                    <h4><?php esc_html_e('Installation Methods:', 'ultimate-auth'); ?></h4>
                                    <ol>
                                        <li>
                                            <strong><?php esc_html_e('Using Composer (recommended):', 'ultimate-auth'); ?></strong><br>
                                            <code>cd <?php echo esc_html(UAS_PATH); ?> && composer require google/apiclient:^2.12</code>
                                        </li>
                                        <li>
                                            <strong><?php esc_html_e('Manual installation:', 'ultimate-auth'); ?></strong><br>
                                            <?php printf(
                                                __('Download %s and extract the src/Google folder to %s', 'ultimate-auth'),
                                                '<a href="https://github.com/googleapis/google-api-php-client/releases" target="_blank">google-api-php-client</a>',
                                                '<code>' . esc_html(UAS_PATH . 'includes/google-api-php-client/') . '</code>'
                                            ); ?>
                                        </li>
                                    </ol>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public static function handle_google_auth() {
        try {
            check_ajax_referer('uas-auth-nonce', 'security');

            if (!self::is_google_client_available()) {
                throw new Exception(__('Google API client is not properly configured.', 'ultimate-auth'));
            }

            $settings = get_option('uas_google_settings');
            if (empty($settings['client_id']) || empty($settings['client_secret'])) {
                throw new Exception(__('Google authentication is not properly configured.', 'ultimate-auth'));
            }

            $token = sanitize_text_field($_POST['credential'] ?? '');
            if (empty($token)) {
                throw new Exception(__('Invalid Google token.', 'ultimate-auth'));
            }

            $client = new Google\Client([
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret']
            ]);

            $payload = $client->verifyIdToken($token);
            if (!$payload) {
                throw new Exception(__('Invalid Google token.', 'ultimate-auth'));
            }

            $email = sanitize_email($payload['email'] ?? '');
            if (empty($email)) {
                throw new Exception(__('Email not provided by Google.', 'ultimate-auth'));
            }

            // User handling logic
            $user = get_user_by('email', $email);
            
            if (!$user) {
                $username = self::generate_username_from_email($email);
                $password = wp_generate_password();
                $userdata = [
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_pass' => $password,
                    'first_name' => sanitize_text_field($payload['given_name'] ?? ''),
                    'last_name' => sanitize_text_field($payload['family_name'] ?? ''),
                    'role' => 'subscriber'
                ];

                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    throw new Exception($user_id->get_error_message());
                }

                // Trigger WooCommerce registration if active
                if (function_exists('wc_create_new_customer')) {
                    do_action('woocommerce_created_customer', $user_id, $userdata, true);
                }

                $user = get_user_by('id', $user_id);
            }

            // Authenticate the user
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            do_action('wp_login', $user->user_login, $user);

            wp_send_json_success([
                'redirect' => apply_filters('uas_google_auth_redirect', home_url(), $user),
                'message' => __('Google login successful!', 'ultimate-auth')
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private static function load_google_client() {
        $autoload_paths = [
            UAS_PATH . 'vendor/autoload.php',
            UAS_PATH . 'includes/google-api-php-client/vendor/autoload.php'
        ];

        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }

        // Fallback to manual include
        if (file_exists(UAS_PATH . 'includes/google-api-php-client/src/Google/Client.php')) {
            require_once UAS_PATH . 'includes/google-api-php-client/src/Google/Client.php';
            return;
        }

        throw new Exception(__('Google API client files not found.', 'ultimate-auth'));
    }

    private static function verify_client_installation() {
        $result = [
            'installed' => false,
            'version' => 'Unknown',
            'method' => 'Not installed'
        ];

        // Check Composer installation
        $composer_path = UAS_PATH . 'vendor/google/apiclient-services/composer.json';
        if (file_exists($composer_path)) {
            $result['installed'] = true;
            $result['method'] = 'composer';
            $composer_data = json_decode(file_get_contents($composer_path), true);
            $result['version'] = $composer_data['version'] ?? 'Unknown';
        }
        // Check manual installation
        elseif (file_exists(UAS_PATH . 'includes/google-api-php-client/Client.php')) {
            $result['installed'] = true;
            $result['method'] = 'manual';
            
            $version_file = UAS_PATH . 'includes/google-api-php-client/VERSION';
            if (file_exists($version_file)) {
                $result['version'] = trim(file_get_contents($version_file));
            }
        }

        return $result;
    }

    private static function generate_username_from_email($email) {
        $username = sanitize_user(current(explode('@', $email)), true);
        $original = $username;
        $i = 1;

        while (username_exists($username)) {
            $username = $original . $i;
            $i++;
        }

        return $username;
    }

    public static function is_google_client_available() {
        try {
            self::load_google_client();
            return class_exists('Google\Client');
        } catch (Exception $e) {
            return false;
        }
    }
}