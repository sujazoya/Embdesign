<?php
/**
 * Must-Use plugin to ensure EMB Design Order loads correctly
 */
 
add_action('plugins_loaded', function() {
    if (defined('EMB_DESIGN_ORDER_PLUGIN_DIR')) {
        require_once EMB_DESIGN_ORDER_PLUGIN_DIR . 'includes/class-emb-design-order-forms.php';
        EMB_Design_Order_Forms::init();
    }
}, 1);