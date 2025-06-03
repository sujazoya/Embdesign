jQuery(document).ready(function($) {
    // Initialize tag input
    if ($().select2) {
        $('.wcsu-tags-input').select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: wcsu_tags_vars.placeholder,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        term: params.term,
                        action: 'wcsu_get_existing_tags',
                        security: wcsu_tags_vars.nonce
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            createTag: function(params) {
                return {
                    id: params.term,
                    text: params.term,
                    newTag: true
                };
            }
        });
    } else {
        // Fallback for when Select2 is not available
        $('.wcsu-tags-input').on('keydown', function(e) {
            if (e.keyCode === 13 || e.keyCode === 188) {
                e.preventDefault();
                var value = $(this).val().trim();
                if (value) {
                    var tags = value.split(',');
                    $(this).val(tags.join(', ') + ', ');
                }
            }
        });
    }
});