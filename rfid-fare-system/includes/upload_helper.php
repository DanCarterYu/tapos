<?php
// includes/upload_helper.php
// Helper functions for file uploads

function uploadReceiptImage($file, $request_id = null) {
    // Define allowed file types
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_file_size = 2 * 1024 * 1024; // 2MB
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed: ' . $file['error']];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Please upload JPG or PNG images only.'];
    }
    
    // Check file size
    if ($file['size'] > $max_file_size) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is 2MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'receipt_' . date('Ymd_His') . ($request_id ? '_' . $request_id : '') . '.' . $extension;
    
    // Define upload path
    $upload_dir = __DIR__ . '/../assets/uploads/requests/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Move file to upload directory
    $destination = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => 'assets/uploads/requests/' . $filename,
            'message' => 'File uploaded successfully'
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

function getReceiptImageUrl($filepath) {
    // Convert filepath to URL
    return '../' . $filepath;
}

function deleteReceiptImage($filepath) {
    // Delete image file from server
    $full_path = __DIR__ . '/../' . $filepath;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}
?>