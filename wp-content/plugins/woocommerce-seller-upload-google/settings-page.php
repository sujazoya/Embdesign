<?php
// Add settings page
add_action('admin_menu', 'wcsu_add_settings_page');
function wcsu_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        'Seller Upload Settings',
        'Seller Upload',
        'manage_options',
        'wcsu-settings',
        'wcsu_render_settings_page'
    );
    
    // Register settings
    add_action('admin_init', 'wcsu_register_settings');
}

function wcsu_register_settings() {
    register_setting('wcsu_settings_group', 'wcsu_drive_folder_id');
}

function wcsu_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>Seller Upload Settings</h1>
        
        <form method="post" action="options.php">
            <?php 
            settings_fields('wcsu_settings_group');
            do_settings_sections('wcsu-settings');
            $folder_id = get_option('wcsu_drive_folder_id');
            ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Google Drive Folder ID</th>
                    <td>
                        <input type="text" name="wcsu_drive_folder_id" 
                               value="<?php echo esc_attr($folder_id); ?>" class="regular-text">
                        <p class="description">The ID of your Google Drive folder (found in the folder URL)</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <h2>Setup Instructions</h2>
        <ol>
            <li>Go to the <a href="https://console.developers.google.com/" target="_blank">Google API Console</a></li>
            <li>Create a new project or select an existing one</li>
            <li>Enable the "Google Drive API"</li>
            <li>Create OAuth credentials (OAuth client ID) for "Web application"</li>
            <li>Add these authorized redirect URIs:
                <ul>
                    <li><?php echo admin_url('admin.php?page=wcsu-settings'); ?></li>
                    <li><?php echo home_url(); ?></li>
                </ul>
            </li>
            <li>Download the credentials JSON file</li>
            <li>Save it as <code>credentials.json</code> in the plugin directory</li>
            <li>Create a folder in Google Drive and share it with your service account email</li>
            <li>Copy the folder ID from the URL and enter it above</li>
        </ol>
    </div>
    <?php
}