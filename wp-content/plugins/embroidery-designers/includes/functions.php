<?php
/**
 * Helper functions for the Embroidery Designers plugin
 */

if (!function_exists('ed_get_designer_rating')) {
    /**
     * Get designer rating
     */
    function ed_get_designer_rating($user_id) {
        $rating = get_user_meta($user_id, 'seller_rating', true);
        return $rating ? floatval($rating) : 0;
    }
}

if (!function_exists('ed_get_designer_products_count')) {
    /**
     * Get designer products count
     */
    function ed_get_designer_products_count($user_id) {
        return count(get_posts(array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        )));
    }
}

if (!function_exists('ed_get_designer_categories')) {
    /**
     * Get designer's product categories
     */
    function ed_get_designer_categories($user_id) {
        $categories = array();
        $products = get_posts(array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        foreach ($products as $product_id) {
            $product_categories = wp_get_post_terms($product_id, 'product_cat');
            foreach ($product_categories as $category) {
                $categories[$category->term_id] = $category;
            }
        }

        return $categories;
    }
}

if (!function_exists('ed_update_designer_rating')) {
    /**
     * Update designer rating based on product reviews
     */
    function ed_update_designer_rating($user_id) {
        $products = get_posts(array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        $total_rating = 0;
        $review_count = 0;

        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            $average_rating = $product->get_average_rating();
            
            if ($average_rating > 0) {
                $total_rating += $average_rating;
                $review_count += $product->get_rating_count();
            }
        }

        if ($review_count > 0) {
            $average = $total_rating / count($products);
            update_user_meta($user_id, 'seller_rating', $average);
            update_user_meta($user_id, 'review_count', $review_count);
        }
    }
}

// Update designer rating when a review is approved
add_action('comment_unapproved_to_approved', 'ed_update_designer_rating_on_review');
add_action('comment_approved_to_unapproved', 'ed_update_designer_rating_on_review');

if (!function_exists('ed_update_designer_rating_on_review')) {
    function ed_update_designer_rating_on_review($comment) {
        $post_id = $comment->comment_post_ID;
        $post_type = get_post_type($post_id);

        if ('product' === $post_type) {
            $product = wc_get_product($post_id);
            $author_id = $product->get_post_data()->post_author;
            ed_update_designer_rating($author_id);
        }
    }
}

// Flush rewrite rules on plugin activation/deactivation
register_activation_hook(__FILE__, 'ed_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'ed_flush_rewrite_rules');

if (!function_exists('ed_flush_rewrite_rules')) {
    function ed_flush_rewrite_rules() {
        flush_rewrite_rules();
    }
}

// Add link to designers page in menu
add_filter('wp_nav_menu_items', 'ed_add_designers_link_to_menu', 10, 2);

if (!function_exists('ed_add_designers_link_to_menu')) {
    function ed_add_designers_link_to_menu($items, $args) {
        if ($args->theme_location == 'primary') {
            $items .= '<li class="menu-item"><a href="' . home_url('/designers/') . '">' . __('Designers', 'embroidery-designers') . '</a></li>';
        }
        return $items;
    }
}