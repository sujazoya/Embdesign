<?php
/*
Plugin Name: Advanced Product Search
Description: Adds an advanced search bar with filters for products. Shows as button by default that expands to full form.
Version: 1.1
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit;
}

class Advanced_Product_Search {

    public function __construct() {
        add_shortcode('advanced_product_search', [$this, 'render_search_form']);
        add_action('pre_get_posts', [$this, 'modify_search_query']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'add_search_button_to_all_pages']);
    }

    public function render_search_form($atts = []) {
        $atts = shortcode_atts(['show_button_only' => false], $atts);
        
        ob_start(); ?>
        <div class="aps-search-container">
            <?php if ($atts['show_button_only']) : ?>
                <button class="aps-search-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            <?php endif; ?>
            
            <div class="aps-search-form" <?php echo $atts['show_button_only'] ? 'style="display:none;"' : ''; ?>>
                <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <!-- Product Title -->
                    <div class="aps-field">
                        <label for="aps-product-title"><?php esc_html_e('Product Title', 'advanced-product-search'); ?></label>
                        <input type="text" name="s" id="aps-product-title" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search products...', 'advanced-product-search'); ?>">
                    </div>

                    <!-- Design Code -->
                    <div class="aps-field">
                        <label for="aps-design-code"><?php esc_html_e('Design Code', 'advanced-product-search'); ?></label>
                        <input type="text" name="design_code" id="aps-design-code" value="<?php echo isset($_GET['design_code']) ? esc_attr($_GET['design_code']) : ''; ?>" placeholder="<?php esc_attr_e('Enter design code...', 'advanced-product-search'); ?>">
                    </div>

                    <!-- Category Dropdown - Fixed to show all options -->
                    <div class="aps-field">
                        <label for="aps-product-cat"><?php esc_html_e('Category', 'advanced-product-search'); ?></label>
                        <select name="product_cat" id="aps-product-cat" class="aps-select">
                            <option value=""><?php esc_html_e('All Categories', 'advanced-product-search'); ?></option>
                            <?php
                            $categories = get_terms([
                                'taxonomy' => 'product_cat',
                                'hide_empty' => false, // Show all categories
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ]);
                            
                            foreach ($categories as $category) {
                                $selected = isset($_GET['product_cat']) && $_GET['product_cat'] == $category->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Tag Dropdown - Fixed to properly load all tags -->
                    <div class="aps-field">
                        <label for="aps-tags"><?php esc_html_e('Tags', 'advanced-product-search'); ?></label>
                        <select name="tag" id="aps-tags" class="aps-select">
                            <option value=""><?php esc_html_e('All Tags', 'advanced-product-search'); ?></option>
                            <?php
                            // Get all post tags
                            $post_tags = get_terms([
                                'taxonomy' => 'post_tag',
                                'hide_empty' => false,
                                'orderby' => 'name',
                                'order' => 'ASC'
                            ]);
                            
                            // Get all product tags if WooCommerce is active
                            $product_tags = [];
                            if (taxonomy_exists('product_tag')) {
                                $product_tags = get_terms([
                                    'taxonomy' => 'product_tag',
                                    'hide_empty' => false,
                                    'orderby' => 'name',
                                    'order' => 'ASC'
                                ]);
                            }
                            
                            // Combine and remove duplicates
                            $all_tags = array_unique(array_merge($post_tags, $product_tags), SORT_REGULAR);
                            
                            foreach ($all_tags as $tag) {
                                $selected = isset($_GET['tag']) && $_GET['tag'] == $tag->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($tag->slug) . '" ' . $selected . '>' . esc_html($tag->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <input type="hidden" name="post_type" value="product">
                    <div class="aps-submit">
                        <button type="submit" class="aps-button"><?php esc_html_e('Search', 'advanced-product-search'); ?></button>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="aps-reset"><?php esc_html_e('Reset', 'advanced-product-search'); ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function add_search_button_to_all_pages() {
        echo do_shortcode('[advanced_product_search show_button_only="true"]');
    }

    public function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            if (isset($_GET['post_type']) && $_GET['post_type'] == 'product') {
                $meta_query = [];

                if (!empty($_GET['design_code'])) {
                    $meta_query[] = [
                        'key' => 'design_code',
                        'value' => sanitize_text_field($_GET['design_code']),
                        'compare' => 'LIKE',
                    ];
                }

                if (!empty($meta_query)) {
                    $query->set('meta_query', $meta_query);
                }

                $query->set('post_type', 'product');
            }
        }
    }

    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'advanced-product-search',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css')
        );

        // JS
        wp_enqueue_script(
            'advanced-product-search',
            plugin_dir_url(__FILE__) . 'assets/js/script.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/script.js'),
            true
        );

        // Select2
        if (apply_filters('aps_enable_select2', true)) {
            wp_enqueue_script(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                ['jquery'],
                '4.1.0',
                true
            );
            wp_enqueue_style(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                [],
                '4.1.0'
            );
            
            wp_add_inline_script('select2', '
                jQuery(document).ready(function($) {
                    $(".aps-select").select2({
                        placeholder: "'.__('Select an option', 'advanced-product-search').'",
                        allowClear: true,
                        width: "100%",
                        dropdownParent: $(".aps-search-form")
                    });
                });
            ');
        }
    }
}

new Advanced_Product_Search();