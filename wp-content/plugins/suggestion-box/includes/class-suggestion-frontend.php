<?php
class Suggestion_Box_Frontend {
    public function __construct() {
        add_shortcode('suggestion_box', [$this, 'render_suggestion_box']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_submit_suggestion', [$this, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_submit_suggestion', [$this, 'handle_ajax_submission']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'suggestion-box-css',
            SUGGESTION_BOX_URL . 'assets/css/suggestion-box.css',
            [],
            SUGGESTION_BOX_VERSION
        );

        wp_enqueue_script(
            'suggestion-box-js',
            SUGGESTION_BOX_URL . 'assets/js/suggestion-box.js',
            ['jquery'],
            SUGGESTION_BOX_VERSION,
            true
        );

        wp_localize_script(
            'suggestion-box-js',
            'suggestionBox',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('suggestion_box_nonce')
            ]
        );
    }

    public function render_suggestion_box($atts) {
        $atts = shortcode_atts([
            'title' => 'Drop Your Suggestion',
            'description' => 'We value your feedback! Please share your suggestions with us.'
        ], $atts);

        ob_start();
        include SUGGESTION_BOX_PATH . 'templates/frontend-form.php';
        return ob_get_clean();
    }

    public function handle_ajax_submission() {
        check_ajax_referer('suggestion_box_nonce', 'nonce');

        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['suggestion'])) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }

        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'suggestion' => sanitize_textarea_field($_POST['suggestion'])
        ];

        $db = new Suggestion_Box_DB();
        $result = $db->insert_suggestion($data);

        if ($result) {
            $this->send_notification_email($data);
            wp_send_json_success(['message' => 'Thank you for your suggestion!']);
        } else {
            wp_send_json_error(['message' => 'There was an error submitting your suggestion.']);
        }
    }

    private function send_notification_email($data) {
        $to = get_option('admin_email');
        $subject = 'New Suggestion Submitted';
        $message = "A new suggestion has been submitted:\n\n";
        $message .= "Name: {$data['name']}\n";
        $message .= "Email: {$data['email']}\n";
        $message .= "Suggestion:\n{$data['suggestion']}\n";

        wp_mail($to, $subject, $message);
    }
}