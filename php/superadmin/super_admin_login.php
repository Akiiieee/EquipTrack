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

// Start session
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Received: ' . $_SERVER['REQUEST_METHOD']]);
    exit;
}

// Get form data
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate input
$errors = [];

if (empty($username)) {
    $errors[] = 'Username is required';
}

if (empty($password)) {
    $errors[] = 'Password is required';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Check if user exists via Supabase
[$code, $rows] = supabase_select('super_admin', ['username' => 'eq.' . $username], 'super_admin_id,username,password,email', ['limit' => 1]);

if (!is_array($rows) || count($rows) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

$user = $rows[0];

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

// Login successful - set session variables
$_SESSION['super_admin_id'] = $user['super_admin_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['logged_in'] = true;
$_SESSION['user_type'] = 'super_admin';

echo json_encode([
    'success' => true, 
    'message' => 'Login successful',
    'redirect' => '../../views/superadmin/dashboard.html' // Adjust path as needed
]);
?>
