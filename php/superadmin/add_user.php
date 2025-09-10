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

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role = strtolower(trim($_POST['role'] ?? ''));
$department = trim($_POST['department'] ?? '');

$errors = [];
if ($username === '') { $errors[] = 'Username is required'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required'; }
if ($password === '' || strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters'; }
if (!in_array($role, ['admin','staff'], true)) { $errors[] = 'Role must be admin or staff'; }
if ($department === '') { $errors[] = 'Department is required'; }

if (!empty($errors)) { echo json_encode(['success' => false, 'message' => implode(', ', $errors)]); exit; }

// Ensure username unique
[$codeCheck, $rowsCheck] = supabase_select('user', ['username' => 'eq.' . $username], 'user_id', ['limit' => 1]);
if (is_array($rowsCheck) && count($rowsCheck) > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

[$code, $json, $raw] = supabase_insert('user', [
    'username' => $username,
    'email' => $email,
    'password' => $hash,
    'role' => $role,
    'department' => $department
]);

if ($code >= 200 && $code < 300) {
    log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'create_user', ['username' => $username, 'role' => $role]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Insert failed',
        'status' => $code,
        'detail' => is_string($raw) ? $raw : json_encode($json)
    ]);
}
?>


