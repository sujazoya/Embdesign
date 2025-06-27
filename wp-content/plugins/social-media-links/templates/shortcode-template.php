<?php
/**
 * Social Media Links - Shortcode Template
 * 
 * @package Social Media Links
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Determine container classes
$container_classes = array('sml-container');
if ($atts['layout'] === 'list') {
    $container_classes[] = 'sml-list-layout';
}
if (!$atts['show_labels']) {
    $container_classes[] = 'sml-no-labels';
}

// Determine grid columns
$grid_style = $atts['layout'] === 'grid' ? 'style="--sml-columns: ' . esc_attr($atts['columns']) . '"' : '';
?>

<div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>">
    <header class="sml-header">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <?php if ($atts['subtitle']) : ?>
            <p class="sml-tagline"><?php echo esc_html($atts['subtitle']); ?></p>
        <?php endif; ?>
    </header>
    
    <div class="sml-<?php echo esc_attr($atts['layout']); ?>" <?php echo $grid_style; ?>>
        <?php 
        // Supported platforms
        $platforms = array(
            'facebook'  => array('name' => 'Facebook', 'desc' => __('', 'social-media-links')),
            'instagram' => array('name' => 'Instagram', 'desc' => __('', 'social-media-links')),
            'twitter'   => array('name' => 'Twitter', 'desc' => __('', 'social-media-links')),
            'youtube'   => array('name' => 'YouTube', 'desc' => __('', 'social-media-links')),
            'linkedin'  => array('name' => 'LinkedIn', 'desc' => __('', 'social-media-links')),
            'pinterest' => array('name' => 'Pinterest', 'desc' => __('', 'social-media-links')),
            'whatsapp'  => array('name' => 'WhatsApp', 'desc' => __('', 'social-media-links')),
            'telegram'  => array('name' => 'Telegram', 'desc' => __('', 'social-media-links')),
            'tiktok'    => array('name' => 'TikTok', 'desc' => __('', 'social-media-links'))
        );
        
        // Output cards for each platform with URL
        foreach ($platforms as $platform => $data) {
            if (!empty($atts[$platform])) {
                include SML_PLUGIN_DIR . 'templates/platform-card.php';
            }
        }
        ?>
    </div>
</div>