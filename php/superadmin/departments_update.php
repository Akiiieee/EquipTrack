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

$oldName = trim($_POST['old_name'] ?? '');
$newName = trim($_POST['new_name'] ?? '');
if ($oldName === '' || $newName === '') { echo json_encode(['success'=>false,'message'=>'old_name and new_name required']); exit; }

[$code, $json, $raw] = supabase_update('departments', ['department_name' => 'eq.' . $oldName], ['department_name' => $newName]);
if ($code >= 200 && $code < 300) {
    log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'update_department', ['from' => $oldName, 'to' => $newName]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


