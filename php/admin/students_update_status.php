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
$rfid = trim($input['rfid'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

if (empty($rfid)) {
    echo json_encode(['success' => false, 'message' => 'RFID is required']);
    exit;
}

try {
    // First check if the student exists and belongs to admin's department
    [$code, $json, $raw] = supabase_request('GET', 'students', [
        'id' => 'eq.' . $id,
        'program' => 'eq.' . $adminDepartment,
        'select' => 'id,program'
    ]);
    
    if ($code < 200 || $code >= 300 || empty($json)) {
        echo json_encode(['success' => false, 'message' => 'Student not found or not in your department']);
        exit;
    }
    
    // Check if RFID already exists for another student
    [$code2, $json2, $raw2] = supabase_request('GET', 'students', [
        'rfid' => 'eq.' . $rfid,
        'id' => 'neq.' . $id,
        'select' => 'id'
    ]);
    
    if ($code2 >= 200 && $code2 < 300 && !empty($json2)) {
        echo json_encode(['success' => false, 'message' => 'RFID already assigned to another student']);
        exit;
    }
    
    // Update student with RFID and set status to registered
    $updateData = [
        'rfid' => $rfid,
        'status' => 'registered'
    ];
    
    [$code3, $json3, $raw3] = supabase_request('PATCH', 'students', ['id' => 'eq.' . $id], $updateData, ['Prefer: return=representation']);
    
    if ($code3 >= 200 && $code3 < 300) {
        echo json_encode(['success' => true, 'message' => 'Student registration completed successfully']);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to complete registration', 
            'status' => $code3,
            'debug' => [
                'raw_response' => $raw3,
                'json_response' => $json3
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
