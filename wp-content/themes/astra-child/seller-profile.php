<?php
/**
 * Template Name: Seller Profile Page
 */

get_header();

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!is_user_logged_in()) wp_die('You must be logged in.');

    $product_id = intval($_POST['product_id']);
    $current_user_id = get_current_user_id();

    if (get_post_field('post_author', $product_id) != $current_user_id) {
        wp_die('Unauthorized access.');
    }

    wp_delete_post($product_id, true);
    echo '<div class="success-notice">✅ Product deleted successfully!</div>';
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    if (!is_user_logged_in()) wp_die('You must be logged in.');

    $product_id = intval($_POST['product_id']);
    $current_user_id = get_current_user_id();

    if (get_post_field('post_author', $product_id) != $current_user_id) {
        wp_die('Unauthorized access.');
    }

    $product_data = [
        'title'       => sanitize_text_field($_POST['design_code']),
        'description' => sanitize_textarea_field($_POST['description']),
        'price'       => floatval($_POST['price']),
        'needle'      => sanitize_text_field($_POST['needle']),
        'height'      => floatval($_POST['height']),
        'width'       => floatval($_POST['width']),
        'stitches'    => sanitize_text_field($_POST['stitches']),
        'area'        => sanitize_text_field($_POST['area']),
        'formats'     => sanitize_text_field($_POST['formats']),
        'category'    => intval($_POST['product_category'])
    ];

    wp_update_post([
        'ID'           => $product_id,
        'post_title'   => $product_data['title'],
        'post_content' => $product_data['description']
    ]);

    wp_set_object_terms($product_id, $product_data['category'], 'product_cat');
    update_post_meta($product_id, '_price', $product_data['price']);
    update_post_meta($product_id, '_regular_price', $product_data['price']);
    update_post_meta($product_id, '_height', $product_data['height']);
    update_post_meta($product_id, '_width', $product_data['width']);

    $acf_fields = [
        'design_code' => $product_data['title'],
        'needle'      => $product_data['needle'],
        'stitches'    => $product_data['stitches'],
        'area'        => $product_data['area'],
        'formats'     => $product_data['formats']
    ];

    foreach ($acf_fields as $key => $val) {
        update_field($key, $val, $product_id);
    }

    // Handle images upload
    if (!empty($_FILES['product_images']['name'][0])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_ids = [];
        foreach ($_FILES['product_images']['name'] as $key => $val) {
            if ($_FILES['product_images']['error'][$key] === 0) {
                $file_array = [
                    'name'     => $_FILES['product_images']['name'][$key],
                    'type'     => $_FILES['product_images']['type'][$key],
                    'tmp_name' => $_FILES['product_images']['tmp_name'][$key],
                    'error'    => $_FILES['product_images']['error'][$key],
                    'size'     => $_FILES['product_images']['size'][$key],
                ];

                $attachment_id = media_handle_sideload($file_array, $product_id);
                if (!is_wp_error($attachment_id)) {
                    $attachment_ids[] = $attachment_id;
                }
            }
        }

        if (!empty($attachment_ids)) {
            set_post_thumbnail($product_id, $attachment_ids[0]);
            if (count($attachment_ids) > 1) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($attachment_ids, 1)));
            }
        }
    }

    // Optional design files upload
    $file_fields = ['emb_e4', 'emb_w6', 'dst', 'all_zip', 'other_file'];
    foreach ($file_fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $file_id = media_handle_upload($field, $product_id);
            if (!is_wp_error($file_id)) {
                update_field($field, $file_id, $product_id);
            }
        }
    }

    echo '<div class="success-notice">✅ Product updated successfully!</div>';
}

if (!is_user_logged_in()) {
    echo '<p>You must be <a href="' . esc_url(wp_login_url()) . '">logged in</a> to view this page.</p>';
    get_footer();
    exit;
}

$current_user = wp_get_current_user();

