<?php
/**
 * Template Name: Seller Profile Page
 */

// Force refresh user data if username was just updated
if (isset($_GET['updated']) && $_GET['updated'] === 'username') {
    $current_user = wp_get_current_user();
    clean_user_cache($current_user->ID);
    wp_cache_delete($current_user->ID, 'users');
}

// Handle profile picture update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_picture'])) {
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in.', 'seller-profile'));
    }
    
    if (!isset($_POST['profile_picture_nonce']) || !wp_verify_nonce($_POST['profile_picture_nonce'], 'update_profile_picture_action')) {
        wp_die(__('Security check failed.', 'seller-profile'));
    }
    
    $current_user = wp_get_current_user();
    
    if (!empty($_FILES['profile_picture']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $file = $_FILES['profile_picture'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error-notice">❌ ' . __('Error uploading file. Please try again.', 'seller-profile') . '</div>';
        } else {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $filetype = wp_check_filetype($file['name']);
            
            if (!in_array($filetype['type'], $allowed_types)) {
                echo '<div class="error-notice">❌ ' . __('Only JPG, PNG, and GIF files are allowed.', 'seller-profile') . '</div>';
            } else {
                $overrides = ['test_form' => false];
                $uploaded_file = wp_handle_upload($file, $overrides);
                
                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $attachment = [
                        'post_mime_type' => $uploaded_file['type'],
                        'post_title' => sanitize_file_name($file['name']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ];
                    
                    $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);
                    
                    if (!is_wp_error($attachment_id)) {
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        update_user_meta($current_user->ID, 'profile_picture', $attachment_id);
                        
                        $old_picture_id = get_user_meta($current_user->ID, 'profile_picture', true);
                        if ($old_picture_id && $old_picture_id != $attachment_id) {
                            wp_delete_attachment($old_picture_id, true);
                        }
                        
                        echo '<div class="success-notice">✅ ' . __('Profile picture updated successfully!', 'seller-profile') . '</div>';
                    } else {
                        echo '<div class="error-notice">❌ ' . __('Error saving profile picture.', 'seller-profile') . '</div>';
                    }
                } else {
                    echo '<div class="error-notice">❌ ' . esc_html($uploaded_file['error']) . '</div>';
                }
            }
        }
    } else {
        echo '<div class="error-notice">❌ ' . __('No file selected.', 'seller-profile') . '</div>';
    }
}

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    if (!isset($_POST['update_username_nonce']) || !wp_verify_nonce($_POST['update_username_nonce'], 'update_username_action')) {
        wp_die(__('Security check failed.', 'seller-profile'));
    }
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in.', 'seller-profile'));
    }

    $current_user = wp_get_current_user();
    $new_username = sanitize_user($_POST['new_username']);
    $current_username = $current_user->user_login;

    if ($new_username === $current_username) {
        echo '<div class="notice notice-info">'.__('Username unchanged.', 'seller-profile').'</div>';
    } else {
        $error = '';
        
        if (empty($new_username)) {
            $error = __('Username cannot be empty.', 'seller-profile');
        } elseif (!validate_username($new_username)) {
            $error = __('Username contains invalid characters.', 'seller-profile');
        } elseif (username_exists($new_username) && $new_username !== $current_username) {
            $error = __('Username already exists. Please choose another.', 'seller-profile');
        } else {
            $userdata = array(
                'ID' => $current_user->ID,
                'user_login' => $new_username,
                'user_nicename' => sanitize_title($new_username),
                'display_name' => $new_username
            );
            
            $user_id = wp_update_user($userdata);
            
            if (!is_wp_error($user_id)) {
                clean_user_cache($current_user->ID);
                wp_cache_delete($current_user->ID, 'users');
                wp_cache_delete($current_user->user_login, 'userlogins');
                
                $current_user = wp_get_current_user();
                
                wp_clear_auth_cookie();
                wp_set_current_user($current_user->ID);
                wp_set_auth_cookie($current_user->ID);
                
                wp_redirect(add_query_arg('username_updated', '1', get_permalink()));
                exit;
            } else {
                $error = __('Failed to update username. Please try again.', 'seller-profile');
            }
        }

        if (!empty($error)) {
            echo '<div class="error-notice">❌ ' . esc_html($error) . '</div>';
        }
    }
}

