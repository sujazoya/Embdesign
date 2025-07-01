<?php
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

function upload_file_to_google_drive($file) {
    // Added more robust check for file data
    if (empty($file) || !isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        error_log('WCSU: drive-upload.php: Invalid file data or temporary file missing. File: ' . print_r($file, true)); // Log the file array for debugging
        return false;
    }

    try {
        // Initialize Google Client
        $client = new Google_Client();
        
        // This line is correct for a service account JSON key
        $client->setAuthConfig(plugin_dir_path(__FILE__) . 'credentials.json');
        
        // Correct scope for file uploads. DRIVE_FILE is generally sufficient
        // if you want files owned by the service account.
        // If you need to access/upload to folders shared with the service account,
        // or if you want broader access, you might use Google_Service_Drive::DRIVE.
        $client->addScope(Google_Service_Drive::DRIVE_FILE);

        // --- IMPORTANT: REMOVED TOKEN.JSON AND REFRESH TOKEN LOGIC HERE ---
        // Service accounts authenticate directly with the JSON key, no user tokens needed.
        
        // Create Drive service
        $service = new Google_Service_Drive($client);

        // Get Folder ID from WordPress option.
        // Ensure this option is correctly set in your plugin's settings page.
        $folderId = get_option('wcsu_drive_folder_id');
        if (empty($folderId)) {
            error_log('WCSU: Google Drive Folder ID not set in plugin settings.');
            return false;
        }

        // File metadata
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => sanitize_file_name(basename($file['name'])), // Sanitize filename for safety
            'parents' => [$folderId] // Use the retrieved folder ID
        ]);

        // Read file content
        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            error_log('WCSU: drive-upload.php: Failed to read file content from temporary path: ' . $file['tmp_name']);
            return false;
        }

        // Upload file
        $uploadedFile = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $file['type'],
            'uploadType' => 'multipart',
            'fields' => 'id' // Request only ID for now, we'll construct the link later
        ]);

        $fileId = $uploadedFile->getId();
        
        // --- CRUCIAL: Set permissions for public access if the folder is not already public ---
        // For direct download links (uc?export=download) to work, the file MUST be publicly readable.
        // If your destination folder is ALREADY publicly shared with the service account as editor,
        // this might not be strictly necessary, but it's good practice for the file itself.
        $permission = new Google_Service_Drive_Permission([
            'type' => 'anyone',
            'role' => 'reader'
        ]);
        $service->permissions->create($fileId, $permission, ['fields' => 'id']); 

        // Return the direct download link
        // This URL works if the file (or its parent folder) has "anyone with link can view" permission.
        return "https://drive.google.com/uc?export=download&id=" . $fileId;

    } catch (Exception $e) {
        // Log the specific error message from the Google API for debugging
        error_log('WCSU: Google Drive upload error: ' . $e->getMessage());
        return false;
    }
}