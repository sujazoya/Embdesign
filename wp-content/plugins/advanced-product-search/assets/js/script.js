jQuery(document).ready(function($) {
    // Toggle search form visibility
    $('body').on('click', '.aps-search-toggle', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.aps-search-container');
        var $form = $container.find('.aps-search-form');
        
        $(this).toggleClass('active');
        $form.slideToggle(300);
        
        // Close when clicking outside
        if ($form.is(':visible')) {
            $(document).on('click.apssearch', function(e) {
                if (!$(e.target).closest('.aps-search-container').length) {
                    $container.find('.aps-search-toggle').removeClass('active');
                    $form.slideUp(300);
                    $(document).off('click.apssearch');
                }
            });
        } else {
            $(document).off('click.apssearch');
        }
    });
    
    // Initialize Select2 if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.aps-select').select2({
            placeholder: aps_vars.select_placeholder || 'Select an option',
            allowClear: true,
            width: '100%'
        });
    }
    
    // Handle form submission
    $('.aps-search-form form').on('submit', function(e) {
        // You can add any pre-submission logic here
        // For example, validate fields or add loading indicator
    });
});