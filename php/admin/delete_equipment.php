<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'user' || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$adminDepartment = $_SESSION['department'] ?? '';
if (empty($adminDepartment)) {
    echo json_encode(['success' => false, 'message' => 'Admin department not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
    exit;
}

try {
    // First check if the equipment exists and belongs to admin's department
    [$code, $json, $raw] = supabase_request('GET', 'equipments', [
        'equipment_id' => 'eq.' . $id,
        'department' => 'eq.' . $adminDepartment,
        'select' => 'equipment_id,department,equipment_img'
    ]);
    
    if ($code < 200 || $code >= 300 || empty($json)) {
        echo json_encode(['success' => false, 'message' => 'Equipment not found or not in your department']);
        exit;
    }
    
    $equipmentImg = $json[0]['equipment_img'] ?? null;
    
    // Delete equipment from database
    [$code2, $json2, $raw2] = supabase_request('DELETE', 'equipments', ['equipment_id' => 'eq.' . $id], null, ['Prefer: return=representation']);
    
    if ($code2 >= 200 && $code2 < 300) {
        // Delete associated image file
        if ($equipmentImg && file_exists('../../' . $equipmentImg)) {
            unlink('../../' . $equipmentImg);
        }
        echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete equipment', 'status' => $code2]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
