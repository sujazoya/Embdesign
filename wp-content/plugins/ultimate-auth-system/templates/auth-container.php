<div class="uas-auth-container" data-default-view="<?php echo esc_attr($atts['default_view']); ?>">
    <div class="uas-auth-tabs">
        <button class="uas-tab-button uas-login-tab active" data-tab="login">
            <?php esc_html_e('Login', 'ultimate-auth'); ?>
        </button>
        <button class="uas-tab-button uas-register-tab" data-tab="register">
            <?php esc_html_e('Register', 'ultimate-auth'); ?>
        </button>
    </div>

    <div class="uas-auth-content">
        <!-- Login Form -->
        <div class="uas-auth-form uas-login-form active" data-form="login">
            <?php include UAS_PATH . 'templates/login-form.php'; ?>
        </div>

        <!-- Register Form -->
        <div class="uas-auth-form uas-register-form" data-form="register">
            <?php include UAS_PATH . 'templates/register-form.php'; ?>
        </div>
    </div>

    <div class="uas-message"></div>
</div>