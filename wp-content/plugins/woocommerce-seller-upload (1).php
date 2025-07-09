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

function wcsu_enqueue_auto_design_assets() {
    // Only load on relevant pages/shortcodes
    if (!is_admin()) {
        wp_enqueue_script(
            'wcsu-auto-design-script',
            plugin_dir_url(__FILE__) . 'assets/js/auto-design-select.js',
            array(),
            '1.0',
            true
        );

        wp_enqueue_style(
            'wcsu-auto-design-style',
            plugin_dir_url(__FILE__) . 'assets/css/auto-design-style.css',
            array(),
            '1.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'wcsu_enqueue_auto_design_assets');

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
    if (!isset($_FILES[$field_name]) || empty($_FILES[$field_name]['name'])) {
        error_log("WCSU: No file uploaded for '{$field_name}'.");
        return false;
    }

    $file = $_FILES[$field_name];
    $file['name'] = sanitize_file_name($file['name']);
    $file_name = $file['name'];
    $tmp_name = $file['tmp_name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $is_image = in_array($ext, $image_extensions);

    if (!file_exists($tmp_name) || !is_uploaded_file($tmp_name)) {
        error_log("WCSU: Uploaded file temp path invalid for '{$field_name}' at '{$tmp_name}'.");
        return false;
    }

    // --- IMAGE UPLOAD TO WORDPRESS MEDIA LIBRARY ---
    if ($is_image) {
        error_log("WCSU: Uploading image '{$file_name}' to WP Media Library.");

        $upload = wp_upload_bits($file_name, null, file_get_contents($tmp_name));

        if (!is_array($upload) || !empty($upload['error'])) {
            error_log("WCSU: Image upload error for '{$file_name}': " . $upload['error']);
            return false;
        }

        $file_path = $upload['file'];
        $file_type_info = wp_check_filetype($file_name);
        $mime_type = $file_type_info['type'] ?? 'application/octet-stream';
        $wp_upload_dir = wp_upload_dir();

        $attachment = [
            'guid' => $wp_upload_dir['url'] . '/' . basename($file_path),
            'post_mime_type' => $mime_type,
            'post_title' => sanitize_title(pathinfo($file_name, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);

        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        try {
            $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
        } catch (Exception $e) {
            error_log("WCSU: Image metadata error: " . $e->getMessage());
        }

        return $attach_id;
    }

    // --- NON-IMAGE FILE UPLOAD TO GOOGLE DRIVE ---
    error_log("WCSU: Uploading non-image '{$file_name}' to Google Drive.");

    if (!function_exists('upload_file_to_google_drive')) {
        error_log("WCSU: Google Drive upload function missing!");
        return wcsu_fallback_local_upload($file, $post_id, $field_name);
    }

    $drive_result = upload_file_to_google_drive($file); // must return ['download' => '', 'view' => '', 'id' => '']

    if (!is_array($drive_result) || empty($drive_result['download'])) {
        error_log("WCSU: Google Drive upload failed or returned invalid result for '{$file_name}'.");
        return wcsu_fallback_local_upload($file, $post_id, $field_name);
    }

    // Store real file name
    $file_names = get_post_meta($post_id, 'wcsu_file_names', true);
    if (!is_array($file_names)) $file_names = [];
    $file_names[$field_name] = $file_name;
    update_post_meta($post_id, 'wcsu_file_names', $file_names);

    // Mark product as downloadable
    update_post_meta($post_id, '_downloadable', 'yes');
    update_post_meta($post_id, '_virtual', 'yes');

    // Add to WooCommerce downloadable files
    $downloadable_files = get_post_meta($post_id, '_downloadable_files', true);
    if (!is_array($downloadable_files)) $downloadable_files = [];

    $downloadable_files[] = [
        'name' => $file_name,
        'file' => $drive_result['download'],
        'type' => $file['type'] ?? 'application/octet-stream',
    ];

    update_post_meta($post_id, '_downloadable_files', $downloadable_files);

    // Store Drive metadata
    update_post_meta($post_id, '_wcsu_drive_data_' . $field_name, $drive_result);

    return $drive_result['download'];
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

           <div class="design-picture-section">
               <style>
  .design-picture-field {
    margin-bottom: 12px;
  }

  .dropzone {
    border: 2px dashed #ccc;
    border-radius: 6px;
    padding: 12px;
    background: #fafafa;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 80px;
    position: relative;
  }

  .dropzone:hover {
    border-color: #4caf50;
  }

  .dropzone input[type="file"] {
    display: none;
  }

 .preview-thumb {
  position: relative;
  width: 80px;
  height: auto; /* allow flexible height */
  border: 1px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
}


  .preview-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: zoom-in;
  }

  .file-status {
    font-size: 13px;
    color: #4caf50;
    margin-top: 4px;
  }

  .dropzone-placeholder {
    font-size: 13px;
    color: #888;
    position: absolute;
    left: 12px;
    top: 12px;
  }

  /* Zoom modal */
  .image-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    justify-content: center;
    align-items: center;
  }

  .image-modal img {
    max-width: 90%;
    max-height: 90%;
    border: 4px solid white;
    border-radius: 8px;
    cursor: zoom-out;
  }
  .thumb-name {
  font-size: 11px;
  text-align: center;
  margin-top: 4px;
  word-break: break-all;
  max-width: 80px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

</style>

<div class="design-picture-field">
  <label for="design-gallery">Design Pictures:</label>
  <div class="dropzone" id="gallery-dropzone">
    <span class="dropzone-placeholder" id="dropzone-placeholder">Click or drop images here</span>
    <input type="file" name="gallery" id="design-gallery" accept="image/*">
  </div>
  <div class="file-status" id="gallery-status">No images selected</div>
</div>

<!-- Fullscreen Zoom Viewer -->
<div class="image-modal" id="zoom-modal" onclick="this.style.display='none'">
  <img id="zoomed-image" src="" alt="Zoomed View">
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const dropzone = document.getElementById("gallery-dropzone");
    const input = document.getElementById("design-gallery");
    const placeholder = document.getElementById("dropzone-placeholder");
    const status = document.getElementById("gallery-status");
    const zoomModal = document.getElementById("zoom-modal");
    const zoomedImage = document.getElementById("zoomed-image");

    let currentFiles = [];

    function showZoom(src) {
      zoomedImage.src = src;
      zoomModal.style.display = 'flex';
    }

    function updatePreviews(files) {
      // Store files in memory
      currentFiles = Array.from(files);

      // Rebuild the file input manually
      const dt = new DataTransfer();
      currentFiles.forEach(file => dt.items.add(file));
      input.files = dt.files;

      // Remove all previous thumbnails but NOT the input
      dropzone.querySelectorAll('.preview-thumb').forEach(e => e.remove());

      currentFiles.forEach(file => {
        if (!file.type.startsWith("image/")) return;

        const thumb = document.createElement("div");
        thumb.className = "preview-thumb";

        const img = document.createElement("img");
        img.alt = "Preview";
        img.title = file.name;
        img.addEventListener("click", () => showZoom(URL.createObjectURL(file)));

        const reader = new FileReader();
        reader.onload = e => img.src = e.target.result;
        reader.readAsDataURL(file);

        thumb.appendChild(img);

         const nameDiv = document.createElement("div");
         nameDiv.textContent = file.name;
         nameDiv.className = "thumb-name";
         thumb.appendChild(nameDiv);
        dropzone.appendChild(thumb);
      });

      if (placeholder) {
        placeholder.style.display = currentFiles.length ? "none" : "block";
      }

      if (status) {
        status.textContent = currentFiles.length > 0
          ? `✅ ${currentFiles.length} image(s) selected`
          : '❌ No images selected';
      }
    }

    input.addEventListener("change", () => updatePreviews(input.files));

    dropzone.addEventListener("click", () => input.click());

    dropzone.addEventListener("dragover", e => {
      e.preventDefault();
      dropzone.style.borderColor = "#4caf50";
    });

    dropzone.addEventListener("dragleave", e => {
      e.preventDefault();
      dropzone.style.borderColor = "#ccc";
    });

    dropzone.addEventListener("drop", e => {
      e.preventDefault();
      dropzone.style.borderColor = "#ccc";

      const droppedFiles = e.dataTransfer.files;
      if (droppedFiles.length > 0) {
        updatePreviews(droppedFiles);
      }
    });
  });
</script>

   
    
    
</div>

<style>
.design-picture-section {
    margin-bottom: 20px;
}

.picture-upload-container {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.design-preview {
    width: 150px;
    height: 150px;
    border: 2px dashed #ccc;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    overflow: hidden;
    position: relative;
}

.design-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.design-preview span {
    color: #666;
    text-align: center;
    padding: 10px;
}

.upload-controls {
    flex: 1;
}

.upload-button {
    display: inline-block;
    background-color: #4CAF50;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 5px;
    text-align: center;
}

.upload-button:hover {
    background-color: #45a049;
}

.file-status {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}
</style>
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
            
            <!-- Add this section above your file upload fields -->
         <!-- Auto Design Selector UI -->
<div class="auto-upload-container">
  <div class="auto-upload-bar">
    <!-- Folder Input -->
    <label class="inline-group">
      📁 Folder:
      <input type="file" id="parent_folder" webkitdirectory directory multiple>
    </label>

    <!-- Design Name -->
    <label class="inline-group">
      ✏️ Design:
      <input type="text" id="design_name" placeholder="e.g., DesignA">
    </label>

    <!-- Auto-Select Button -->
    <button type="button" onclick="autoSelectDesignFiles()">🎯 Auto-Select Files</button>
  </div>

  <p class="note">
    This will automatically pick <code>.dst</code> from <code>/DST/</code>,
    <code>.emb</code> from <code>/EMB_E4/</code> and <code>/EMB_W6/</code>, and image from <code>/IMG/</code> using the design name.
  </p>
</div>

<!-- File Upload UI Fields -->
<div id="file-upload-fields" class="wcsu-upload-grid">

  <!-- DST Field with Parse Button -->
  <div class="upload-box">
    <div>
      <label>DST:</label><br>
      <label for="dst-file" class="custom-file-label">Choose DST File</label>
      <input type="file" id="dst-file" name="dst" accept=".dst" onchange="updateFileName(this, 'dst-status')" style="display: none;">
      <button type="button" class="parse-button" onclick="parseDSTFile()">🔍 Parse DST</button>
    </div>
    <div id="dst-status" class="file-status">No file chosen</div>
    <div id="dst-parser-status" class="parser-status"></div>
  </div>

  <!-- EMB E4 Field -->
  <div class="upload-box">
    <div>
      <label>EMB E4 / EMB:</label><br>
      <label for="emb-e4-file" class="custom-file-label">Choose EMB E4</label>
      <input type="file" id="emb-e4-file" name="emb_e4" accept=".emb" onchange="updateFileName(this, 'emb-e4-status')" style="display: none;">
    </div>
    <div id="emb-e4-status" class="file-status">No file chosen</div>
  </div>

  <!-- EMB W6 Field -->
  <div class="upload-box">
    <div>
      <label>EMB W6:</label><br>
      <label for="emb-w6-file" class="custom-file-label">Choose EMB W6</label>
      <input type="file" id="emb-w6-file" name="emb_w6" accept=".emb" onchange="updateFileName(this, 'emb-w6-status')" style="display: none;">
    </div>
    <div id="emb-w6-status" class="file-status">No file chosen</div>
  </div>
</div>
<!-- CSS Styling -->
<style>
.auto-upload-container {
  background: #f8f9fa;
  padding: 15px;
  border: 1px dashed #ccc;
  border-radius: 8px;
  margin-bottom: 20px;
}

.auto-upload-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  align-items: flex-end;
}

.auto-upload-bar .inline-group {
  display: flex;
  flex-direction: column;
  font-size: 14px;
}

.auto-upload-bar input[type="file"],
.auto-upload-bar input[type="text"] {
  padding: 7px 10px;
  font-size: 14px;
  border: 1px solid #ccc;
  border-radius: 4px;
  min-width: 200px;
  box-sizing: border-box;
}

.auto-upload-bar button {
  background-color: #28a745;
  color: white;
  padding: 8px 14px;
  font-size: 14px;
  font-weight: 600;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.auto-upload-bar button:hover {
  background-color: #218838;
}

.auto-upload-container .note {
  margin-top: 12px;
  font-size: 13px;
  color: #555;
}

.wcsu-upload-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  font-size: 13px;
}

.upload-box {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 100px;
  border: 1px solid #ccc;
  padding: 8px;
  border-radius: 6px;
  background: #fff;
}

.custom-file-label {
  background: #f0f0f0;
  border: 1px solid #aaa;
  padding: 4px 8px;
  cursor: pointer;
  display: inline-block;
  border-radius: 4px;
  margin-top: 4px;
}

.file-status {
  font-size: 11px;
  color: green;
  margin-top: 6px;
}

.parse-button {
  background-color: #17a2b8;
  color: white;
  border: none;
  padding: 4px 8px;
  margin-top: 5px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.parse-button:hover {
  background-color: #138496;
}

.parser-status {
  font-size: 11px;
  color: #333;
  margin-top: 4px;
  min-height: 16px;
}
</style>

<!-- JavaScript -->
<script>
function updateFileName(input, statusId) {
  const file = input.files[0];
  const status = document.getElementById(statusId);
  status.textContent = file ? `✅ ${file.name}` : '❌ No file chosen';
}
// Update design preview when image is selected
function autoSelectDesignFiles() {
  const folderInput = document.getElementById('parent_folder');
  const designName = document.getElementById('design_name').value.trim().toLowerCase();

  if (!folderInput.files.length) {
    alert('❗ Please select the parent folder.');
    return;
  }

  // If no design name entered, try to extract from folder structure
    if (!designName) {
        const firstFile = folderInput.files[0];
        if (firstFile && firstFile.webkitRelativePath) {
            const pathParts = firstFile.webkitRelativePath.split(/[\/\\]/);
            if (pathParts.length > 0) {
                designName = pathParts[0];
                document.getElementById('design_name').value = designName;
            }
        }
        
        if (!designName) {
            alert('❗ Please enter the design name (e.g., DesignA).');
            return;
        }
    }
    
     // Format design name for matching
    const cleanDesignName = designName.toLowerCase().replace(/\s+/g, '_');
    const displayName = designName.split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

  const files = Array.from(folderInput.files);

  const dstFile = files.find(f =>
    f.name.toLowerCase() === `${designName}.dst` &&
    /[\/\\]dst[\/\\]/i.test(f.webkitRelativePath)
  );

  const embE4File = files.find(f =>
    f.name.toLowerCase() === `${designName}.emb` &&
    /[\/\\]emb_e4[\/\\]/i.test(f.webkitRelativePath)
  );

  const embW6File = files.find(f =>
    f.name.toLowerCase() === `${designName}.emb` &&
    /[\/\\]emb_w6[\/\\]/i.test(f.webkitRelativePath)
  );

 // 2. Find design image (look in /img/ subfolder
    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
    const imageFile = files.find(f => {
        const fileName = f.name.toLowerCase();
        const isImage = imageExtensions.some(ext => fileName.endsWith(ext));
        return isImage && 
               (fileName.startsWith(cleanDesignName) || 
                fileName.includes(cleanDesignName)) && 
               /[\/\\]img[\/\\]/i.test(f.webkitRelativePath);
    });

  function assignFile(inputId, statusId, file) {
    const input = document.getElementById(inputId);
    const status = document.getElementById(statusId);
    if (!file) {
      status.textContent = '❌ Not found';
      return false;
    }
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    status.textContent = `✅ ${file.name}`;
    return true;
  }

  const dstOK = assignFile('dst-file', 'dst-status', dstFile);
  const e4OK = assignFile('emb-e4-file', 'emb-e4-status', embE4File);
  const w6OK = assignFile('emb-w6-file', 'emb-w6-status', embW6File);
  
  // Special handling for image file
   if (imageFile) {
    const galleryInput = document.getElementById('design-gallery');
    const dt = new DataTransfer();
    dt.items.add(imageFile);
    galleryInput.files = dt.files;

    // Update preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewImg = document.getElementById('preview-image');
        if (previewImg) {
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
        }

        const galleryStatus = document.getElementById('gallery-status');
        if (galleryStatus) {
            galleryStatus.textContent = `✅ ${imageFile.name}`;
            galleryStatus.style.color = 'green';
        }
    };
    reader.readAsDataURL(imageFile);
}


 
// Update product title
    const productTitleInput = document.querySelector('input[name="product_title"]');
    if (productTitleInput) {
        productTitleInput.value = displayName;
    }

    // Show results
    let successMsg = `✅ Selected files for "${displayName}":\n`;
    if (dstOK) successMsg += "- DST file\n";
    if (e4OK) successMsg += "- EMB E4 file\n";
    if (w6OK) successMsg += "- EMB W6 file\n";
    if (imageFile) successMsg += "- Design image\n";
    
    let errorMsg = `⚠️ Missing files:\n`;
    if (!dstOK) errorMsg += "- DST file\n";
    if (!e4OK) errorMsg += "- EMB E4 file\n";
    if (!w6OK) errorMsg += "- EMB W6 file\n";
    if (!imageFile) errorMsg += "- Design image\n";

    if (dstOK || e4OK || w6OK || imageFile) {
        alert(successMsg + (dstOK && e4OK && w6OK && imageFile ? "\nAll files found!" : "\n" + errorMsg));
    } else {
        alert("❌ No matching files found for \"" + displayName + "\"");
    }
}
// Update design preview when image is selected
function updateDesignPreview(input) {
    const preview = document.getElementById('design-preview');
    const status = document.getElementById('gallery-status');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Design Preview">`;
            status.textContent = `✅ ${input.files[0].name}`;
            status.style.color = 'green';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '<span>Preview will appear here</span>';
        status.textContent = 'No image selected';
        status.style.color = '#666';
    }
}

// Make sure to add the event listener to your gallery input
document.getElementById('gallery').addEventListener('change', function() {
    updateDesignPreview(this);
});
</script>


<script>

/**
 * Parse DST file to extract design information and update design name
 */
function parseDSTFile() {
    const dstInput = document.getElementById('dst-file');
    const statusDiv = document.getElementById('dst-parser-status');
    
    if (!dstInput.files || dstInput.files.length === 0) {
        statusDiv.textContent = "⚠️ Please select a DST file first";
        statusDiv.style.color = "red";
        return;
    }

    const dstFile = dstInput.files[0];
    statusDiv.textContent = "⏳ Parsing DST file...";
    statusDiv.style.color = "#17a2b8";

    // Extract design name from filename (remove .dst extension)
    const dstFilename = dstFile.name;
    const designNameFromFile = dstFilename.replace(/\.dst$/i, '').replace(/_/g, ' ');
    
    // Immediately update design name field
    document.getElementById('design_name').value = designNameFromFile;
    document.querySelector('input[name="product_title"]').value = designNameFromFile;

    const formData = new FormData();
    formData.append('action', 'wcsu_parse_dst');
    formData.append('dst_file', dstFile);

    fetch(wcsu_ajax.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusDiv.textContent = "✅ DST parsed successfully";
            statusDiv.style.color = "green";
            
            // Update form fields with parsed data
            const parsed = data.data;
            
            // Only update design name if the parsed one is better (longer/more complete)
            if (parsed.design_name && parsed.design_name.length > designNameFromFile.length) {
                document.getElementById('design_name').value = parsed.design_name;
                document.querySelector('input[name="product_title"]').value = parsed.design_name;
            }
            
            document.querySelector('input[name="stitches"]').value = parsed.stitches || '';
            document.querySelector('input[name="height"]').value = parsed.height || '';
            document.querySelector('input[name="width"]').value = parsed.width || '';
        } else {
            statusDiv.textContent = "❌ Error: " + (data.data || "Failed to parse DST");
            statusDiv.style.color = "red";
        }
    })
    .catch(error => {
        statusDiv.textContent = "❌ Network error: " + error.message;
        statusDiv.style.color = "red";
    });
}

