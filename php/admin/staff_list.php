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
    echo json_encode([
        'success' => false, 
        'message' => 'Admin department not found in session',
        'debug' => [
            'department' => $adminDepartment,
            'session_department' => $_SESSION['department'] ?? 'not set',
            'username' => $_SESSION['username'] ?? 'not set',
            'role' => $_SESSION['role'] ?? 'not set'
        ]
    ]);
    exit;
}

try {
    [$code, $json, $raw] = supabase_request('GET', 'user', [
        'role' => 'eq.staff',
        'department' => 'eq.' . $adminDepartment,
        'select' => 'user_id,username,email,role,department'
    ]);
    
    if ($code >= 200 && $code < 300) {
        $staff = array_map(function($user) {
            return [
                'id' => $user['user_id'] ?? 0,
                'name' => $user['username'] ?? '',
                'email' => $user['email'] ?? '',
                'role' => $user['role'] ?? '',
                'department' => $user['department'] ?? ''
            ];
        }, $json ?? []);
        
        echo json_encode(['success' => true, 'staff' => $staff]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to load staff', 
            'status' => $code,
            'debug' => [
                'admin_department' => $adminDepartment,
                'raw_response' => $raw,
                'json_response' => $json
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
