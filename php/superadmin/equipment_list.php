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
    // Get all equipment with department information
    [$code, $rows] = supabase_select('equipments', [], 'equipment_id,equipment_name,quantity,equipment_img,department,status,date_added', ['order' => 'equipment_name.asc']);
    
    if ($code >= 200 && $code < 300) {
        $equipment = is_array($rows) ? $rows : [];
        echo json_encode([
            'success' => true,
            'equipment' => $equipment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch equipment list'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