/**
 * Auto-select design files and update product name
 */
function autoSelectDesignFiles() {
    const folderInput = document.getElementById('parent_folder');
    let designName = document.getElementById('design_name').value.trim();
    
    if (!folderInput.files || folderInput.files.length === 0) {
        alert('❗ Please select the "MyDesigns" folder.');
        return;
    }
    
    // If no design name entered, try to extract from folder structure
    if (!designName) {
        const firstFile = folderInput.files[0];
        if (firstFile && firstFile.webkitRelativePath) {
            const pathParts = firstFile.webkitRelativePath.split(/[\/\\]/);
            if (pathParts.length > 0) {
                designName = pathParts[0];
                document.getElementById('design_name').value = designName;
            }
        }
        
        if (!designName) {
            alert('❗ Please enter the design name (e.g., DesignA).');
            return;
        }
    }
    
    // Format design name for matching
    const cleanDesignName = designName.toLowerCase().replace(/\s+/g, '_');
    const displayName = designName.split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

    // Find all matching files
    const files = Array.from(folderInput.files);
    
    // 1. Find embroidery files
    const dstFile = files.find(f => 
        f.name.toLowerCase() === `${cleanDesignName}.dst` && 
        /[\/\\]dst[\/\\]/i.test(f.webkitRelativePath)
    );
    
    const embE4File = files.find(f => 
        f.name.toLowerCase() === `${cleanDesignName}.emb` && 
        /[\/\\]emb_e4[\/\\]/i.test(f.webkitRelativePath)
    );
    
    const embW6File = files.find(f => 
        f.name.toLowerCase() === `${cleanDesignName}.emb` && 
        /[\/\\]emb_w6[\/\\]/i.test(f.webkitRelativePath)
    );

    // 2. Find design image (look in /img/ subfolder)
    const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
    const imageFile = files.find(f => {
        const fileName = f.name.toLowerCase();
        const isImage = imageExtensions.some(ext => fileName.endsWith(ext));
        return isImage && 
               (fileName.startsWith(cleanDesignName) || 
                fileName.includes(cleanDesignName)) && 
               /[\/\\]img[\/\\]/i.test(f.webkitRelativePath);
    });

    // Assign files to inputs
    const dstOK = assignFile('dst-file', 'dst-status', dstFile);
    const e4OK = assignFile('emb-e4-file', 'emb-e4-status', embE4File);
    const w6OK = assignFile('emb-w6-file', 'emb-w6-status', embW6File);
    
    // Special handling for image file
    if (imageFile) {
        const galleryInput = document.getElementById('gallery');
        const dt = new DataTransfer();
        dt.items.add(imageFile);
        galleryInput.files = dt.files;
        
        // Manually trigger the preview update
        updateDesignPreview(galleryInput);
    }

    // Update product title
    const productTitleInput = document.querySelector('input[name="product_title"]');
    if (productTitleInput) {
        productTitleInput.value = displayName;
    }

    // Show results
    let successMsg = `✅ Selected files for "${displayName}":\n`;
    if (dstOK) successMsg += "- DST file\n";
    if (e4OK) successMsg += "- EMB E4 file\n";
    if (w6OK) successMsg += "- EMB W6 file\n";
    if (imageFile) successMsg += "- Design image\n";
    
    let errorMsg = `⚠️ Missing files:\n`;
    if (!dstOK) errorMsg += "- DST file\n";
    if (!e4OK) errorMsg += "- EMB E4 file\n";
    if (!w6OK) errorMsg += "- EMB W6 file\n";
    if (!imageFile) errorMsg += "- Design image\n";

    if (dstOK || e4OK || w6OK || imageFile) {
        alert(successMsg + (dstOK && e4OK && w6OK && imageFile ? "\nAll files found!" : "\n" + errorMsg));
    } else {
        alert("❌ No matching files found for \"" + displayName + "\"");
    }
}

