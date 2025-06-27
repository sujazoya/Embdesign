<?php
class Embroidery_Shortcodes {

    public static function init() {
        add_shortcode('advanced_categories', [__CLASS__, 'advanced_categories_shortcode']);
    }

    public static function advanced_categories_shortcode($atts) {
        $atts = shortcode_atts([
            'mode' => 'list',
            'list_title' => 'All Categories',
            'grid_title' => 'Shop by Category'
        ], $atts, 'advanced_categories');

        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);

        if (empty($categories)) {
            return '<p>No categories found</p>';
        }

        ob_start();

        if ($atts['mode'] === 'list') {
            echo '<div class="category-list-view">';
            echo '<h3 class="category-list-title">' . esc_html($atts['list_title']) . '</h3>';
            echo '<ul class="category-list">';
            foreach ($categories as $category) {
                echo '<li class="category-list-item">';
                echo '<a href="' . esc_url(get_term_link($category)) . '">';
                echo esc_html($category->name);
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="category-grid-view">';
            echo '<h3 class="category-grid-title">' . esc_html($atts['grid_title']) . '</h3>';
            echo '<div class="category-grid">';
            foreach ($categories as $category) {
                $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                $image = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : wc_placeholder_img_src();
                
                echo '<div class="category-grid-item">';
                echo '<a href="' . esc_url(get_term_link($category)) . '">';
                echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($category->name) . '">';
                echo '<span class="category-name">' . esc_html($category->name) . '</span>';
                echo '</a>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }

        return ob_get_clean();
    }
}