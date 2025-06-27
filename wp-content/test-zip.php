<?php
require_once('wp-load.php');

$zip = new ZipArchive();
$filename = WP_CONTENT_DIR . '/uploads/uag-apps/test-' . time() . '.zip';

if ($zip->open($filename, ZipArchive::CREATE) === TRUE) {
    $zip->addFromString('test.txt', 'This is a test file');
    $zip->close();
    echo "Success! File created at: $filename";
} else {
    echo "Failed to create ZIP. Error: " . print_r(error_get_last(), true);
}