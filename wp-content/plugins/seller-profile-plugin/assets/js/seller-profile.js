// Function for toggling profile settings dropdown
function toggleProfileSettings() {
    var content = document.querySelector('.profile-settings-content');
    var arrow = document.querySelector('.profile-settings-toggle .dropdown-arrow');
    if (content.style.display === 'block') {
        content.style.display = 'none';
        arrow.textContent = '▼';
    } else {
        content.style.display = 'block';
        arrow.textContent = '▲';
    }
}

// Preview profile picture before upload
document.getElementById('profile-picture').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.createElement('div');
            preview.className = 'profile-picture-preview';
            preview.innerHTML = '<p>' + sellerProfile.i18n.newProfilePreview + '</p><img src="' + e.target.result + '" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-top:10px;">';
            
            var existingPreview = document.querySelector('.profile-picture-preview');
            if (existingPreview) {
                existingPreview.replaceWith(preview);
            } else {
                var form = document.getElementById('profile-picture-form');
                form.insertBefore(preview, form.querySelector('button'));
            }
        };
        reader.readAsDataURL(file);
    }
});

// View Toggle Functionality
function toggleView(viewType) {
    const productList = document.querySelector('.product-list');
    const gridBtn = document.querySelector('.grid-view-btn');
    const listBtn = document.querySelector('.list-view-btn');
    
    if (viewType === 'grid') {
        productList.classList.add('grid-view');
        productList.classList.remove('list-view');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
        localStorage.setItem('sellerProfileView', 'grid');
    } else {
        productList.classList.add('list-view');
        productList.classList.remove('grid-view');
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
        localStorage.setItem('sellerProfileView', 'list');
    }
}

jQuery(document).ready(function($) {
    // Initialize view from localStorage
    const savedView = localStorage.getItem('sellerProfileView') || 'grid';
    toggleView(savedView);
    
    // Add event listeners to toggle buttons
    document.querySelector('.grid-view-btn').addEventListener('click', () => toggleView('grid'));
    document.querySelector('.list-view-btn').addEventListener('click', () => toggleView('list'));

    // Username availability check
    $('#new-username').on('input', function() {
        var username = $(this).val();
        var availability = $('#username-availability');
        
        if (username.length < 3) {
            availability.html('<span class="checking">' + sellerProfile.i18n.usernameMinLength + '</span>');
            return;
        }

        availability.html('<span class="checking">' + sellerProfile.i18n.checkingUsername + '</span>');

        $.ajax({
            url: sellerProfile.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_username_availability',
                username: username,
                security: sellerProfile.nonce
            },
            success: function(response) {
                if (response.available) {
                    availability.html('<span class="available">✓ ' + sellerProfile.i18n.usernameAvailable + '</span>');
                } else {
                    availability.html('<span class="unavailable">✗ ' + sellerProfile.i18n.usernameTaken + '</span>');
                }
            },
            error: function() {
                availability.html('<span class="error">' + sellerProfile.i18n.usernameCheckError + '</span>');
            }
        });
    });

    // Form validation
    $('#username-update-form').on('submit', function(e) {
        var username = $('#new-username').val();
        var currentUsername = $('#current-username').val();
        var availability = $('#username-availability');
        
        if (username.length < 3) {
            availability.html('<span class="error">' + sellerProfile.i18n.usernameMinLength + '</span>');
            e.preventDefault();
            return false;
        }
        
        if (username === currentUsername) {
            return true;
        }

        if (availability.find('.unavailable').length) {
            availability.html('<span class="error">' + sellerProfile.i18n.chooseAvailableUsername + '</span>');
            e.preventDefault();
            return false;
        }
    });
});

function toggleEditForm(id) {
    const form = document.getElementById('edit-form-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}