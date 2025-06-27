jQuery(document).ready(function($) {
    const form = $('.aps-search-form');

    // Add close button to form
    if (!form.find('.aps-search-close').length) {
        form.prepend('<button class="aps-search-close" aria-label="Close">&times;</button>');
    }

    // Replace toggle icon
    $('.aps-search-toggle').html('<i class="fas fa-search"></i>');

    // Toggle search form
    $('.aps-search-toggle, .aps-search-close').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const isClosing = $(this).hasClass('aps-search-close');
        const toggleBtn = $('.aps-search-toggle');

        toggleBtn.toggleClass('active');

        if (isClosing || !toggleBtn.hasClass('active')) {
            form.fadeOut(300);
        } else {
            centerForm();
            form.fadeIn(300);
        }
    });

    // Close form when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.aps-search-toggle, .aps-search-form').length) {
            $('.aps-search-toggle').removeClass('active');
            form.fadeOut(300);
        }
    });

    // Reposition on scroll and resize
    $(window).on('scroll resize', function() {
        if (form.is(':visible')) {
            centerForm();
        }
    });

    // Center the form based on current viewport
    function centerForm() {
        const winW = $(window).width();
        const winH = $(window).height();
        const formW = form.outerWidth();
        const formH = form.outerHeight();

        form.css({
            position: 'fixed',
            top: (winH - formH) / 2 + 'px',
            left: (winW - formW) / 2 + 'px',
            zIndex: 9999
        });
    }

    // Initialize Select2 for designer filter if available
    if ($.fn.select2 && $('#aps-designer').length) {
        $('#aps-designer').select2({
            ajax: {
                url: aps_vars.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1,
                        action: 'aps_search_designers',
                        security: aps_vars.designer_search_nonce
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || [],
                        pagination: { more: data.pagination?.more || false }
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: aps_vars.searching_text,
            allowClear: true,
            width: '100%'
        });
    }

    // === Voice Search ===
    function isSpeechRecognitionSupported() {
        return 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
    }

    if (isSpeechRecognitionSupported()) {
        const RecognitionAPI = window.SpeechRecognition || window.webkitSpeechRecognition;

        initVoiceSearch('.aps-global-voice', '#aps-global-search', handleGlobalVoiceResult);
        initVoiceSearch('.aps-designer-voice', '#aps-designer', handleDesignerVoiceResult);
        initVoiceSearch('.aps-title-voice', '#aps-product-title', handleTitleVoiceResult);

        setupGlobalSearch();
    } else {
        $('.aps-voice-search-button').hide();
        $('.aps-voice-search-status')
            .text(aps_vars.voice_search_no_microphone)
            .addClass('active');
    }

    function initVoiceSearch(buttonSelector, targetSelector, resultHandler) {
        const button = $(buttonSelector);
        const status = button.siblings('.aps-voice-search-status');
        const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();

        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = document.documentElement.lang || 'en-US';

        let isListening = false;

        button.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isListening) {
                recognition.stop();
                return;
            }

            try {
                recognition.start();
                button.addClass('listening');
                status.text(aps_vars.voice_search_start).addClass('active');
                isListening = true;

                setTimeout(() => {
                    if (isListening) recognition.stop();
                }, 10000);
            } catch (err) {
                console.error('Voice error:', err);
                status.text(aps_vars.voice_search_error + err.message).addClass('active');
                setTimeout(() => status.removeClass('active'), 3000);
                isListening = false;
            }
        });

        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript.trim();
            status.text('"' + transcript + '"').addClass('active');

            if (targetSelector) {
                $(targetSelector).val(transcript).trigger('change');
            }

            if (resultHandler) {
                resultHandler(transcript);
            }

            setTimeout(() => status.removeClass('active'), 3000);
            isListening = false;
        };

        recognition.onerror = function(event) {
            let message = event.error === 'no-speech'
                ? aps_vars.voice_search_no_speech
                : aps_vars.voice_search_error + event.error;

            status.text(message).addClass('active');
            setTimeout(() => status.removeClass('active'), 3000);
            isListening = false;
        };

        recognition.onend = function() {
            button.removeClass('listening');
            isListening = false;
        };
    }

    // Handlers for voice result
    function handleGlobalVoiceResult(transcript) {
        $('#aps-global-search').val(transcript).trigger('input');
    }

    function handleDesignerVoiceResult(transcript) {
        const select = $('#aps-designer');
        if (select.data('select2')) {
            select.select2('open');
            const searchBox = select.data('select2').dropdown?.$search ||
                              select.data('select2').selection?.$search;
            if (searchBox) {
                searchBox.val(transcript).trigger('input');
            }
        } else {
            select.val(transcript).trigger('change');
        }
    }

    function handleTitleVoiceResult(transcript) {
        $('#aps-product-title').val(transcript);
    }

    // === Global Live Search ===
    function setupGlobalSearch() {
        const input = $('#aps-global-search');
        const resultBox = $('.aps-global-search-results');

        input.on('input', function() {
            const query = $(this).val().trim();
            resultBox.empty();

            if (query.length < 2) {
                resultBox.removeClass('active');
                return;
            }

            resultBox.addClass('active');

            searchCategories(query, resultBox);
            searchTags(query, resultBox);
            searchDesigners(query, resultBox);
            searchProducts(query, resultBox);
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.aps-global-search').length) {
                resultBox.removeClass('active');
            }
        });

        resultBox.on('click', '.aps-global-search-item', function() {
            const type = $(this).data('type');
            const value = $(this).data('value');
            const text = $(this).data('text');

            switch (type) {
                case 'category':
                    $('#aps-product-cat').val(value).trigger('change');
                    break;
                case 'tag':
                    $('#aps-tags').val(value).trigger('change');
                    break;
                case 'designer':
                    const select = $('#aps-designer');
                    if (select.data('select2')) {
                        const option = new Option(text, value, true, true);
                        select.append(option).trigger('change');
                    } else {
                        select.val(value).trigger('change');
                    }
                    break;
                case 'product':
                    $('#aps-product-title').val(text);
                    break;
            }

            input.val('');
            resultBox.removeClass('active');
        });
    }

    // === Helper filters ===
    function searchCategories(query, container) {
        const matches = $('#aps-product-cat option').map(function() {
            const text = $(this).text();
            if (text.toLowerCase().includes(query.toLowerCase())) {
                return { text, value: $(this).val() };
            }
        }).get();

        if (matches.length) {
            container.append('<div class="aps-global-search-section">Categories</div>');
            matches.forEach(item => {
                container.append(`
                    <div class="aps-global-search-item" data-type="category" data-value="${item.value}" data-text="${item.text}">
                        ${item.text}<div class="aps-global-search-item-type">Category</div>
                    </div>
                `);
            });
        }
    }

    function searchTags(query, container) {
        const matches = $('#aps-tags option').map(function() {
            const text = $(this).text();
            if (text.toLowerCase().includes(query.toLowerCase())) {
                return { text, value: $(this).val() };
            }
        }).get();

        if (matches.length) {
            container.append('<div class="aps-global-search-section">Tags</div>');
            matches.forEach(item => {
                container.append(`
                    <div class="aps-global-search-item" data-type="tag" data-value="${item.value}" data-text="${item.text}">
                        ${item.text}<div class="aps-global-search-item-type">Tag</div>
                    </div>
                `);
            });
        }
    }

    function searchDesigners(query, container) {
        $.ajax({
            url: aps_vars.ajax_url,
            method: 'GET',
            data: {
                action: 'aps_search_designers',
                q: query,
                security: aps_vars.designer_search_nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.results?.length) {
                    container.append('<div class="aps-global-search-section">Designers</div>');
                    response.results.forEach(d => {
                        container.append(`
                            <div class="aps-global-search-item" data-type="designer" data-value="${d.id}" data-text="${d.text}">
                                ${d.text}<div class="aps-global-search-item-type">Designer</div>
                            </div>
                        `);
                    });
                }
            }
        });
    }

    function searchProducts(query, container) {
        $.ajax({
            url: aps_vars.ajax_url,
            method: 'GET',
            data: {
                action: 'woocommerce_json_search_products',
                term: query,
                security: aps_vars.designer_search_nonce
            },
            dataType: 'json',
            success: function(response) {
                const entries = Object.entries(response || {});
                if (entries.length) {
                    container.append('<div class="aps-global-search-section">Designs</div>');
                    entries.forEach(([id, title]) => {
                        container.append(`
                            <div class="aps-global-search-item" data-type="product" data-value="${id}" data-text="${title}">
                                ${title}<div class="aps-global-search-item-type">Design</div>
                            </div>
                        `);
                    });
                }
            }
        });
    }
});
