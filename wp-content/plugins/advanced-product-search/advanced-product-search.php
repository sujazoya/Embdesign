<?php
/*
Plugin Name: Advanced Product Search
Description: Adds an advanced search bar with filters for products. Shows as button by default that expands to full form with global voice search capabilities.
Version: 1.7
Author: Sujauddin Sekh
*/

if (!defined('ABSPATH')) {
    exit;
}

class Advanced_Product_Search {

    public function __construct() {
        add_shortcode('advanced_product_search', [$this, 'render_search_form']);
        add_action('pre_get_posts', [$this, 'modify_search_query'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'add_search_button_to_all_pages']);
        add_action('wp_ajax_aps_search_designers', [$this, 'search_designers_ajax']);
        add_action('wp_ajax_nopriv_aps_search_designers', [$this, 'search_designers_ajax']);
        
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);
    }

    public function activate_plugin() {
        delete_transient('wc_term_counts');
    }

    public function render_search_form($atts = []) {
        $atts = shortcode_atts(['show_button_only' => false], $atts);
        
        ob_start(); ?>
        <div class="aps-search-container">
            <?php if ($atts['show_button_only']) : ?>
                <button class="aps-search-toggle">
                    <svg class="aps-search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <svg class="aps-close-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            <?php endif; ?>
            
            <div class="aps-search-form" <?php echo $atts['show_button_only'] ? 'style="display:none;"' : ''; ?>>
                <form method="get" action="<?php echo esc_url(home_url('/')); ?>" id="aps-search-form">
                    <!-- Global Search with Voice -->
                    <div class="aps-field aps-global-search">
                        <label for="aps-global-search"><?php esc_html_e('Global Search', 'advanced-product-search'); ?></label>
                        <div class="aps-voice-search-container">
                            <input type="text" id="aps-global-search" placeholder="<?php esc_attr_e('Search anything...', 'advanced-product-search'); ?>">
                            <button type="button" class="aps-voice-search-button aps-global-voice" aria-label="<?php esc_attr_e('Global Voice Search', 'advanced-product-search'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                    <line x1="12" y1="19" x2="12" y2="23"></line>
                                    <line x1="8" y1="23" x2="16" y2="23"></line>
                                </svg>
                            </button>
                            <div class="aps-voice-search-status"></div>
                        </div>
                        <div class="aps-global-search-results"></div>
                    </div>
                    
                    <!-- Category Dropdown with Voice Search -->
                    <div class="aps-field">
                        <label for="aps-product-cat"><?php esc_html_e('Category', 'advanced-product-search'); ?></label>
                        <div class="aps-voice-search-container">
                            <select name="product_cat" id="aps-product-cat" class="aps-select">
                                <option value=""><?php esc_html_e('All Categories', 'advanced-product-search'); ?></option>
                                <?php
                                $categories = get_terms([
                                    'taxonomy' => 'product_cat',
                                    'hide_empty' => false,
                                    'orderby' => 'name',
                                    'order' => 'ASC',
                                    'number' => 0
                                ]);
                                
                                foreach ($categories as $category) {
                                    $selected = isset($_GET['product_cat']) && $_GET['product_cat'] == $category->slug ? 'selected' : '';
                                    echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                            <div class="aps-voice-search-status"></div>
                        </div>
                    </div>
                    
                    <!-- Tag Dropdown with Voice Search -->
                    <div class="aps-field">
                        <label for="aps-tags"><?php esc_html_e('Tags', 'advanced-product-search'); ?></label>
                        <div class="aps-voice-search-container">
                            <select name="product_tag" id="aps-tags" class="aps-select">
                                <option value=""><?php esc_html_e('All Tags', 'advanced-product-search'); ?></option>
                                <?php
                                if (taxonomy_exists('product_tag')) {
                                    $product_tags = get_terms([
                                        'taxonomy' => 'product_tag',
                                        'hide_empty' => false,
                                        'orderby' => 'name',
                                        'order' => 'ASC',
                                        'number' => 0
                                    ]);
                                    
                                    foreach ($product_tags as $tag) {
                                        $selected = isset($_GET['product_tag']) && $_GET['product_tag'] == $tag->slug ? 'selected' : '';
                                        echo '<option value="' . esc_attr($tag->slug) . '" ' . $selected . '>' . esc_html($tag->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <div class="aps-voice-search-status"></div>
                        </div>
                    </div>
                    
                    <!-- Designer Search with Voice -->
                    <div class="aps-field">
                        <label for="aps-designer"><?php esc_html_e('Designer', 'advanced-product-search'); ?></label>
                        <div class="aps-voice-search-container aps-designer-container">
                            <select name="designer" id="aps-designer" class="aps-select" data-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>">
                                <?php if (isset($_GET['designer'])) : ?>
                                    <?php $designer = get_user_by('id', intval($_GET['designer'])); ?>
                                    <?php if ($designer) : ?>
                                        <option value="<?php echo esc_attr($designer->ID); ?>" selected><?php echo esc_html($designer->display_name); ?></option>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <option value=""><?php esc_html_e('Search for a designer...', 'advanced-product-search'); ?></option>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="aps-voice-search-button aps-designer-voice" aria-label="<?php esc_attr_e('Voice Search for Designer', 'advanced-product-search'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                    <line x1="12" y1="19" x2="12" y2="23"></line>
                                    <line x1="8" y1="23" x2="16" y2="23"></line>
                                </svg>
                            </button>
                            <div class="aps-voice-search-status"></div>
                        </div>
                    </div>

                    <!-- Product Title with Voice -->
                    <div class="aps-field">
                        <label for="aps-product-title"><?php esc_html_e('Design Name', 'advanced-product-search'); ?></label>
                        <div class="aps-voice-search-container">
                            <input type="text" name="s" id="aps-product-title" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search Designs...', 'advanced-product-search'); ?>">
                            <button type="button" class="aps-voice-search-button aps-title-voice" aria-label="<?php esc_attr_e('Voice Search for Design Name', 'advanced-product-search'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                                    <line x1="12" y1="19" x2="12" y2="23"></line>
                                    <line x1="8" y1="23" x2="16" y2="23"></line>
                                </svg>
                            </button>
                            <div class="aps-voice-search-status"></div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="post_type" value="product">
                    <div class="aps-submit">
                        <button type="submit" name="search_products" class="aps-button"><?php esc_html_e('Search', 'advanced-product-search'); ?></button>
                        <button type="submit" name="view_designer" class="aps-button aps-button-secondary"><?php esc_html_e('Designer', 'advanced-product-search'); ?></button>
                         <button type="submit" name="aps-reset" class="aps-reset"><?php esc_html_e('Reset', 'advanced-product-search'); ?></button>
                        
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function search_designers_ajax() {
        check_ajax_referer('aps_designer_search', 'security');

        $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $results = [];

        $user_query = new WP_User_Query([
            'search'         => '*' . $search . '*',
            'search_columns' => ['user_login', 'user_nicename', 'user_email', 'display_name'],
            'role__in'       => ['author', 'contributor', 'seller'],
            'number'         => 20,
            'paged'          => $page,
            'fields'         => ['ID', 'display_name']
        ]);

        foreach ($user_query->get_results() as $user) {
            $results[] = [
                'id'   => $user->ID,
                'text' => $user->display_name
            ];
        }

        wp_send_json([
            'results' => $results,
            'pagination' => [
                'more' => $page < $user_query->max_num_pages
            ]
        ]);
    }

    public function add_search_button_to_all_pages() {
        echo do_shortcode('[advanced_product_search show_button_only="true"]');
    }

    public function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
                if (isset($_GET['view_designer']) && !empty($_GET['designer'])) {
                    $designer_id = intval($_GET['designer']);
                    $designer = get_user_by('id', $designer_id);
                    if ($designer) {
                        wp_redirect(get_author_posts_url($designer_id));
                        exit;
                    }
                }

                $meta_query = [];
                $tax_query = ['relation' => 'AND'];

                if (!empty($_GET['design_code'])) {
                    $meta_query[] = [
                        'key' => 'design_code',
                        'value' => sanitize_text_field($_GET['design_code']),
                        'compare' => 'LIKE',
                    ];
                }

                if (!empty($_GET['designer'])) {
                    $query->set('author', intval($_GET['designer']));
                }

                if (!empty($_GET['product_tag'])) {
                    $tax_query[] = [
                        'taxonomy' => 'product_tag',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['product_tag']),
                        'operator' => 'IN'
                    ];
                }

                if (!empty($_GET['product_cat'])) {
                    $tax_query[] = [
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['product_cat']),
                        'operator' => 'IN'
                    ];
                }

                if (!empty($meta_query)) {
                    $query->set('meta_query', $meta_query);
                }

                if (count($tax_query) > 1) {
                    $query->set('tax_query', $tax_query);
                }

                $query->set('post_type', 'product');
                $query->set('orderby', 'relevance');
            }
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'advanced-product-search',
            plugin_dir_url(__FILE__) . 'assets/css/advanced-product-search.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/advanced-product-search.css')
        );

        wp_enqueue_script(
            'advanced-product-search',
            plugin_dir_url(__FILE__) . 'assets/js/advanced-product-search.js',
            ['jquery', 'wp-a11y'],
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/advanced-product-search.js'),
            true
        );

        wp_localize_script('advanced-product-search', 'aps_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'designer_search_nonce' => wp_create_nonce('aps_designer_search'),
            'no_results_text' => __('No products found', 'advanced-product-search'),
            'voice_search_start' => __('Speak now...', 'advanced-product-search'),
            'voice_search_no_microphone' => __('Microphone access is blocked or not available.', 'advanced-product-search'),
            'voice_search_no_speech' => __('No speech was detected. You may need to adjust your microphone settings.', 'advanced-product-search'),
            'voice_search_error' => __('Error occurred in speech recognition: ', 'advanced-product-search'),
            'searching_text' => __('Searching...', 'advanced-product-search'),
            'global_search_placeholder' => __('Search for categories, tags, designers, or designs...', 'advanced-product-search')
        ]);

        if (apply_filters('aps_enable_select2', true)) {
            wp_enqueue_script(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                ['jquery'],
                '4.1.0',
                true
            );
            wp_enqueue_style(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                [],
                '4.1.0'
            );
        }
    }
}

new Advanced_Product_Search();