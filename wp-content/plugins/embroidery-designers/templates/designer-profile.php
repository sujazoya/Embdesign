<?php
/**
 * Designer Profile Template
 * Displays detailed information about an embroidery designer
 */

get_header();

$author = get_queried_object();
$author_id = $author->ID;
$author_name = $author->display_name;

// Get designer products
$designer_products = get_posts([
    'post_type' => 'product',
    'author' => $author_id,
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get designer info
$joined_date = date_i18n('F Y', strtotime($author->user_registered));
$designer_bio = get_the_author_meta('description', $author_id);
$designer_email = $author->user_email;

$product_count = count($designer_products);
$designer_joined = date_i18n('F Y', strtotime($author->user_registered));

// Get designer contact info
$designer_email = $author->user_email;
$designer_phone = get_user_meta($author_id, 'billing_phone', true);
$designer_website = $author->user_url;

// Get designer social media
$designer_social = [
    'facebook' => get_user_meta($author_id, 'facebook', true),
    'instagram' => get_user_meta($author_id, 'instagram', true),
    'pinterest' => get_user_meta($author_id, 'pinterest', true),
    'youtube' => get_user_meta($author_id, 'youtube', true)
];

// Get designer's product categories
$designer_categories = [];
foreach ($designer_products as $product) {
    $categories = wp_get_post_terms($product->ID, 'product_cat');
    foreach ($categories as $category) {
        $designer_categories[$category->term_id] = $category;
    }
}

// Calculate designer ratings
$designer_rating = get_user_meta($author_id, 'seller_rating', true);
$review_count = get_user_meta($author_id, 'review_count', true);

// Get designer bio/description
$designer_bio = get_user_meta($author_id, 'description', true);

// Check if designer is verified
$is_verified = get_user_meta($author_id, 'seller_verified', true);

// Get featured product (most recent or manually selected)
$featured_product = !empty($designer_products) ? $designer_products[0] : null;
?>

<div class="ed-designer-profile-container">
    <!-- Back to Designers Link -->
    <div class="ed-back-to-designers">
        <a href="<?php echo esc_url(home_url('/designers/')); ?>">
            <i class="fas fa-arrow-left"></i> <?php esc_html_e('Back to All Designers', 'embroidery-designers'); ?>
        </a>
    </div>
    
    <!-- Designer Header Section -->
    <div class="ed-designer-header">
        <div class="ed-designer-avatar-container">
            <?php echo get_avatar($author_id, 200); ?>
            <?php if ($is_verified): ?>
                <div class="ed-verified-badge" title="<?php esc_attr_e('Verified Designer', 'embroidery-designers'); ?>">
                    <i class="fas fa-check-circle"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="ed-designer-info">
            <h1>
                <?php echo esc_html($author_name); ?>
                <?php if ($is_verified): ?>
                    <span class="ed-verified-icon" title="<?php esc_attr_e('Verified Designer', 'embroidery-designers'); ?>">
                        <i class="fas fa-check-circle"></i>
                    </span>
                <?php endif; ?>
            </h1>
            
            <p class="ed-member-since">
                <i class="far fa-calendar-alt"></i> 
                <?php printf(esc_html__('Member since %s', 'embroidery-designers'), esc_html($designer_joined)); ?>
            </p>
            
            <?php if ($designer_rating): ?>
                <div class="ed-designer-rating">
                    <div class="ed-stars" style="--rating: <?php echo esc_attr($designer_rating); ?>;"></div>
                    <span class="ed-review-count">
                        (<?php printf(esc_html__('%d reviews', 'embroidery-designers'), esc_html($review_count)); ?>)
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="ed-designer-stats">
                <div class="ed-stat">
                    <span class="ed-stat-number"><?php echo esc_html($product_count); ?></span>
                    <span class="ed-stat-label"><?php esc_html_e('Designs', 'embroidery-designers'); ?></span>
                </div>
                
                <?php if ($designer_rating): ?>
                    <div class="ed-stat">
                        <span class="ed-stat-number"><?php echo esc_html(number_format($designer_rating, 1)); ?></span>
                        <span class="ed-stat-label"><?php esc_html_e('Rating', 'embroidery-designers'); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="ed-stat">
                    <span class="ed-stat-number">
                        <?php echo esc_html(count($designer_categories)); ?>
                    </span>
                    <span class="ed-stat-label"><?php esc_html_e('Categories', 'embroidery-designers'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Designer Bio Section -->
    <?php if ($designer_bio): ?>
        <div class="ed-designer-bio-section">
            <h2><?php esc_html_e('About the Designer', 'embroidery-designers'); ?></h2>
            <div class="ed-bio-content">
                <?php echo wpautop(esc_html($designer_bio)); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Designer Specialties Section -->
    <?php if (!empty($designer_categories)): ?>
        <div class="ed-designer-specialties">
            <h2><?php esc_html_e('Design Specialties', 'embroidery-designers'); ?></h2>
            <div class="ed-specialties-list">
                <?php foreach ($designer_categories as $category): ?>
                    <a href="<?php echo esc_url(get_term_link($category)); ?>" class="ed-specialty-item">
                        <?php 
                        $category_image = get_term_meta($category->term_id, 'thumbnail_id', true);
                        if ($category_image) {
                            echo wp_get_attachment_image($category_image, 'thumbnail');
                        } else {
                            echo '<div class="ed-category-icon"><i class="fas fa-palette"></i></div>';
                        }
                        ?>
                        <span><?php echo esc_html($category->name); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Featured Product Section -->
    <?php if ($featured_product): ?>
        <div class="ed-featured-product-section">
            <h2><?php esc_html_e('Featured Design', 'embroidery-designers'); ?></h2>
            <div class="ed-featured-product">
                <div class="ed-featured-image">
                    <a href="<?php echo esc_url(get_permalink($featured_product->ID)); ?>">
                        <?php echo get_the_post_thumbnail($featured_product->ID, 'large'); ?>
                    </a>
                </div>
                <div class="ed-featured-details">
                    <h3>
                        <a href="<?php echo esc_url(get_permalink($featured_product->ID)); ?>">
                            <?php echo esc_html(get_the_title($featured_product->ID)); ?>
                        </a>
                    </h3>
                    <div class="ed-product-price">
                        <?php echo wc_price(get_post_meta($featured_product->ID, '_price', true)); ?>
                    </div>
                    <div class="ed-product-meta">
                        <span class="ed-meta-item">
                            <i class="fas fa-ruler-combined"></i>
                            <?php 
                            $width = get_post_meta($featured_product->ID, 'width', true);
                            $height = get_post_meta($featured_product->ID, 'height', true);
                            echo esc_html("{$width}mm × {$height}mm");
                            ?>
                        </span>
                        <span class="ed-meta-item">
                            <i class="fas fa-shoe-prints"></i>
                            <?php echo esc_html(get_post_meta($featured_product->ID, 'stitches', true)); ?> 
                            <?php esc_html_e('stitches', 'embroidery-designers'); ?>
                        </span>
                    </div>
                    <a href="<?php echo esc_url(get_permalink($featured_product->ID)); ?>" class="ed-view-product-btn">
                        <?php esc_html_e('View Design Details', 'embroidery-designers'); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Contact Section -->
    <div class="ed-designer-contact-section">
        <h2><?php esc_html_e('Contact Designer', 'embroidery-designers'); ?></h2>
        <div class="ed-contact-container">
            <?php if (is_user_logged_in()): ?>
                <div class="ed-contact-methods">
                    <?php if ($designer_email): ?>
                        <div class="ed-contact-method">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo esc_attr($designer_email); ?>"><?php echo esc_html($designer_email); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($designer_phone): ?>
                        <div class="ed-contact-method">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $designer_phone)); ?>">
                                <?php echo esc_html($designer_phone); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($designer_website): ?>
                        <div class="ed-contact-method">
                            <i class="fas fa-globe"></i>
                            <a href="<?php echo esc_url($designer_website); ?>" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Visit Website', 'embroidery-designers'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (array_filter($designer_social)): ?>
                    <div class="ed-social-links">
                        <h3><?php esc_html_e('Follow on Social Media', 'embroidery-designers'); ?></h3>
                        <div class="ed-social-icons">
                            <?php foreach ($designer_social as $platform => $url): ?>
                                <?php if ($url): ?>
                                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" class="ed-social-<?php echo esc_attr($platform); ?>">
                                        <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="ed-login-notice">
                    <p>
                        <?php esc_html_e('Please', 'embroidery-designers'); ?> 
                        <a href="<?php echo esc_url(wp_login_url(get_author_posts_url($author_id))); ?>">
                            <?php esc_html_e('login', 'embroidery-designers'); ?>
                        </a> 
                        <?php esc_html_e('to view contact information.', 'embroidery-designers'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- All Products Section -->
    <div class="ed-designer-products-section">
        <h2>
            <?php printf(esc_html__('All Designs by %s', 'embroidery-designers'), esc_html($author_name)); ?>
        </h2>
        
        <?php if ($designer_products): ?>
            <div class="ed-products-grid">
                <?php foreach ($designer_products as $product): ?>
                    <div class="ed-product-card">
                        <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="ed-product-image-link">
                            <?php echo get_the_post_thumbnail($product->ID, 'medium'); ?>
                        </a>
                        <div class="ed-product-info">
                            <h3>
                                <a href="<?php echo esc_url(get_permalink($product->ID)); ?>">
                                    <?php echo esc_html(get_the_title($product->ID)); ?>
                                </a>
                            </h3>
                            <div class="ed-product-meta">
                                <span class="ed-product-price">
                                    <?php echo wc_price(get_post_meta($product->ID, '_price', true)); ?>
                                </span>
                                <span class="ed-product-size">
                                    <?php 
                                    $width = get_post_meta($product->ID, 'width', true);
                                    $height = get_post_meta($product->ID, 'height', true);
                                    echo esc_html("{$width}mm × {$height}mm");
                                    ?>
                                </span>
                            </div>
                            <div class="ed-product-actions">
                                <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="ed-view-product-btn">
                                    <?php esc_html_e('View Design', 'embroidery-designers'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ed-no-products">
                <i class="fas fa-palette"></i>
                <p><?php esc_html_e('This designer hasn\'t uploaded any designs yet.', 'embroidery-designers'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>