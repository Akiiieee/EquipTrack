<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS and JSON headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';

// Fetch counts from Supabase 'user' table by role
[$codeAdmin, $rowsAdmin] = supabase_select('user', ['role' => 'eq.admin'], 'user_id');
[$codeStaff, $rowsStaff] = supabase_select('user', ['role' => 'eq.staff'], 'user_id');

$totalAdmins = (is_array($rowsAdmin)) ? count($rowsAdmin) : 0;
$totalStaff = (is_array($rowsStaff)) ? count($rowsStaff) : 0;
$totalUsers = $totalAdmins + $totalStaff;

echo json_encode([
    'success' => true,
    'admins' => $totalAdmins,
    'staff' => $totalStaff,
    'total' => $totalUsers
]);
?>


