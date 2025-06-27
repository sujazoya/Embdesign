<?php
/*
Plugin Name: WooCommerce Seller Upload
Description: Sellers can submit WooCommerce products with downloadable files.
Version: 3.1
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Enqueue CSS only when shortcode is used
add_action('wp_enqueue_scripts', 'wcsu_enqueue_styles');
function wcsu_enqueue_styles() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'product_submission_form')) {
        wp_enqueue_style(
            'wcsu-style', 
            plugin_dir_url(__FILE__) . 'style.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'style.css')
        );
    }
}

// Handle file upload with extension validation
function wcsu_handle_file_upload($file_field, $post_id) {
    if (!empty($_FILES[$file_field]['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file_name = $_FILES[$file_field]['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = [
    'emb'     => ['emb'],
    'emb_e4'  => ['emb'],
    'emb_w6'  => ['emb'],
    'dst'     => ['dst'],
    'all_zip' => ['zip'],
    'other'   => [] // allow anything
    ];


        if (!empty($allowed_extensions[$file_field]) && !in_array($file_ext, $allowed_extensions[$file_field])) {
            return false;
        }

        $file_id = media_handle_upload($file_field, $post_id);
        if (!is_wp_error($file_id)) return $file_id;
    }
    return false;
}

// Shortcode: Product Submission Form
add_shortcode('product_submission_form', function () {
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
                
                if ($selected_cat->name === 'Bundles' && !empty($_POST['subcategory'])) {
                    // If main category is Bundles and subcategory is selected
                    $subcat_id = (int) $_POST['subcategory'];
                    wp_set_post_terms($post_id, [$subcat_id], 'product_cat');
                } else {
                    // For all other categories
                    wp_set_post_terms($post_id, [$cat_id], 'product_cat');
                }
            }

            // Process tags
            if (!empty($_POST['product_tags'])) {
                $tags = array_map('intval', (array)$_POST['product_tags']);
                $valid_tags = [];
                
                // Verify tags exist
                foreach ($tags as $tag_id) {
                    if (term_exists($tag_id, 'product_tag')) {
                        $valid_tags[] = $tag_id;
                    }
                }
                
                if (!empty($valid_tags)) {
                    wp_set_object_terms($post_id, $valid_tags, 'product_tag');
                }
            } else {
                // Remove all tags if none selected
                wp_set_object_terms($post_id, [], 'product_tag');
            }

            $thumb_id = wcsu_handle_file_upload('gallery', $post_id);
            if ($thumb_id) set_post_thumbnail($post_id, $thumb_id);

            $file_fields = ['dst', 'all_zip', 'emb_e4', 'emb_w6', 'emb'];
            $file_ids = [];
            foreach ($file_fields as $field) {
                $file_id = wcsu_handle_file_upload($field, $post_id);
                if ($file_id) $file_ids[$field] = $file_id;
            }
            update_post_meta($post_id, 'wcsu_files', $file_ids);

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
            <input name="product_title" required placeholder="Design Name" value="<?php echo esc_attr($_POST['product_title'] ?? '') ?>">
            <textarea name="product_desc" required placeholder="Description"><?php echo esc_textarea($_POST['product_desc'] ?? '') ?></textarea>
            <input name="stitches" placeholder="Stitches" value="<?php echo esc_attr($_POST['stitches'] ?? '') ?>">
            <input name="area" placeholder="Machine Area" value="<?php echo esc_attr($_POST['area'] ?? '') ?>">
            <div> <input name="height" placeholder="Height (MM)" type="number" step="0.1" value="<?php echo esc_attr($_POST['height'] ?? '') ?>"> </div>
            <input name="width" placeholder="Width (MM)" type="number" step="0.1" value="<?php echo esc_attr($_POST['width'] ?? '') ?>">
            <input name="formats" placeholder="Design Formats (emb , dst)" value="<?php echo esc_attr($_POST['formats'] ?? '') ?>">
            <input name="needle" placeholder="Needle" value="<?php echo esc_attr($_POST['needle'] ?? '') ?>">
            <input name="price" type="number" min="0" step="0.01" required placeholder="Price" value="<?php echo esc_attr($_POST['price'] ?? '') ?>">

            <div class="wcsu-upload-grid">
                <div><label>DST:</label><input type="file" name="dst" accept=".dst"></div>
                <div><label>EMB E4:</label><input type="file" name="emb_e4" accept=".emb" multiple></div>
                <div><label>EMB W6:</label><input type="file" name="emb_w6" accept=".emb" multiple></div>
                <div><label>Bundle (Zip):</label><input type="file" name="all_zip" accept=".zip"></div>
            </div>

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
                    // Find the Bundles category
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

            <div class="wcsu-form-field">
                <label for="product_tags">Design Tags (What includes)</label>
                
                <div class="tags-dropdown-container">
                    <button type="button" class="tags-dropdown-button" onclick="toggleTagsDropdown()">
                        Select Tags ▼
                    </button>
                    <div class="tags-dropdown-content" style="display: none;">
                        <?php
                        // Get all existing product tags
                        $all_tags = get_terms([
                            'taxonomy' => 'product_tag',
                            'hide_empty' => false,
                            'orderby' => 'name',
                        ]);

                        // Get previously selected tags (if form was submitted)
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
        var selectedOption = mainCategory.options[mainCategory.selectedIndex].text;
        
        if (selectedOption === 'Bundles') {
            subcategoryField.style.display = 'block';
        } else {
            subcategoryField.style.display = 'none';
        }
    }

    // Initialize the subcategory field visibility on page load
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
});

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
    foreach ($file_ids as $label => $id) {
        $url = wp_get_attachment_url($id);
        $name = basename(get_attached_file($id));
        $output .= "<p><a href='$url' download class='button'>Download $name</a></p>";
    }

    return $output;
});

// Optional: Add download links on WooCommerce Thank You page
add_action('woocommerce_thankyou', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $pid = $item->get_product_id();
        $file_ids = get_post_meta($pid, 'wcsu_files', true);
        if (!empty($file_ids)) {
            echo "<h3>Download Files for: " . esc_html(get_the_title($pid)) . "</h3>";
            foreach ($file_ids as $label => $id) {
                $url = wp_get_attachment_url($id);
                $name = basename(get_attached_file($id));
                echo "<p><a href='$url' download class='button'>Download $name</a></p>";
            }
        }
    }
});
// ✅ 1. Allow .emb and .dst MIME types
add_filter('upload_mimes', function ($mimes) {
    $mimes['emb'] = 'application/octet-stream';
    $mimes['dst'] = 'application/octet-stream';
    return $mimes;
});

// ✅ 2. Force file extension recognition for .emb and .dst
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

// ✅ 3. Bypass WordPress file type restriction (works on all servers)
add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
    if (!empty($args[0]) && $args[0] === 'upload_files') {
        $allcaps['unfiltered_upload'] = true;
    }
    return $allcaps;
}, 10, 4);
