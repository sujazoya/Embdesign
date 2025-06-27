<?php
/**
 * Template Name: Seller Profile Page
 */

get_header();

// Add this at the top of your template, after get_header()
// Force refresh user data if username was just updated
if (isset($_GET['updated']) && $_GET['updated'] === 'username') {
    $current_user = wp_get_current_user();
    clean_user_cache($current_user->ID);
    wp_cache_delete($current_user->ID, 'users');
}

// Handle profile picture update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_picture'])) {
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in.', 'embroidery-designs'));
    }
    
    if (!isset($_POST['profile_picture_nonce']) || !wp_verify_nonce($_POST['profile_picture_nonce'], 'update_profile_picture_action')) {
        wp_die(__('Security check failed.', 'embroidery-designs'));
    }
    
    $current_user = wp_get_current_user();
    
    if (!empty($_FILES['profile_picture']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $file = $_FILES['profile_picture'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error-notice">❌ ' . __('Error uploading file. Please try again.', 'embroidery-designs') . '</div>';
        } else {
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $filetype = wp_check_filetype($file['name']);
            
            if (!in_array($filetype['type'], $allowed_types)) {
                echo '<div class="error-notice">❌ ' . __('Only JPG, PNG, and GIF files are allowed.', 'embroidery-designs') . '</div>';
            } else {
                // Handle the upload
                $overrides = ['test_form' => false];
                $uploaded_file = wp_handle_upload($file, $overrides);
                
                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    // Create attachment post
                    $attachment = [
                        'post_mime_type' => $uploaded_file['type'],
                        'post_title' => sanitize_file_name($file['name']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ];
                    
                    $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);
                    
                    if (!is_wp_error($attachment_id)) {
                        // Generate metadata and update user meta
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        // Update user meta with the new profile picture ID
                        update_user_meta($current_user->ID, 'profile_picture', $attachment_id);
                        
                        // Delete previous profile picture if it exists
                        $old_picture_id = get_user_meta($current_user->ID, 'profile_picture', true);
                        if ($old_picture_id && $old_picture_id != $attachment_id) {
                            wp_delete_attachment($old_picture_id, true);
                        }
                        
                        echo '<div class="success-notice">✅ ' . __('Profile picture updated successfully!', 'embroidery-designs') . '</div>';
                    } else {
                        echo '<div class="error-notice">❌ ' . __('Error saving profile picture.', 'embroidery-designs') . '</div>';
                    }
                } else {
                    echo '<div class="error-notice">❌ ' . esc_html($uploaded_file['error']) . '</div>';
                }
            }
        }
    } else {
        echo '<div class="error-notice">❌ ' . __('No file selected.', 'embroidery-designs') . '</div>';
    }
}

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
     // First check: Verify nonce for security
    if (!isset($_POST['update_username_nonce']) || !wp_verify_nonce($_POST['update_username_nonce'], 'update_username_action')) {
        wp_die(__('Security check failed.', 'embroidery-designs'));
    }
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in.', 'embroidery-designs'));
    }

    $current_user = wp_get_current_user();
    $new_username = sanitize_user($_POST['new_username']);
    $current_username = $current_user->user_login;

    // Check if username actually changed
    if ($new_username === $current_username) {
        echo '<div class="notice notice-info">'.__('Username unchanged.', 'embroidery-designs').'</div>';
    } else {
        $error = '';
        
        // Validate new username
        if (empty($new_username)) {
            $error = __('Username cannot be empty.', 'embroidery-designs');
        } elseif (!validate_username($new_username)) {
            $error = __('Username contains invalid characters.', 'embroidery-designs');
        } elseif (username_exists($new_username) && $new_username !== $current_username) {
            $error = __('Username already exists. Please choose another.', 'embroidery-designs');
        } else {
            // Update all user fields at once
            $userdata = array(
                'ID' => $current_user->ID,
                'user_login' => $new_username,
                'user_nicename' => sanitize_title($new_username),
                'display_name' => $new_username
            );
            
            $user_id = wp_update_user($userdata);
            
            if (!is_wp_error($user_id)) {
                // Clear all relevant caches
                clean_user_cache($current_user->ID);
                wp_cache_delete($current_user->ID, 'users');
                wp_cache_delete($current_user->user_login, 'userlogins');
                
                // Refresh the current user object
                $current_user = wp_get_current_user();
                
                // Update auth cookies to prevent issues
                wp_clear_auth_cookie();
                wp_set_current_user($current_user->ID);
                wp_set_auth_cookie($current_user->ID);
                
                // Force immediate refresh of the page
                wp_redirect(add_query_arg('username_updated', '1', get_permalink()));
                exit;
            } else {
                $error = __('Failed to update username. Please try again.', 'embroidery-designs');
            }
        }

        if (!empty($error)) {
            echo '<div class="error-notice">❌ ' . esc_html($error) . '</div>';
        }
    }
}

