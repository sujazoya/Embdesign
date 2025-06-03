jQuery(document).ready(function($) {
    // Initialize Dropzone for gallery images
    if (typeof Dropzone !== 'undefined') {
        // Gallery images dropzone
        new Dropzone('#gallery-dropzone', {
            url: apu_vars.ajax_url,
            paramName: 'file',
            maxFiles: apu_vars.max_files,
            maxFilesize: apu_vars.max_file_size / 1024 / 1024, // Convert to MB
            acceptedFiles: '.jpg,.jpeg,.png,.gif,.svg',
            addRemoveLinks: true,
            dictDefaultMessage: apu_vars.i18n.drop_files_here,
            dictFileTooBig: apu_vars.i18n.file_too_big,
            dictInvalidFileType: apu_vars.i18n.invalid_type,
            dictRemoveFile: apu_vars.i18n.remove_file,
            dictCancelUpload: apu_vars.i18n.cancel_upload,
            init: function() {
                this.on('sending', function(file, xhr, formData) {
                    formData.append('action', 'apu_upload_file');
                    formData.append('nonce', apu_vars.upload_nonce);
                    formData.append('file_type', 'image');
                });
                
                this.on('success', function(file, response) {
                    if (response.success) {
                        file.previewElement.classList.add('dz-success');
                        file.previewElement.dataset.fileId = response.data.id;
                        addFileToPreview(response.data, 'gallery');
                    } else {
                        file.previewElement.classList.add('dz-error');
                        showErrorMessage(response.data.message);
                    }
                });
                
                this.on('error', function(file, message) {
                    showErrorMessage(message);
                });
            }
        });

        // Design files dropzone
        new Dropzone('#design-dropzone', {
            url: apu_vars.ajax_url,
            paramName: 'file',
            maxFiles: apu_vars.max_files,
            maxFilesize: apu_vars.max_file_size / 1024 / 1024, // Convert to MB
            acceptedFiles: '.emb,.dst,.pes,.svg,.eps,.zip,.rar',
            addRemoveLinks: true,
            dictDefaultMessage: apu_vars.i18n.drop_files_here,
            dictFileTooBig: apu_vars.i18n.file_too_big,
            dictInvalidFileType: apu_vars.i18n.invalid_type,
            dictRemoveFile: apu_vars.i18n.remove_file,
            dictCancelUpload: apu_vars.i18n.cancel_upload,
            init: function() {
                this.on('sending', function(file, xhr, formData) {
                    formData.append('action', 'apu_upload_file');
                    formData.append('nonce', apu_vars.upload_nonce);
                    formData.append('file_type', 'design');
                });
                
                this.on('success', function(file, response) {
                    if (response.success) {
                        file.previewElement.classList.add('dz-success');
                        file.previewElement.dataset.fileId = response.data.id;
                        addFileToPreview(response.data, 'design');
                    } else {
                        file.previewElement.classList.add('dz-error');
                        showErrorMessage(response.data.message);
                    }
                });
                
                this.on('error', function(file, message) {
                    showErrorMessage(message);
                });
            }
        });
    }

    // Handle file previews
    function addFileToPreview(fileData, type) {
        const previewContainer = $(`#${type}-preview`);
        let previewHtml = '';
        
        if (fileData.type.startsWith('image/')) {
            previewHtml = `
                <div class="apu-preview-item" data-file-id="${fileData.id}">
                    <img src="${fileData.url}" alt="${fileData.name}">
                    <span class="apu-file-name">${fileData.name}</span>
                    <button type="button" class="apu-remove-file" aria-label="Remove file">×</button>
                </div>
            `;
        } else {
            previewHtml = `
                <div class="apu-preview-item" data-file-id="${fileData.id}">
                    <div class="apu-file-icon">📄</div>
                    <span class="apu-file-name">${fileData.name}</span>
                    <button type="button" class="apu-remove-file" aria-label="Remove file">×</button>
                </div>
            `;
        }
        
        previewContainer.append(previewHtml);
    }

    // Remove file from preview
    $(document).on('click', '.apu-remove-file', function() {
        const fileItem = $(this).closest('.apu-preview-item');
        const fileId = fileItem.data('file-id');
        
        // Send AJAX request to delete file
        $.ajax({
            url: apu_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'apu_remove_file',
                file_id: fileId,
                nonce: apu_vars.upload_nonce
            },
            success: function(response) {
                if (response.success) {
                    fileItem.remove();
                } else {
                    showErrorMessage(response.data.message);
                }
            },
            error: function() {
                showErrorMessage(apu_vars.i18n.upload_failed);
            }
        });
    });

    // Handle form submission
    $('#apu-product-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const statusMessage = $('#apu-status');
        
        // Disable submit button
        submitButton.prop('disabled', true);
        statusMessage.removeClass('success error').html('').hide();
        
        // Collect all data
        const formData = new FormData(form[0]);
        
        // Add gallery images
        const galleryImages = [];
        $('#gallery-preview .apu-preview-item').each(function() {
            galleryImages.push($(this).data('file-id'));
        });
        formData.append('gallery_images', galleryImages);
        
        // Add design files
        const designFiles = [];
        $('#design-preview .apu-preview-item').each(function() {
            designFiles.push($(this).data('file-id'));
        });
        formData.append('design_files', designFiles);
        
        // Submit via AJAX
        $.ajax({
            url: apu_vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    statusMessage.addClass('success').html(response.data.message).show();
                    
                    // Redirect after delay
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1500);
                    }
                } else {
                    statusMessage.addClass('error').html(response.data.message).show();
                    submitButton.prop('disabled', false);
                }
            },
            error: function() {
                statusMessage.addClass('error').html(apu_vars.i18n.upload_failed).show();
                submitButton.prop('disabled', false);
            }
        });
    });

    // Show error message
    function showErrorMessage(message) {
        const statusMessage = $('#apu-status');
        statusMessage.addClass('error').html(message).show();
        setTimeout(() => statusMessage.fadeOut(), 5000);
    }
});