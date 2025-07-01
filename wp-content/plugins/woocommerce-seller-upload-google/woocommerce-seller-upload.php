<?php
/*
Plugin Name: WooCommerce Seller Upload
Description: Sellers can submit WooCommerce products with downloadable files.
Version: 3.1
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Load required files
require_once plugin_dir_path(__FILE__) . 'drive-upload.php'; // This file MUST contain upload_file_to_google_drive
require_once plugin_dir_path(__FILE__) . 'settings-page.php';

// Enqueue CSS and JS only when shortcode is used
add_action('wp_enqueue_scripts', 'wcsu_enqueue_assets');
function wcsu_enqueue_assets() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'product_submission_form')) {
        // Enqueue CSS
        wp_enqueue_style(
            'wcsu-style',
            plugin_dir_url(__FILE__) . 'style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'style.css')
        );

        // Enqueue dummy JS handle for localization
        wp_enqueue_script(
            'wcsu-ajax',
            plugin_dir_url(__FILE__) . 'wcsu-ajax.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'wcsu-ajax.js'),
            true
        );

        // Localize Ajax URL
        wp_localize_script('wcsu-ajax', 'wcsu_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }
}

// Handle file upload with extension validation
function wcsu_handle_file_upload($field_name, $post_id) {
    if (!isset($_FILES[$field_name]) || !is_array($_FILES[$field_name]) || empty($_FILES[$field_name]['name'])) {
        error_log("WCSU: No file data found for field '{$field_name}'.");
        return false;
    }

    $file = $_FILES[$field_name];
    $file_name = $file['name'];
    $tmp_name = $file['tmp_name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $is_image = in_array($ext, $image_extensions);

    // Ensure the temporary file exists before trying to read it
    if (!file_exists($tmp_name) || !is_uploaded_file($tmp_name)) {
        error_log("WCSU: Uploaded file temporary path invalid or file not found for field '{$field_name}'. Path: {$tmp_name}");
        return false;
    }

    // --- REQUIREMENT 1: IF IMAGE (DESIGN PICTURE) → upload to WordPress Media Library ---
    if ($is_image) {
        error_log("WCSU: Uploading image '{$file_name}' to WordPress Media Library.");
        $upload = wp_upload_bits($file_name, null, file_get_contents($tmp_name));

        if (!is_array($upload) || !empty($upload['error'])) {
            error_log('WCSU: Image upload error for field ' . $field_name . ': ' . (is_array($upload) ? $upload['error'] : 'Unknown'));
            return false;
        }

        $file_path = $upload['file'];
        $file_type_info = wp_check_filetype($file_name);
        $mime_type = $file_type_info['type'] ?? 'application/octet-stream';
        $attachment_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
        $wp_upload_dir = wp_upload_dir();

        $attachment = [
            'guid'           => $wp_upload_dir['url'] . '/' . basename($file_path),
            'post_mime_type' => $mime_type,
            'post_title'     => $attachment_title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);

        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id; // Return the attachment ID for images
    }

    // --- REQUIREMENT 2: IF NOT IMAGE (DST, EMB, ZIP) → upload to Google Drive ---
    // This section will only run for non-image files (e.g., DST, EMB, ZIP)
    error_log("WCSU: Attempting to upload non-image file '{$file_name}' to Google Drive.");

    // Check if the Google Drive upload function is available
    if (!function_exists('upload_file_to_google_drive')) {
        error_log('WCSU Error: upload_file_to_google_drive function is not defined in drive-upload.php!');
        // Fallback to local WP upload if Google Drive function is missing
        return wcsu_fallback_local_upload($file, $post_id, $field_name);
    }

    $drive_link = upload_file_to_google_drive($file); // $file contains name, type, tmp_name, error, size

    if ($drive_link) {
        error_log("WCSU: Successfully uploaded '{$file_name}' to Google Drive. Link: {$drive_link}");

        // Update product meta to mark as downloadable and virtual
        update_post_meta($post_id, '_downloadable', 'yes');
        update_post_meta($post_id, '_virtual', 'yes');

        // Get existing downloadable files
        $downloadable_files = get_post_meta($post_id, '_downloadable_files', true);
        if (!is_array($downloadable_files)) $downloadable_files = [];

        // Add the new Google Drive file to downloadable files
        $downloadable_files[] = [
            'name' => $file_name,
            'file' => $drive_link,
            'type' => $file['type'] ?? 'application/octet-stream', // Use the actual file type if available
        ];

        update_post_meta($post_id, '_downloadable_files', $downloadable_files);

        return $drive_link; // Return the Google Drive link for downloadable files
    } else {
        error_log("WCSU: Google Drive upload failed for '{$file_name}'. Falling back to local WordPress upload.");
        // Fallback to local WordPress upload if Google Drive upload explicitly fails
        return wcsu_fallback_local_upload($file, $post_id, $field_name);
    }
}

/**
 * Helper function for fallback local WordPress upload.
 * This function is called if Google Drive upload fails or the function is not found.
 */