// Helper function to assign files to inputs
function assignFile(inputId, statusId, file) {
    const input = document.getElementById(inputId);
    if (!input) return false;
    
    const status = document.getElementById(statusId);
    if (!file) {
        if (status) {
            status.textContent = '❌ Not found';
            status.style.color = 'red';
        }
        return false;
    }

    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    
    if (status) {
        status.textContent = `✅ ${file.name}`;
        status.style.color = 'green';
    }
    
    return true;
}

function updateFileName(input, statusId) {
    const file = input.files[0];
    const status = document.getElementById(statusId);
    
    if (file) {
        status.textContent = `✅ ${file.name}`;
        status.style.color = 'green';
        
        // If this is a DST file, update design name
        if (input.id === 'dst-file' && file.name.toLowerCase().endsWith('.dst')) {
            const designName = file.name.replace(/\.dst$/i, '').replace(/_/g, ' ');
            document.getElementById('design_name').value = designName;
            document.querySelector('input[name="product_title"]').value = designName;
        }
    } else {
        status.textContent = '❌ No file chosen';
        status.style.color = 'red';
    }
}


// Update file name display
function updateFileName(input, targetId) {
    const statusDiv = document.getElementById(targetId);
    if (input.files.length > 0) {
        statusDiv.textContent = input.files[0].name;
    } else {
        statusDiv.textContent = 'No file chosen';
    }
}
</script>
            


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
            <!-- Add this section for description dropdown -->
