// Google Sign-In initialization
if (typeof uas_vars !== 'undefined' && uas_vars.google_client_id) {
    // Function to show status messages
    const showMessage = (message, type = 'info') => {
        const $message = $('.uas-message');
        $message
            .removeClass('uas-error uas-success uas-info')
            .addClass(`uas-${type}`)
            .text(message)
            .stop(true, true)
            .fadeIn();
        
        if (type !== 'info') {
            setTimeout(() => $message.fadeOut(), 5000);
        }
    };

    // Function to handle Google auth response
    window.handleGoogleAuth = (response) => {
        showMessage(uas_vars.processing_text || 'Authenticating with Google...', 'info');

        if (!response?.credential) {
            showMessage(uas_vars.google_error || 'Invalid Google response: Missing credential', 'error');
            return;
        }

        $.ajax({
            type: 'POST',
            url: uas_vars.ajax_url,
            data: {
                action: 'uas_google_auth',
                credential: response.credential,
                security: uas_vars.auth_nonce
            },
            dataType: 'json',
            success: (response) => {
                if (response?.success) {
                    const redirectUrl = response.data?.redirect || uas_vars.home_url;
                    window.location.href = redirectUrl;
                } else {
                    const errorMsg = response?.data?.message || 
                                  uas_vars.google_error || 
                                  'Authentication failed';
                    showMessage(errorMsg, 'error');
                }
            },
            error: (xhr) => {
                let errorMsg = uas_vars.google_error || 'Server error during authentication';
                
                try {
                    if (xhr.responseJSON?.data?.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (xhr.statusText) {
                        errorMsg += ` (${xhr.statusText})`;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                showMessage(errorMsg, 'error');
            }
        });
    };

    // Function to initialize Google Sign-In
    const initGoogleSignIn = (attempt = 1) => {
        if (typeof google?.accounts?.id !== 'undefined') {
            try {
                google.accounts.id.initialize({
                    client_id: uas_vars.google_client_id,
                    callback: handleGoogleAuth,
                    ux_mode: 'popup'
                });

                const buttonContainer = document.getElementById('uas-google-login');
                if (buttonContainer) {
                    google.accounts.id.renderButton(buttonContainer, {
                        theme: 'filled_blue',
                        size: 'large',
                        width: buttonContainer.offsetWidth,
                        text: 'signin_with'
                    });
                    
                    // Optional: Add prompt to show One Tap UI
                    if (uas_vars.google_one_tap === '1') {
                        google.accounts.id.prompt();
                    }
                }
            } catch (e) {
                console.error('Google Sign-In initialization failed:', e);
                showMessage('Failed to initialize Google Sign-In', 'error');
            }
        } else if (attempt < 3) {
            setTimeout(() => initGoogleSignIn(attempt + 1), 500 * attempt);
        } else {
            showMessage('Failed to load Google Sign-In library', 'error');
        }
    };

    // Load Google library if not already loaded
    if (typeof google === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://accounts.google.com/gsi/client';
        script.async = true;
        script.defer = true;
        script.onerror = () => showMessage('Failed to load Google Sign-In library', 'error');
        document.head.appendChild(script);
    }

    // Start initialization when DOM is ready
    if (document.readyState === 'complete') {
        initGoogleSignIn();
    } else {
        window.addEventListener('DOMContentLoaded', initGoogleSignIn);
    }
}