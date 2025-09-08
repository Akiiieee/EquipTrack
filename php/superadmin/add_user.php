<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS and JSON headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read form data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$department = trim($_POST['department'] ?? '');
$role = trim($_POST['role'] ?? '');

// Validate
$errors = [];
if ($username === '') { $errors[] = 'Username is required'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required'; }
if ($password === '' || strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters'; }
if ($department === '') { $errors[] = 'Department is required'; }
if (!in_array(strtolower($role), ['admin', 'staff'], true)) { $errors[] = 'Role must be admin or staff'; }

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Check duplicates in Supabase (username OR email) in user table
[$codeUser, $rowsUser] = supabase_select('user', ['username' => 'eq.' . $username], 'user_id', ['limit' => 1]);
[$codeEmail, $rowsEmail] = supabase_select('user', ['email' => 'eq.' . $email], 'user_id', ['limit' => 1]);

if ((is_array($rowsUser) && count($rowsUser) > 0) || (is_array($rowsEmail) && count($rowsEmail) > 0)) {
    echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into Supabase user table (columns per your screenshot)
$row = [
    'username' => $username,
    'password' => $hashedPassword,
    'department' => $department,
    'role' => strtolower($role),
    'email' => $email,
];

[$insertCode, $insertJson, $raw] = supabase_insert('user', $row);

if ($insertCode >= 200 && $insertCode < 300) {
    // Try to capture the new user id if returned
    $newId = null;
    if (is_array($insertJson) && count($insertJson) > 0 && isset($insertJson[0]['user_id'])) {
        $newId = $insertJson[0]['user_id'];
    }
    // Log activity by super_admin if available in session
    session_start();
    if (isset($_SESSION['super_admin_id'])) {
        log_activity('super_admin', $_SESSION['super_admin_id'], 'create_user', ['created_user_id' => $newId, 'username' => $username, 'role' => $role]);
    }
    echo json_encode(['success' => true, 'message' => 'User added successfully']);
} else {
    $detail = is_string($raw) ? $raw : json_encode($insertJson);
    echo json_encode(['success' => false, 'message' => 'Failed to add user', 'detail' => $detail, 'status' => $insertCode]);
}
?>


