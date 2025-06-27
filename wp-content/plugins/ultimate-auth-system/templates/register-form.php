<form id="uas-register-form" class="uas-form">
    <div class="uas-form-group">
        <label for="uas-reg-username"><?php esc_html_e('Username', 'ultimate-auth'); ?></label>
        <input type="text" id="uas-reg-username" name="username" required>
    </div>

    <div class="uas-form-group">
        <label for="uas-reg-email"><?php esc_html_e('Email', 'ultimate-auth'); ?></label>
        <input type="email" id="uas-reg-email" name="email" required>
    </div>

    <div class="uas-form-group">
        <label for="uas-reg-password"><?php esc_html_e('Password', 'ultimate-auth'); ?></label>
        <input type="password" id="uas-reg-password" name="password" required>
        <div class="uas-password-strength">
            <div class="uas-strength-meter">
                <span class="uas-strength-bar"></span>
                <span class="uas-strength-bar"></span>
                <span class="uas-strength-bar"></span>
                <span class="uas-strength-bar"></span>
            </div>
            <span class="uas-strength-text"></span>
        </div>
    </div>

    <div class="uas-form-group">
        <label for="uas-reg-first-name"><?php esc_html_e('First Name', 'ultimate-auth'); ?></label>
        <input type="text" id="uas-reg-first-name" name="first_name">
    </div>

    <div class="uas-form-group">
        <label for="uas-reg-last-name"><?php esc_html_e('Last Name', 'ultimate-auth'); ?></label>
        <input type="text" id="uas-reg-last-name" name="last_name">
    </div>

    <input type="hidden" name="redirect" value="<?php echo esc_url($atts['redirect']); ?>">
    <input type="hidden" name="action" value="uas_register">
    <input type="hidden" name="security" value="<?php echo wp_create_nonce('uas-auth-nonce'); ?>">

    <button type="submit" class="uas-submit-button">
        <?php esc_html_e('Register', 'ultimate-auth'); ?>
    </button>

    <div class="uas-form-footer">
        <?php esc_html_e('Already have an account?', 'ultimate-auth'); ?>
        <a href="#" class="uas-switch-to-login">
            <?php esc_html_e('Log in here', 'ultimate-auth'); ?>
        </a>
    </div>
</form>