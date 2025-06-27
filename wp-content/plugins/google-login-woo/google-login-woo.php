<?php
/**
 * Plugin Name: Google Login for WooCommerce
 * Description: Adds "Login with Google" to WooCommerce My Account page with automatic registration.
 * Version: 1.0
 * Author: Your Name
 */

// Output Google login button on My Account page
add_action('wp_footer', 'glfw_render_google_login_button');
function glfw_render_google_login_button() {
    if (!function_exists('is_account_page') || !is_account_page() || is_user_logged_in()) return;

    $client_id = 'YOUR_GOOGLE_CLIENT_ID'; // Replace with your actual Client ID
    $login_uri = esc_url(home_url('/google-login-callback/'));
    ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <div id="g_id_onload"
         data-client_id="<?php echo esc_attr($client_id); ?>"
         data-login_uri="<?php echo $login_uri; ?>"
         data-auto_prompt="true"
         data-context="signin">
    </div>
    <div class="g_id_signin"
         data-type="standard"
         data-size="large"
         data-theme="outline"
         data-text="signin_with"
         data-shape="rectangular"
         data-logo_alignment="left">
    </div>
    <?php
}

// Handle the login callback
add_action('init', 'glfw_handle_google_login_callback');
function glfw_handle_google_login_callback() {
    if ($_SERVER['REQUEST_URI'] !== '/google-login-callback/' || $_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['credential'])) {
        wp_die('Missing Google credential.');
    }

    $token = $input['credential'];
    $payload = explode('.', $token)[1];
    $data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);

    if (!isset($data['email'])) {
        wp_die('Invalid token data.');
    }

    $email = sanitize_email($data['email']);
    $name = sanitize_text_field($data['name'] ?? '');
    $user = get_user_by('email', $email);

    if (!$user) {
        $username = sanitize_user(current(explode('@', $email)));
        if (username_exists($username)) {
            $username .= rand(1000, 9999);
        }

        $user_id = wp_create_user($username, wp_generate_password(), $email);
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $name,
        ]);
        $user = get_user_by('id', $user_id);
    }

    wp_set_auth_cookie($user->ID, true);
    wp_redirect(wc_get_page_permalink('myaccount'));
    exit;
}

// Automatically create callback page
register_activation_hook(__FILE__, 'glfw_create_login_callback_page');
function glfw_create_login_callback_page() {
    if (!get_page_by_path('google-login-callback')) {
        wp_insert_post([
            'post_title'   => 'Google Login Callback',
            'post_name'    => 'google-login-callback',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => 'This page handles Google Login. Do not delete.',
        ]);
    }
}
