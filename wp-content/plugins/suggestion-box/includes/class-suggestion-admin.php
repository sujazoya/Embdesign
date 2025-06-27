<?php
class Suggestion_Box_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Suggestions',
            'Suggestions',
            'manage_options',
            'suggestion-box',
            [$this, 'render_admin_page'],
            'dashicons-testimonial',
            25
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_suggestion-box') return;

        wp_enqueue_style(
            'suggestion-box-admin-css',
            SUGGESTION_BOX_URL . 'assets/css/admin.css',
            [],
            SUGGESTION_BOX_VERSION
        );

        wp_enqueue_script(
            'suggestion-box-admin-js',
            SUGGESTION_BOX_URL . 'assets/js/admin.js',
            ['jquery'],
            SUGGESTION_BOX_VERSION,
            true
        );
    }

    public function render_admin_page() {
        $db = new Suggestion_Box_DB();
        $pending = $db->get_suggestions('pending');
        $approved = $db->get_suggestions('approved', 20);
        $rejected = $db->get_suggestions('rejected', 20);

        include SUGGESTION_BOX_PATH . 'templates/admin-display.php';
    }
}