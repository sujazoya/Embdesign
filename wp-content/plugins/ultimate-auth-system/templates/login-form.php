<form id="uas-login-form" class="uas-form">
    <div class="uas-form-group">
        <label for="uas-login-username"><?php esc_html_e('Username or Email', 'ultimate-auth'); ?></label>
        <input type="text" id="uas-login-username" name="username" required>
    </div>

    <div class="uas-form-group">
        <label for="uas-login-password"><?php esc_html_e('Password', 'ultimate-auth'); ?></label>
        <input type="password" id="uas-login-password" name="password" required>
    </div>

    <?php if ($atts['show_remember_me']) : ?>
    <div class="uas-form-group uas-remember-me">
        <input type="checkbox" id="uas-login-remember" name="rememberme">
        <label for="uas-login-remember"><?php esc_html_e('Remember Me', 'ultimate-auth'); ?></label>
    </div>
    <?php endif; ?>

    <input type="hidden" name="redirect" value="<?php echo esc_url($atts['redirect']); ?>">
    <input type="hidden" name="action" value="uas_login">
    <input type="hidden" name="security" value="<?php echo wp_create_nonce('uas-auth-nonce'); ?>">

    <button type="submit" class="uas-submit-button">
        <?php esc_html_e('Log In', 'ultimate-auth'); ?>
    </button>

    <?php if ($atts['show_lost_password']) : ?>
    <div class="uas-form-footer">
        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="uas-lost-password">
            <?php esc_html_e('Lost your password?', 'ultimate-auth'); ?>
        </a>
    </div>
    <?php endif; ?>
</form>

<?php if ($atts['show_google_login'] && get_option('uas_google_client_id')) : ?>
<div class="uas-social-login">
    <div class="uas-separator">
        <span><?php esc_html_e('OR', 'ultimate-auth'); ?></span>
    </div>
    <div id="uas-google-login"></div>
</div>
<?php endif; ?>