jQuery(document).ready(function($) {
    // Handle search form submission
    $('.ed-search-form').on('submit', function(e) {
        e.preventDefault();
        const searchInput = $(this).find('.ed-search-input');
        const searchQuery = searchInput.val().trim();
        
        if (searchQuery.length > 0) {
            window.location.href = $(this).attr('action') + '?designer_search=' + encodeURIComponent(searchQuery);
        } else {
            window.location.href = $(this).attr('action');
        }
    });

    // AJAX search functionality (optional enhancement)
    $('.ed-search-input').on('input', function() {
        const searchQuery = $(this).val().trim();
        
        if (searchQuery.length > 2) {
            $.ajax({
                url: ed_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ed_search_designers',
                    search: searchQuery,
                    nonce: ed_ajax.nonce
                },
                beforeSend: function() {
                    // Show loading indicator
                },
                success: function(response) {
                    if (response.success) {
                        // Update designers grid with results
                        $('.ed-designers-grid').html(response.data.html);
                    }
                }
            });
        } else if (searchQuery.length === 0) {
            // Reset to show all designers
            $.ajax({
                url: ed_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ed_reset_designers',
                    nonce: ed_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.ed-designers-grid').html(response.data.html);
                    }
                }
            });
        }
    });

    // Product card hover effect
    $('.ed-product-card').hover(
        function() {
            $(this).find('.ed-product-image-link img').css('transform', 'scale(1.05)');
        },
        function() {
            $(this).find('.ed-product-image-link img').css('transform', 'scale(1)');
        }
    );
});