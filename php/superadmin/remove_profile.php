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
    
    // Get current profile image
    $result = supabase_select('super_admins', ['profile_image'], ['super_admin_id' => $super_admin_id]);
    
    if ($result && count($result) > 0 && $result[0]['profile_image']) {
        $filename = $result[0]['profile_image'];
        $filepath = '../../uploads/avatars/' . $filename;
        
        // Remove file if it exists
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Update database to remove profile image
        $update_result = supabase_update('super_admins', ['profile_image' => null], ['super_admin_id' => $super_admin_id]);
        
        if ($update_result) {
            echo json_encode(['success' => true, 'message' => 'Profile picture removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'No profile picture to remove']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