<div class="description-dropdown-container">
    <label>Description Template:</label>
     <select id="description_template" onchange="applyDescriptionTemplate()">
        <option value="">-- Select a Template --</option>
        <optgroup label="3mm Designs">
            <option value="Multy_Sequen_3">Multi Sequence 3mm (Multi-Color)</option>
            <option value="Multy_Sequen_3_1">Multi Sequence 3mm (Single-Color)</option>
            <option value="Multy_Sequen_3_Cord">Multi Sequence 3mm with Cord (Multi-Color)</option>
            <option value="Multy_Sequen_3_Cord_1">Multi Sequence 3mm with Cord (Single-Color)</option>
        </optgroup>
        <optgroup label="5mm Designs">
            <option value="Multy_Sequen_5">Multi Sequence 5mm (Multi-Color)</option>
            <option value="Multy_Sequen_5_1">Multi Sequence 5mm (Single-Color)</option>
            <option value="Multy_Sequen_5_Cord">Multi Sequence 5mm with Cord (Multi-Color)</option>
            <option value="Multy_Sequen_5_Cord_1">Multi Sequence 5mm with Cord (Single-Color)</option>
        </optgroup>
        <optgroup label="7mm Designs">
            <option value="Multy_Sequen_7">Multi Sequence 7mm (Multi-Color)</option>
            <option value="Multy_Sequen_7_1">Multi Sequence 7mm (Single-Color)</option>
            <option value="Multy_Sequen_7_Cord">Multi Sequence 7mm with Cord (Multi-Color)</option>
            <option value="Multy_Sequen_7_Cord_1">Multi Sequence 7mm with Cord (Single-Color)</option>
        </optgroup>
    </select>
