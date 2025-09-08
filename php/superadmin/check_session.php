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

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin') {
    
    echo json_encode([
        'logged_in' => true,
        'username' => $_SESSION['username'] ?? 'Super Admin',
        'email' => $_SESSION['email'] ?? '',
        'user_type' => $_SESSION['user_type']
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'message' => 'Not logged in'
    ]);
}
?>
