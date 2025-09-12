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
    echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
    exit;
}

// Prevent admin from deleting themselves
if ($id == ($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

try {
    // First check if the staff member exists and belongs to admin's department
    [$code, $json, $raw] = supabase_request('GET', 'user', [
        'user_id' => 'eq.' . $id,
        'department' => 'eq.' . $adminDepartment,
        'select' => 'user_id,department'
    ]);
    
    if ($code < 200 || $code >= 300 || empty($json)) {
        echo json_encode(['success' => false, 'message' => 'Staff member not found or not in your department']);
        exit;
    }
    
    [$code2, $json2, $raw2] = supabase_request('DELETE', 'user', ['user_id' => 'eq.' . $id], null, ['Prefer: return=representation']);
    
    if ($code2 >= 200 && $code2 < 300) {
        echo json_encode(['success' => true, 'message' => 'Staff member deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete staff member', 'status' => $code2]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
