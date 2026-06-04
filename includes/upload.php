<?php
// File upload utility functions

function handleImageUpload($file, $targetDir = '../uploads/') {
    // Ensure upload directory exists
    $absoluteDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0777, true);
    }
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed'];
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_') . '.' . $extension;
    $targetPath = $absoluteDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    } else {
        return ['success' => false, 'message' => 'Failed to save uploaded file'];
    }
}

function deleteImage($imagePath) {
    if (file_exists($imagePath)) {
        return unlink($imagePath);
    }
    return false;
}

function getImageUrl($filename, $baseUrl = '') {
    if (empty($filename)) {
        return '';
    }
    return $baseUrl . 'uploads/' . $filename;
}
?>