// Display success message if username was just updated
if (isset($_GET['username_updated']) && $_GET['username_updated'] === '1') {
    echo '<div class="success-notice">'.__('✅ Username updated successfully!', 'embroidery-designs').'</div>';
    
    // Refresh the current user data after update
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

// Get search term if any
$search_term = isset($_GET['product_search']) ? sanitize_text_field($_GET['product_search']) : '';

// Get user products with search filter
$product_args = [
    'post_type'   => 'product',
    'post_status' => ['publish', 'private'],
    'author'      => $current_user->ID,
    'numberposts' => -1
];

// Add search filter if term exists
if (!empty($search_term)) {
    $product_args['s'] = $search_term;
}

$user_products = get_posts($product_args);

// Get product categories
$product_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

// Calculate seller earnings
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
                $profile_picture = wp_get_attachment_image($custom_profile_pic, 'medium', false, array(
                    'class' => 'seller-photo',
                    'style' => 'width:150px; height:150px; border-radius:50%; object-fit:cover;'
                ));
            }
            echo $profile_picture;
            ?>
        </div>
    </div>

    <!-- Updated Dropdown for Profile Settings -->
    <div class="profile-settings-dropdown">
        <button class="profile-settings-toggle" onclick="toggleProfileSettings()">
            Profile Settings
            <span class="dropdown-arrow">▼</span>
        </button>
        
        <div class="profile-settings-content" style="display:none;">
            <!-- Username Update Form -->
            <div class="settings-section">
                <h4>Update Username</h4>
                <form method="post" id="username-update-form">
                    <?php wp_nonce_field('update_username_action', 'update_username_nonce'); ?>
                    <div class="form-group">
                        <label for="current-username">Current Username:</label>
                        <input type="text" id="current-username" value="<?php echo esc_attr($current_user->user_login); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new-username">New Username:</label>
                        <input type="text" name="new_username" id="new-username" required>
                        <div id="username-availability"></div>
                    </div>
                    <button type="submit" name="update_username" class="update-button">Update Username</button>
                </form>
            </div>
            
            <!-- Profile Picture Update Form -->
            <div class="settings-section">
                <h4>Update Profile Picture</h4>
                <form method="post" enctype="multipart/form-data" id="profile-picture-form">
                    <?php wp_nonce_field('update_profile_picture_action', 'profile_picture_nonce'); ?>
                    <div class="form-group">
                        <label for="profile-picture">Choose an image (JPG, PNG, GIF):</label>
                        <input type="file" name="profile_picture" id="profile-picture" accept="image/jpeg,image/png,image/gif">
                    </div>
                    <button type="submit" name="update_profile_picture" class="update-button">Update Profile Picture</button>
                </form>
            </div>
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
    
    <!-- Product Search Form -->
    <form method="get" class="product-search-form">
        <input type="hidden" name="p" value="<?php echo get_the_ID(); ?>">
        <input type="text" name="product_search" placeholder="Search your designs..." value="<?php echo esc_attr($search_term); ?>">
        <button type="submit">Search</button>
        <?php if (!empty($search_term)): ?>
            <a href="<?php echo remove_query_arg('product_search'); ?>" class="clear-search">Clear Search</a>
        <?php endif; ?>
    </form>

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
            preview.innerHTML = '<p>New Profile Picture Preview:</p><img src="' + e.target.result + '" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-top:10px;">';
            
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

