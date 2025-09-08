<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user') {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'department' => $_SESSION['department'] ?? '',
        'email' => $_SESSION['email'] ?? ''
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>


