<?php
/**
 * Social Media Links - Platform Card Template
 * 
 * @package Social Media Links
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get SVG icon path
$icon_path = SML_PLUGIN_DIR . 'assets/icons/' . $platform . '.svg';

// Button text
$button_text = __('Follow', 'social-media-links');
if ($platform === 'whatsapp') {
    $button_text = __('Message', 'social-media-links');
} elseif ($platform === 'youtube' || $platform === 'telegram') {
    $button_text = __('Subscribe', 'social-media-links');
} elseif ($platform === 'facebook') {
    $button_text = __('Join', 'social-media-links');
}
?>

<a href="<?php echo esc_url($atts[$platform]); ?>" class="sml-card sml-<?php echo esc_attr($platform); ?>" target="_blank" rel="noopener noreferrer">
    <div class="sml-icon">
        <?php 
        if (file_exists($icon_path)) {
            include $icon_path;
        } else {
            echo '<span class="sml-fallback-icon">' . substr($data['name'], 0, 1) . '</span>';
        }
        ?>
    </div>
    <h3><?php echo esc_html($data['name']); ?></h3>
    <p><?php echo esc_html($data['desc']); ?></p>
</a>