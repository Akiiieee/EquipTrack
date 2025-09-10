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
    
    // Get super admin details
    $result = supabase_select('super_admins', ['username', 'profile_image'], ['super_admin_id' => $super_admin_id]);
    
    if ($result && count($result) > 0) {
        $admin = $result[0];
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