function wcsu_fallback_local_upload($file_data, $post_id, $field_name) {
    if (!isset($file_data['tmp_name']) || empty($file_data['tmp_name']) || !file_exists($file_data['tmp_name'])) {
        error_log("WCSU: Cannot perform fallback local upload. Temporary file missing for field '{$field_name}'.");
        return false;
    }

    $upload = wp_upload_bits($file_data['name'], null, file_get_contents($file_data['tmp_name']));
    if (!is_array($upload) || !empty($upload['error'])) {
        error_log('WCSU: Fallback WordPress upload error for field ' . $field_name . ': ' . (is_array($upload) ? $upload['error'] : 'Unknown'));
        return false;
    }

    $file_path = $upload['file'];
    $file_name_for_attachment = basename($file_path);
    $file_type_info = wp_check_filetype($file_name_for_attachment, null);
    $mime_type = (is_array($file_type_info) && isset($file_type_info['type']))
        ? $file_type_info['type']
        : 'application/octet-stream';

    $attachment_title = sanitize_file_name(pathinfo($file_name_for_attachment, PATHINFO_FILENAME));
    $wp_upload_dir = wp_upload_dir();

    $post_info = [
        'guid'           => $wp_upload_dir['url'] . '/' . $file_name_for_attachment,
        'post_mime_type' => $mime_type,
        'post_title'     => $attachment_title,
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($post_info, $file_path, $post_id);

    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    error_log("WCSU: Fallback local WordPress upload successful for '{$file_data['name']}'. Attachment ID: {$attach_id}");
    return $attach_id;
}


// Shortcode: Product Submission Form
add_shortcode('product_submission_form', 'wcsu_render_product_submission_form');

function wcsu_render_product_submission_form() {

    if (!is_user_logged_in()) return '<p>Please <a href="' . wp_login_url() . '">log in</a> to submit a product.</p>';

    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wcsu_submit'])) {
        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['product_title']);
        $desc = sanitize_textarea_field($_POST['product_desc']);
        $price = floatval($_POST['price']);

        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $desc,
            'post_status'  => 'publish',
            'post_type'    => 'product',
            'post_author'  => $user_id,
        ]);

        if ($post_id) {
            update_post_meta($post_id, '_price', $price);
            update_post_meta($post_id, '_regular_price', $price);
            wp_set_object_terms($post_id, 'simple', 'product_type');

            $custom_fields = ['design_code', 'stitches', 'area', 'height', 'width', 'formats', 'needle'];
            foreach ($custom_fields as $field) {
                if (!empty($_POST[$field])) {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            }

            if (!empty($_POST['category'])) {
                $cat_id = (int) $_POST['category'];
                $selected_cat = get_term_by('id', $cat_id, 'product_cat');

                if ($selected_cat && $selected_cat->name === 'Bundles' && !empty($_POST['subcategory'])) {
                    $subcat_id = (int) $_POST['subcategory'];
                    wp_set_post_terms($post_id, [$subcat_id], 'product_cat');
                } else {
                    wp_set_post_terms($post_id, [$cat_id], 'product_cat');
                }
            }

            // Process tags
            if (!empty($_POST['product_tags'])) {
                $tags = array_map('intval', (array)$_POST['product_tags']);
                $valid_tags = [];

                foreach ($tags as $tag_id) {
                    if (term_exists($tag_id, 'product_tag')) {
                        $valid_tags[] = $tag_id;
                    }
                }

                if (!empty($valid_tags)) {
                    wp_set_object_terms($post_id, $valid_tags, 'product_tag');
                }
            } else {
                wp_set_object_terms($post_id, [], 'product_tag');
            }

            // Correctly handle the gallery image as the featured image
            $thumb_id = wcsu_handle_file_upload('gallery', $post_id);
            if ($thumb_id) set_post_thumbnail($post_id, $thumb_id);

            $file_fields = ['dst', 'all_zip', 'emb_e4', 'emb_w6']; // Removed 'emb' as it's covered by emb_e4/emb_w6
            $uploaded_file_data = []; // Store file IDs/links for the post meta
            foreach ($file_fields as $field) {
                $file_result = wcsu_handle_file_upload($field, $post_id);
                if ($file_result) {
                    // $file_result will be either a Google Drive link (string) or a WP attachment ID (int)
                    $uploaded_file_data[$field] = $file_result;
                }
            }
            update_post_meta($post_id, 'wcsu_files', $uploaded_file_data);


            wp_redirect($price == 0 ? site_url("/download-product/?product_id=$post_id") : get_permalink($post_id));
            exit;
        }
    }
    ?>

    <div class="wcsu-form-wrapper">
        <form method="post" enctype="multipart/form-data" class="wcsu-form">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">✨ Submit Your EmbDesign ✨</h2>
                <a href="https://embdesign.shop/how-to-upload/"
                   target="_blank"
                   style="display: inline-flex;
                           align-items: center;
                           justify-content: center;
                           width: 70px;
                           height: 90px;
                           background-color: #FFD700;
                           color: white;
                           border-radius: 4px;
                           font-family: Arial, sans-serif;
                           text-decoration: none;
                           font-weight: bold;
                           font-size: 76px;
                           transition: all 0.3s ease;">
                    ﹖
                </a>
            </div>

            <label>Design Pictures:</label>
            <input type="file" name="gallery" accept="image/*">
             <label>Design Type (Category):</label>
            <select name="category" id="main_category" onchange="toggleSubcategory()">
                <?php foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]) as $term): ?>
                    <option value="<?= esc_attr($term->term_id) ?>" <?php selected($_POST['category'] ?? '', $term->term_id) ?>><?= esc_html($term->name) ?></option>
                <?php endforeach; ?>
            </select>

            <div id="subcategory_field" style="display: none;">
                <label>Bundle Subcategory:</label>
                <select name="subcategory" id="subcategory">
                    <?php
                    $bundles_cat = get_term_by('name', 'Bundles', 'product_cat');
                    if ($bundles_cat) {
                        $subcategories = get_terms([
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                            'parent' => $bundles_cat->term_id
                        ]);
                        foreach ($subcategories as $subcat): ?>
                            <option value="<?= esc_attr($subcat->term_id) ?>" <?php selected($_POST['subcategory'] ?? '', $subcat->term_id) ?>><?= esc_html($subcat->name) ?></option>
                        <?php endforeach;
                    }
                    ?>
                </select>
            </div>

            <div id="file-upload-fields" class="wcsu-upload-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 12px;">
                <div style="display: flex; flex-direction: column; justify-content: space-between; min-height: 100px; border: 1px solid #ccc; padding: 6px; border-radius: 6px;">
                    <div>
                        <label style="margin-bottom: 4px;">DST:</label><br>
                        <label for="dst-file" style="background: #f0f0f0; border: 1px solid #aaa; padding: 4px 8px; cursor: pointer; display: inline-block; border-radius: 4px;">Choose DST File</label>
                        <input type="file" id="dst-file" name="dst" accept=".dst" onchange="updateFileName(this, 'dst-status')" style="display: none;">
                    </div>
                    <div id="dst-status" style="font-size: 11px; color: green; margin-top: 4px;">No file chosen</div>
                </div>

                <div style="display: flex; flex-direction: column; justify-content: space-between; min-height: 100px; border: 1px solid #ccc; padding: 6px; border-radius: 6px;">
                    <div>
                        <label style="margin-bottom: 4px;">EMB E4 / EMB:</label><br>
                        <label for="emb-e4-file" style="background: #f0f0f0; border: 1px solid #aaa; padding: 4px 8px; cursor: pointer; display: inline-block; border-radius: 4px;">Choose EMB E4</label>
                        <input type="file" id="emb-e4-file" name="emb_e4" accept=".emb" onchange="updateFileName(this, 'emb-e4-status')" style="display: none;">
                    </div>
                    <div id="emb-e4-status" style="font-size: 11px; color: green; margin-top: 4px;">No file chosen</div>
                </div>

                <div style="display: flex; flex-direction: column; justify-content: space-between; min-height: 100px; border: 1px solid #ccc; padding: 6px; border-radius: 6px;">
                    <div>
                        <label style="margin-bottom: 4px;">EMB W6:</label><br>
                        <label for="emb-w6-file" style="background: #f0f0f0; border: 1px solid #aaa; padding: 4px 8px; cursor: pointer; display: inline-block; border-radius: 4px;">Choose EMB W6</label>
                        <input type="file" id="emb-w6-file" name="emb_w6" accept=".emb" onchange="updateFileName(this, 'emb-w6-status')" style="display: none;">
                    </div>
                    <div id="emb-w6-status" style="font-size: 11px; color: green; margin-top: 4px;">No file chosen</div>
                </div>
            </div>

            <script>
            function updateFileName(input, targetId) {
                const statusDiv = document.getElementById(targetId);
                if (input.files.length > 0) {
                    const names = Array.from(input.files).map(file => file.name).join(', ');
                    statusDiv.textContent = names;
                } else {
                    statusDiv.textContent = 'No file chosen';
                }
            }
            </script>

            <input name="product_title" required placeholder="Design Name" value="<?php echo esc_attr($_POST['product_title'] ?? '') ?>">
            <textarea name="product_desc" required placeholder="Description"><?php echo esc_textarea($_POST['product_desc'] ?? '') ?></textarea>
            <input name="area" placeholder="Machine Area" value="<?php echo esc_attr($_POST['area']  ?? '300,400'); ?>">
            <input name="stitches" placeholder="Stitches" value="<?php echo esc_attr($_POST['stitches'] ?? '') ?>">

            <div id="dimension-fields">
                <input name="height" placeholder="Height (MM)" type="number" step="0.1" value="<?php echo esc_attr($_POST['height'] ?? '') ?>">
                <input name="width" placeholder="Width (MM)" type="number" step="0.1" value="<?php echo esc_attr($_POST['width'] ?? '') ?>">
            </div>

            <input name="formats" placeholder="Design Formats (emb , dst)" value="<?php echo esc_attr($_POST['formats'] ?? 'EMB,DST'); ?>">
            <input name="needle" placeholder="Needle" value="<?php echo esc_attr($_POST['needle'] ?? '') ?>">
            <input name="price" type="number" min="0" step="0.01" required placeholder="Price" value="<?php echo esc_attr($_POST['price'] ?? '') ?>">

            <div id="zip-upload-field" class="wcsu-upload-grid">
                <div><label>Bundle (Zip):</label><input type="file" name="all_zip" accept=".zip"></div>
            </div>

            <div class="wcsu-form-field">
                <label for="product_tags">Design Tags (What includes)</label>

                <div class="tags-dropdown-container">
                    <button type="button" class="tags-dropdown-button" onclick="toggleTagsDropdown()">
                        Select Tags ▼
                    </button>
                    <div class="tags-dropdown-content" style="display: none;">
                        <?php
                        $all_tags = get_terms([
                            'taxonomy' => 'product_tag',
                            'hide_empty' => false,
                            'orderby' => 'name',
                        ]);

                        $selected_tags = isset($_POST['product_tags']) ? array_map('intval', (array)$_POST['product_tags']) : [];
                        ?>

                        <div class="tags-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 5px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach ($all_tags as $tag) : ?>
                                <div class="tag-item" style="margin: 2px 0;">
                                    <input type="checkbox"
                                                 name="product_tags[]"
                                                 id="product_tag_<?php echo esc_attr($tag->term_id); ?>"
                                                 value="<?php echo esc_attr($tag->term_id); ?>"
                                                 <?php checked(in_array($tag->term_id, $selected_tags)); ?> />
                                    <label for="product_tag_<?php echo esc_attr($tag->term_id); ?>" style="margin-left: 5px;">
                                        <?php echo esc_html($tag->name); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="wcsu_submit">🚀 Submit Design</button>
        </form>
    </div>

    <script>
    function toggleTagsDropdown() {
        var dropdown = document.querySelector('.tags-dropdown-content');
        var button = document.querySelector('.tags-dropdown-button');

        if (dropdown.style.display === 'none' || dropdown.style.display === '') {
            dropdown.style.display = 'block';
            button.innerHTML = 'Select Tags ▲';
        } else {
            dropdown.style.display = 'none';
            button.innerHTML = 'Select Tags ▼';
        }
    }

    function toggleSubcategory() {
        var mainCategory = document.getElementById('main_category');
        var subcategoryField = document.getElementById('subcategory_field');
        var fileUploadFields = document.getElementById('file-upload-fields');
        var dimensionFields = document.getElementById('dimension-fields');
        var zipUploadField = document.getElementById('zip-upload-field');
        var selectedOption = mainCategory.options[mainCategory.selectedIndex].text;

        if (selectedOption === 'Bundles') {
            subcategoryField.style.display = 'block';
            fileUploadFields.style.display = 'none';
            dimensionFields.style.display = 'none';
            zipUploadField.style.display = 'block';
        } else {
            subcategoryField.style.display = 'none';
            fileUploadFields.style.display = 'grid';
            dimensionFields.style.display = 'block';
            zipUploadField.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleSubcategory();
    });
    </script>

    <style>
    .tags-dropdown-button {
        padding: 8px 15px;
        background-color: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        width: 100%;
        text-align: left;
        margin-bottom: 5px;
    }

    .tags-dropdown-button:hover {
        background-color: #e0e0e0;
    }

    .tags-dropdown-content {
        position: relative;
        z-index: 1;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    </style>

    <?php
    return ob_get_clean();
}