</div>

<textarea name="product_desc" id="product_desc" required placeholder="Description"></textarea>

<script>
function applyDescriptionTemplate() {
    const template = document.getElementById('description_template').value;
    const textarea = document.getElementById('product_desc');

    const templates = {
        // 3mm Designs
        'Multy_Sequen_3': 'Multi Sequence 3mm design with multiple colors',
        'Multy_Sequen_3_1': 'Multi Sequence 3mm design with single color',
        'Multy_Sequen_3_Cord': 'Multi Sequence 3mm with cord, multiple colors',
        'Multy_Sequen_3_Cord_1': 'Multi Sequence 3mm with cord, single color',
        
        // 5mm Designs
        'Multy_Sequen_5': 'Multi Sequence 5mm design with multiple colors',
        'Multy_Sequen_5_1': 'Multi Sequence 5mm design with single color',
        'Multy_Sequen_5_Cord': 'Multi Sequence 5mm with cord, multiple colors',
        'Multy_Sequen_5_Cord_1': 'Multi Sequence 5mm with cord, single color',
        
        // 7mm Designs
        'Multy_Sequen_7': 'Multi Sequence 7mm design with multiple colors',
        'Multy_Sequen_7_1': 'Multi Sequence 7mm design with single color',
        'Multy_Sequen_7_Cord': 'Multi Sequence 7mm with cord, multiple colors',
        'Multy_Sequen_7_Cord_1': 'Multi Sequence 7mm with cord, single color'
    };

    if (template) {
        textarea.value = templates[template] || '';
    }
}
</script>

