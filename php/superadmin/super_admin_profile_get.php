<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

[$code, $rows] = supabase_select('super_admin', ['super_admin_id' => 'eq.' . $id], 'super_admin_id,username,email,profile', ['limit' => 1]);
if (!is_array($rows) || count($rows) === 0) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

$row = $rows[0];
$profile = [
    'super_admin_id' => $row['super_admin_id'] ?? null,
    'username' => $row['username'] ?? '',
    'email' => $row['email'] ?? '',
    // Normalize to avatar_url for frontend while DB column is `profile`
    'avatar_url' => $row['profile'] ?? ''
];
echo json_encode(['success' => true, 'profile' => $profile]);
?>


