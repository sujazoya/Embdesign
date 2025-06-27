jQuery(document).ready(function($) {
    // File upload handling
    $('.the-design-order-upload-button').on('click', function(e) {
        e.preventDefault();
        $('#design_order_files').trigger('click');
    });
    
    $('#design_order_files').on('change', function() {
        var files = this.files;
        var fileList = $('.the-design-order-file-list');
        var fileInfo = $('.the-design-order-file-info');
        
        if (files.length > 0) {
            fileInfo.text(files.length + ' ' + theDesignOrder.i18n.files_selected);
            
            fileList.empty();
            $.each(files, function(i, file) {
                if (file.size > theDesignOrder.max_file_size) {
                    alert(theDesignOrder.i18n.file_too_large);
                    return;
                }
                
                var fileType = file.type;
                var allowedTypes = Object.values(theDesignOrder.allowed_file_types);
                
                if (!allowedTypes.includes(fileType)) {
                    alert(theDesignOrder.i18n.invalid_file_type);
                    return;
                }
                
                var listItem = $('<li>').html(
                    file.name + ' (' + formatFileSize(file.size) + ') ' +
                    '<a href="#" class="the-design-order-remove-file" data-file-name="' + file.name + '">' + 
                    theDesignOrder.i18n.remove_file + '</a>'
                );
                
                fileList.append(listItem);
            });
        } else {
            fileInfo.text(theDesignOrder.i18n.no_files_selected);
            fileList.empty();
        }
    });
    
    $('.the-design-order-file-list').on('click', '.the-design-order-remove-file', function(e) {
        e.preventDefault();
        var fileName = $(this).data('file-name');
        $(this).parent().remove();
        
        // Remove from file input
        var fileInput = $('#design_order_files')[0];
        var files = Array.from(fileInput.files);
        files = files.filter(function(file) {
            return file.name !== fileName;
        });
        
        var dataTransfer = new DataTransfer();
        files.forEach(function(file) {
            dataTransfer.items.add(file);
        });
        
        fileInput.files = dataTransfer.files;
        
        // Update file info
        if (fileInput.files.length === 0) {
            $('.the-design-order-file-info').text(theDesignOrder.i18n.no_files_selected);
        } else {
            $('.the-design-order-file-info').text(fileInput.files.length + ' ' + theDesignOrder.i18n.files_selected);
        }
    });
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});