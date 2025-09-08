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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

$errors = [];
if ($username === '') { $errors[] = 'Username is required'; }
if ($password === '') { $errors[] = 'Password is required'; }
if ($errors) { echo json_encode(['success' => false, 'message' => implode(', ', $errors)]); exit; }

[$code, $rows] = supabase_select('user', ['username' => 'eq.' . $username], 'user_id,username,password,role,department,email', ['limit' => 1]);
if (!is_array($rows) || count($rows) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

$user = $rows[0];
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['department'] = $user['department'];
$_SESSION['email'] = $user['email'];
$_SESSION['logged_in'] = true;
$_SESSION['user_type'] = 'user'; // distinguish from super_admin login

// Log activity
log_activity('user', $_SESSION['user_id'], 'login', ['username' => $_SESSION['username'], 'role' => $_SESSION['role']]);

// Role-based redirect
$role = strtolower($user['role']);
$redirect = ($role === 'admin') ? '../../views/user/admin_dashboard.html' : '../../views/user/staff_dashboard.html';

echo json_encode(['success' => true, 'message' => 'Login successful', 'role' => $user['role'], 'redirect' => $redirect]);
?>


