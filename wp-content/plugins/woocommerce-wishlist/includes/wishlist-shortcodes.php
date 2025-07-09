<?php
/**
 * Wishlist Shortcodes
 *
 * Provides shortcodes for the WooCommerce Wishlist plugin
 *
 * @package WooCommerce Wishlist
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Wishlist shortcode to display the wishlist page
 *
 * @return string
 */
function wcwl_wishlist_shortcode() {
    ob_start();

    // Check if user is logged in for private wishlist
    if (!is_user_logged_in()) {
        echo '<div class="woocommerce-info">';
        echo sprintf(
            __('Please <a href="%s">login</a> to view your wishlist or continue as guest.', 'woocommerce-wishlist'),
            esc_url(wp_login_url(wcwl_get_wishlist_url()))
        );
        echo '</div>';
    }

    // Display wishlist content
    WC_Wishlist::instance()->wishlist_content();

    return ob_get_clean();
}
add_shortcode('woocommerce_wishlist', 'wcwl_wishlist_shortcode');

/**
 * Shortcode to display wishlist counter/link
 *
 * @param array $atts Shortcode attributes
 * @return string
 */
function wcwl_wishlist_counter_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_count' => 'true',
        'show_text'  => 'true',
        'icon'       => 'heart',
    ), $atts, 'wcwl_wishlist_counter');

    $wishlist = WC_Wishlist::instance();
    $count = count($wishlist->get_wishlist());

    ob_start();
    ?>
    <div class="wcwl-wishlist-counter">
        <a href="<?php echo esc_url(wcwl_get_wishlist_url()); ?>" class="wcwl-wishlist-link">
            <?php if ($atts['icon'] !== 'none') : ?>
                <span class="wcwl-icon wcwl-icon-<?php echo esc_attr($atts['icon']); ?>">
                    <?php echo ($atts['icon'] === 'heart') ? '♥' : '♡'; ?>
                </span>
            <?php endif; ?>

            <?php if ($atts['show_count'] === 'true') : ?>
                <span class="wcwl-wishlist-count"><?php echo esc_html($count); ?></span>
            <?php endif; ?>

            <?php if ($atts['show_text'] === 'true') : ?>
                <span class="wcwl-wishlist-text">
                    <?php echo esc_html(_n('Wishlist', 'Wishlist', $count, 'woocommerce-wishlist')); ?>
                </span>
            <?php endif; ?>
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('wcwl_wishlist_counter', 'wcwl_wishlist_counter_shortcode');

/**
 * Shortcode to display "Add to Wishlist" button
 *
 * @param array $atts Shortcode attributes
 * @return string
 */
function wcwl_add_to_wishlist_shortcode($atts) {
    $atts = shortcode_atts(array(
        'product_id' => '',
        'style'      => 'button', // 'button' or 'icon'
    ), $atts, 'wcwl_add_to_wishlist');

    if (empty($atts['product_id'])) {
        global $product;
        if ($product) {
            $atts['product_id'] = $product->get_id();
        } else {
            return '';
        }
    }

    $product_id = absint($atts['product_id']);
    $wishlist = WC_Wishlist::instance();
    $is_in_wishlist = $wishlist->is_product_in_wishlist($product_id);

    ob_start();

    if ($atts['style'] === 'icon') {
        ?>
        <a href="#" class="wcwl-add-to-wishlist wcwl-add-to-wishlist-icon <?php echo $is_in_wishlist ? 'added' : ''; ?>"
           data-product-id="<?php echo esc_attr($product_id); ?>"
           data-nonce="<?php echo wp_create_nonce('wcwl-add-' . $product_id); ?>"
           title="<?php echo $is_in_wishlist ? esc_attr__('Remove from Wishlist', 'woocommerce-wishlist') : esc_attr__('Add to Wishlist', 'woocommerce-wishlist'); ?>">
            <?php echo $is_in_wishlist ? '♥' : '♡'; ?>
        </a>
        <?php
    } else {
        ?>
        <a href="#" class="button wcwl-add-to-wishlist <?php echo $is_in_wishlist ? 'added' : ''; ?>"
           data-product-id="<?php echo esc_attr($product_id); ?>"
           data-nonce="<?php echo wp_create_nonce('wcwl-add-' . $product_id); ?>">
            <?php echo $is_in_wishlist ? esc_html__('Remove from Wishlist', 'woocommerce-wishlist') : esc_html__('Add to Wishlist', 'woocommerce-wishlist'); ?>
        </a>
        <?php
    }

    return ob_get_clean();
}
add_shortcode('wcwl_add_to_wishlist', 'wcwl_add_to_wishlist_shortcode');

/**
 * Shortcode to display wishlist products grid
 *
 * @param array $atts Shortcode attributes
 * @return string
 */
function wcwl_wishlist_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'columns' => '4',
        'limit'   => '12',
        'title'   => __('Your Wishlist', 'woocommerce-wishlist'),
    ), $atts, 'wcwl_wishlist_products');

    $wishlist = WC_Wishlist::instance()->get_wishlist();
    $product_ids = array_filter(array_slice($wishlist, 0, absint($atts['limit'])), function ($id) {
        return get_post_status($id) === 'publish';
    });

    if (empty($product_ids)) {
        return '<p class="wcwl-empty-wishlist">' . esc_html__('Your wishlist is currently empty.', 'woocommerce-wishlist') . '</p>';
    }

    $args = array(
        'post_type'      => 'product',
        'post__in'       => $product_ids,
        'posts_per_page' => absint($atts['limit']),
        'orderby'        => 'post__in',
    );

    $products = new WP_Query($args);

    ob_start();

    if ($products->have_posts()) :
        $columns = absint($atts['columns']);
        $original_columns = wc_get_loop_prop('columns');
        wc_set_loop_prop('columns', $columns);

        echo '<div class="wcwl-wishlist-products">';

        if (!empty($atts['title'])) :
            echo '<h2 class="wcwl-wishlist-products-title">' . esc_html($atts['title']) . '</h2>';
        endif;

        woocommerce_product_loop_start();

        while ($products->have_posts()) : $products->the_post();
            wc_get_template_part('content', 'product');
        endwhile;

        woocommerce_product_loop_end();

        echo '</div>';

        wc_set_loop_prop('columns', $original_columns);
    else :
        echo '<p class="wcwl-empty-wishlist">' . esc_html__('Your wishlist is currently empty.', 'woocommerce-wishlist') . '</p>';
    endif;

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('wcwl_wishlist_products', 'wcwl_wishlist_products_shortcode');
