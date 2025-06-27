<?php
/*
Plugin Name: WooCommerce Free Products - Responsive
Description: Display all free WooCommerce products with automatic responsive design
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Free_Products_Responsive {

    public function __construct() {
        add_shortcode('free_products', array($this, 'free_products_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        // Inline CSS for better performance
        $css = "
        .wc-free-products-container {
            margin: 20px 0;
        }
        .wc-free-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .wc-free-product {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            transition: all 0.3s ease;
            text-align: center;
        }
        .wc-free-product:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .wc-free-product-image {
            margin-bottom: 15px;
        }
        .wc-free-product-image img {
            width: 100%;
            height: auto;
            border-radius: 3px;
            max-width: 100%;
        }
        .wc-free-product-title {
            font-size: 1.1em;
            margin: 0 0 10px 0;
            word-break: break-word;
        }
        .wc-free-product-price {
            color: #4CAF50;
            font-weight: bold;
            display: block;
        }
        .wc-free-products-none {
            text-align: center;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .wc-free-products {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
        }
        @media (max-width: 480px) {
            .wc-free-products {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }
            .wc-free-product {
                padding: 10px;
            }
        }";

        wp_add_inline_style('wp-block-library', $css);
    }

    public function free_products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 'auto',
            'limit' => '-1',
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts, 'free_products');

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => array(
                array(
                    'key' => '_price',
                    'value' => 0,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                )
            )
        );

        $products = new WP_Query($args);
        $output = '';

        if ($products->have_posts()) {
            ob_start();
            ?>
            <div class="wc-free-products-container">
                <ul class="wc-free-products">
                    <?php while ($products->have_posts()) : $products->the_post(); ?>
                        <?php global $product; ?>
                        <li class="wc-free-product">
                            <a href="<?php echo esc_url(get_permalink()); ?>">
                                <div class="wc-free-product-image">
                                    <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                                </div>
                                <h3 class="wc-free-product-title"><?php the_title(); ?></h3>
                                <span class="wc-free-product-price"><?php echo $product->get_price_html(); ?></span>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php
            $output = ob_get_clean();
            wp_reset_postdata();
        } else {
            $output = '<p class="wc-free-products-none">No free products available at this time.</p>';
        }

        return $output;
    }
}

new WC_Free_Products_Responsive();