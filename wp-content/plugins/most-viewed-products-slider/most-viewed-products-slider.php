<?php
/*
Plugin Name: Most Viewed Products Slider
Description: Displays a slider of the top 10 most viewed WooCommerce products using Slick Carousel.
Version: 1.0
Author: Sujauddin Sekh
*/

if (!defined('ABSPATH')) exit;

class Most_Viewed_Products_Slider {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('most_viewed_products_slider', [$this, 'render_slider']);
        add_action('wp_head', [$this, 'track_views']);

        // Initialize views only once
        add_action('init', [$this, 'initialize_product_views_once']);
    }

    // Track product views
    public function track_views() {
        if (is_singular('product')) {
            global $post;
            $views = (int) get_post_meta($post->ID, 'product_views', true);
            update_post_meta($post->ID, 'product_views', $views + 1);
        }
    }

    // Initialize views for all products only once
    public function initialize_product_views_once() {
        // Run only once on plugin activation
        $flag = get_option('mvp_initialized');
        if ($flag === 'yes') return;

        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'product_views',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        foreach ($products as $product) {
            add_post_meta($product->ID, 'product_views', 1, true);
        }

        update_option('mvp_initialized', 'yes');
    }

    // Enqueue Slick CSS/JS and slider script
    public function enqueue_assets() {
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], null, true);

        wp_add_inline_script('slick-js', "
            jQuery(document).ready(function($){
                $('.most-viewed-products-slider').slick({
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    autoplay: true,
                    autoplaySpeed: 2000,
                    speed: 600,
                    arrows: true,
                    dots: false,
                    infinite: true,
                    pauseOnHover: true,
                    responsive: [
                        { breakpoint: 1024, settings: { slidesToShow: 3 }},
                        { breakpoint: 768, settings: { slidesToShow: 2 }},
                        { breakpoint: 480, settings: { slidesToShow: 1 }}
                    ]
                });
            });
        ");
    }

    // Render most viewed product slider
    public function render_slider() {
        if (!class_exists('WooCommerce')) {
            return '<p>WooCommerce is required.</p>';
        }

        $query = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 10,
            'meta_key' => 'product_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'post_status' => 'publish'
        ]);

        if (!$query->have_posts()) {
            return '<p>No products found.</p>';
        }

        ob_start();
        ?>
        <div class="most-viewed-products-slider" style="margin: 20px;">
            <?php while ($query->have_posts()) : $query->the_post(); global $product; ?>
                <div class="product-card-small">
                    <a href="<?php the_permalink(); ?>" class="product-link-small">
                        <div class="image-box-small">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('woocommerce_thumbnail', ['class' => 'thumb-small']);
                            } else {
                                echo wc_placeholder_img('woocommerce_thumbnail');
                            }
                            ?>
                        </div>
                        <h4 class="product-title-small"><?php the_title(); ?></h4>
                        <span class="price-small"><?php echo $product->get_price_html(); ?></span>
                    </a>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <style>
        .product-card-small {
            width: 180px;
            height: 250px;
            margin: 0 6px;
            padding: 10px;
            text-align: center;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        .image-box-small {
            width: 140px;
            height: 140px;
            background: #f8f8f8;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .thumb-small {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-title-small {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 0;
            padding: 0;
            height: 38px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .price-small {
            font-size: 15px;
            font-weight: bold;
            color: #0073aa;
            margin-top: auto;
        }

        .product-link-small {
            color: inherit;
            text-decoration: none;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

new Most_Viewed_Products_Slider();
