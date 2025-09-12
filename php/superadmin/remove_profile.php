<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['super_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $super_admin_id = $_SESSION['super_admin_id'];
    
    // Get current profile image
    [$code, $rows] = supabase_select('super_admin', ['super_admin_id' => 'eq.' . $super_admin_id], 'profile_image', ['limit' => 1]);
    
    if (is_array($rows) && count($rows) > 0 && !empty($rows[0]['profile_image'])) {
        $filename = $rows[0]['profile_image'];
        $filepath = '../../uploads/avatars/' . $filename;
        
        // Remove file if it exists
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Update database to remove profile image
        [$update_code, $update_rows] = supabase_update('super_admin', ['super_admin_id' => 'eq.' . $super_admin_id], ['profile_image' => null]);
        
        if ($update_code >= 200 && $update_code < 300) {
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
