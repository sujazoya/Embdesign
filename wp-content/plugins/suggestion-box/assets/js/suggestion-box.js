jQuery(document).ready(function($) {
    $('#suggestion-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var response = $('#suggestion-response');
        var submitBtn = form.find('button[type="submit"]');
        var originalBtnText = submitBtn.text();
        
        // Show loading state
        submitBtn.text('Submitting...').prop('disabled', true);
        response.removeClass('success error').hide();
        
        // Prepare data
        var formData = {
            action: 'submit_suggestion',
            nonce: suggestionBox.nonce,
            name: $('#suggestion-name').val(),
            email: $('#suggestion-email').val(),
            suggestion: $('#suggestion-text').val()
        };
        
        // Send AJAX request
        $.post(suggestionBox.ajax_url, formData, function(res) {
            if (res.success) {
                response.addClass('success').text(res.data.message).show();
                form[0].reset();
            } else {
                response.addClass('error').text(res.data.message).show();
            }
        }).fail(function() {
            response.addClass('error').text('An error occurred. Please try again.').show();
        }).always(function() {
            submitBtn.text(originalBtnText).prop('disabled', false);
        });
    });
});