<style>
.description-dropdown-container {
    margin-bottom: 15px;
}
.description-dropdown-container select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>
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
    $file_names = get_post_meta($product_id, 'wcsu_file_names', true);

    if (empty($file_ids)) return '<p>No files found for this product.</p>';

    $output = "<div class='wcsu-download-container'>";
    $output .= "<h2>🎉 Download Product Files</h2>";
    $output .= "<ul class='wcsu-download-list'>";

    foreach ($file_ids as $label => $file_identifier) {
        $url = '';
        $file_name = $file_names[$label] ?? ''; // Prefer saved name

        // Determine URL
        if (is_string($file_identifier) && filter_var($file_identifier, FILTER_VALIDATE_URL)) {
            $url = $file_identifier;
            if (empty($file_name)) {
                $path = parse_url($url, PHP_URL_PATH);
                $file_name = basename($path);
                if (empty($file_name) || $file_name === 'uc' || strlen($file_name) <= 3 || !pathinfo($file_name, PATHINFO_EXTENSION)) {
                    parse_str(parse_url($url, PHP_URL_QUERY), $query);
                    $file_name = $label . '-' . ($query['id'] ?? 'file') . '.file';
                }
            }
        } elseif (is_numeric($file_identifier)) {
            $url = wp_get_attachment_url($file_identifier);
            $file_path = get_attached_file($file_identifier);
            if (empty($file_name)) {
                $file_name = basename($file_path);
            }
        } else {
            error_log("WCSU: Unexpected file identifier type or format for label {$label}: " . print_r($file_identifier, true));
            continue;
        }

        if ($url) {
            $display_label = ucfirst(str_replace('_', ' ', $label));
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

            $output .= "<li class='wcsu-download-item'>";
            $output .= "<span class='wcsu-file-info'>";
            $output .= "<span class='wcsu-file-type'>{$display_label}</span>: ";
            $output .= "<span class='wcsu-file-name'>{$file_name}</span>";
            $output .= "</span>";
            $output .= "<a href='" . esc_url($url) . "' download class='wcsu-download-button'>Download (.{$file_extension})</a>";
            $output .= "</li>";
        } else {
            error_log("WCSU: Could not get URL for file identifier: " . print_r($file_identifier, true));
        }
    }

    $output .= "</ul></div>";
    $output .= "
    <style>
        .wcsu-download-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .wcsu-download-list {
            list-style: none;
            padding: 0;
        }
        .wcsu-download-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .wcsu-file-info {
            flex: 1;
        }
        .wcsu-file-type {
            font-weight: bold;
            color: #333;
        }
        .wcsu-file-name {
            color: #666;
            font-size: 0.9em;
        }
        .wcsu-download-button {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        .wcsu-download-button:hover {
            background-color: #45a049;
        }
    </style>";

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