// Shortcode: Download Page
add_shortcode('product_download_page', function () {
    if (empty($_GET['product_id'])) return '<p>No product ID provided.</p>';
    $product_id = intval($_GET['product_id']);
    $price = floatval(get_post_meta($product_id, '_price', true));
    $user_id = get_current_user_id();

    $has_access = $price == 0;
    if (!$has_access && is_user_logged_in()) {
        $orders = wc_get_orders(['customer_id' => $user_id, 'status' => 'completed']);
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $product_id) {
                    $has_access = true;
                    break 2;
                }
            }
        }
    }

    if (!$has_access) return '<p>You must purchase this product to download files.</p>';

    $file_ids = get_post_meta($product_id, 'wcsu_files', true);
    if (empty($file_ids)) return '<p>No files found for this product.</p>';

    $output = "<h2>🎉 Download Product Files</h2>";
    foreach ($file_ids as $label => $file_identifier) {
        // Determine if it's a Google Drive link or a WordPress attachment ID
        if (is_string($file_identifier) && filter_var($file_identifier, FILTER_VALIDATE_URL)) {
            $url = $file_identifier;
            $name = basename(parse_url($url, PHP_URL_PATH)); // Get filename from URL path
        } elseif (is_numeric($file_identifier)) {
            $url = wp_get_attachment_url($file_identifier);
            $name = basename(get_attached_file($file_identifier));
        } else {
            // Handle unexpected cases, log an error
            error_log("WCSU: Unexpected file identifier type or format for label {$label}: " . print_r($file_identifier, true));
            continue;
        }

        if ($url) {
            $output .= "<p><a href='" . esc_url($url) . "' download class='button'>Download " . esc_html($name) . "</a></p>";
        } else {
            error_log("WCSU: Could not get URL for file identifier: " . print_r($file_identifier, true));
        }
    }

    return $output;
});

