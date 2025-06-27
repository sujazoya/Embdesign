<?php
class Embroidery_Styles_Scripts {

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_styles_scripts']);
        add_action('wp_head', [__CLASS__, 'inline_styles']);
    }

    public static function enqueue_styles_scripts() {
        // Desktop styles and scripts
        wp_enqueue_style(
            'embroidery-menu-desktop',
            EMBROIDERY_MENU_PLUGIN_URL . 'assets/css/desktop.css',
            [],
            EMBROIDERY_MENU_VERSION
        );

        wp_enqueue_script(
            'embroidery-menu-desktop',
            EMBROIDERY_MENU_PLUGIN_URL . 'assets/js/desktop.js',
            ['jquery'],
            EMBROIDERY_MENU_VERSION,
            true
        );

        // Mobile styles and scripts
        wp_enqueue_style(
            'embroidery-menu-mobile',
            EMBROIDERY_MENU_PLUGIN_URL . 'assets/css/mobile.css',
            [],
            EMBROIDERY_MENU_VERSION
        );

        wp_enqueue_script(
            'embroidery-menu-mobile',
            EMBROIDERY_MENU_PLUGIN_URL . 'assets/js/mobile.js',
            ['jquery'],
            EMBROIDERY_MENU_VERSION,
            true
        );
    }

    public static function inline_styles() {
        echo '<style>
            .new-badge {
                background: #ff0000;
                color: #fff;
                font-size: 10px;
                padding: 2px 5px;
                border-radius: 3px;
                margin-left: 5px;
                display: inline-block;
                vertical-align: middle;
            }
            
            .login-notice {
                font-size: 12px;
                color: #ff0000;
                display: block;
            }
        </style>';
    }
}