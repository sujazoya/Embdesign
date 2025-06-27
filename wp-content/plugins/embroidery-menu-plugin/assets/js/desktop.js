jQuery(document).ready(function($) {
    // Desktop menu hover effects
    $('.embroidery-desktop-menu .menu-item').hover(
        function() {
            $(this).addClass('hover');
        },
        function() {
            $(this).removeClass('hover');
        }
    );
    
    // Add any other desktop-specific functionality here
});