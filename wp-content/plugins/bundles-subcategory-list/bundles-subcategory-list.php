<?php
/*
Plugin Name: Bundles Subcategory List
Description: Displays Bundles subcategories in a horizontal scrollable list with Bundles icon centered above.
Version: 1.0
Author: YourName
*/

if (!defined('ABSPATH')) exit;

class Bundles_Subcategory_List {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('bundles_subcategory_list', [$this, 'render_subcategories']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('bundles-subcategory-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    }

    public function render_subcategories() {
        if (!class_exists('WooCommerce')) {
            return '<p><strong>WooCommerce is not active.</strong></p>';
        }

        $parent = get_term_by('slug', 'bundles', 'product_cat');
        if (!$parent || is_wp_error($parent)) {
            return '<p><strong>Bundles category not found.</strong></p>';
        }

        $header_img_id = get_term_meta($parent->term_id, 'thumbnail_id', true);
        $header_img_url = $header_img_id ? wp_get_attachment_url($header_img_id) : wc_placeholder_img_src();

        $subcategories = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => $parent->term_id,
            'hide_empty' => false,
        ]);

        ob_start();
        ?>
        <div class="bundles-section-wrapper">
            <div class="bundles-header">
                <img src="<?php echo esc_url($header_img_url); ?>" alt="Bundles Icon" />
                <h2><?php echo esc_html($parent->name); ?></h2>
            </div>

            <div class="bundles-horizontal-list">
                <?php foreach ($subcategories as $subcategory):
                    $sub_img_id = get_term_meta($subcategory->term_id, 'thumbnail_id', true);
                    $sub_img_url = $sub_img_id ? wp_get_attachment_url($sub_img_id) : wc_placeholder_img_src();
                    $sub_link = get_term_link($subcategory);
                ?>
                    <a href="<?php echo esc_url($sub_link); ?>" class="bundle-subcard">
                        <img src="<?php echo esc_url($sub_img_url); ?>" alt="<?php echo esc_attr($subcategory->name); ?>" />
                        <span><?php echo esc_html($subcategory->name); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Bundles_Subcategory_List();