// Add download links on WooCommerce Thank You page
add_action('woocommerce_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $pid = $item->get_product_id();
        $file_ids = get_post_meta($pid, 'wcsu_files', true);
        if (!empty($file_ids)) {
            echo "<h3>Download Files for: " . esc_html(get_the_title($pid)) . "</h3>";
            foreach ($file_ids as $label => $file_identifier) {
                // Determine if it's a Google Drive link or a WordPress attachment ID
                if (is_string($file_identifier) && filter_var($file_identifier, FILTER_VALIDATE_URL)) {
                    $url = $file_identifier;
                    $name = basename(parse_url($url, PHP_URL_PATH)); // Get filename from URL path
                } elseif (is_numeric($file_identifier)) {
                    $url = wp_get_attachment_url($file_identifier);
                    $name = basename(get_attached_file($file_identifier));
                } else {
                    error_log("WCSU: Unexpected file identifier type or format on thank you page for label {$label}: " . print_r($file_identifier, true));
                    continue;
                }

                if ($url) {
                    echo "<p><a href='" . esc_url($url) . "' download class='button'>Download " . esc_html($name) . "</a></p>";
                } else {
                    error_log("WCSU: Could not get URL for file identifier on thank you page: " . print_r($file_identifier, true));
                }
            }
        }
    }
});