// Get user products
$user_products = get_posts([
    'post_type'   => 'product',
    'post_status' => ['publish', 'private'],
    'author'      => $current_user->ID,
    'numberposts' => -1
]);

// Get product categories
$product_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

// Calculate seller earnings (basic example: sum of completed orders for user products)
global $wpdb;
$earnings = 0;
$order_items = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE oi.order_item_type = 'line_item'"
    )
);

foreach ($order_items as $item) {
    $order_item_id = $item->order_item_id;
    // Get product_id from order item meta
    $product_id = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta
        WHERE order_item_id = %d AND meta_key = '_product_id'", $order_item_id
    ));
    if ($product_id && get_post_field('post_author', $product_id) == $current_user->ID) {
        // Get order_id
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_item_id = %d", $order_item_id
        ));
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order && in_array($order->get_status(), ['completed', 'processing'])) {
                // Get item total
                $item_total = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta
                    WHERE order_item_id = %d AND meta_key = '_line_total'", $order_item_id
                ));
                $earnings += floatval($item_total);
            }
        }
    }
}

// Count user products
$product_count = count($user_products);

// User contact info
$user_email = $current_user->user_email;
$user_phone = get_user_meta($current_user->ID, 'billing_phone', true);

?>

<div class="seller-profile">
    <div class="seller-profile">
    <div class="profile-header">
        <!-- Seller Photo Section -->
        <div class="seller-photo-container">
            <?php
            // Get user profile picture (from Gravatar or custom field)
            $user_id = $current_user->ID;
            $profile_picture = get_avatar($user_id, 150); // Default to Gravatar
            
            // Check if custom profile picture exists (using ACF or user meta)
            $custom_profile_pic = get_user_meta($user_id, 'profile_picture', true);
            if ($custom_profile_pic) {
                $profile_picture = wp_get_attachment_image($custom_profile_pic, 'medium', false, array(
                    'class' => 'seller-photo',
                    'style' => 'width:150px; height:150px; border-radius:50%; object-fit:cover;'
                ));
            }
            echo $profile_picture;
            ?>
        </div>
    </div>
     <a href="https://embdesign.shop/product-upload/" class="submit-product-button">
    <span class="button-icon">+</span>
    <span class="button-text">Add New Product</span>
    </a>
    <h2><?php echo esc_html($current_user->display_name); ?></h2>
    <div class="seller-info">
        <p><strong>Email:</strong> <?php echo esc_html($user_email); ?></p>
        <?php if ($user_phone): ?>
            <p><strong>Phone:</strong> <?php echo esc_html($user_phone); ?></p>
        <?php endif; ?>
        <p><strong>Total Products:</strong> <?php echo esc_html($product_count); ?></p>
        <p><strong>Estimated Earnings:</strong> <?php echo wc_price($earnings); ?></p>
    </div>

    <h3>Your Designs</h3>

    <?php if ($user_products): ?>
        <div class="product-list">
            <?php foreach ($user_products as $product): ?>
                <?php
                    global $post;
                    $post = $product;
                    setup_postdata($post);
                    $product_obj = wc_get_product($post->ID);
                ?>
                <div class="product-card">
                    <h4><?php the_title(); ?></h4>
                    <p><strong>Price:</strong> <?php echo wc_price($product_obj->get_price()); ?></p>
                    <?php if (has_post_thumbnail($post->ID)): ?>
                        <div class="product-thumbnail"><?php echo get_the_post_thumbnail($post->ID, 'medium'); ?></div>
                    <?php endif; ?>
                    <div class="product-actions">
                        <button class="edit-button" onclick="toggleEditForm(<?php echo $post->ID; ?>)">Edit</button>
                        <form method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="product_id" value="<?php echo $post->ID; ?>">
                            <button type="submit" name="delete_product" class="delete-button">Delete</button>
                        </form>
                    </div>

                    <form method="post" enctype="multipart/form-data" class="edit-form" id="edit-form-<?php echo $post->ID; ?>" style="display:none;">
                        <input type="hidden" name="product_id" value="<?php echo $post->ID; ?>">

                        <label>Design Code *</label>
                        <input type="text" name="design_code" value="<?php echo esc_attr(get_field('design_code')); ?>" required>

                        <label>Description *</label>
                        <textarea name="description" required><?php echo esc_textarea(get_the_content()); ?></textarea>

                        <label>Category *</label>
                        <select name="product_category" required>
                            <?php foreach ($product_categories as $cat): ?>
                                <option value="<?php echo $cat->term_id; ?>" <?php selected(has_term($cat->term_id, 'product_cat', $post->ID)); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Price *</label>
                        <input type="number" step="0.01" name="price" value="<?php echo esc_attr(get_post_meta($post->ID, '_price', true)); ?>" required>

                        <label>Needle *</label>
                        <input type="text" name="needle" value="<?php echo esc_attr(get_field('needle')); ?>" required>
                      
                        <label>Height (mm) *</label>
                        <input type="number" step="0.1" name="height" value="<?php echo esc_attr(get_post_meta($post->ID, '_height', true)); ?>" required>

                        <label>Width (mm) *</label>
                        <input type="number" step="0.1" name="width" value="<?php echo esc_attr(get_post_meta($post->ID, '_width', true)); ?>" required>

                        <label>Stitches *</label>
                        <input type="text" name="stitches" value="<?php echo esc_attr(get_field('stitches')); ?>" required>

                        <label>Area *</label>
                        <input type="text" name="area" value="<?php echo esc_attr(get_field('area')); ?>" required>

                        <label>Formats *</label>
                        <input type="text" name="formats" value="<?php echo esc_attr(get_field('formats')); ?>" required>

                        <label>Replace Product Images</label>
                        <input type="file" name="product_images[]" multiple accept="image/*">

                        <label>Optional Design Files</label>
                       
                        <input type="file" name="dst">
                        <input type="file" name="all_zip">
                        <div class="form-buttons">
                            <button type="submit" name="update_product" class="submit-button">💾 Save Changes</button>
                            <button type="button" class="cancel-button" onclick="toggleEditForm(<?php echo $post->ID; ?>)">Cancel</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    <?php else: ?>
        <p>No designs found. <a href="/product-submit-page">Submit one here.</a></p>
    <?php endif; ?>
