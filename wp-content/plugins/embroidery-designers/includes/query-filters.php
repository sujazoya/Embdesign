<?php
/**
 * Query filters for the Embroidery Designers plugin
 */

class ED_Query_Filters {

    /**
     * Initialize query filters
     */
    public static function init() {
        // Handle custom query vars
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        
        // Handle AJAX search
        add_action('wp_ajax_ed_search_designers', array(__CLASS__, 'ajax_search_designers'));
        add_action('wp_ajax_nopriv_ed_search_designers', array(__CLASS__, 'ajax_search_designers'));
        
        // Handle AJAX reset
        add_action('wp_ajax_ed_reset_designers', array(__CLASS__, 'ajax_reset_designers'));
        add_action('wp_ajax_nopriv_ed_reset_designers', array(__CLASS__, 'ajax_reset_designers'));
    }

    /**
     * Add custom query vars
     */
    public static function add_query_vars($vars) {
        $vars[] = 'designers_page';
        $vars[] = 'designers_search';
        return $vars;
    }

    /**
     * Handle AJAX designer search
     */
    public static function ajax_search_designers() {
        check_ajax_referer('ed-search-nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $designers_args = array(
            'role__in' => array('author', 'editor', 'administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'has_published_posts' => array('product'),
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_nicename', 'display_name')
        );
        
        $designers = get_users($designers_args);
        
        ob_start();
        if (!empty($designers)) {
            foreach ($designers as $designer) {
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
                <?php
            }
        } else {
            ?>
            <div class="ed-no-designers">
                <i class="fas fa-user-slash"></i>
                <p><?php esc_html_e('No designers found.', 'embroidery-designers'); ?></p>
            </div>
            <?php
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Handle AJAX reset designers list
     */
    public static function ajax_reset_designers() {
        check_ajax_referer('ed-search-nonce', 'nonce');
        
        $designers_args = array(
            'role__in' => array('author', 'editor', 'administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'has_published_posts' => array('product')
        );
        
        $designers = get_users($designers_args);
        
        ob_start();
        if (!empty($designers)) {
            foreach ($designers as $designer) {
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
                <?php
            }
        } else {
            ?>
            <div class="ed-no-designers">
                <i class="fas fa-user-slash"></i>
                <p><?php esc_html_e('No designers found.', 'embroidery-designers'); ?></p>
            </div>
            <?php
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
}