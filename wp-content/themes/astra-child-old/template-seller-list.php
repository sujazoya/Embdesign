<?php
/**
 * Template Name: Seller List Page
 */

get_header();

// Get all users with the 'seller' role (or 'author' if you're using that)
$sellers = get_users([
    'role__in' => ['seller', 'author', 'contributor'], // Adjust roles as needed
    'orderby' => 'display_name',
    'order' => 'ASC'
]);

// Count total sellers
$total_sellers = count($sellers);

// Get all product categories for filtering
$product_categories = get_terms([
    'taxonomy' => 'product_cat',
    'hide_empty' => true
]);

?>

<div class="seller-list-page">
    <h1>Our Designers</h1>
    <p class="seller-count"><?php echo esc_html($total_sellers); ?> designers available</p>
    
    <div class="seller-list-filters">
        <div class="search-filter">
            <input type="text" id="seller-search" placeholder="Search designers...">
        </div>
        <div class="category-filter">
            <select id="seller-category-filter">
                <option value="">All Categories</option>
                <?php foreach ($product_categories as $category): ?>
                    <option value="<?php echo esc_attr($category->term_id); ?>">
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="seller-grid">
        <?php foreach ($sellers as $seller): ?>
            <?php
            // Get seller info
            $seller_id = $seller->ID;
            $display_name = $seller->display_name;
            $username = $seller->user_nicename;
            $profile_url = get_author_posts_url($seller_id);
            
            // Get seller products count
            $product_count = count_user_posts($seller_id, 'product');
            
            // Get seller avatar
            $avatar = get_avatar($seller_id, 150);
            
            // Get seller's featured products (3 most recent)
            $featured_products = get_posts([
                'post_type' => 'product',
                'author' => $seller_id,
                'posts_per_page' => 3,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            
            // Get seller's product categories
            $seller_categories = [];
            if ($product_count > 0) {
                $seller_products = get_posts([
                    'post_type' => 'product',
                    'author' => $seller_id,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ]);
                
                foreach ($seller_products as $product_id) {
                    $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                    $seller_categories = array_merge($seller_categories, $categories);
                }
                $seller_categories = array_unique($seller_categories);
            }
            
            // Convert category IDs to string for data attribute
            $category_data = implode(',', $seller_categories);
            ?>
            
            <div class="seller-card" data-categories="<?php echo esc_attr($category_data); ?>">
                <div class="seller-avatar">
                    <a href="<?php echo esc_url($profile_url); ?>">
                        <?php echo $avatar; ?>
                    </a>
                </div>
                <div class="seller-info">
                    <h3><a href="<?php echo esc_url($profile_url); ?>"><?php echo esc_html($display_name); ?></a></h3>
                    <p class="product-count"><?php echo esc_html($product_count); ?> designs</p>
                    
                    <?php if (!empty($seller_categories)): ?>
                        <div class="seller-categories">
                            <?php 
                            $category_names = [];
                            foreach ($seller_categories as $cat_id) {
                                $cat = get_term($cat_id, 'product_cat');
                                if ($cat) {
                                    $category_names[] = $cat->name;
                                }
                            }
                            echo esc_html(implode(', ', $category_names));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($featured_products)): ?>
                    <div class="seller-featured-products">
                        <h4>Featured Designs</h4>
                        <div class="product-thumbnails">
                            <?php foreach ($featured_products as $product): ?>
                                <a href="<?php echo esc_url(get_permalink($product->ID)); ?>">
                                    <?php echo get_the_post_thumbnail($product->ID, 'thumbnail'); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url($profile_url); ?>" class="view-profile-btn">View Profile</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search functionality
    $('#seller-search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.seller-card').each(function() {
            var sellerName = $(this).find('h3 a').text().toLowerCase();
            if (sellerName.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Category filter functionality
    $('#seller-category-filter').on('change', function() {
        var selectedCategory = $(this).val();
        if (selectedCategory === '') {
            $('.seller-card').show();
        } else {
            $('.seller-card').each(function() {
                var categories = $(this).data('categories').split(',');
                if (categories.includes(selectedCategory)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    });
});
</script>

<style>
.seller-list-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.seller-count {
    color: #666;
    margin-bottom: 2rem;
}

.seller-list-filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.seller-list-filters .search-filter input {
    padding: 0.5rem;
    width: 300px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.seller-list-filters .category-filter select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.seller-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.seller-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1.5rem;
    transition: transform 0.3s ease;
}

.seller-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.seller-avatar {
    text-align: center;
    margin-bottom: 1rem;
}

.seller-avatar img {
    border-radius: 50%;
    width: 150px;
    height: 150px;
    object-fit: cover;
}

.seller-info {
    text-align: center;
    margin-bottom: 1.5rem;
}

.seller-info h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
}

.seller-info h3 a {
    color: #333;
    text-decoration: none;
}

.seller-info h3 a:hover {
    color: #0073aa;
}

.product-count {
    color: #666;
    font-size: 0.9rem;
    margin: 0 0 0.5rem;
}

.seller-categories {
    color: #888;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

.seller-featured-products {
    margin: 1.5rem 0;
}

.seller-featured-products h4 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    color: #555;
}

.product-thumbnails {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.product-thumbnails a {
    display: block;
    width: 60px;
    height: 60px;
    overflow: hidden;
    border-radius: 4px;
}

.product-thumbnails img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.view-profile-btn {
    display: block;
    text-align: center;
    background: #0073aa;
    color: white;
    padding: 0.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.3s ease;
}

.view-profile-btn:hover {
    background: #005f8a;
    color: white;
}

@media (max-width: 768px) {
    .seller-list-filters {
        flex-direction: column;
    }
    
    .seller-list-filters .search-filter input {
        width: 100%;
    }
    
    .seller-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>