// Allow .emb and .dst MIME types
add_filter('upload_mimes', function ($mimes) {
    $mimes['emb'] = 'application/octet-stream';
    $mimes['dst'] = 'application/octet-stream';
    return $mimes;
});

// Force file extension recognition for .emb and .dst
add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'emb') {
        return [
            'ext' => 'emb',
            'type' => 'application/octet-stream',
            'proper_filename' => $filename,
        ];
    }
    if ($ext === 'dst') {
        return [
            'ext' => 'dst',
            'type' => 'application/octet-stream',
            'proper_filename' => $filename,
        ];
    }
    return $data;
}, 99, 4);

// Bypass WordPress file type restriction
add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
    if (!empty($args[0]) && $args[0] === 'upload_files') {
        $allcaps['unfiltered_upload'] = true;
    }
    return $allcaps;
}, 10, 4);

// DST Parser AJAX handler
add_action('wp_ajax_wcsu_parse_dst', 'wcsu_parse_dst_ajax_handler');
add_action('wp_ajax_nopriv_wcsu_parse_dst', 'wcsu_parse_dst_ajax_handler');

function wcsu_parse_dst_ajax_handler() {
    if (empty($_FILES['dst_file'])) {
        wp_send_json_error('No file uploaded');
    }

    $uploaded_file = $_FILES['dst_file'];
    $file_tmp_path = $uploaded_file['tmp_name'];
    $file_name = $uploaded_file['name'];

    $url = 'https://embroidery-parser.onrender.com/parse_embroidery';
    $curl = curl_init();

    $cfile = new CURLFile($file_tmp_path, 'application/octet-stream', $file_name);
    $post_data = ['file' => $cfile];

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        wp_send_json_error("cURL error: $error");
    }

    $parsed = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($parsed['success'])) {
        wp_send_json_error($parsed['error'] ?? 'Failed to parse response');
    }

    wp_send_json_success([
        'design_name'   => $parsed['design_name'] ?? '',
        'stitches'      => $parsed['stitches'] ?? '',
       // 'machine_area'  => $parsed['area'] ?? '',
        'height'        => $parsed['height'] ?? '',
        'width'         => $parsed['width'] ?? '',
        //'design_formats'=> $parsed['formats'] ?? '',
       // 'needles'       => $parsed['needle'] ?? '',
    ]);
}

