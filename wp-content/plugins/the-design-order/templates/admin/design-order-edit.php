<?php
/**
 * Admin edit screen template for design orders
 */

if (!defined('ABSPATH')) {
    exit;
}

global $post;

$order_id = $post->ID;
$name = get_post_meta($order_id, '_design_order_name', true);
$mobile = get_post_meta($order_id, '_design_order_mobile', true);
$email = get_post_meta($order_id, '_design_order_email', true);
$status = wp_get_object_terms($order_id, 'design_order_status', ['fields' => 'slugs']);
$status = !empty($status) ? $status[0] : 'new';
$files = get_post_meta($order_id, '_design_order_files', false);
$proposals = get_post_meta($order_id, '_design_order_proposals', true);
$proposals = is_array($proposals) ? $proposals : [];
$approved_proposal = get_post_meta($order_id, '_design_order_approved_proposal', true);
$deliverables = get_post_meta($order_id, '_design_order_deliverables', false);
$product_id = get_post_meta($order_id, '_design_order_product_id', true);
?>

<div class="wrap the-design-order-edit">
    <h1 class="wp-heading-inline"><?php _e('Edit Design Order', 'the-design-order'); ?></h1>
    
    <a href="<?php echo admin_url('edit.php?post_type=design_order'); ?>" class="page-title-action">
        <?php _e('Back to Orders', 'the-design-order'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <form method="post" action="" enctype="multipart/form-data">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" for="title"><?php _e('Title', 'the-design-order'); ?></label>
                            <input type="text" name="post_title" size="30" value="<?php echo esc_attr($post->post_title); ?>" id="title" spellcheck="true" autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Design Description', 'the-design-order'); ?></span></h2>
                        <div class="inside">
                            <?php wp_editor($post->post_content, 'content', [
                                'textarea_rows' => 10,
                                'media_buttons' => false,
                                'teeny' => true
                            ]); ?>
                        </div>
                    </div>
                    
                    <?php do_meta_boxes('design_order', 'normal', $post); ?>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <?php do_meta_boxes('design_order', 'side', $post); ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle proposal form
    $('.the-design-order-toggle-proposal-form').on('click', function(e) {
        e.preventDefault();
        $('.the-design-order-proposal-form').toggle();
    });
    
    // Handle proposal submission via AJAX
    $('#the-design-order-proposal-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'the_design_order_submit_proposal',
                post_id: <?php echo $order_id; ?>,
                amount: $form.find('#design_order_proposal_amount').val(),
                description: $form.find('#design_order_proposal_description').val(),
                nonce: '<?php echo wp_create_nonce('the-design-order-proposal'); ?>'
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Submitting...');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                    $button.prop('disabled', false).text('Submit Proposal');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).text('Submit Proposal');
            }
        });
    });
});
</script>