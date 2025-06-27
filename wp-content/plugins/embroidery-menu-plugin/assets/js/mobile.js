jQuery(document).ready(function($) {
    // Mobile menu toggle
    $('.mobile-menu-toggle').on('click', function() {
        $('#embroidery-mobile-menu').addClass('active');
        $('.mobile-menu-overlay').addClass('active');
    });
    
    // Close mobile menu
    $('.mobile-menu-close, .mobile-menu-overlay').on('click', function() {
        $('#embroidery-mobile-menu').removeClass('active');
        $('.mobile-menu-overlay').removeClass('active');
    });
    
    // Prevent closing when clicking inside menu
    $('#embroidery-mobile-menu').on('click', function(e) {
        e.stopPropagation();
    });
});