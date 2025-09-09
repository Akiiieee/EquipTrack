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
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$department = trim($_POST['department'] ?? '');

if ($userId <= 0) { echo json_encode(['success' => false, 'message' => 'user_id required']); exit; }
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Invalid email']); exit; }
if ($role !== '' && !in_array(strtolower($role), ['admin','staff'], true)) { echo json_encode(['success' => false, 'message' => 'Invalid role']); exit; }

$update = [];
if ($email !== '') $update['email'] = $email;
if ($role !== '') $update['role'] = strtolower($role);
if ($department !== '') $update['department'] = $department;

if (empty($update)) { echo json_encode(['success' => false, 'message' => 'Nothing to update']); exit; }

[$code, $json, $raw] = supabase_update('user', ['user_id' => 'eq.' . $userId], $update);
if ($code >= 200 && $code < 300) {
    log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'update_user', ['user_id' => $userId, 'fields' => array_keys($update)]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


