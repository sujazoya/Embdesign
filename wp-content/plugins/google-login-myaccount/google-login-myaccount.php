<?php
/**
 * Plugin Name: Google Login My Account (Final Fixed)
 * Description: Adds a centered, styled Google Sign-In button to the WooCommerce My Account page.
 * Version: 2.0
 * Author: Your Name
 */

// ✅ 1. Inject button with styling + working callback JS
add_action('wp_footer', function () {
    if (!is_account_page() || is_user_logged_in()) return;

    $client_id = '486496287484-umi5atsq75cgbirh6fq0rqimnpj9to94.apps.googleusercontent.com'; // 🔁 Replace with your real Google OAuth client ID
    $login_uri = esc_url(home_url('/google-login-callback/'));
    $redirect_uri = esc_url(wc_get_page_permalink('myaccount'));
    ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        .glma-google-wrapper {
            text-align: center;
            margin-top: 30px;
        }
        .glma-google-wrapper .g_id_signin {
            display: inline-block !important;
            width: 300px !important;
            max-width: 90% !important;
            padding: 16px 36px !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            border-radius: 16px !important;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
            border: 1px solid #ccc !important;
            background: #fff !important;
            color: #333 !important;
            transition: all 0.3s ease;
        }
        .glma-google-wrapper .g_id_signin:hover {
            background-color: #f0f0f0 !important;
            transform: scale(1.03);
        }
        @media (max-width: 600px) {
            .glma-google-wrapper .g_id_signin {
                width: 90% !important;
                font-size: 16px !important;
                padding: 14px 28px !important;
            }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const target = document.querySelector('.woocommerce');
        if (!target || document.getElementById('g_id_onload')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'glma-google-wrapper';
        wrapper.innerHTML = `
            <div id="g_id_onload"
                data-client_id="<?php echo esc_attr($client_id); ?>"
                data-context="signin"
                data-callback="handleGoogleSignIn"
                data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                data-type="standard"
                data-size="large"
                data-theme="outline"
                data-text="signin_with"
                data-shape="rectangular"
                data-logo_alignment="left">
            </div>
        `;
        target.appendChild(wrapper);
    });

    function handleGoogleSignIn(response) {
        if (!response || !response.credential) {
            alert("Google login failed.");
            return;
        }

        fetch('<?php echo $login_uri; ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credential: response.credential })
        })
        .then(() => {
            window.location.href = '<?php echo $redirect_uri; ?>';
        })
        .catch(err => {
            console.error('Google Login Error:', err);
            alert("Google login error.");
        });
    }
    </script>
    <?php
});

// ✅ 2. Handle POST token on /google-login-callback/
add_action('init', function () {
    if ($_SERVER['REQUEST_URI'] !== '/google-login-callback/' || $_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['credential'])) wp_die('Missing Google credential.');

    $token = $input['credential'];
    $payload = explode('.', $token)[1] ?? null;
    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

    if (!isset($data['email'])) wp_die('Invalid token payload.');

    $email = sanitize_email($data['email']);
    $name = sanitize_text_field($data['name'] ?? '');
    $user = get_user_by('email', $email);

    if (!$user) {
        $username = sanitize_user(current(explode('@', $email)));
        if (username_exists($username)) {
            $username .= rand(1000, 9999);
        }

        $user_id = wp_create_user($username, wp_generate_password(), $email);
        wp_update_user(['ID' => $user_id, 'display_name' => $name]);
        $user = get_user_by('id', $user_id);
    }

    wp_set_auth_cookie($user->ID, true);
    wp_redirect(wc_get_page_permalink('myaccount'));
    exit;
});

// ✅ 3. Auto-create /google-login-callback/ page
register_activation_hook(__FILE__, function () {
    if (!get_page_by_path('google-login-callback')) {
        wp_insert_post([
            'post_title'   => 'Google Login Callback',
            'post_name'    => 'google-login-callback',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => 'Used internally for Google Login. Do not delete.'
        ]);
    }
});
