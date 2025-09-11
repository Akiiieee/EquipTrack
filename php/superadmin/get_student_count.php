<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

[$code, $rows] = supabase_select('students', [], 'id', ['limit' => 100000]);
$count = (is_array($rows) ? count($rows) : 0);

if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true, 'students' => $count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch count', 'status' => $code]);
}
?>