// Display success message if username was just updated
if (isset($_GET['username_updated']) && $_GET['username_updated'] === '1') {
    echo '<div class="success-notice">'.__('✅ Username updated successfully!', 'seller-profile').'</div>';
    $current_user = wp_get_current_user();
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!is_user_logged_in()) wp_die('You must be logged in.');

    $product_id = intval($_POST['product_id']);
    $current_user_id = get_current_user_id();

    if (get_post_field('post_author', $product_id) != $current_user_id) {
        wp_die('Unauthorized access.');
    }

    wp_delete_post($product_id, true);
    echo '<div class="success-notice">✅ ' . __('Product deleted successfully!', 'seller-profile') . '</div>';
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

    $file_fields = ['emb_e4', 'emb_w6', 'dst', 'all_zip', 'other_file'];
    foreach ($file_fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $file_id = media_handle_upload($field, $product_id);
            if (!is_wp_error($file_id)) {
                update_field($field, $file_id, $product_id);
            }
        }
    }

    echo '<div class="success-notice">✅ ' . __('Product updated successfully!', 'seller-profile') . '</div>';
}

if (!is_user_logged_in()) {
    echo '<p>' . __('You must be <a href="' . esc_url(wp_login_url()) . '">logged in</a> to view this page.', 'seller-profile') . '</p>';
    return;
}

$current_user = wp_get_current_user();
$search_term = isset($_GET['product_search']) ? sanitize_text_field($_GET['product_search']) : '';
$user_products = get_posts([
    'post_type'   => 'product',
    'post_status' => ['publish', 'private'],
    'author'      => $current_user->ID,
    'numberposts' => -1,
    's'           => $search_term
]);

$product_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

global $wpdb;
$earnings = 0;
$order_items = $wpdb->get_results(
    "SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items oi
    INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
    WHERE oi.order_item_type = 'line_item'"
);

foreach ($order_items as $item) {
    $order_item_id = $item->order_item_id;
    $product_id = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta
        WHERE order_item_id = %d AND meta_key = '_product_id'", $order_item_id
    ));
    
    if ($product_id && get_post_field('post_author', $product_id) == $current_user->ID) {
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items
            WHERE order_item_id = %d", $order_item_id
        ));
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order && in_array($order->get_status(), ['completed', 'processing'])) {
                $item_total = $wpdb->get_var($wpdb->prepare(
                    "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta
                    WHERE order_item_id = %d AND meta_key = '_line_total'", $order_item_id
                ));
                $earnings += floatval($item_total);
            }
        }
    }
}

$product_count = count($user_products);
$user_email = $current_user->user_email;
$user_phone = get_user_meta($current_user->ID, 'billing_phone', true);
?>

