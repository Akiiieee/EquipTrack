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
$rfid = trim($_POST['rfid'] ?? '');

if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'id required']); exit; }
if ($rfid === '') { echo json_encode(['success' => false, 'message' => 'rfid required']); exit; }

$update = [ 'rfid' => $rfid, 'status' => 'registered' ];
[$code, $json, $raw] = supabase_update('students', ['id' => 'eq.' . $id], $update);

if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


