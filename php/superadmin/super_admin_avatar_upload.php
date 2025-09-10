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

$id = intval($_SESSION['super_admin_id'] ?? 0);
if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid session']); exit; }

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed']);
    exit;
}

$file = $_FILES['avatar'];
$allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif', 'image/webp' => '.webp'];
$type = mime_content_type($file['tmp_name']);
if (!isset($allowed[$type])) { echo json_encode(['success' => false, 'message' => 'Unsupported file type']); exit; }

$dir = dirname(__DIR__, 2) . '/uploads/avatars';
if (!is_dir($dir)) { mkdir($dir, 0777, true); }

$name = 'sa_' . $id . '_' . time() . $allowed[$type];
$dest = $dir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Unable to save file']);
    exit;
}

$relative = '../../uploads/avatars/' . $name;

// Update DB column `profile` to store URL
[$code, $json, $raw] = supabase_update('super_admin', ['super_admin_id' => 'eq.' . $id], ['profile' => $relative]);
if ($code >= 200 && $code < 300) {
    log_activity('super_admin', $id, 'update_avatar', ['avatar_url' => $relative]);
    echo json_encode(['success' => true, 'avatar_url' => $relative]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'status' => $code, 'detail' => is_string($raw)? $raw : json_encode($json)]);
}
?>