<div class="seller-profile">
    <div class="profile-header">
        <div class="seller-photo-container">
            <?php
            $user_id = $current_user->ID;
            $profile_picture = get_avatar($user_id, 150);
            $custom_profile_pic = get_user_meta($user_id, 'profile_picture', true);
            if ($custom_profile_pic) {
                $profile_picture = wp_get_attachment_image($custom_profile_pic, 'medium', false, [
                    'class' => 'seller-photo',
                    'style' => 'width:150px; height:150px; border-radius:50%; object-fit:cover;'
                ]);
            }
            echo $profile_picture;
            ?>
        </div>
    </div>

    <div class="profile-settings-dropdown">
        <button class="profile-settings-toggle" onclick="toggleProfileSettings()">
            <?php _e('Profile Settings', 'seller-profile'); ?>
            <span class="dropdown-arrow">▼</span>
        </button>
        
        <div class="profile-settings-content" style="display:none;">
            <div class="settings-section">
                <h4><?php _e('Update Username', 'seller-profile'); ?></h4>
                <form method="post" id="username-update-form">
                    <?php wp_nonce_field('update_username_action', 'update_username_nonce'); ?>
                    <div class="form-group">
                        <label for="current-username"><?php _e('Current Username:', 'seller-profile'); ?></label>
                        <input type="text" id="current-username" value="<?php echo esc_attr($current_user->user_login); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new-username"><?php _e('New Username:', 'seller-profile'); ?></label>
                        <input type="text" name="new_username" id="new-username" required>
                        <div id="username-availability"></div>
                    </div>
                    <button type="submit" name="update_username" class="update-button"><?php _e('Update Username', 'seller-profile'); ?></button>
                </form>
            </div>
            
            <div class="settings-section">
                <h4><?php _e('Update Profile Picture', 'seller-profile'); ?></h4>
                <form method="post" enctype="multipart/form-data" id="profile-picture-form">
                    <?php wp_nonce_field('update_profile_picture_action', 'profile_picture_nonce'); ?>
                    <div class="form-group">
                        <label for="profile-picture"><?php _e('Choose an image (JPG, PNG, GIF):', 'seller-profile'); ?></label>
                        <input type="file" name="profile_picture" id="profile-picture" accept="image/jpeg,image/png,image/gif">
                    </div>
                    <button type="submit" name="update_profile_picture" class="update-button"><?php _e('Update Profile Picture', 'seller-profile'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <a href="<?php echo esc_url(home_url('/product-upload/')); ?>" class="submit-product-button">
        <span class="button-icon">+</span>
        <span class="button-text"><?php _e('Add New Product', 'seller-profile'); ?></span>
    </a>
    
    <h2><?php echo esc_html($current_user->display_name); ?></h2>
    <div class="seller-info">
        <p><strong><?php _e('Email:', 'seller-profile'); ?></strong> <?php echo esc_html($user_email); ?></p>
        <?php if ($user_phone): ?>
            <p><strong><?php _e('Phone:', 'seller-profile'); ?></strong> <?php echo esc_html($user_phone); ?></p>
        <?php endif; ?>
        <p><strong><?php _e('Total Products:', 'seller-profile'); ?></strong> <?php echo esc_html($product_count); ?></p>
        <p><strong><?php _e('Estimated Earnings:', 'seller-profile'); ?></strong> <?php echo wc_price($earnings); ?></p>
    </div>

    <h3><?php _e('Your Designs', 'seller-profile'); ?></h3>
    
    <form method="get" class="product-search-form">
        <input type="hidden" name="p" value="<?php echo get_the_ID(); ?>">
        <input type="text" name="product_search" placeholder="<?php _e('Search your designs...', 'seller-profile'); ?>" value="<?php echo esc_attr($search_term); ?>">
        <button type="submit"><?php _e('Search', 'seller-profile'); ?></button>
        <?php if (!empty($search_term)): ?>
            <a href="<?php echo remove_query_arg('product_search'); ?>" class="clear-search"><?php _e('Clear Search', 'seller-profile'); ?></a>
        <?php endif; ?>
    </form>

    <div class="view-toggle">
        <button class="grid-view-btn active" onclick="toggleView('grid')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <?php _e('Grid', 'seller-profile'); ?>
        </button>
        <button class="list-view-btn" onclick="toggleView('list')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            <?php _e('List', 'seller-profile'); ?>
        </button>
    </div>

    <?php if ($user_products): ?>
        <div class="product-list grid-view">
            <?php foreach ($user_products as $product): ?>
                <?php
                    global $post;
                    $post = $product;
                    setup_postdata($post);
                    $product_obj = wc_get_product($post->ID);
                    $thumbnail = has_post_thumbnail($post->ID) ? get_the_post_thumbnail($post->ID, 'medium') : wc_placeholder_img('medium');
                ?>
                <div class="product-card">
                    <div class="product-thumbnail">
                        <?php echo $thumbnail; ?>
                    </div>
                    <h4><?php the_title(); ?></h4>
                    <p class="product-price"><strong><?php _e('Price:', 'seller-profile'); ?></strong> <?php echo wc_price($product_obj->get_price()); ?></p>
                    
                    <div class="product-actions">
                        <button class="edit-button" onclick="toggleEditForm(<?php echo $post->ID; ?>)">
                            <?php _e('Edit', 'seller-profile'); ?>
                        </button>
                        <form method="post" class="delete-form" onsubmit="return confirm('<?php _e('Are you sure you want to delete this product?', 'seller-profile'); ?>');">
                            <input type="hidden" name="product_id" value="<?php echo $post->ID; ?>">
                            <button type="submit" name="delete_product" class="delete-button">
                                <?php _e('Delete', 'seller-profile'); ?>
                            </button>
                        </form>
                    </div>

                    <form method="post" enctype="multipart/form-data" class="edit-form" id="edit-form-<?php echo $post->ID; ?>" style="display:none;">
                        <input type="hidden" name="product_id" value="<?php echo $post->ID; ?>">

                        <label><?php _e('Design Code *', 'seller-profile'); ?></label>
                        <input type="text" name="design_code" value="<?php echo esc_attr(get_field('design_code')); ?>" required>

                        <label><?php _e('Description *', 'seller-profile'); ?></label>
                        <textarea name="description" required><?php echo esc_textarea(get_the_content()); ?></textarea>

                        <label><?php _e('Category *', 'seller-profile'); ?></label>
                        <select name="product_category" required>
                            <?php foreach ($product_categories as $cat): ?>
                                <option value="<?php echo $cat->term_id; ?>" <?php selected(has_term($cat->term_id, 'product_cat', $post->ID)); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label><?php _e('Price *', 'seller-profile'); ?></label>
                        <input type="number" step="0.01" name="price" value="<?php echo esc_attr(get_post_meta($post->ID, '_price', true)); ?>" required>

                        <label><?php _e('Needle *', 'seller-profile'); ?></label>
                        <input type="text" name="needle" value="<?php echo esc_attr(get_field('needle')); ?>" required>
                      
                        <label><?php _e('Height (mm) *', 'seller-profile'); ?></label>
                        <input type="number" step="0.1" name="height" value="<?php echo esc_attr(get_post_meta($post->ID, '_height', true)); ?>" required>

                        <label><?php _e('Width (mm) *', 'seller-profile'); ?></label>
                        <input type="number" step="0.1" name="width" value="<?php echo esc_attr(get_post_meta($post->ID, '_width', true)); ?>" required>

                        <label><?php _e('Stitches *', 'seller-profile'); ?></label>
                        <input type="text" name="stitches" value="<?php echo esc_attr(get_field('stitches')); ?>" required>

                        <label><?php _e('Area *', 'seller-profile'); ?></label>
                        <input type="text" name="area" value="<?php echo esc_attr(get_field('area')); ?>" required>

                        <label><?php _e('Formats *', 'seller-profile'); ?></label>
                        <input type="text" name="formats" value="<?php echo esc_attr(get_field('formats')); ?>" required>

                        <label><?php _e('Replace Product Images', 'seller-profile'); ?></label>
                        <input type="file" name="product_images[]" multiple accept="image/*">

                        <label><?php _e('Optional Design Files', 'seller-profile'); ?></label>
                       
                        <input type="file" name="dst">
                        <input type="file" name="all_zip">
                        <div class="form-buttons">
                            <button type="submit" name="update_product" class="submit-button">💾 <?php _e('Save Changes', 'seller-profile'); ?></button>
                            <button type="button" class="cancel-button" onclick="toggleEditForm(<?php echo $post->ID; ?>)"><?php _e('Cancel', 'seller-profile'); ?></button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    <?php else: ?>
        <p><?php _e('No designs found.', 'seller-profile'); ?> <a href="<?php echo esc_url(home_url('/product-submit-page/')); ?>"><?php _e('Submit one here.', 'seller-profile'); ?></a></p>
    <?php endif; ?>
</div>

<script>
// Function for toggling profile settings dropdown
function toggleProfileSettings() {
    var content = document.querySelector('.profile-settings-content');
    var arrow = document.querySelector('.profile-settings-toggle .dropdown-arrow');
    if (content.style.display === 'block') {
        content.style.display = 'none';
        arrow.textContent = '▼';
    } else {
        content.style.display = 'block';
        arrow.textContent = '▲';
    }
}

// Preview profile picture before upload
document.getElementById('profile-picture').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.createElement('div');
            preview.className = 'profile-picture-preview';
            preview.innerHTML = '<p><?php _e('New Profile Picture Preview:', 'seller-profile'); ?></p><img src="' + e.target.result + '" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-top:10px;">';
            
            var existingPreview = document.querySelector('.profile-picture-preview');
            if (existingPreview) {
                existingPreview.replaceWith(preview);
            } else {
                var form = document.getElementById('profile-picture-form');
                form.insertBefore(preview, form.querySelector('button'));
            }
        };
        reader.readAsDataURL(file);
    }
});

