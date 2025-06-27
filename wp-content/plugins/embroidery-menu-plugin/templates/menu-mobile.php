<?php
/**
 * Mobile Menu Template
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="embroidery-mobile-menu-wrapper">
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" aria-label="<?php esc_attr_e('Toggle Mobile Menu', 'embroidery-menu'); ?>">
        <span class="toggle-bar"></span>
        <span class="toggle-bar"></span>
        <span class="toggle-bar"></span>
    </button>

    <!-- Mobile Menu Panel -->
    <div class="embroidery-mobile-menu-panel">
        <div class="mobile-menu-header">
            <button class="mobile-menu-close" aria-label="<?php esc_attr_e('Close Mobile Menu', 'embroidery-menu'); ?>">
                &times;
            </button>
        </div>

        <nav class="embroidery-mobile-nav">
            <?php
            // Display mobile menu
            if (has_nav_menu('embroidery-mobile')) {
                wp_nav_menu([
                    'theme_location' => 'embroidery-mobile',
                    'container'      => false,
                    'menu_class'     => 'mobile-menu',
                    'depth'         => 1,
                    'walker'        => new Embroidery_Mobile_Menu_Walker()
                ]);
            } else {
                // Fallback menu
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
                    ],
                    [
                        'title' => 'Contact Us',
                        'url' => 'tel:+91-7878537979',
                        'class' => 'menu-item-contact',
                        'phone' => '+91-7878537979'
                    ],
                    [
                        'title' => 'Drop Your Suggestion',
                        'url' => '#',
                        'class' => 'menu-item-suggestion'
                    ]
                ];

                echo '<ul class="mobile-menu">';
                foreach ($menu_items as $item) {
                    echo '<li class="mobile-menu-item ' . esc_attr($item['class']) . '">';
                    echo '<a href="' . esc_url($item['url']) . '">';
                    echo esc_html($item['title']);
                    
                    if (!empty($item['badge'])) {
                        echo '<span class="mobile-menu-badge">' . esc_html($item['badge']) . '</span>';
                    }
                    
                    if (!empty($item['notice'])) {
                        echo '<span class="mobile-menu-notice">' . esc_html($item['notice']) . '</span>';
                    }
                    
                    if (!empty($item['phone'])) {
                        echo '<span class="mobile-menu-phone">' . esc_html($item['phone']) . '</span>';
                    }
                    
                    echo '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }
            ?>
        </nav>

        <?php if (class_exists('WooCommerce')) : ?>
        <div class="mobile-account-section">
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="mobile-cart-link">
                <?php esc_html_e('Cart', 'embroidery-menu'); ?>
                <span class="mobile-cart-count"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
            </a>
            
            <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="mobile-account-link">
                <?php echo is_user_logged_in() ? esc_html__('My Account', 'embroidery-menu') : esc_html__('Login/Register', 'embroidery-menu'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay"></div>
</div>

<?php
/**
 * Mobile Menu Walker
 */
class Embroidery_Mobile_Menu_Walker extends Walker_Nav_Menu {
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'mobile-menu-item-' . $item->ID;
        
        // Add custom classes based on menu item title
        if (strpos(strtolower($item->title), 'career') !== false) {
            $classes[] = 'has-new-badge';
        }
        
        if (strpos(strtolower($item->title), 'downloads') !== false) {
            $classes[] = 'require-login';
        }
        
        if (strpos(strtolower($item->title), 'contact') !== false) {
            $classes[] = 'menu-item-contact';
        }
        
        if (strpos(strtolower($item->title), 'suggestion') !== false) {
            $classes[] = 'menu-item-suggestion';
        }
        
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $output .= '<li' . $class_names . '>';
        
        $atts = array();
        $atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target)     ? $item->target     : '';
        $atts['rel']    = !empty($item->xfn)        ? $item->xfn        : '';
        $atts['href']   = !empty($item->url)        ? $item->url        : '';
        $atts['class']  = 'mobile-menu-link';
        
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
            $item_output .= '<span class="mobile-menu-badge">NEW</span>';
        }
        
        // Add notice for Downloads item
        if (strpos(strtolower($item->title), 'downloads') !== false) {
            $item_output .= '<span class="mobile-menu-notice">MUST LOGIN</span>';
        }
        
        // Add phone number for Contact item
        if (strpos(strtolower($item->title), 'contact') !== false) {
            $item_output .= '<span class="mobile-menu-phone">+91-7878537979</span>';
        }
        
        $item_output .= '</a>';
        $item_output .= $args->after;
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}
?>