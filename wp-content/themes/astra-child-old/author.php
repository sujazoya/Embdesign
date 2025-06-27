<?php
/**
 * Author/Seller Profile Template
 * Displays detailed information about each embroidery designer/seller
 */

get_header();

$author = get_queried_object();
$author_id = $author->ID;
$author_name = $author->display_name;

// Get seller products
$seller_products = get_posts([
    'post_type' => 'product',
    'author' => $author_id,
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);
// Get author info
$joined_date = date_i18n('F Y', strtotime($author->user_registered));
$author_bio = get_the_author_meta('description', $author_id);
$author_email = $author->user_email;

$product_count = count($seller_products);
$seller_joined = date_i18n('F Y', strtotime($author->user_registered));

// Get seller contact info
$seller_email = $author->user_email;
$seller_phone = get_user_meta($author_id, 'billing_phone', true);
$seller_website = $author->user_url;

// Get seller social media
$seller_social = [
    'facebook' => get_user_meta($author_id, 'facebook', true),
    'instagram' => get_user_meta($author_id, 'instagram', true),
    'pinterest' => get_user_meta($author_id, 'pinterest', true),
    'youtube' => get_user_meta($author_id, 'youtube', true)
];

// Get seller's product categories
$seller_categories = [];
foreach ($seller_products as $product) {
    $categories = wp_get_post_terms($product->ID, 'product_cat');
    foreach ($categories as $category) {
        $seller_categories[$category->term_id] = $category;
    }
}

// Calculate seller ratings
$seller_rating = get_user_meta($author_id, 'seller_rating', true);
$review_count = get_user_meta($author_id, 'review_count', true);

// Get seller bio/description
$seller_bio = get_user_meta($author_id, 'description', true);

// Check if seller is verified
$is_verified = get_user_meta($author_id, 'seller_verified', true);

// Get featured product (most recent or manually selected)
$featured_product = !empty($seller_products) ? $seller_products[0] : null;
?>

<div class="seller-profile-container">
    <!-- Seller Header Section -->
    <div class="seller-header">
        <div class="seller-avatar-container">
            <?php echo get_avatar($author_id, 200); ?>
            <?php if ($is_verified): ?>
                <div class="verified-badge" title="Verified Designer">
                    <i class="fas fa-check-circle"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="seller-info">
            <h1>
                <?php echo esc_html($author_name); ?>
                <?php if ($is_verified): ?>
                    <span class="verified-icon" title="Verified Designer">
                        <i class="fas fa-check-circle"></i>
                    </span>
                <?php endif; ?>
            </h1>
            
            <p class="member-since">
                <i class="far fa-calendar-alt"></i> Member since <?php echo esc_html($seller_joined); ?>
            </p>
            
            <?php if ($seller_rating): ?>
                <div class="seller-rating">
                    <div class="stars" style="--rating: <?php echo esc_attr($seller_rating); ?>;"></div>
                    <span class="review-count">(<?php echo esc_html($review_count); ?> reviews)</span>
                </div>
            <?php endif; ?>
            
            <div class="seller-stats">
                <div class="stat">
                    <span class="stat-number"><?php echo esc_html($product_count); ?></span>
                    <span class="stat-label">Designs</span>
                </div>
                
                <?php if ($seller_rating): ?>
                    <div class="stat">
                        <span class="stat-number"><?php echo esc_html(number_format($seller_rating, 1)); ?></span>
                        <span class="stat-label">Rating</span>
                    </div>
                <?php endif; ?>
                
                <div class="stat">
                    <span class="stat-number">
                        <?php echo esc_html(count($seller_categories)); ?>
                    </span>
                    <span class="stat-label">Categories</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Seller Bio Section -->
    <?php if ($seller_bio): ?>
        <div class="seller-bio-section">
            <h2>About the Designer</h2>
            <div class="bio-content">
                <?php echo wpautop(esc_html($seller_bio)); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Seller Specialties Section -->
    <?php if (!empty($seller_categories)): ?>
        <div class="seller-specialties">
            <h2>Design Specialties</h2>
            <div class="specialties-list">
                <?php foreach ($seller_categories as $category): ?>
                    <a href="<?php echo esc_url(get_term_link($category)); ?>" class="specialty-item">
                        <?php 
                        $category_image = get_term_meta($category->term_id, 'thumbnail_id', true);
                        if ($category_image) {
                            echo wp_get_attachment_image($category_image, 'thumbnail');
                        } else {
                            echo '<div class="category-icon"><i class="fas fa-palette"></i></div>';
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
        <div class="featured-product-section">
            <h2>Featured Design</h2>
            <div class="featured-product">
                <div class="featured-image">
                    <a href="<?php echo esc_url(get_permalink($featured_product->ID)); ?>">
                        <?php echo get_the_post_thumbnail($featured_product->ID, 'large'); ?>
                    </a>
                </div>
                <div class="featured-details">
                    <h3>
                        <a href="<?php echo esc_url(get_permalink($featured_product->ID)); ?>">
                            <?php echo esc_html(get_the_title($featured_product->ID)); ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <?php echo wc_price(get_post_meta($featured_product->ID, '_price', true)); ?>
                    </div>
                    <div class="product-meta">
                        <span class="meta-item">
                            <i class="fas fa-ruler-combined"></i>
                            <?php 
                            $width = get_post_meta($featured_product->ID, '_width', true);
                            $height = get_post_meta($featured_product->ID, '_height', true);
                            echo esc_html("{$width}mm × {$height}mm");
                            ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-shoe-prints"></i>
                            <?php echo esc_html(get_field('stitches', $featured_product->ID)); ?> stitches
                        </span>
                    </div>
                    <a href="<?php echo esc_url(get_permalink($featured_product->ID)); ?>" class="view-product-btn">
                        View Design Details
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Contact Section -->
    <div class="seller-contact-section">
        <h2>Contact Designer</h2>
        <div class="contact-container">
            <?php if (is_user_logged_in()): ?>
                <div class="contact-methods">
                    <?php if ($seller_email): ?>
                        <div class="contact-method">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo esc_attr($seller_email); ?>"><?php echo esc_html($seller_email); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($seller_phone): ?>
                        <div class="contact-method">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $seller_phone)); ?>">
                                <?php echo esc_html($seller_phone); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($seller_website): ?>
                        <div class="contact-method">
                            <i class="fas fa-globe"></i>
                            <a href="<?php echo esc_url($seller_website); ?>" target="_blank" rel="noopener noreferrer">
                                Visit Website
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (array_filter($seller_social)): ?>
                    <div class="social-links">
                        <h3>Follow on Social Media</h3>
                        <div class="social-icons">
                            <?php foreach ($seller_social as $platform => $url): ?>
                                <?php if ($url): ?>
                                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" class="social-<?php echo esc_attr($platform); ?>">
                                        <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="login-notice">
                    <p>Please <a href="<?php echo esc_url(wp_login_url(get_author_posts_url($author_id))); ?>">login</a> to view contact information.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- All Products Section -->
    <div class="seller-products-section">
        <h2>All Designs by <?php echo esc_html($author_name); ?></h2>
        
        <?php if ($seller_products): ?>
            <div class="products-grid">
                <?php foreach ($seller_products as $product): ?>
                    <div class="product-card">
                        <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="product-image-link">
                            <?php echo get_the_post_thumbnail($product->ID, 'medium'); ?>
                        </a>
                        <div class="product-info">
                            <h3>
                                <a href="<?php echo esc_url(get_permalink($product->ID)); ?>">
                                    <?php echo esc_html(get_the_title($product->ID)); ?>
                                </a>
                            </h3>
                            <div class="product-meta">
                                <span class="product-price">
                                    <?php echo wc_price(get_post_meta($product->ID, '_price', true)); ?>
                                </span>
                                <span class="product-size">
                                    <?php 
                                    $width = get_post_meta($product->ID, '_width', true);
                                    $height = get_post_meta($product->ID, '_height', true);
                                    echo esc_html("{$width}mm × {$height}mm");
                                    ?>
                                </span>
                            </div>
                            <div class="product-actions">
                                <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="view-product-btn">
                                    View Design
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-palette"></i>
                <p>This designer hasn't uploaded any designs yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Base Styles */