// View Toggle Functionality
function toggleView(viewType) {
    const productList = document.querySelector('.product-list');
    const gridBtn = document.querySelector('.grid-view-btn');
    const listBtn = document.querySelector('.list-view-btn');
    
    if (viewType === 'grid') {
        productList.classList.add('grid-view');
        productList.classList.remove('list-view');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
        localStorage.setItem('sellerProfileView', 'grid');
    } else {
        productList.classList.add('list-view');
        productList.classList.remove('grid-view');
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
        localStorage.setItem('sellerProfileView', 'list');
    }
}

// Initialize view from localStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('sellerProfileView') || 'grid';
    toggleView(savedView);
});

jQuery(document).ready(function($) {
    // Username availability check
    $('#new-username').on('input', function() {
        var username = $(this).val();
        var availability = $('#username-availability');
        
        if (username.length < 3) {
            availability.html('<span class="checking"><?php _e('Username must be at least 3 characters', 'seller-profile'); ?></span>');
            return;
        }

        availability.html('<span class="checking"><?php _e('Checking availability...', 'seller-profile'); ?></span>');

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'check_username_availability',
                username: username,
                security: '<?php echo wp_create_nonce("username_check_nonce"); ?>'
            },
            success: function(response) {
                if (response.available) {
                    availability.html('<span class="available">✓ <?php _e('Username available', 'seller-profile'); ?></span>');
                } else {
                    availability.html('<span class="unavailable">✗ <?php _e('Username already taken', 'seller-profile'); ?></span>');
                }
            },
            error: function() {
                availability.html('<span class="error"><?php _e('Error checking username', 'seller-profile'); ?></span>');
            }
        });
    });

    // Form validation
    $('#username-update-form').on('submit', function(e) {
        var username = $('#new-username').val();
        var currentUsername = $('#current-username').val();
        var availability = $('#username-availability');
        
        if (username.length < 3) {
            availability.html('<span class="error"><?php _e('Username must be at least 3 characters', 'seller-profile'); ?></span>');
            e.preventDefault();
            return false;
        }
        
        if (username === currentUsername) {
            return true;
        }

        if (availability.find('.unavailable').length) {
            availability.html('<span class="error"><?php _e('Please choose an available username', 'seller-profile'); ?></span>');
            e.preventDefault();
            return false;
        }
    });
});

function toggleEditForm(id) {
    const form = document.getElementById('edit-form-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>