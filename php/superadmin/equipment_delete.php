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
    $equipmentId = intval($_POST['equipment_id'] ?? 0);
    
    // Validation
    if ($equipmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
        exit;
    }
    
    // Get equipment info before deletion for logging
    [$codeCheck, $rowsCheck] = supabase_select('equipments', ['equipment_id' => 'eq.' . $equipmentId], 'equipment_name,equipment_img', ['limit' => 1]);
    if (!is_array($rowsCheck) || count($rowsCheck) === 0) {
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit;
    }
    
    $equipment = $rowsCheck[0];
    
    // Delete equipment
    [$code, $json, $raw] = supabase_request('DELETE', 'equipments', ['equipment_id' => 'eq.' . $equipmentId]);
    
    if ($code >= 200 && $code < 300) {
        // Delete associated image file if exists
        if (!empty($equipment['equipment_img']) && file_exists('../../' . $equipment['equipment_img'])) {
            unlink('../../' . $equipment['equipment_img']);
        }
        
        log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'delete_equipment', [
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipment['equipment_name']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete equipment']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
