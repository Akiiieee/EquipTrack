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

$studId = trim($_POST['stud_id'] ?? '');
$studName = trim($_POST['stud_name'] ?? '');
$email = trim($_POST['email'] ?? '');

if (empty($studId) || empty($studName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Force program to be the admin's department
$program = $adminDepartment;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Check if student ID already exists
    [$code, $json, $raw] = supabase_request('GET', 'students', ['stud_id' => 'eq.' . $studId, 'select' => 'id']);
    
    if ($code >= 200 && $code < 300 && !empty($json)) {
        echo json_encode(['success' => false, 'message' => 'Student ID already exists']);
        exit;
    }
    
    // Check if email already exists
    [$code2, $json2, $raw2] = supabase_request('GET', 'students', ['email' => 'eq.' . $email, 'select' => 'id']);
    
    if ($code2 >= 200 && $code2 < 300 && !empty($json2)) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Insert new student
    $studentData = [
        'stud_id' => $studId,
        'stud_name' => $studName,
        'email' => $email,
        'program' => $program,
        'rfid' => null,
        'status' => 'not_registered',
        'created' => date('Y-m-d H:i:s')
    ];
    
    [$code, $json, $raw] = supabase_request('POST', 'students', [], $studentData, ['Prefer: return=representation']);
    
    if ($code >= 200 && $code < 300) {
        echo json_encode(['success' => true, 'message' => 'Student added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add student', 'status' => $code]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