// Auto-Fill Frontend Script for DST Upload
add_action('wp_footer', function () {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'product_submission_form')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('input[name="dst"]').on('change', function () {
                var input = this;
                if (!input.files.length) return;

                var formData = new FormData();
                formData.append('action', 'wcsu_parse_dst');
                formData.append('dst_file', input.files[0]);

                var $spinner = $('<span class="wcsu-loading">Parsing DST...</span>');
                $(input).after($spinner);

                $.ajax({
                    url: wcsu_ajax.ajaxurl,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (res) {
                        if (res.success) {
                            const d = res.data;
                            $('input[name="product_title"]').val(d.design_name);
                            $('input[name="stitches"]').val(d.stitches);
                           // $('input[name="area"]').val(d.machine_area);
                            $('input[name="height"]').val(d.height);
                            $('input[name="width"]').val(d.width);
                            // $('input[name="formats"]').val(d.design_formats);
                           // $('input[name="needle"]').val(d.needles);
                        } else {
                            alert("DST Parsing Failed: " + res.data);
                        }
                    },
                    error: function () {
                        alert("AJAX error parsing DST file.");
                    },
                    complete: function () {
                        $spinner.remove();
                    }
                });
            });
        });
        </script>
        <style>
        .wcsu-loading {
            color: #0073aa;
            margin-left: 10px;
            font-style: italic;
            font-size: 13px;
        }
        </style>
        <?php
    }
});

add_action('init', function () {
    error_log('✅ WCSU plugin loaded');
});