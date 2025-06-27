<?php
if (!defined('ABSPATH')) {
    exit;
}

class ACD_Shortcodes {
    public function __construct() {
        add_shortcode('advanced_categories', array($this, 'categories_shortcode'));
    }

    private function get_all_descendants($parent_id, &$exclude_ids) {
        $children = get_terms([
            'taxonomy' => 'product_cat',
            'parent'   => $parent_id,
            'hide_empty' => false
        ]);

        foreach ($children as $child) {
            $exclude_ids[] = $child->term_id;
            $this->get_all_descendants($child->term_id, $exclude_ids);
        }
    }

    public function categories_shortcode($atts) {
        $atts = shortcode_atts([
            'mode' => 'dual',
            'list_title' => 'Design Categories',
            'grid_title' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'show_all' => true,
            'grid_min' => 1,
            'grid_max' => 5,
            'limit' => 0
        ], $atts);

        // 🔥 Exclude 'Bundles' category and all subcategories
        $exclude_ids = [];
        $bundles = get_term_by('slug', 'bundles', 'product_cat');
        if ($bundles && !is_wp_error($bundles)) {
            $exclude_ids[] = $bundles->term_id;
            $this->get_all_descendants($bundles->term_id, $exclude_ids);
        }

        // Get all categories excluding 'Bundles' & children
        $args = array(
            'taxonomy' => 'product_cat',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'hide_empty' => $atts['hide_empty'],
            'number' => 9999,
            'exclude' => $exclude_ids
        );

        $categories = get_terms($args);

        if (empty($categories) || is_wp_error($categories)) {
            return '<p>No categories found.</p>';
        }

        ob_start();

        // === mode: dual ===
        if ($atts['mode'] === 'dual') {
            ?>
            <div class="acd-container">
                <div class="acd-list-view">
                    <?php if ($atts['list_title']) : ?>
                        <h6 class="acd-section-title"><?php echo esc_html($atts['list_title']); ?></h6>
                    <?php endif; ?>
                    <ul class="acd-list">
                        <li class="acd-list-item all-categories">
                            <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
                                All Categories
                            </a>
                        </li>
                        <?php foreach ($categories as $category) : ?>
                            <li class="acd-list-item">
                                <a href="<?php echo esc_url(get_term_link($category)); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="acd-grid-view">
                    <?php if ($atts['grid_title']) : ?>
                        <h3 class="acd-section-title"><?php echo esc_html($atts['grid_title']); ?></h3>
                    <?php endif; ?>
                    <div class="acd-grid">
                        <?php foreach ($categories as $category) :
                            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                            $image = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : wc_placeholder_img_src();
                        ?>
                            <div class="acd-grid-item">
                                <a href="<?php echo esc_url(get_term_link($category)); ?>" class="acd-grid-link">
                                    <div class="acd-image-container">
                                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($category->name); ?>" class="acd-category-image">
                                    </div>
                                    <span class="acd-category-name"><?php echo esc_html($category->name); ?></span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php

        // === mode: list ===
        } elseif ($atts['mode'] === 'list') {
            ?>
            <div class="acd-list-view">
                <?php if ($atts['list_title']) : ?>
                    <h6 class="acd-section-title"><?php echo esc_html($atts['list_title']); ?></h6>
                <?php endif; ?>
                <ul class="acd-list">
                    <li class="acd-list-item all-categories"><a href="#">All Categories</a></li>
                    <?php foreach ($categories as $category) : ?>
                        <li class="acd-list-item">
                            <a href="<?php echo esc_url(get_term_link($category)); ?>">
                                <?php echo esc_html($category->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php

        // === mode: grid ===
        } elseif ($atts['mode'] === 'grid') {
            ?>
            <div class="acd-grid-view">
                <?php if ($atts['grid_title']) : ?>
                    <h3 class="acd-section-title"><?php echo esc_html($atts['grid_title']); ?></h3>
                <?php endif; ?>
                <div class="acd-grid">
                    <?php foreach ($categories as $category) :
                        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                        $image = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : wc_placeholder_img_src();
                    ?>
                        <div class="acd-grid-item">
                            <a href="<?php echo esc_url(get_term_link($category)); ?>" class="acd-grid-link">
                                <div class="acd-image-container">
                                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($category->name); ?>" class="acd-category-image">
                                </div>
                                <span class="acd-category-name"><?php echo esc_html($category->name); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }
}
