<?php
// Enqueue parent theme styles
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('astra-parent-style', get_template_directory_uri() . '/style.css');
});
function enqueue_seller_profile_styles() {
    if (is_page_template('seller-profile.php')) {
        wp_enqueue_style('seller-profile-css', get_stylesheet_directory_uri() . '/css/seller-profile.css');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_seller_profile_styles');

// Handle profile picture upload
add_action('wp_ajax_update_profile_picture', 'handle_profile_picture_upload');
function handle_profile_picture_upload() {
    if (!is_user_logged_in()) wp_die('Unauthorized');
    
    $user_id = intval($_POST['user_id']);
    if ($user_id != get_current_user_id()) wp_die('Unauthorized');
    
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    if ($_FILES) {
        foreach ($_FILES as $file => $array) {
            if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
                wp_die('Upload error: ' . $_FILES[$file]['error']);
            }
            
            $attachment_id = media_handle_upload($file, 0);
            
            if (is_wp_error($attachment_id)) {
                wp_die('Error: ' . $attachment_id->get_error_message());
            } else {
                // Update user meta
                update_user_meta($user_id, 'profile_picture', $attachment_id);
                echo wp_get_attachment_url($attachment_id);
            }
        }
    }
    
    wp_die();
}

// Helper function: Upload and attach files
function handle_file_upload_and_attach_to_product($file_field, $post_id) {
    if (!empty($_FILES[$file_field]['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $file = $_FILES[$file_field];
        $upload_overrides = ['test_form' => false];
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (!isset($uploaded_file['error'])) {
            $file_url = $uploaded_file['url'];
            $file_path = $uploaded_file['file'];
            $file_type = wp_check_filetype(basename($file_path), null);

            $attachment = [
                'guid'           => $file_url,
                'post_mime_type' => $file_type['type'],
                'post_title'     => sanitize_file_name($file['name']),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ];

            $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
            $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return [
                'id' => $attach_id,
                'url' => $file_url,
                'name' => $file['name']
            ];
        }
    }
    return false;
}

// Product submission
add_action('init', 'handle_product_submission');
function handle_product_submission() {
    if (!isset($_POST['submit_product']) || !is_user_logged_in()) return;

    $title = sanitize_text_field($_POST['design_code']);
    $description = sanitize_textarea_field($_POST['description']);
    $price = floatval($_POST['price']);

    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'publish',
        'post_type'    => 'product',
        'post_author'  => get_current_user_id(),
    ]);
    if (is_wp_error($post_id)) return;

    update_post_meta($post_id, '_regular_price', $price);
    update_post_meta($post_id, '_price', $price);
    update_post_meta($post_id, '_virtual', 'yes');
    update_post_meta($post_id, '_downloadable', 'yes');

    $fields = ['stitches', 'area', 'height', 'width', 'formats'];
    foreach ($fields as $field) {
        update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
    }

    // Upload product images
    if (!empty($_FILES['product_images']['name'][0])) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        foreach ($_FILES['product_images']['name'] as $key => $value) {
            $file = [
                'name'     => $_FILES['product_images']['name'][$key],
                'type'     => $_FILES['product_images']['type'][$key],
                'tmp_name' => $_FILES['product_images']['tmp_name'][$key],
                'error'    => $_FILES['product_images']['error'][$key],
                'size'     => $_FILES['product_images']['size'][$key]
            ];
            $attachment_id = media_handle_sideload($file, $post_id);
            if (!is_wp_error($attachment_id)) {
                add_post_meta($post_id, 'product_image_gallery', $attachment_id);
                if ($key === 0) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }
        }
    }

    // Upload and attach downloadable files
    $download_files = [];
    $doc_fields = ['product_zip', 'emb_w6', 'dst', 'other_file'];

    foreach ($doc_fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $attachment_id = media_handle_upload($field, $post_id);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
                $file_name = basename($file_url);
                $download = new WC_Product_Download();
                $download->set_name($file_name);
                $download->set_file($file_url);
                $download_files[] = $download;
            }
        }
    }

  

    if (!empty($download_files)) {
        $product = wc_get_product($post_id);
        $product->set_downloads($download_files);
        $product->save();
    }

    wp_redirect(home_url('/product-submit-page/'));
    exit;
}

// Remove billing fields
add_filter('woocommerce_checkout_fields', 'custom_remove_billing_fields');
function custom_remove_billing_fields($fields) {
    unset($fields['billing']['billing_first_name']);
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_phone']);
    return $fields;
}

