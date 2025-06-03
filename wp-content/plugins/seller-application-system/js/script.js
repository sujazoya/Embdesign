jQuery(document).ready(function($) {
    $('#seller-application').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'submit_seller_application');
        formData.append('nonce', seller_application.nonce);
        
        $.ajax({
            url: seller_application.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.submit-application').prop('disabled', true).text('Submitting...');
            },
            success: function(response) {
                var responseDiv = $('#seller-application-response');
                responseDiv.removeClass('success error');
                
                if (response.success) {
                    responseDiv.addClass('success').html(response.data.message);
                    form.hide();
                } else {
                    responseDiv.addClass('error').html(response.data.message);
                }
            },
            complete: function() {
                $('.submit-application').prop('disabled', false).text('Submit Application');
            }
        });
    });
});