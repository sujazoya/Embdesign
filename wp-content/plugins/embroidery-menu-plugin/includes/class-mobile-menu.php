<?php
class Embroidery_Mobile_Menu {

    public static function init() {
        add_action('wp_footer', [__CLASS__, 'render_mobile_menu']);
        add_action('wp_footer', [__CLASS__, 'render_mobile_menu_toggle']);
    }

    public static function render_mobile_menu() {
        echo '<div id="embroidery-mobile-menu" class="embroidery-mobile-menu">';
        echo '<div class="mobile-menu-header">';
        echo '<span class="mobile-menu-close">&times;</span>';
        echo '</div>';
        
        wp_nav_menu([
            'theme_location' => 'embroidery-mobile',
            'container'      => false,
            'menu_class'     => 'embroidery-mobile-nav',
            'fallback_cb'    => [__CLASS__, 'default_mobile_menu']
        ]);
        
        echo '</div>';
        echo '<div class="mobile-menu-overlay"></div>';
    }

    public static function default_mobile_menu() {
        $menu_items = [
            'Bulk Downloads' => '#',
            'Digitizing Service' => '#',
            'Free Designs' => '#',
            'Career' => '#',
            'My Downloads' => wc_get_page_permalink('myaccount'),
            'Contact Us' => 'tel:+91-7878537979',
            'Drop Your Suggestion' => '#'
        ];

        $menu = '<ul class="embroidery-mobile-nav">';
        foreach ($menu_items as $title => $url) {
            $new_badge = ($title == 'Career') ? '<span class="new-badge">NEW</span>' : '';
            $login_notice = ($title == 'My Downloads') ? '<span class="login-notice">MUST LOGIN</span>' : '';
            
            $menu .= sprintf(
                '<li class="mobile-menu-item"><a href="%s">%s %s %s</a></li>',
                esc_url($url),
                esc_html($title),
                $new_badge,
                $login_notice
            );
        }
        $menu .= '</ul>';

        return $menu;
    }

    public static function render_mobile_menu_toggle() {
        echo '<div class="mobile-menu-toggle">';
        echo '<span class="toggle-bar"></span>';
        echo '<span class="toggle-bar"></span>';
        echo '<span class="toggle-bar"></span>';
        echo '</div>';
    }
}