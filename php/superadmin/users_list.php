<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/db.php';

// Get all users
[$code, $rows] = supabase_select('user', [], 'user_id,username,email,role,department', ['order' => 'username.asc']);
$users = is_array($rows) ? $rows : [];

echo json_encode(['success' => true, 'users' => $users]);
?>


