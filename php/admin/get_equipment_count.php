<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

try {
    // Get total equipment count for admin's department
    [$code, $json, $raw] = supabase_request('GET', 'equipments', [
        'department' => 'eq.' . $adminDepartment,
        'select' => '*'
    ]);
    $total = ($code >= 200 && $code < 300 && is_array($json)) ? count($json) : 0;
    
    // Get available equipment count for admin's department
    [$code2, $json2, $raw2] = supabase_request('GET', 'equipments', [
        'department' => 'eq.' . $adminDepartment,
        'status' => 'eq.available',
        'select' => '*'
    ]);
    $available = ($code2 >= 200 && $code2 < 300 && is_array($json2)) ? count($json2) : 0;
    
    echo json_encode(['success' => true, 'total' => $total, 'available' => $available]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
