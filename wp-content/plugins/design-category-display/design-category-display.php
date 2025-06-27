<?php
/**
 * Plugin Name: Design Category Display
 * Description: Display WooCommerce product categories in grid or list view with toggle, advanced filtering, and AJAX performance.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dcd-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_script('dcd-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
    wp_localize_script('dcd-script', 'dcd_ajax_obj', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
});

// Shortcode
add_shortcode('design_categories', 'dcd_render_category_display');

function dcd_render_category_display() {
    ob_start(); ?>
    <div class="dcd-container">
        <div class="dcd-toggle-wrap">
            <button id="dcd-toggle-grid" class="active">Grid View</button>
            <button id="dcd-toggle-list">List View</button>
        </div>

        <div class="dcd-filter-wrap">
            <label for="dcd-parent-filter">Filter by Parent Category:</label>
            <select id="dcd-parent-filter">
                <option value="">All Categories</option>
                <?php
                $parents = get_terms([
                    'taxonomy' => 'product_cat',
                    'parent' => 0,
                    'hide_empty' => false
                ]);
                foreach ($parents as $parent) {
                    echo '<option value="' . esc_attr($parent->term_id) . '">' . esc_html($parent->name) . '</option>';
                }
                ?>
            </select>
        </div>

        <div id="dcd-category-wrapper" class="dcd-wrapper dcd-grid-view">
            <!-- Categories will load here via AJAX -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX handler
add_action('wp_ajax_dcd_load_categories', 'dcd_load_categories');
add_action('wp_ajax_nopriv_dcd_load_categories', 'dcd_load_categories');

function dcd_load_categories() {
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $parent_id
    ]);

    if (empty($terms) || is_wp_error($terms)) {
        wp_send_json_error('No categories found.');
    }

    $output = '';
    foreach ($terms as $term) {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
        $description = term_description($term);

        $output .= '<div class="dcd-item">';
        $output .= '<a href="' . esc_url(get_term_link($term)) . '">';
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($term->name) . '">';
        $output .= '<div class="dcd-content">';
        $output .= '<h3 class="dcd-title">' . esc_html($term->name) . '</h3>';
        if ($description) {
            $output .= '<p class="dcd-description">' . wp_trim_words($description, 12) . '</p>';
        }
        $output .= '</div></a></div>';
    }

    wp_send_json_success($output);
}