// Google OAuth login
add_action('template_redirect', 'handle_google_oauth_login');
function handle_google_oauth_login() {
    if (!is_page('my-account') || !isset($_GET['action']) || $_GET['action'] !== 'google_oauth_login') return;

    $client_id = '486496287484-umi5atsq75cgbirh6fq0rqimnpj9to94.apps.googleusercontent.com';
    $client_secret = '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';
    $redirect_uri = 'https://embdesign.shop/my-account/?action=google_oauth_login';

    if (!isset($_GET['code'])) {
        $auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ]);
        wp_redirect($auth_url);
        exit;
    } else {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $_GET['code'],
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            ]
        ]);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['access_token'])) wp_die('Google login failed: No access token.');

        $userinfo = wp_remote_get('https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $body['access_token']);
        $userdata = json_decode(wp_remote_retrieve_body($userinfo), true);
        if (!isset($userdata['email'])) wp_die('Google login failed: No email received.');

        $user = get_user_by('email', $userdata['email']);
        if (!$user) {
            $username = sanitize_user(current(explode('@', $userdata['email'])), true);
            if (username_exists($username)) $username .= '_' . wp_generate_password(4, false);
            $random_password = wp_generate_password(12, false);
            $user_id = wp_create_user($username, $random_password, $userdata['email']);
            if (is_wp_error($user_id)) wp_die('Google login failed: Could not create user.');
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $userdata['given_name'] ?? '',
                'last_name' => $userdata['family_name'] ?? '',
            ]);
            $user = get_user_by('ID', $user_id);
        }

        wp_set_auth_cookie($user->ID, true);
        wp_redirect(home_url('/my-account/'));
        exit;
    }
}

// Product edit form
function custom_product_update_form() {
    if (!is_user_logged_in()) return '<p>You must be logged in to edit a product.</p>';
    if (!isset($_GET['product_id'])) return '<p>No product selected.</p>';

    $product_id = intval($_GET['product_id']);
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product') return '<p>Invalid product.</p>';
    if (get_current_user_id() !== (int) $product->post_author && !current_user_can('manage_woocommerce')) return '<p>You are not allowed to edit this product.</p>';

    $title = esc_attr($product->post_title);
    $description = esc_textarea($product->post_content);
    $price = get_post_meta($product_id, '_price', true);

    ob_start(); ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="edit_product_id" value="<?php echo esc_attr($product_id); ?>">
        <p><label>Product Name</label><br>
            <input type="text" name="product_name" value="<?php echo $title; ?>" required></p>
        <p><label>Price</label><br>
            <input type="number" name="product_price" step="0.01" value="<?php echo esc_attr($price); ?>" required></p>
        <p><label>Description</label><br>
            <textarea name="product_description" required><?php echo $description; ?></textarea></p>
        <p><label>Update Image (optional)</label><br>
            <input type="file" name="product_image"></p>
        <p><input type="submit" name="update_product" value="Update Product"></p>
    </form>
    <?php return ob_get_clean();
}
add_shortcode('product_update_form', 'custom_product_update_form');

// Handle product update
add_action('init', 'handle_product_update');
function handle_product_update() {
    if (!isset($_POST['update_product']) || !is_user_logged_in()) return;

    $product_id = intval($_POST['edit_product_id']);
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product') return;
    if (get_current_user_id() !== (int) $product->post_author && !current_user_can('manage_woocommerce')) return;

    $title = sanitize_text_field($_POST['product_name']);
    $price = floatval($_POST['product_price']);
    $description = wp_kses_post($_POST['product_description']);

    wp_update_post(['ID' => $product_id, 'post_title' => $title, 'post_content' => $description]);
    update_post_meta($product_id, 'download_file_id', $attachment_id);
    update_post_meta($post_id, 'download_file_name', sanitize_file_name($_FILES['product_zip']['name']));

    if (!empty($_FILES['product_image']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_id = media_handle_upload('product_image', $product_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }

    wp_redirect(home_url('/my-account/?product_updated=1'));
    exit;
}

// Auto-complete downloadable orders
add_action('woocommerce_thankyou', 'auto_complete_virtual_orders');
function auto_complete_virtual_orders($order_id) {
    if (!$order_id) return;
    $order = wc_get_order($order_id);
    if ($order->has_status('processing') && $order->has_downloadable_item()) {
        $order->update_status('completed');
    }
}

// Add Google Login Button
add_action('woocommerce_before_customer_login_form', function () {
    echo '<a href="' . esc_url(site_url('/my-account/?action=google_oauth_login')) . '" class="button alt" style="margin-bottom: 20px; background:#4285F4; color:white;">Login with Google</a>';
});
