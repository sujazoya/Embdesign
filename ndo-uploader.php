<?php
// Secure the uploader: require WordPress environment and admin login
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Set upload directory
$upload_dir = wp_upload_dir();
$target_dir = $upload_dir['basedir'] . '/ndo_documents/';
$target_url = $upload_dir['baseurl'] . '/ndo_documents/';

if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['file_to_upload']['name'])) {
    $file = $_FILES['file_to_upload'];
    $filename = basename($file['name']);
    $target_file = $target_dir . $filename;
    $filetype = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','zip','psd','ai','eps'];
    if (!in_array($filetype, $allowed)) {
        echo "<div style='color:red;'>Error: File type not allowed.</div>";
    } elseif (move_uploaded_file($file['tmp_name'], $target_file)) {
        echo "<div style='color:green;'>Upload successful!</div>";
        echo "<a href='{$target_url}{$filename}' target='_blank'>View File</a>";
    } else {
        echo "<div style='color:red;'>Error uploading file.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>NDO Document Uploader</title>
    <style>
        body {
            font-family: Arial;
            margin: 40px;
        }
        form {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 6px;
            max-width: 500px;
        }
        input[type="file"] {
            margin-bottom: 20px;
        }
        input[type="submit"] {
            background: #2271b1;
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2>NDO Document Uploader</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file_to_upload" required><br>
        <input type="submit" value="Upload File">
    </form>
</body>
</html>
