jQuery(document).ready(function($) {
    // Toggle file type options
    $('.apu-file-type-toggle').on('change', function() {
        const type = $(this).val();
        const isChecked = $(this).is(':checked');
        
        $(`.apu-file-type-${type}`).prop('disabled', !isChecked);
    });

    // Initialize file type toggles
    $('.apu-file-type-toggle').each(function() {
        const type = $(this).val();
        const isChecked = $(this).is(':checked');
        
        $(`.apu-file-type-${type}`).prop('disabled', !isChecked);
    });

    // Bulk actions
    $('.apu-bulk-action').on('click', function(e) {
        e.preventDefault();
        
        const action = $(this).data('action');
        const confirmed = confirm(`Are you sure you want to ${action} all files?`);
        
        if (confirmed) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'apu_bulk_action',
                    bulk_action: action,
                    nonce: apu_admin_vars.nonce
                },
                beforeSend: function() {
                    $('#apu-bulk-action-result').removeClass('notice-success notice-error').html('').hide();
                },
                success: function(response) {
                    const noticeClass = response.success ? 'notice-success' : 'notice-error';
                    $('#apu-bulk-action-result').addClass(noticeClass).html(response.data.message).show();
                    
                    if (response.success) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                }
            });
        }
    });

    // Regenerate thumbnails
    $('.apu-regenerate-thumbs').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('This will regenerate thumbnails for all uploaded images. Continue?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'apu_regenerate_thumbs',
                    nonce: apu_admin_vars.nonce
                },
                beforeSend: function() {
                    $('#apu-regenerate-result').removeClass('notice-success notice-error').html('').hide();
                },
                success: function(response) {
                    const noticeClass = response.success ? 'notice-success' : 'notice-error';
                    $('#apu-regenerate-result').addClass(noticeClass).html(response.data.message).show();
                }
            });
        }
    });
});