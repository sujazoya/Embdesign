<?php
/**
 * Custom user fields for designers
 */

class ED_User_Fields {

    /**
     * Initialize user fields
     */
    public static function init() {
        // Add fields to user profile
        add_action('show_user_profile', array(__CLASS__, 'add_user_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'add_user_fields'));

        // Save fields
        add_action('personal_options_update', array(__CLASS__, 'save_user_fields'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_user_fields'));

        // Add designer verification status column to users list
        add_filter('manage_users_columns', array(__CLASS__, 'add_verification_column'));
        add_filter('manage_users_custom_column', array(__CLASS__, 'show_verification_column'), 10, 3);
    }

    /**
     * Add custom fields to user profile
     */
    public static function add_user_fields($user) {
        // Only show for users with author capabilities
        if (!user_can($user, 'edit_posts')) {
            return;
        }

        // Get current values
        $is_verified = get_user_meta($user->ID, 'seller_verified', true);
        $facebook = get_user_meta($user->ID, 'facebook', true);
        $instagram = get_user_meta($user->ID, 'instagram', true);
        $pinterest = get_user_meta($user->ID, 'pinterest', true);
        $youtube = get_user_meta($user->ID, 'youtube', true);
        ?>
        <h3><?php esc_html_e('Designer Profile Information', 'embroidery-designers'); ?></h3>

        <table class="form-table">
            <tr>
                <th>
                    <label for="seller_verified"><?php esc_html_e('Verified Designer', 'embroidery-designers'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="seller_verified" id="seller_verified" value="1" <?php checked($is_verified, 1); ?> />
                    <span class="description"><?php esc_html_e('Check this if this designer is verified.', 'embroidery-designers'); ?></span>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="facebook"><?php esc_html_e('Facebook URL', 'embroidery-designers'); ?></label>
                </th>
                <td>
                    <input type="url" name="facebook" id="facebook" value="<?php echo esc_attr($facebook); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="instagram"><?php esc_html_e('Instagram URL', 'embroidery-designers'); ?></label>
                </th>
                <td>
                    <input type="url" name="instagram" id="instagram" value="<?php echo esc_attr($instagram); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="pinterest"><?php esc_html_e('Pinterest URL', 'embroidery-designers'); ?></label>
                </th>
                <td>
                    <input type="url" name="pinterest" id="pinterest" value="<?php echo esc_attr($pinterest); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="youtube"><?php esc_html_e('YouTube URL', 'embroidery-designers'); ?></label>
                </th>
                <td>
                    <input type="url" name="youtube" id="youtube" value="<?php echo esc_attr($youtube); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save custom user fields
     */
    public static function save_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Save verification status
        update_user_meta($user_id, 'seller_verified', isset($_POST['seller_verified']) ? 1 : 0);

        // Save social media URLs
        $social_fields = array('facebook', 'instagram', 'pinterest', 'youtube');
        foreach ($social_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($user_id, $field, esc_url_raw($_POST[$field]));
            }
        }
    }

    /**
     * Add verification column to users list
     */
    public static function add_verification_column($columns) {
        $columns['seller_verified'] = __('Verified', 'embroidery-designers');
        return $columns;
    }

    /**
     * Show verification status in users list
     */
    public static function show_verification_column($value, $column_name, $user_id) {
        if ('seller_verified' === $column_name) {
            $is_verified = get_user_meta($user_id, 'seller_verified', true);
            return $is_verified ? '<span class="dashicons dashicons-yes" style="color: #46b450;"></span>' : '<span class="dashicons dashicons-no" style="color: #dc3232;"></span>';
        }
        return $value;
    }
}