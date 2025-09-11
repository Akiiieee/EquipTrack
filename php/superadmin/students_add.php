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

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studId = trim($_POST['stud_id'] ?? '');
$studName = trim($_POST['stud_name'] ?? '');
$program = trim($_POST['program'] ?? '');
$email = trim($_POST['email'] ?? '');

$errors = [];
if ($studId === '') { $errors[] = 'stud_id required'; }
if ($studName === '') { $errors[] = 'stud_name required'; }
if ($program === '') { $errors[] = 'program required'; }
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'valid email required or leave empty'; }

if (!empty($errors)) { echo json_encode(['success' => false, 'message' => implode(', ', $errors)]); exit; }

// Default status: Not fully registered until RFID tap
$payload = [
    'stud_id' => $studId,
    'stud_name' => $studName,
    'program' => $program,
    'email' => ($email !== '' ? $email : null),
    'rfid' => null,
    'status' => 'not fully registered'
];

[$code, $json, $raw] = supabase_insert('students', $payload);

if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


