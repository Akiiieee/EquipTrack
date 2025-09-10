<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

// Check if super admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';

try {
    // Get equipment count
    [$code, $rows] = supabase_select('equipments', [], 'equipment_id');
    $totalEquipment = is_array($rows) ? count($rows) : 0;
    
    echo json_encode([
        'success' => true,
        'equipment' => $totalEquipment
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
