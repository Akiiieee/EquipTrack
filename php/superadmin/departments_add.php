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
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = trim($_POST['name'] ?? '');
if ($name === '') { echo json_encode(['success' => false, 'message' => 'Name is required']); exit; }

// Check duplicate
[$codeExist, $rowsExist] = supabase_select('departments', ['department_name' => 'eq.' . $name], 'department_id', ['limit' => 1]);
if (is_array($rowsExist) && count($rowsExist) > 0) {
    echo json_encode(['success' => false, 'message' => 'Department already exists']);
    exit;
}

[$insertCode, $insertJson, $raw] = supabase_insert('departments', [ 'department_name' => $name ]);
if ($insertCode >= 200 && $insertCode < 300) {
    // Log activity
    if (isset($_SESSION['super_admin_id'])) {
        log_activity('super_admin', $_SESSION['super_admin_id'], 'create_department', ['name' => $name]);
    }
    echo json_encode(['success' => true, 'message' => 'Department added']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add department', 'status' => $insertCode, 'detail' => is_string($raw)? $raw : json_encode($insertJson)]);
}
?>


