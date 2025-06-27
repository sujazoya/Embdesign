jQuery(document).ready(function($) {
    // File upload preview
    $('.wcdu-file-upload input[type="file"]').on('change', function() {
        const $container = $(this).closest('.wcdu-file-upload');
        const fileName = this.files[0] ? this.files[0].name : 'No file selected';
        
        $container.find('.wcdu-file-name').text(fileName);
        
        if (this.files[0]) {
            $container.find('.wcdu-file-upload-label').css({
                'border-color': '#a0aec0',
                'background-color': '#edf2f7'
            });
        } else {
            $container.find('.wcdu-file-upload-label').css({
                'border-color': '#cbd5e0',
                'background-color': '#f8fafc'
            });
        }
    });

    // Form submission handling
    $('.wcdu-form').on('submit', function(e) {
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.prop('disabled', true).html(`
            <span class="wcdu-spinner"></span> ${wcduPro.i18n.uploading}
        `);
        
        // Validate required files
        const $fileInputs = $form.find('input[type="file"][required]');
        let isValid = true;
        
        $fileInputs.each(function() {
            if (!this.files || this.files.length === 0) {
                $(this).closest('.wcdu-upload-item').css({
                    'border-color': '#e53e3e',
                    'background-color': '#fff5f5'
                });
                isValid = false;
            }
        });
        
        if (!isValid) {
            $submitBtn.prop('disabled', false).html(originalText);
            return false;
        }
        
        // Continue with form submission if valid
        return true;
    });

    // Initialize tooltips
    $('[data-wcdu-tooltip]').each(function() {
        const $el = $(this);
        const tooltipText = $el.data('wcdu-tooltip');
        
        $el.tooltip({
            content: tooltipText,
            position: {
                my: "center bottom-10",
                at: "center top"
            },
            tooltipClass: "wcdu-tooltip",
            show: {
                effect: "fadeIn",
                duration: 200
            },
            hide: {
                effect: "fadeOut",
                duration: 200
            }
        });
    });

    // Tab functionality for settings/metadata
    $('.wcdu-tabs-nav button').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update active tab
        $('.wcdu-tabs-nav button').removeClass('active');
        $(this).addClass('active');
        
        // Show corresponding content
        $('.wcdu-tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');
    });

    // Auto-format design code
    $('input[name="design_code"]').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    // Show/hide advanced options
    $('.wcdu-toggle-advanced').on('click', function(e) {
        e.preventDefault();
        $('.wcdu-advanced-options').slideToggle();
        $(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });
});