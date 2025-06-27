<?php
/**
 * Shortcodes for the Embroidery Designers plugin
 */

class ED_Shortcodes {

    /**
     * Initialize shortcodes
     */
    public static function init() {
        add_shortcode('designer_profile', array(__CLASS__, 'designer_profile_shortcode'));
        add_shortcode('designer_products', array(__CLASS__, 'designer_products_shortcode'));
        add_shortcode('designer_info', array(__CLASS__, 'designer_info_shortcode'));
        add_shortcode('designers_list', array(__CLASS__, 'designers_list_shortcode'));
    }

    /**
     * Display a designer profile card
     */
    public static function designer_profile_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_author_meta('ID'),
            'show_products' => 'true',
            'show_contact' => 'true'
        ), $atts, 'designer_profile');

        $author_id = $atts['id'];
        $author = get_user_by('ID', $author_id);

        if (!$author) {
            return '<p>' . esc_html__('Designer not found.', 'embroidery-designers') . '</p>';
        }

        ob_start();
        ?>
        <div class="ed-designer-profile-card">
            <div class="ed-designer-header">
                <div class="ed-designer-avatar-container">
                    <?php echo get_avatar($author_id, 120); ?>
                    <?php if (get_user_meta($author_id, 'seller_verified', true)): ?>
                        <span class="ed-verified-badge" title="<?php esc_attr_e('Verified Designer', 'embroidery-designers'); ?>">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="ed-designer-info">
                    <h3>
                        <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>">
                            <?php echo esc_html($author->display_name); ?>
                        </a>
                    </h3>
                    
                    <div class="ed-designer-meta">
                        <span class="ed-meta-item">
                            <i class="fas fa-palette"></i>
                            <?php 
                            printf(
                                _n('%d design', '%d designs', ed_get_designer_products_count($author_id)), 
                                ed_get_designer_products_count($author_id)
                            );
                            ?>
                        </span>
                        
                        <?php if (ed_get_designer_rating($author_id) > 0): ?>
                            <span class="ed-meta-item">
                                <div class="ed-stars" style="--rating: <?php echo esc_attr(ed_get_designer_rating($author_id)); ?>;"></div>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>" class="ed-view-profile-button">
                        <?php esc_html_e('View Full Profile', 'embroidery-designers'); ?>
                    </a>
                </div>
            </div>
            
            <?php if ($atts['show_products'] === 'true'): ?>
                <?php echo do_shortcode('[designer_products id="' . $author_id . '" limit="3"]'); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display a designer's products grid
     */
    public static function designer_products_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_author_meta('ID'),
            'limit' => 6,
            'columns' => 3
        ), $atts, 'designer_products');

        $author_id = $atts['id'];
        $author = get_user_by('ID', $author_id);

        if (!$author) {
            return '<p>' . esc_html__('Designer not found.', 'embroidery-designers') . '</p>';
        }

        $products = get_posts(array(
            'post_type' => 'product',
            'author' => $author_id,
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        ));

        if (empty($products)) {
            return '<p>' . esc_html__('No designs found for this designer.', 'embroidery-designers') . '</p>';
        }

        ob_start();
        ?>
        <div class="ed-designer-products-grid" style="--columns: <?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($products as $product): ?>
                <div class="ed-designer-product-card">
                    <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="ed-product-image">
                        <?php echo get_the_post_thumbnail($product->ID, 'medium'); ?>
                    </a>
                    <div class="ed-product-info">
                        <h4>
                            <a href="<?php echo esc_url(get_permalink($product->ID)); ?>">
                                <?php echo esc_html(get_the_title($product->ID)); ?>
                            </a>
                        </h4>
                        <div class="ed-product-price">
                            <?php echo wc_price(get_post_meta($product->ID, '_price', true)); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <style>
            .ed-designer-products-grid {
                display: grid;
                grid-template-columns: repeat(var(--columns), 1fr);
                gap: 20px;
                margin-top: 20px;
            }
            .ed-designer-product-card {
                border: 1px solid #eee;
                border-radius: 5px;
                overflow: hidden;
            }
            .ed-designer-product-card .ed-product-image img {
                width: 100%;
                height: auto;
                display: block;
            }
            .ed-designer-product-card .ed-product-info {
                padding: 15px;
            }
            .ed-designer-product-card .ed-product-info h4 {
                margin: 0 0 10px;
            }
            .ed-designer-product-card .ed-product-price {
                font-weight: bold;
                color: #27ae60;
            }
            @media (max-width: 768px) {
                .ed-designer-products-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 480px) {
                .ed-designer-products-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Display designer information
     */
    public static function designer_info_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_author_meta('ID'),
            'show' => 'name,avatar,products_count,rating',
            'avatar_size' => 80
        ), $atts, 'designer_info');

        $author_id = $atts['id'];
        $author = get_user_by('ID', $author_id);

        if (!$author) {
            return '<p>' . esc_html__('Designer not found.', 'embroidery-designers') . '</p>';
        }

        $show_elements = array_map('trim', explode(',', $atts['show']));
        $products_count = ed_get_designer_products_count($author_id);
        $rating = ed_get_designer_rating($author_id);
        $is_verified = get_user_meta($author_id, 'seller_verified', true);

        ob_start();
        ?>
        <div class="ed-designer-info-shortcode">
            <?php if (in_array('avatar', $show_elements)): ?>
                <div class="ed-designer-avatar">
                    <?php echo get_avatar($author_id, $atts['avatar_size']); ?>
                    <?php if ($is_verified && in_array('verified', $show_elements)): ?>
                        <span class="ed-verified-badge" title="<?php esc_attr_e('Verified Designer', 'embroidery-designers'); ?>">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (in_array('name', $show_elements)): ?>
                <h3 class="ed-designer-name"><?php echo esc_html($author->display_name); ?></h3>
            <?php endif; ?>
            
            <div class="ed-designer-meta">
                <?php if (in_array('products_count', $show_elements)): ?>
                    <span class="ed-meta-item">
                        <i class="fas fa-palette"></i> 
                        <?php 
                        printf(
                            _n('%d design', '%d designs', $products_count), 
                            $products_count
                        );
                        ?>
                    </span>
                <?php endif; ?>
                
                <?php if (in_array('rating', $show_elements) && $rating > 0): ?>
                    <span class="ed-meta-item">
                        <div class="ed-stars" style="--rating: <?php echo esc_attr($rating); ?>;"></div>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .ed-designer-info-shortcode {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 10px;
            }
            .ed-designer-info-shortcode .ed-designer-avatar {
                position: relative;
            }
            .ed-designer-info-shortcode .ed-verified-badge {
                position: absolute;
                bottom: 5px;
                right: 5px;
                background: #27ae60;
                color: white;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8rem;
                border: 2px solid white;
            }
            .ed-designer-info-shortcode .ed-designer-meta {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .ed-designer-info-shortcode .ed-meta-item {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 0.9em;
                color: #666;
            }
            .ed-designer-info-shortcode .ed-stars {
                --percent: calc(var(--rating) / 5 * 100%);
                display: inline-block;
                font-size: 1em;
                line-height: 1;
                position: relative;
                color: #ddd;
            }
            .ed-designer-info-shortcode .ed-stars::before {
                content: '★★★★★';
                position: absolute;
                top: 0;
                left: 0;
                width: var(--percent);
                overflow: hidden;
                color: #f39c12;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Display a list of designers
     */
    public static function designers_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'columns' => 3,
            'show_search' => 'true'
        ), $atts, 'designers_list');

        $designers_args = array(
            'role__in' => array('author', 'editor', 'administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'has_published_posts' => array('product'),
            'number' => $atts['limit']
        );

        $designers = get_users($designers_args);

        ob_start();
        ?>
        <div class="ed-designers-list-shortcode">
            <?php if ($atts['show_search'] === 'true'): ?>
                <div class="ed-search-container">
                    <form method="get" action="<?php echo esc_url(home_url('/designers/')); ?>" class="ed-search-form">
                        <input type="text" 
                               name="designer_search" 
                               placeholder="<?php esc_attr_e('Search designers...', 'embroidery-designers'); ?>" 
                               class="ed-search-input">
                        <button type="submit" class="ed-search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="ed-designers-grid" style="--columns: <?php echo esc_attr($atts['columns']); ?>">
                <?php if (!empty($designers)): ?>
                    <?php foreach ($designers as $designer): ?>
                        <?php
                        $designer_id = $designer->ID;
                        $designer_name = $designer->display_name;
                        $designer_link = get_author_posts_url($designer_id);
                        $designer_products_count = ed_get_designer_products_count($designer_id);
                        $designer_rating = ed_get_designer_rating($designer_id);
                        $is_verified = get_user_meta($designer_id, 'seller_verified', true);
                        ?>
                        
                        <div class="ed-designer-card">
                            <a href="<?php echo esc_url($designer_link); ?>" class="ed-designer-avatar">
                                <?php echo get_avatar($designer_id, 200); ?>
                                <?php if ($is_verified): ?>
                                    <span class="ed-verified-badge" title="<?php esc_attr_e('Verified Designer', 'embroidery-designers'); ?>">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="ed-designer-info">
                                <h2 class="ed-designer-name">
                                    <a href="<?php echo esc_url($designer_link); ?>">
                                        <?php echo esc_html($designer_name); ?>
                                    </a>
                                </h2>
                                
                                <div class="ed-designer-meta">
                                    <span class="ed-meta-item">
                                        <i class="fas fa-palette"></i>
                                        <?php 
                                        printf(
                                            _n('%d design', '%d designs', $designer_products_count), 
                                            $designer_products_count
                                        );
                                        ?>
                                    </span>
                                    
                                    <?php if ($designer_rating > 0): ?>
                                        <span class="ed-meta-item">
                                            <div class="ed-stars" style="--rating: <?php echo esc_attr($designer_rating); ?>;"></div>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="<?php echo esc_url($designer_link); ?>" class="ed-view-profile-button">
                                    <?php esc_html_e('View Profile', 'embroidery-designers'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ed-no-designers">
                        <i class="fas fa-user-slash"></i>
                        <p><?php esc_html_e('No designers found.', 'embroidery-designers'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .ed-designers-list-shortcode {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem 1rem;
            }
            .ed-designers-grid {
                display: grid;
                grid-template-columns: repeat(var(--columns), 1fr);
                gap: 2rem;
                margin-top: 2rem;
            }
            @media (max-width: 768px) {
                .ed-designers-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 480px) {
                .ed-designers-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }
}