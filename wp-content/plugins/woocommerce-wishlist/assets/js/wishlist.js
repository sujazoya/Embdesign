jQuery(document).ready(function($) {
    // Handle add to wishlist button click
    $(document).on('click', '.wcwl-add-to-wishlist', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var product_id = $button.data('product-id');
        var nonce = $button.data('nonce');
        
        $button.addClass('loading');
        
        $.ajax({
            url: wcwl_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcwl_add_to_wishlist',
                product_id: product_id,
                nonce: wcwl_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'added') {
                        $button.addClass('added').text(wcwl_vars.removed_from_wishlist);
                        if ($button.hasClass('wcwl-add-to-wishlist-loop')) {
                            $button.html('♥');
                        }
                    } else {
                        $button.removeClass('added').text(wcwl_vars.added_to_wishlist);
                        if ($button.hasClass('wcwl-add-to-wishlist-loop')) {
                            $button.html('♡');
                        }
                    }
                    
                    // Update wishlist count in header if exists
                    var $count = $('.wcwl-wishlist-count');
                    if ($count.length) {
                        $count.text(response.data.count);
                    }
                    
                    // Show notice
                    if (typeof wc_add_notice === 'function') {
                        wc_add_notice(response.data.message, 'success');
                    }
                } else {
                    if (typeof wc_add_notice === 'function') {
                        wc_add_notice(response.data.message, 'error');
                    }
                }
            },
            complete: function() {
                $button.removeClass('loading');
            }
        });
    });
    
    // Handle remove from wishlist in wishlist page
    $(document).on('click', '.wcwl-remove-from-wishlist', function(e) {
        e.preventDefault();
        
        var $row = $(this).closest('tr');
        var product_id = $(this).data('product-id');
        var nonce = $(this).data('nonce');
        
        $row.addClass('loading');
        
        $.ajax({
            url: wcwl_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'wcwl_add_to_wishlist',
                product_id: product_id,
                nonce: wcwl_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Update wishlist count in header if exists
                        var $count = $('.wcwl-wishlist-count');
                        if ($count.length) {
                            $count.text(response.data.count);
                        }
                        
                        // Check if table is empty
                        if ($('.wcwl-wishlist-table tbody tr').length === 0) {
                            $('.wcwl-wishlist-table').replaceWith('<p class="wcwl-empty-wishlist">' + wcwl_vars.empty_wishlist + '</p>');
                        }
                    });
                    
                    // Show notice
                    if (typeof wc_add_notice === 'function') {
                        wc_add_notice(response.data.message, 'success');
                    }
                } else {
                    if (typeof wc_add_notice === 'function') {
                        wc_add_notice(response.data.message, 'error');
                    }
                }
            },
            complete: function() {
                $row.removeClass('loading');
            }
        });
    });
});