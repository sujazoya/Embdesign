<?php
/**
 * Class Embroidery_Menu_Builder - Handles the construction of custom menus
 */
class Embroidery_Menu_Builder {

    /**
     * Initialize menu builder functionality
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_menus']);
        add_filter('wp_nav_menu_items', [__CLASS__, 'add_custom_menu_items'], 10, 2);
        add_action('embroidery_desktop_menu', [__CLASS__, 'render_desktop_menu']);
    }

    /**
     * Register menu locations
     */
    public static function register_menus() {
        register_nav_menus([
            'embroidery-primary' => __('Primary Menu', 'embroidery-menu'),
            'embroidery-mobile' => __('Mobile Menu', 'embroidery-menu'),
            'embroidery-account' => __('Account Menu', 'embroidery-menu')
        ]);
    }

    /**
     * Add custom items to the menu
     */
    public static function add_custom_menu_items($items, $args) {
        if ($args->theme_location == 'embroidery-primary') {
            // Add WooCommerce account links if WooCommerce is active
            if (class_exists('WooCommerce')) {
                $items .= self::get_woocommerce_menu_items();
            }
            
            // Add custom contact items
            $items .= '<li class="menu-item menu-item-contact">';
            $items .= '<a href="tel:+91-7878537979">Contact Us <span class="phone-number">+91-7878537979</span></a>';
            $items .= '</li>';
            
            $items .= '<li class="menu-item menu-item-suggestion">';
            $items .= '<a href="#" class="suggestion-link">Drop Your Suggestion</a>';
            $items .= '</li>';
        }
        
        return $items;
    }

    /**
     * Render the desktop menu
     */
    public static function render_desktop_menu() {
        if (has_nav_menu('embroidery-primary')) {
            wp_nav_menu([
                'theme_location' => 'embroidery-primary',
                'container'      => false,
                'menu_class'     => 'embroidery-desktop-menu',
                'depth'         => 2,
                'walker'        => new Embroidery_Menu_Walker()
            ]);
        } else {
            echo self::default_menu();
        }
    }

    /**
     * Default menu items if no menu is set
     */
    public static function default_menu() {
        $menu_items = [
            [
                'title' => 'Bulk Downloads',
                'url' => '#',
                'class' => ''
            ],
            [
                'title' => 'Digitizing Service',
                'url' => '#',
                'class' => ''
            ],
            [
                'title' => 'Free Designs',
                'url' => '#',
                'class' => ''
            ],
            [
                'title' => 'Career',
                'url' => '#',
                'class' => 'has-new-badge',
                'badge' => 'NEW'
            ],
            [
                'title' => 'My Downloads',
                'url' => class_exists('WooCommerce') ? wc_get_page_permalink('myaccount') : '#',
                'class' => 'require-login',
                'notice' => 'MUST LOGIN'
            ]
        ];

        $menu = '<ul class="embroidery-desktop-menu">';
        
        foreach ($menu_items as $item) {
            $menu .= '<li class="menu-item ' . esc_attr($item['class']) . '">';
            $menu .= '<a href="' . esc_url($item['url']) . '">';
            $menu .= esc_html($item['title']);
            
            if (!empty($item['badge'])) {
                $menu .= '<span class="menu-badge">' . esc_html($item['badge']) . '</span>';
            }
            
            if (!empty($item['notice'])) {
                $menu .= '<span class="menu-notice">' . esc_html($item['notice']) . '</span>';
            }
            
            $menu .= '</a>';
            $menu .= '</li>';
        }
        
        // Add contact items to default menu
        $menu .= '<li class="menu-item menu-item-contact">';
        $menu .= '<a href="tel:+91-7878537979">Contact Us <span class="phone-number">+91-7878537979</span></a>';
        $menu .= '</li>';
        
        $menu .= '<li class="menu-item menu-item-suggestion">';
        $menu .= '<a href="#" class="suggestion-link">Drop Your Suggestion</a>';
        $menu .= '</li>';
        
        $menu .= '</ul>';

        return $menu;
    }

    /**
     * Get WooCommerce menu items
     */
    protected static function get_woocommerce_menu_items() {
        $items = '';
        
        // My Downloads item
        $items .= '<li class="menu-item menu-item-downloads require-login">';
        $items .= '<a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">';
        $items .= 'My Downloads <span class="menu-notice">MUST LOGIN</span>';
        $items .= '</a>';
        $items .= '</li>';
        
        // Cart item
        $items .= '<li class="menu-item menu-item-cart">';
        $items .= '<a href="' . esc_url(wc_get_cart_url()) . '" class="cart-contents">';
        $items .= '<span class="cart-text">Cart</span>';
        $items .= '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
        $items .= '</a>';
        $items .= '</li>';
        
        // Account item
        $items .= '<li class="menu-item menu-item-account">';
        $items .= '<a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">';
        $items .= is_user_logged_in() ? 'My Account' : 'Login/Register';
        $items .= '</a>';
        $items .= '</li>';
        
        return $items;
    }
}

/**
 * Custom menu walker for additional styling
 */
class Embroidery_Menu_Walker extends Walker_Nav_Menu {
    
    /**
     * Start the element output
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        // Add custom classes based on menu item title
        if (strpos(strtolower($item->title), 'career') !== false) {
            $classes[] = 'has-new-badge';
        }
        
        if (strpos(strtolower($item->title), 'downloads') !== false) {
            $classes[] = 'require-login';
        }
        
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        $output .= '<li' . $id . $class_names . '>';
        
        $atts = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target)     ? $item->target     : '';
        $atts['rel']    = !empty($item->xfn)        ? $item->xfn        : '';
        $atts['href']   = !empty($item->url)        ? $item->url        : '';
        $atts['class']  = 'menu-link';
        
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
        
        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
        
        // Add badge for Career item
        if (strpos(strtolower($item->title), 'career') !== false) {
            $item_output .= '<span class="menu-badge">NEW</span>';
        }
        
        // Add notice for Downloads item
        if (strpos(strtolower($item->title), 'downloads') !== false) {
            $item_output .= '<span class="menu-notice">MUST LOGIN</span>';
        }
        
        $item_output .= '</a>';
        $item_output .= $args->after;
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}