<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['super_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $super_admin_id = $_SESSION['super_admin_id'];
    
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image uploaded']);
        exit;
    }
    
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'superadmin_' . $super_admin_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Remove old profile image if exists
    $old_result = supabase_select('super_admins', ['profile_image'], ['super_admin_id' => $super_admin_id]);
    if ($old_result && count($old_result) > 0 && $old_result[0]['profile_image']) {
        $old_file = $upload_dir . $old_result[0]['profile_image'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Update database
        $result = supabase_update('super_admins', ['profile_image' => $filename], ['super_admin_id' => $super_admin_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            unlink($filepath); // Remove file if database update failed
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
