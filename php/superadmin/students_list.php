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

// Table name assumed to be `students`
// Columns: id, stud_id, stud_name, program, email, rfid, status, created
[$code, $rows] = supabase_select('students', [], 'id,stud_id,stud_name,program,email,rfid,status,created', ['order' => 'id.desc']);

if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true, 'data' => $rows ?: []]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch students', 'status' => $code]);
}
?>


