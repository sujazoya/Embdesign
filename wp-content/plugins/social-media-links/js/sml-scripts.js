jQuery(document).ready(function($) {
    // Add hover effects for better interactivity
    $('.sml-card').hover(
        function() {
            // Mouse enter
            $(this).addClass('is-hovered');
        },
        function() {
            // Mouse leave
            $(this).removeClass('is-hovered');
        }
    );

    // Handle click events for analytics (optional)
    $('.sml-card').on('click', function(e) {
        var platform = $(this).attr('class').split(' ').find(cls => cls.startsWith('sml-'));
        platform = platform ? platform.replace('sml-', '') : 'unknown';
        
        // You could send this data to Google Analytics or your tracking system
        console.log('Social media link clicked:', platform);
        
        // Or use the WordPress REST API to track clicks
        /*
        $.ajax({
            url: sml_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'sml_track_click',
                platform: platform,
                nonce: sml_vars.nonce
            }
        });
        */
    });
});