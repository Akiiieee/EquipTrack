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

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
    exit;
}

if (empty($name) || empty($email) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields except password are required']);
    exit;
}

// Force department to be the admin's department
$department = $adminDepartment;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (!in_array($role, ['admin', 'staff'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
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
    
    // Check if email already exists for another user
    [$code2, $json2, $raw2] = supabase_request('GET', 'user', ['email' => 'eq.' . $email, 'user_id' => 'neq.' . $id, 'select' => 'user_id']);
    
    if ($code2 >= 200 && $code2 < 300 && !empty($json2)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
        exit;
    }
    
    // Prepare update data
    $updateData = [
        'username' => $name,
        'email' => $email,
        'role' => $role,
        'department' => $department
    ];
    
    // Include password only if provided
    if (!empty($password)) {
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Update staff member
    [$code, $json, $raw] = supabase_request('PATCH', 'user', ['user_id' => 'eq.' . $id], $updateData, ['Prefer: return=representation']);
    
    if ($code >= 200 && $code < 300) {
        echo json_encode(['success' => true, 'message' => 'Staff member updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update staff member', 'status' => $code]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
