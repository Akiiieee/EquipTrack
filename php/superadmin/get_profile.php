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
    
    // Get super admin details
    [$code, $rows] = supabase_select('super_admin', ['super_admin_id' => 'eq.' . $super_admin_id], 'username,profile_image', ['limit' => 1]);
    
    if (is_array($rows) && count($rows) > 0) {
        $admin = $rows[0];
        echo json_encode([
            'success' => true,
            'username' => $admin['username'],
            'profile_image' => $admin['profile_image']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Admin not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
