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

$id = intval($_POST['id'] ?? 0);
$studId = trim($_POST['stud_id'] ?? '');
$studName = trim($_POST['stud_name'] ?? '');
$program = trim($_POST['program'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'id required']); exit; }

$update = [];
if ($studId !== '') $update['stud_id'] = $studId;
if ($studName !== '') $update['stud_name'] = $studName;
if ($program !== '') $update['program'] = $program;
if ($email !== '') $update['email'] = $email;

if (empty($update)) { echo json_encode(['success' => false, 'message' => 'Nothing to update']); exit; }

[$code, $json, $raw] = supabase_update('students', ['id' => 'eq.' . $id], $update);

if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


