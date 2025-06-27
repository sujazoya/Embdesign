<?php
/*
Plugin Name: WooCommerce Design Upload Pro
Description: Advanced product submission system for embroidery designs with stylish UI and enhanced features.
Version: 4.0
Author: Your Name
Text Domain: wc-design-upload
*/

if (!defined('ABSPATH')) exit;

class WC_Design_Upload_Pro {

    private static $instance;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        // Check WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('wc-design-upload', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        // Register hooks
        $this->register_hooks();
    }

    private function register_hooks() {
        // MIME types
        add_filter('upload_mimes', array($this, 'custom_mime_types'));

        // Assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Shortcodes
        add_shortcode('design_submission_form', array($this, 'submission_form_shortcode'));
        add_shortcode('design_download_page', array($this, 'download_page_shortcode'));

        // Order processing
        add_action('woocommerce_thankyou', array($this, 'add_download_links_to_thankyou'));

        // Admin columns
        add_filter('manage_product_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'render_admin_columns'), 10, 2);

        // Settings
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>';
        _e('WooCommerce Design Upload Pro requires WooCommerce to be installed and active!', 'wc-design-upload');
        echo '</p></div>';
    }

    public function custom_mime_types($mimes) {
        $mimes['emb'] = 'application/octet-stream';
        $mimes['dst'] = 'application/octet-stream';
        $mimes['pes'] = 'application/octet-stream';
        $mimes['jef'] = 'application/octet-stream';
        $mimes['exp'] = 'application/octet-stream';
        return $mimes;
    }

    public function enqueue_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'design_submission_form') || has_shortcode($post->post_content, 'design_download_page'))) {
            // CSS
            wp_enqueue_style(
                'wcdu-pro-main-css',
                plugins_url('assets/css/main.css', __FILE__),
                array(),
                filemtime(plugin_dir_path(__FILE__) . 'assets/css/main.css')
            );

            // JS
            wp_enqueue_script(
                'wcdu-pro-main-js',
                plugins_url('assets/js/main.js', __FILE__),
                array('jquery'),
                filemtime(plugin_dir_path(__FILE__) . 'assets/js/main.js'),
                true
            );

            // Localize script
            wp_localize_script('wcdu-pro-main-js', 'wcduPro', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcdu-pro-nonce'),
                'i18n' => array(
                    'uploading' => __('Uploading...', 'wc-design-upload'),
                    'maxFileSize' => __('File size exceeds maximum limit', 'wc-design-upload'),
                    'invalidType' => __('Invalid file type', 'wc-design-upload')
                )
            ));
        }
    }

    public function submission_form_shortcode() {
        if (!is_user_logged_in()) {
            return $this->render_template('login-required');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wcdu_submit'])) {
            return $this->process_submission();
        }

        return $this->render_template('submission-form', array(
            'categories' => $this->get_product_categories(),
            'tags' => $this->get_product_tags(),
            'form_data' => $this->get_form_data()
        ));
    }

    private function process_submission() {
        try {
            $user_id = get_current_user_id();
            $data = $this->validate_submission_data();

            // Create product
            $post_id = wp_insert_post(array(
                'post_title'   => $data['title'],
                'post_content' => $data['description'],
                'post_status'  => $this->get_submission_status(),
                'post_type'    => 'product',
                'post_author'  => $user_id,
            ));

            if (is_wp_error($post_id)) {
                throw new Exception(__('Error creating product', 'wc-design-upload'));
            }

            // Set product data
            $this->set_product_data($post_id, $data);

            // Upload files
            $files = $this->upload_files($post_id);
            $this->set_product_files($post_id, $files);

            // Redirect
            if ($data['price'] == 0) {
                $redirect = add_query_arg('product_id', $post_id, get_permalink(get_option('wcdu_download_page')));
            } else {
                $redirect = get_permalink($post_id);
            }

            return $this->render_template('submission-success', array(
                'redirect' => $redirect,
                'is_free' => ($data['price'] == 0)
            ));

        } catch (Exception $e) {
            return $this->render_template('submission-error', array(
                'error' => $e->getMessage(),
                'form_data' => $this->get_form_data()
            ));
        }
    }

    private function validate_submission_data() {
        $data = array(
            'title' => sanitize_text_field($_POST['product_title'] ?? ''),
            'description' => sanitize_textarea_field($_POST['product_desc'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'category' => intval($_POST['category'] ?? 0),
            'tags' => array_map('intval', $_POST['product_tags'] ?? array()),
            'meta' => array()
        );

        // Validate required fields
        if (empty($data['title'])) {
            throw new Exception(__('Product title is required', 'wc-design-upload'));
        }

        if (empty($data['description'])) {
            throw new Exception(__('Product description is required', 'wc-design-upload'));
        }

        // Validate meta fields
        $meta_fields = array(
            'design_code', 'stitches', 'area', 'height', 'width', 
            'formats', 'needle', 'colors', 'designer_notes'
        );

        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                $data['meta'][$field] = sanitize_text_field($_POST[$field]);
            }
        }

        return $data;
    }

    private function upload_files($post_id) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $files = array();
        $max_size = $this->get_max_upload_size();

        // Main image
        if (!empty($_FILES['gallery']['name'])) {
            $file = $_FILES['gallery'];
            
            // Check image type
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
            $filetype = wp_check_filetype($file['name']);
            
            if (!in_array($filetype['type'], $allowed_types)) {
                throw new Exception(__('Invalid image type. Only JPG, PNG and GIF are allowed.', 'wc-design-upload'));
            }

            // Check file size
            if ($file['size'] > $max_size) {
                throw new Exception(sprintf(__('Image file is too large. Maximum size is %s.', 'wc-design-upload'), size_format($max_size)));
            }

            $file_id = media_handle_upload('gallery', $post_id);
            if (!is_wp_error($file_id)) {
                $files['gallery'] = $file_id;
                set_post_thumbnail($post_id, $file_id);
            }
        }

        // Design files
        $design_files = array(
            'dst' => array('ext' => array('dst'), 'label' => 'DST'),
            'pes' => array('ext' => array('pes'), 'label' => 'PES'),
            'jef' => array('ext' => array('jef'), 'label' => 'JEF'),
            'exp' => array('ext' => array('exp'), 'label' => 'EXP'),
            'all_zip' => array('ext' => array('zip'), 'label' => 'ZIP')
        );

        foreach ($design_files as $field => $settings) {
            if (!empty($_FILES[$field]['name'])) {
                $file = $_FILES[$field];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                // Check extension
                if (!in_array($ext, $settings['ext'])) {
                    throw new Exception(sprintf(__('Invalid file type for %s. Allowed: %s', 'wc-design-upload'), $settings['label'], implode(', ', $settings['ext'])));
                }

                // Check file size
                if ($file['size'] > $max_size) {
                    throw new Exception(sprintf(__('%s file is too large. Maximum size is %s.', 'wc-design-upload'), $settings['label'], size_format($max_size)));
                }

                $file_id = media_handle_upload($field, $post_id);
                if (!is_wp_error($file_id)) {
                    $files[$field] = $file_id;
                }
            }
        }

        if (empty($files)) {
            throw new Exception(__('At least one design file is required', 'wc-design-upload'));
        }

        return $files;
    }

    private function set_product_data($post_id, $data) {
        // Basic product data
        update_post_meta($post_id, '_price', $data['price']);
        update_post_meta($post_id, '_regular_price', $data['price']);
        wp_set_object_terms($post_id, 'simple', 'product_type');

        // Virtual/downloadable
        update_post_meta($post_id, '_virtual', 'yes');
        update_post_meta($post_id, '_downloadable', 'yes');

        // Category
        if ($data['category'] > 0) {
            wp_set_post_terms($post_id, array($data['category']), 'product_cat');
        }

        // Tags
        if (!empty($data['tags'])) {
            wp_set_post_terms($post_id, $data['tags'], 'product_tag');
        }

        // Custom meta
        foreach ($data['meta'] as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
    }

    private function set_product_files($post_id, $files) {
        $downloads = array();

        foreach ($files as $type => $file_id) {
            if ($type === 'gallery') continue;

            $file_url = wp_get_attachment_url($file_id);
            $file_name = basename(get_attached_file($file_id));

            $downloads[$file_id] = array(
                'name' => $file_name,
                'file' => $file_url
            );
        }

        update_post_meta($post_id, '_downloadable_files', $downloads);
        update_post_meta($post_id, '_wc_design_files', $files);
    }

    public function download_page_shortcode() {
        if (!isset($_GET['product_id'])) {
            return $this->render_template('download-error', array(
                'message' => __('No product ID provided', 'wc-design-upload')
            ));
        }

        $product_id = intval($_GET['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            return $this->render_template('download-error', array(
                'message' => __('Invalid product', 'wc-design-upload')
            ));
        }

        // Check access
        if (!$this->user_has_access($product_id)) {
            return $this->render_template('download-error', array(
                'message' => __('You must purchase this product to download files', 'wc-design-upload'),
                'product' => $product
            ));
        }

        // Get files
        $downloads = $product->get_downloads();
        if (empty($downloads)) {
            return $this->render_template('download-error', array(
                'message' => __('No files found for this product', 'wc-design-upload'),
                'product' => $product
            ));
        }

        return $this->render_template('download-page', array(
            'product' => $product,
            'downloads' => $downloads
        ));
    }

    private function user_has_access($product_id) {
        $product = wc_get_product($product_id);
        $price = $product->get_price();
        $user_id = get_current_user_id();

        // Free product
        if ($price == 0) {
            return true;
        }

        // Check purchases
        if (is_user_logged_in()) {
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'status' => array('completed', 'processing'),
                'limit' => -1,
                'return' => 'ids'
            ));

            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                foreach ($order->get_items() as $item) {
                    if ($item->get_product_id() == $product_id) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function add_download_links_to_thankyou($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $has_downloads = false;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $downloads = get_post_meta($product_id, '_downloadable_files', true);

            if (!empty($downloads)) {
                if (!$has_downloads) {
                    echo '<div class="wcdu-order-downloads">';
                    echo '<h3>' . __('Design Files Download', 'wc-design-upload') . '</h3>';
                    $has_downloads = true;
                }

                echo '<div class="wcdu-product-downloads">';
                echo '<h4>' . get_the_title($product_id) . '</h4>';
                
                foreach ($downloads as $file_id => $file_data) {
                    echo '<p><a href="' . esc_url($file_data['file']) . '" download class="button">';
                    echo __('Download', 'wc-design-upload') . ' ' . esc_html($file_data['name']);
                    echo '</a></p>';
                }
                
                echo '</div>';
            }
        }

        if ($has_downloads) {
            echo '</div>';
        }
    }

    private function get_product_categories() {
        return get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name'
        ));
    }

    private function get_product_tags() {
        return get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
            'orderby' => 'name'
        ));
    }

    private function get_form_data() {
        $data = array(
            'product_title' => sanitize_text_field($_POST['product_title'] ?? ''),
            'product_desc' => sanitize_textarea_field($_POST['product_desc'] ?? ''),
            'design_code' => sanitize_text_field($_POST['design_code'] ?? ''),
            'stitches' => sanitize_text_field($_POST['stitches'] ?? ''),
            'area' => sanitize_text_field($_POST['area'] ?? ''),
            'height' => sanitize_text_field($_POST['height'] ?? ''),
            'width' => sanitize_text_field($_POST['width'] ?? ''),
            'formats' => sanitize_text_field($_POST['formats'] ?? ''),
            'needle' => sanitize_text_field($_POST['needle'] ?? ''),
            'colors' => sanitize_text_field($_POST['colors'] ?? ''),
            'designer_notes' => sanitize_text_field($_POST['designer_notes'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'category' => intval($_POST['category'] ?? 0),
            'product_tags' => array_map('intval', $_POST['product_tags'] ?? array())
        );

        return $data;
    }

    private function get_submission_status() {
        $status = get_option('wcdu_default_status', 'publish');
        return in_array($status, array('publish', 'pending', 'draft')) ? $status : 'publish';
    }

    private function get_max_upload_size() {
        $max = wp_max_upload_size();
        $option = get_option('wcdu_max_upload_size', 0);
        
        if ($option > 0 && $option < $max) {
            return $option;
        }
        
        return $max;
    }

    private function render_template($template, $data = array()) {
        $template_path = plugin_dir_path(__FILE__) . 'templates/' . $template . '.php';
        
        if (!file_exists($template_path)) {
            return '<p>' . __('Template not found', 'wc-design-upload') . '</p>';
        }

        ob_start();
        extract($data);
        include $template_path;
        return ob_get_clean();
    }

    public function add_admin_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'name') {
                $new_columns['design_code'] = __('Design Code', 'wc-design-upload');
                $new_columns['design_files'] = __('Files', 'wc-design-upload');
            }
        }
        
        return $new_columns;
    }

    public function render_admin_columns($column, $post_id) {
        if ($column === 'design_code') {
            echo get_post_meta($post_id, 'design_code', true);
        }
        
        if ($column === 'design_files') {
            $files = get_post_meta($post_id, '_wc_design_files', true);
            if (!empty($files)) {
                $count = count($files) - (isset($files['gallery']) ? 1 : 0);
                echo $count . ' ' . _n('file', 'files', $count, 'wc-design-upload');
            }
        }
    }

    public function register_settings() {
        register_setting('wcdu_settings', 'wcdu_default_status');
        register_setting('wcdu_settings', 'wcdu_max_upload_size');
        register_setting('wcdu_settings', 'wcdu_download_page');
        
        add_settings_section(
            'wcdu_general_section',
            __('General Settings', 'wc-design-upload'),
            array($this, 'settings_section_callback'),
            'wcdu_settings'
        );
        
        add_settings_field(
            'wcdu_default_status',
            __('Default Submission Status', 'wc-design-upload'),
            array($this, 'status_field_callback'),
            'wcdu_settings',
            'wcdu_general_section'
        );
        
        add_settings_field(
            'wcdu_max_upload_size',
            __('Maximum Upload Size (bytes)', 'wc-design-upload'),
            array($this, 'upload_size_field_callback'),
            'wcdu_settings',
            'wcdu_general_section'
        );
        
        add_settings_field(
            'wcdu_download_page',
            __('Download Page', 'wc-design-upload'),
            array($this, 'download_page_field_callback'),
            'wcdu_settings',
            'wcdu_general_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>' . __('Configure the behavior of the design upload system.', 'wc-design-upload') . '</p>';
    }

    public function status_field_callback() {
        $status = get_option('wcdu_default_status', 'publish');
        ?>
        <select name="wcdu_default_status">
            <option value="publish" <?php selected($status, 'publish'); ?>><?php _e('Published', 'wc-design-upload'); ?></option>
            <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending Review', 'wc-design-upload'); ?></option>
            <option value="draft" <?php selected($status, 'draft'); ?>><?php _e('Draft', 'wc-design-upload'); ?></option>
        </select>
        <p class="description"><?php _e('Set the default status for newly submitted designs.', 'wc-design-upload'); ?></p>
        <?php
    }

    public function upload_size_field_callback() {
        $size = get_option('wcdu_max_upload_size', 0);
        $max = wp_max_upload_size();
        ?>
        <input type="number" name="wcdu_max_upload_size" value="<?php echo esc_attr($size); ?>" min="0" max="<?php echo esc_attr($max); ?>" step="1">
        <p class="description">
            <?php printf(__('Maximum allowed upload size in bytes. Server limit: %s. Set to 0 to use server limit.', 'wc-design-upload'), size_format($max)); ?>
        </p>
        <?php
    }

    public function download_page_field_callback() {
        $page_id = get_option('wcdu_download_page', 0);
        wp_dropdown_pages(array(
            'name' => 'wcdu_download_page',
            'selected' => $page_id,
            'show_option_none' => __('Select a page', 'wc-design-upload'),
            'option_none_value' => 0
        ));
        ?>
        <p class="description"><?php _e('Select the page containing the [design_download_page] shortcode.', 'wc-design-upload'); ?></p>
        <?php
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Design Upload Settings', 'wc-design-upload'),
            __('Design Upload', 'wc-design-upload'),
            'manage_options',
            'wcdu-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Design Upload Settings', 'wc-design-upload'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wcdu_settings');
                do_settings_sections('wcdu_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin
WC_Design_Upload_Pro::get_instance();