jQuery(document).ready(function($) {
    // Username availability check
    $('#new-username').on('input', function() {
        var username = $(this).val();
        var availability = $('#username-availability');
        
        if (username.length < 3) {
            availability.html('<span class="checking">Username must be at least 3 characters</span>');
            return;
        }

        availability.html('<span class="checking">Checking availability...</span>');

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
                    availability.html('<span class="available">✓ Username available</span>');
                } else {
                    availability.html('<span class="unavailable">✗ Username already taken</span>');
                }
            },
            error: function() {
                availability.html('<span class="error">Error checking username</span>');
            }
        });
    });

    // Form validation
    $('#username-update-form').on('submit', function(e) {
        var username = $('#new-username').val();
        var currentUsername = $('#current-username').val();
        var availability = $('#username-availability');
        
        if (username.length < 3) {
            availability.html('<span class="error">Username must be at least 3 characters</span>');
            e.preventDefault();
            return false;
        }
         // Allow submission if username matches current (no change)
        if (username === currentUsername) {
            return true;
        }

        if (availability.find('.unavailable').length) {
            availability.html('<span class="error">Please choose an available username</span>');
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

<style>
/* Product search form styles */
.product-search-form {
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.product-search-form input[type="text"] {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    flex-grow: 1;
    max-width: 300px;
}

.product-search-form button {
    padding: 8px 16px;
    background: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.product-search-form button:hover {
    background: #005f8a;
}

.clear-search {
    color: #666;
    text-decoration: none;
    font-size: 0.9em;
    margin-left: 10px;
}

.clear-search:hover {
    color: #333;
}

/* Profile settings dropdown styles */
.profile-settings-dropdown {
    margin-bottom: 20px;
}

.profile-settings-toggle {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 200px;
}

.profile-settings-toggle:hover {
    background: #005f8a;
}

.profile-settings-content {
    background: #fff;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    margin-top: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.settings-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.settings-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.update-button {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.update-button:hover {
    background: #005f8a;
}

.profile-picture-preview {
    margin: 15px 0;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 4px;
}

.profile-picture-preview p {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
}

/* Existing styles remain unchanged */
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
.edit-form input, .edit-form textarea, .edit-form select { width: 100%; margin-bottom: 1rem; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
.edit-form .form-buttons { display: flex; gap: 1rem; margin-top: 1rem; }
.edit-form .submit-button { background: #28a745; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; }
.edit-form .submit-button:hover { background: #218838; }
.edit-form .cancel-button { background: #6c757d; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; }
.edit-form .cancel-button:hover { background: #5a6268; }
.success-notice { background: #d4edda; color: #155724; padding: 1rem; border-left: 5px solid #28a745; margin-bottom: 1rem; }
.error-notice { background: #f8d7da; color: #721c24; padding: 1rem; border-left: 5px solid #dc3545; margin-bottom: 1rem; }
.delete-form { display: inline; }
.submit-product-button { display: inline-flex; align-items: center; background: #4CAF50; color: white; padding: 0.75rem 1.5rem; border-radius: 4px; text-decoration: none; margin-bottom: 1.5rem; }
.submit-product-button:hover { background: #3e8e41; }
.button-icon { font-size: 1.2rem; margin-right: 0.5rem; }
#username-availability { margin-top: 0.5rem; font-size: 0.9rem; }
#username-availability .checking { color: #666; }
#username-availability .available { color: #28a745; }
#username-availability .unavailable, #username-availability .error { color: #dc3545; }
</style>

<?php 
get_footer();