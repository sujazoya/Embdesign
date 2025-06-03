jQuery(document).ready(function($) {
    // Handle wallet top-up form submission
    $('.wallet-topup-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var amount = parseFloat($('#topup_amount').val());
        
        // Validate amount
        if (isNaN(amount) {
            alert('Please enter a valid number');
            return false;
        }
        
        if (amount < 10) {
            alert('Minimum top-up amount is 10');
            return false;
        }
        
        // Show loading state
        var $button = $form.find('[type="submit"]');
        $button.prop('disabled', true).html(
            '<span class="spinner"></span> Redirecting to PayU...'
        );
        
        // Submit form after short delay to show loading state
        setTimeout(function() {
            $form.get(0).submit();
        }, 500);
    });
    
    // [Keep the checkout toggle function if you need it...]
});