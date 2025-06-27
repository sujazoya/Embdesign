jQuery(document).ready(function($) {
    // Remove file
    $('.the-design-order-file-list, .the-design-order-deliverable-list').on('click', '.the-design-order-remove-file, .the-design-order-remove-deliverable', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var isDeliverable = $button.hasClass('the-design-order-remove-deliverable');
        var fileId = $button.data('file-id');
        var postId = $('#post_ID').val();
        
        if (!confirm(theDesignOrderAdmin.i18n.confirm_delete)) {
            return;
        }
        
        $.ajax({
            url: theDesignOrderAdmin.ajax_url,
            type: 'POST',
            data: {
                action: isDeliverable ? 'the_design_order_admin_remove_deliverable' : 'the_design_order_admin_remove_file',
                post_id: postId,
                file_id: fileId,
                nonce: theDesignOrderAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('li').remove();
                } else {
                    alert(theDesignOrderAdmin.i18n.error);
                }
            },
            error: function() {
                alert(theDesignOrderAdmin.i18n.error);
            }
        });
    });
    
    // Approve proposal
    $('.the-design-order-proposals').on('click', '.the-design-order-approve-proposal', function(e) {
        e.preventDefault();
        
        var proposalIndex = $(this).data('proposal-index');
        $('#design_order_approve_proposal').val(proposalIndex);
        $(this).closest('form').submit();
    });
    
    // Complete order
    $('.the-design-order-deliverables').on('click', '.the-design-order-complete-order', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to mark this order as completed?')) {
            return;
        }
        
        var postId = $('#post_ID').val();
        var $button = $(this);
        
        $.ajax({
            url: theDesignOrderAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'the_design_order_admin_complete_order',
                post_id: postId,
                nonce: theDesignOrderAdmin.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(theDesignOrderAdmin.i18n.error);
                    $button.prop('disabled', false).text('Mark as Completed');
                }
            },
            error: function() {
                alert(theDesignOrderAdmin.i18n.error);
                $button.prop('disabled', false).text('Mark as Completed');
            }
        });
    });
});