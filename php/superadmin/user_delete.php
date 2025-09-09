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
if ($userId <= 0) { echo json_encode(['success' => false, 'message' => 'user_id required']); exit; }

// Supabase delete via RPC-like: use rest delete
[$code, $json, $raw] = supabase_request('DELETE', 'user', ['user_id' => 'eq.' . $userId]);
if ($code >= 200 && $code < 300) {
    log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'delete_user', ['user_id' => $userId]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


