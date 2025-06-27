<?php
/**
 * Designers List Template
 * Displays all embroidery designers with search functionality
 */

get_header();

// Get search query if exists
$search_query = isset($_GET['designer_search']) ? sanitize_text_field($_GET['designer_search']) : '';

// Get all designers (users who have published products)
$designers_args = array(
    'role__in'    => array('administrator', 'editor', 'author', 'contributor', 'seller'), // Include all potential roles
    'orderby'     => 'display_name',
    'order'       => 'ASC',
    'count_total' => false,
    'fields'      => 'all_with_meta',
    'who'         => 'authors',
);

// Only add product query if not searching (search already handles this)
if (empty($search_query)) {
    $designers_args['has_published_posts'] = array('product');
}

if (!empty($search_query)) {
    $designers_args['search'] = '*' . $search_query . '*';
    $designers_args['search_columns'] = array('user_login', 'user_nicename', 'display_name');
}

// Get all users without pagination
$designers = get_users($designers_args);

// For search results, we need to verify they have products
if (!empty($search_query)) {
    $designers = array_filter($designers, function($designer) {
        return count_user_posts($designer->ID, 'product') > 0;
    });
}
?>

<div class="ed-designers-container">
    <h1 class="ed-main-title"><?php esc_html_e('Our Designers', 'embroidery-designers'); ?></h1>
    
    <!-- Search Form -->
    <div class="ed-search-container">
        <form method="get" action="<?php echo esc_url(home_url('/designers/')); ?>" class="ed-search-form">
            <input type="text" 
                   name="designer_search" 
                   placeholder="<?php esc_attr_e('Search designers...', 'embroidery-designers'); ?>" 
                   value="<?php echo esc_attr($search_query); ?>"
                   class="ed-search-input">
            <button type="submit" class="ed-search-button">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <!-- Search Results Info -->
    <?php if (!empty($search_query)): ?>
        <div class="ed-search-results-info">
            <p>
                <?php 
                printf(
                    _n('Found %d designer matching "%s"', 'Found %d designers matching "%s"', count($designers), 'embroidery-designers'), 
                    count($designers),
                    esc_html($search_query)
                );
                ?>
                <a href="<?php echo esc_url(home_url('/designers/')); ?>" class="ed-clear-search">
                    <?php esc_html_e('Clear search', 'embroidery-designers'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Designers Grid -->
    <div class="ed-designers-grid">
        <?php if (!empty($designers)): ?>
            <?php foreach ($designers as $designer): ?>
                <?php
                $designer_id = $designer->ID;
                $designer_name = $designer->display_name;
                $designer_link = get_author_posts_url($designer_id);
                $designer_products_count = count_user_posts($designer_id, 'product');
                $designer_rating = get_user_meta($designer_id, 'seller_rating', true);
                $is_verified = get_user_meta($designer_id, 'seller_verified', true);
                
                // Skip if no products (shouldn't happen due to filtering but just in case)
                if ($designer_products_count < 1) continue;
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
                                    _n('%d design', '%d designs', $designer_products_count, 'embroidery-designers'), 
                                    $designer_products_count
                                );
                                ?>
                            </span>
                            
                            <?php if ($designer_rating): ?>
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
                <?php if (!empty($search_query)): ?>
                    <a href="<?php echo esc_url(home_url('/designers/')); ?>" class="ed-clear-search">
                        <?php esc_html_e('Clear search', 'embroidery-designers'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>