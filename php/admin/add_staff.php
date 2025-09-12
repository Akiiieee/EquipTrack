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
    echo json_encode([
        'success' => false, 
        'message' => 'Admin department not found in session',
        'debug' => [
            'session_department' => $_SESSION['department'] ?? 'not set',
            'username' => $_SESSION['username'] ?? 'not set',
            'role' => $_SESSION['role'] ?? 'not set',
            'user_id' => $_SESSION['user_id'] ?? 'not set'
        ]
    ]);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Automatically set role as "staff" and department as admin's department
$role = 'staff';
$department = $adminDepartment;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Role is automatically set to 'staff', no validation needed

try {
    // Check if email already exists
    [$code, $json, $raw] = supabase_request('GET', 'user', ['email' => 'eq.' . $email, 'select' => 'user_id']);
    
    if ($code >= 200 && $code < 300 && !empty($json)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new staff member
    $staffData = [
        'username' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'role' => $role,
        'department' => $department,
        'Date_added' => date('Y-m-d H:i:s')
    ];
    
    [$code, $json, $raw] = supabase_request('POST', 'user', [], $staffData, ['Prefer: return=representation']);
    
    if ($code >= 200 && $code < 300) {
        echo json_encode(['success' => true, 'message' => 'Staff member added successfully']);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to add staff member', 
            'status' => $code,
            'debug' => [
                'admin_department' => $adminDepartment,
                'staff_data' => $staffData,
                'raw_response' => $raw,
                'json_response' => $json
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
