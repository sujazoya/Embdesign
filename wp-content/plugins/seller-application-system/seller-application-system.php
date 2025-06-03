<?php
/*
Plugin Name: Seller Application System
Description: A complete system for users to apply for seller role with admin approval
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

class SellerApplicationSystem {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seller_applications';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            email varchar(100) NOT NULL,
            name varchar(100) NOT NULL,
            details text NOT NULL,
            files text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            submitted_at datetime NOT NULL,
            processed_at datetime NULL,
            processed_by bigint(20) NULL,
            notes text NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add seller role if it doesn't exist
        if (!get_role('seller')) {
            add_role('seller', 'Seller', array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => false,
            ));
        }
    }

    public function init() {
        add_shortcode('seller_application_form', array($this, 'application_form_shortcode'));
        add_action('wp_ajax_submit_seller_application', array($this, 'handle_application_submission'));
        add_action('wp_ajax_nopriv_submit_seller_application', array($this, 'handle_application_submission'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('seller-application-style', plugins_url('css/style.css', __FILE__));
        wp_enqueue_script('seller-application-script', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
        
        wp_localize_script('seller-application-script', 'seller_application', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seller_application_nonce')
        ));
    }

    public function admin_enqueue_scripts($hook) {
        if ('toplevel_page_seller-applications' === $hook) {
            wp_enqueue_style('seller-application-admin-style', plugins_url('css/admin-style.css', __FILE__));
            wp_enqueue_script('seller-application-admin-script', plugins_url('js/admin-script.js', __FILE__), array('jquery'), null, true);
        }
    }

    public function application_form_shortcode() {
        ob_start();
        
        $current_user = wp_get_current_user();
        $is_logged_in = is_user_logged_in();
        
        // Check if user already has seller role
        if ($is_logged_in && in_array('seller', $current_user->roles)) {
            return '<div class="seller-application-notice">You are already a seller!</div>';
        }
        
        // Check for pending applications
        if ($is_logged_in) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'seller_applications';
            $pending = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'pending'",
                $current_user->ID
            ));
            
            if ($pending) {
                return '<div class="seller-application-notice">Your application is pending review.</div>';
            }
        }
        
        // Form HTML
        ?>
        <div class="seller-application-form">
            <h2>Apply for Seller Role</h2>
            <form id="seller-application" enctype="multipart/form-data">
                <?php if (!$is_logged_in) : ?>
                    <div class="form-group">
                        <label for="seller-email">Email</label>
                        <input type="email" id="seller-email" name="seller_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="seller-name">Full Name</label>
                        <input type="text" id="seller-name" name="seller_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="seller-password">Password</label>
                        <input type="password" id="seller-password" name="seller_password" required>
                    </div>
                <?php else : ?>
                    <div class="form-group">
                        <label for="seller-email">Email</label>
                        <input type="email" id="seller-email" name="seller_email" value="<?php echo esc_attr($current_user->user_email); ?>" required readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="seller-name">Full Name</label>
                        <input type="text" id="seller-name" name="seller_name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="seller-details">Additional Information</label>
                    <textarea id="seller-details" name="seller_details" rows="5" required placeholder="Tell us about what you plan to sell and your experience"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="seller-files">Supporting Documents</label>
                    <input type="file" id="seller-files" name="seller_files[]" multiple>
                    <p class="description">Upload your files (any type allowed, max 20MB each). You must include at least one non-image file.</p>
                </div>
                
                <button type="submit" class="submit-application">Submit Application</button>
            </form>
            <div id="seller-application-response"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_application_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seller_application_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        $is_logged_in = is_user_logged_in();
        $user_id = 0;
        $email = sanitize_email($_POST['seller_email']);
        $name = sanitize_text_field($_POST['seller_name']);
        $details = sanitize_textarea_field($_POST['seller_details']);
        
        if (empty($name) || empty($details) || empty($email)) {
            wp_send_json_error(array('message' => 'Please fill all required fields.'));
        }
        
        // Handle user registration/login if not logged in
        if (!$is_logged_in) {
            $password = $_POST['seller_password'];
            
            // Check if user exists
            if (email_exists($email)) {
                // Try to log the user in
                $user = wp_signon(array(
                    'user_login' => $email,
                    'user_password' => $password,
                    'remember' => true
                ), false);
                
                if (is_wp_error($user)) {
                    wp_send_json_error(array('message' => 'Invalid email or password.'));
                }
                
                $user_id = $user->ID;
            } else {
                // Create new user
                $user_id = wp_create_user($email, $password, $email);
                
                if (is_wp_error($user_id)) {
                    wp_send_json_error(array('message' => $user_id->get_error_message()));
                }
                
                // Update user name
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $name
                ));
                
                // Log the user in
                wp_set_auth_cookie($user_id);
            }
        } else {
            $user_id = get_current_user_id();
        }
        
        $current_user = get_user_by('id', $user_id);
        
        // Check if user already has seller role
        if (in_array('seller', $current_user->roles)) {
            wp_send_json_error(array('message' => 'You already have seller privileges.'));
        }
        
        // Check for pending applications
        global $wpdb;
        $table_name = $wpdb->prefix . 'seller_applications';
        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'pending'",
            $user_id
        ));
        
        if ($pending) {
            wp_send_json_error(array('message' => 'You already have a pending application.'));
        }
        
        // Handle file uploads
        $uploaded_files = array();
        $has_non_image = false;
        
        if (!empty($_FILES['seller_files'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Completely disable file type checking
            add_filter('upload_mimes', function($mimes) {
                $mimes['*'] = 'application/octet-stream';
                return $mimes;
            }, 999, 1);
            
            // Bypass WordPress file type verification
            add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
                return array(
                    'ext' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'type' => 'application/octet-stream',
                    'proper_filename' => false
                );
            }, 999, 4);
            
            // Increase upload size limit to 20MB
            add_filter('upload_size_limit', function($size) {
                return 20 * 1024 * 1024; // 20MB
            });
            
            $files = $_FILES['seller_files'];
            $upload_overrides = array(
                'test_form' => false,
                'test_type' => false // Disable file type testing
            );
            
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    // Check file size (20MB limit)
                    if ($files['size'][$key] > (20 * 1024 * 1024)) {
                        wp_send_json_error(array('message' => 'File too large: ' . $files['name'][$key] . '. Maximum size is 20MB.'));
                    }
                    
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    );
                    
                    $uploaded_file = wp_handle_upload($file, $upload_overrides);
                    
                    if ($uploaded_file && !isset($uploaded_file['error'])) {
                        $uploaded_files[] = $uploaded_file['url'];
                        
                        // Check if file is not an image (for the "at least one non-image" requirement)
                        $file_ext = strtolower(pathinfo($files['name'][$key], PATHINFO_EXTENSION));
                        $image_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg');
                        if (!in_array($file_ext, $image_exts)) {
                            $has_non_image = true;
                        }
                    } else {
                        wp_send_json_error(array('message' => 'File upload error: ' . $uploaded_file['error']));
                    }
                }
            }
            
            // Remove our filters after upload is complete
            remove_filter('upload_mimes', function($mimes) {
                $mimes['*'] = 'application/octet-stream';
                return $mimes;
            }, 999);
            
            remove_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
                return array(
                    'ext' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'type' => 'application/octet-stream',
                    'proper_filename' => false
                );
            }, 999);
            
            // Check if they only uploaded images
            if (!empty($uploaded_files) && !$has_non_image) {
                // Delete uploaded files
                foreach ($uploaded_files as $file_url) {
                    $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $file_url);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                wp_send_json_error(array('message' => 'You need to upload at least one non-image file (like a PDF or document) to show your designs.'));
            }
        }
        
        // Save application to database
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'email' => $email,
                'name' => $name,
                'details' => $details,
                'files' => maybe_serialize($uploaded_files),
                'submitted_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        wp_send_json_success(array('message' => 'Your application has been submitted successfully!'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Seller Applications',
            'Seller Applications',
            'manage_options',
            'seller-applications',
            array($this, 'render_admin_page'),
            'dashicons-clipboard',
            30
        );
    }

    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'seller_applications';
        
        // Handle application actions
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = sanitize_text_field($_GET['action']);
            $id = intval($_GET['id']);
            
            if ($action === 'approve') {
                $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
                
                if ($application) {
                    // Add seller role to user
                    $user = get_user_by('id', $application->user_id);
                    
                    // First remove any existing seller role to avoid duplicates
                    $user->remove_role('seller');
                    // Then add the seller role
                    $user->add_role('seller');
                    
                    // Update application status
                    $wpdb->update(
                        $table_name,
                        array(
                            'status' => 'approved',
                            'processed_at' => current_time('mysql'),
                            'processed_by' => get_current_user_id()
                        ),
                        array('id' => $id),
                        array('%s', '%s', '%d'),
                        array('%d')
                    );
                    
                    // Send approval email
                    wp_mail(
                        $application->email,
                        'Your Seller Application Has Been Approved',
                        "Dear " . $application->name . ",\n\nYour seller application has been approved. You can now start selling on our platform.\n\nThank you!"
                    );
                    
                    echo '<div class="notice notice-success"><p>Application approved successfully! Seller role has been assigned.</p></div>';
                }
            } elseif ($action === 'decline') {
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => 'declined',
                        'processed_at' => current_time('mysql'),
                        'processed_by' => get_current_user_id(),
                        'notes' => isset($_POST['decline_reason']) ? sanitize_textarea_field($_POST['decline_reason']) : ''
                    ),
                    array('id' => $id),
                    array('%s', '%s', '%d', '%s'),
                    array('%d')
                );
                
                $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
                
                // Send decline email
                wp_mail(
                    $application->email,
                    'Your Seller Application Status',
                    "Dear " . $application->name . ",\n\nWe regret to inform you that your seller application has been declined.\n\n" .
                    (isset($_POST['decline_reason']) ? "Reason: " . sanitize_textarea_field($_POST['decline_reason']) . "\n\n" : "") .
                    "Thank you for your interest."
                );
                
                echo '<div class="notice notice-success"><p>Application declined successfully!</p></div>';
            } elseif ($action === 'view') {
                $this->view_application_details($id);
                return;
            }
        }
        
        $applications = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY status ASC, submitted_at DESC"
        );
        ?>
        <div class="wrap">
            <h1>Seller Applications</h1>
            
            <div class="seller-applications-container">
                <?php if (empty($applications)) : ?>
                    <p>No applications found.</p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Name</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app) : ?>
                                <tr>
                                    <td><?php echo $app->id; ?></td>
                                    <td><?php echo esc_html($app->email); ?></td>
                                    <td><?php echo esc_html($app->name); ?></td>
                                    <td><?php echo date('M j, Y g:i a', strtotime($app->submitted_at)); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($app->status); ?>">
                                            <?php echo ucfirst($app->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=seller-applications&action=view&id=' . $app->id); ?>" class="button">View</a>
                                        <?php if ($app->status === 'pending') : ?>
                                            <a href="<?php echo admin_url('admin.php?page=seller-applications&action=approve&id=' . $app->id); ?>" class="button button-primary">Approve</a>
                                            <a href="<?php echo admin_url('admin.php?page=seller-applications&action=decline&id=' . $app->id); ?>" class="button button-secondary">Decline</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function view_application_details($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'seller_applications';
        
        $application = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        if (!$application) {
            wp_die('Application not found.');
        }
        
        $files = maybe_unserialize($application->files);
        $user = get_user_by('id', $application->user_id);
        $is_seller = in_array('seller', $user->roles);
        ?>
        <div class="wrap">
            <h1>Application Details</h1>
            <a href="<?php echo admin_url('admin.php?page=seller-applications'); ?>" class="button">← Back to Applications</a>
            
            <div class="application-details">
                <h2>Applicant Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Applicant ID</th>
                        <td><?php echo $application->user_id; ?></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?php echo esc_html($application->name); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo esc_html($application->email); ?></td>
                    </tr>
                    <tr>
                        <th>Submitted</th>
                        <td><?php echo date('M j, Y g:i a', strtotime($application->submitted_at)); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($application->status); ?>">
                                <?php echo ucfirst($application->status); ?>
                            </span>
                            <?php if ($is_seller) : ?>
                                <span class="status-badge status-seller" style="margin-left: 5px;">Seller</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <h2>Application Details</h2>
                <div class="application-content">
                    <?php echo wpautop(esc_html($application->details)); ?>
                </div>
                
                <?php if (!empty($files)) : ?>
                    <h2>Attached Files</h2>
                    <div class="application-files">
                        <?php foreach ($files as $file) : ?>
                            <div class="file-item">
                                <?php
                                $file_ext = pathinfo($file, PATHINFO_EXTENSION);
                                if (in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif'))) {
                                    echo '<img src="' . esc_url($file) . '" style="max-width: 200px; height: auto;">';
                                } else {
                                    echo '<div class="file-icon">' . strtoupper($file_ext) . '</div>';
                                }
                                ?>
                                <a href="<?php echo esc_url($file); ?>" target="_blank" class="file-link">View File</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($application->status === 'pending') : ?>
                    <div class="application-actions">
                        <a href="<?php echo admin_url('admin.php?page=seller-applications&action=approve&id=' . $application->id); ?>" class="button button-primary">Approve & Grant Seller Role</a>
                        
                        <form method="post" action="<?php echo admin_url('admin.php?page=seller-applications&action=decline&id=' . $application->id); ?>" style="display: inline-block;">
                            <textarea name="decline_reason" placeholder="Reason for declining..." style="width: 100%; margin-bottom: 10px;"></textarea>
                            <button type="submit" class="button button-secondary">Decline</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

new SellerApplicationSystem();