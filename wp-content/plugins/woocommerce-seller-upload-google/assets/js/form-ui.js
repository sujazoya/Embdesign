document.addEventListener('DOMContentLoaded', function () {
    // Loop through all custom file upload blocks
    document.querySelectorAll('.file-upload').forEach(uploadBlock => {
        const fileInput = uploadBlock.querySelector('input[type="file"]');
        const fileNameSpan = uploadBlock.querySelector('.file-name');
        const uploadBtn = uploadBlock.querySelector('.upload-btn');

        // Click "Choose File" button → trigger file input
        uploadBtn.addEventListener('click', function () {
            fileInput.click();
        });

        // On file change → show file name
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                fileNameSpan.textContent = fileInput.files[0].name;
            } else {
                fileNameSpan.textContent = 'No file chosen';
            }
        });
    });
});
