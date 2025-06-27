<?php
if (!defined('ABSPATH')) exit;

// Register shortcode
add_shortcode('seller_profile', 'seller_profile_shortcode');

function seller_profile_shortcode($atts) {
    ob_start();
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        echo '<p>' . __('You must be <a href="' . esc_url(wp_login_url()) . '">logged in</a> to view this page.', 'seller-profile') . '</p>';
        return ob_get_clean();
    }
    
    // Load the template
    include SELLER_PROFILE_PLUGIN_PATH . 'templates/seller-profile-template.php';
    
    return ob_get_clean();
}