</div>

<script>
function toggleEditForm(id) {
    const form = document.getElementById('edit-form-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<style>
.seller-profile { max-width: 960px; margin: auto; padding: 2rem; }
.seller-info { background: #f9f9f9; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; }
.seller-info p { margin: 0.4rem 0; }
.product-list { display: flex; flex-direction: column; gap: 2rem; }
.product-card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.product-thumbnail img { max-width: 150px; height: auto; margin-bottom: 1rem; }
.product-actions { display: flex; gap: 1rem; margin-top: 1rem; }
.edit-button { background: #0073aa; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
.edit-button:hover { background: #005f8a; }
.delete-button { background: #dc3545; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
.delete-button:hover { background: #c82333; }
.edit-form input, .edit-form textarea, .edit-form select {
    width: 100%; margin-bottom: 1rem; padding: 0.5rem;
    border: 1px solid #ccc; border-radius: 4px;
}
.edit-form .form-buttons { display: flex; gap: 1rem; margin-top: 1rem; }
.edit-form .submit-button {
    background: #28a745; color: white; padding: 0.75rem 1.5rem;
    border: none; border-radius: 4px; cursor: pointer;
}
.edit-form .submit-button:hover { background: #218838; }
.edit-form .cancel-button {
    background: #6c757d; color: white; padding: 0.75rem 1.5rem;
    border: none; border-radius: 4px; cursor: pointer;
}
.edit-form .cancel-button:hover { background: #5a6268; }
.success-notice {
    background: #d4edda; color: #155724; padding: 1rem;
    border-left: 5px solid #28a745; margin-bottom: 1rem;
}
.delete-form { display: inline; }
</style>

<?php get_footer(); ?>