.seller-profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
}

h1, h2, h3 {
    color: #2c3e50;
    margin-top: 0;
}

a {
    color: #3498db;
    text-decoration: none;
    transition: color 0.3s;
}

a:hover {
    color: #2980b9;
}

/* Seller Header */
.seller-header {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-bottom: 3rem;
    align-items: center;
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.seller-avatar-container {
    position: relative;
    flex: 0 0 200px;
}

.seller-avatar-container img {
    border-radius: 50%;
    width: 200px;
    height: 200px;
    object-fit: cover;
    border: 5px solid white;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
}

.verified-badge {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: #27ae60;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.seller-info {
    flex: 1;
    min-width: 300px;
}

.seller-info h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.verified-icon {
    color: #27ae60;
    font-size: 1.2rem;
}

.member-since {
    color: #7f8c8d;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.seller-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.stars {
    --percent: calc(var(--rating) / 5 * 100%);
    display: inline-block;
    font-size: 1.2rem;
    line-height: 1;
    position: relative;
    color: #ddd;
}

.stars::before {
    content: '★★★★★';
    position: absolute;
    top: 0;
    left: 0;
    width: var(--percent);
    overflow: hidden;
    color: #f39c12;
}

.review-count {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.seller-stats {
    display: flex;
    gap: 2rem;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.stat {
    text-align: center;
    min-width: 80px;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: bold;
    display: block;
    color: #2c3e50;
}

.stat-label {
    font-size: 0.9rem;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Bio Section */
.seller-bio-section {
    margin: 3rem 0;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.seller-bio-section h2 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f1f1;
}

.bio-content {
    line-height: 1.6;
}

/* Specialties Section */
.seller-specialties {
    margin: 3rem 0;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.seller-specialties h2 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f1f1;
}

.specialties-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.specialty-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    min-width: 100px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.specialty-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.specialty-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
}

.category-icon {
    width: 60px;
    height: 60px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.specialty-item span {
    font-size: 0.9rem;
    text-align: center;
}

/* Featured Product Section */
.featured-product-section {
    margin: 3rem 0;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.featured-product-section h2 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f1f1;
}

.featured-product {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.featured-image {
    flex: 1;
    min-width: 300px;
}

.featured-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
}

.featured-details {
    flex: 1;
    min-width: 300px;
}

.featured-details h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.product-price {
    font-size: 1.5rem;
    color: #27ae60;
    font-weight: bold;
    margin-bottom: 1.5rem;
}

.product-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #7f8c8d;
}

.view-product-btn {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background: #3498db;
    color: white;
    border-radius: 5px;
    font-weight: 500;
    transition: background 0.3s;
}

.view-product-btn:hover {
    background: #2980b9;
    color: white;
}

/* Contact Section */
.seller-contact-section {
    margin: 3rem 0;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.seller-contact-section h2 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f1f1;
}

.contact-container {
    display: flex;
    gap: 3rem;
    flex-wrap: wrap;
}

.contact-methods {
    flex: 1;
    min-width: 300px;
}

.contact-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.8rem;
    background: #f8f9fa;
    border-radius: 5px;
}

.contact-method i {
    font-size: 1.2rem;
    color: #3498db;
    min-width: 20px;
}

.social-links {
    flex: 1;
    min-width: 300px;
}

.social-links h3 {
    margin-bottom: 1rem;
}

.social-icons {
    display: flex;
    gap: 1rem;
}

.social-icons a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f1f1f1;
    color: #555;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.social-icons a:hover {
    transform: translateY(-3px);
}

.social-facebook:hover {
    background: #3b5998;
    color: white;
}

.social-instagram:hover {
    background: #e1306c;
    color: white;
}

.social-pinterest:hover {
    background: #bd081c;
    color: white;
}

.social-youtube:hover {
    background: #ff0000;
    color: white;
}

.login-notice {
    padding: 1rem;
    background: #fff8e1;
    border-left: 4px solid #ffc107;
    font-size: 0.9rem;
}

/* Products Section */
.seller-products-section {
    margin: 3rem 0;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.seller-products-section h2 {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f1f1;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
}

.product-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
    background: white;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.product-image-link {
    display: block;
    overflow: hidden;
}

.product-image-link img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.5s;
}

.product-card:hover .product-image-link img {
    transform: scale(1.05);
}

.product-info {
    padding: 1.5rem;
}

.product-info h3 {
    font-size: 1.1rem;
    margin-bottom: 0.8rem;
    line-height: 1.4;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.2rem;
}

.product-price {
    color: #27ae60;
    font-weight: bold;
}

.product-size {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.product-actions {
    text-align: center;
}

.view-product-btn {
    display: inline-block;
    padding: 0.6rem 1.2rem;
    background: #3498db;
    color: white;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: background 0.3s;
}

.view-product-btn:hover {
    background: #2980b9;
    color: white;
}

.no-products {
    text-align: center;
    padding: 3rem;
    color: #7f8c8d;
}

.no-products i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #bdc3c7;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .seller-header {
        flex-direction: column;
        text-align: center;
    }
    
    .seller-avatar-container {
        margin-bottom: 1.5rem;
    }
    
    .seller-stats {
        justify-content: center;
    }
    
    .featured-product {
        flex-direction: column;
    }
    
    .contact-container {
        flex-direction: column;
        gap: 2rem;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .seller-info h1 {
        font-size: 1.5rem;
    }
    
    .stat {
        min-width: 70px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>

<?php get_footer(); ?>