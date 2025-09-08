<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Received: ' . $_SERVER['REQUEST_METHOD']]);
    exit;
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate input
$errors = [];

if (empty($username)) {
    $errors[] = 'Username is required';
}

if (empty($password)) {
    $errors[] = 'Password is required';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters long';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Check if username or email already exists (Supabase)
if (empty($errors)) {
    // Use OR logic by querying twice (Supabase REST doesn't support OR easily without RLS bypass)
    [$codeUser, $rowsUser] = supabase_select('super_admin', ['username' => 'eq.' . $username], 'super_admin_id,username,email', ['limit' => 1]);
    [$codeEmail, $rowsEmail] = supabase_select('super_admin', ['email' => 'eq.' . $email], 'super_admin_id,username,email', ['limit' => 1]);

    $existsUser = is_array($rowsUser) && count($rowsUser) > 0;
    $existsEmail = is_array($rowsEmail) && count($rowsEmail) > 0;

    if ($existsUser || $existsEmail) {
        $errors[] = 'Username or email already exists';
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into Supabase
[$insertCode, $insertJson, $raw] = supabase_insert('super_admin', [
    'username' => $username,
    'password' => $hashedPassword,
    'email' => $email,
]);

if ($insertCode >= 200 && $insertCode < 300) {
    echo json_encode(['success' => true, 'message' => 'Super admin added successfully']);
} else {
    $detail = is_string($raw) ? $raw : json_encode($insertJson);
    echo json_encode(['success' => false, 'message' => 'Failed to add super admin', 'detail' => $detail, 'status' => $insertCode]);
}

// Note: no DB connection to close with Supabase REST
?>
