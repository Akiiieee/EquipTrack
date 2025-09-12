<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/db.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'user' || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Session missing user_id']);
    exit;
}

try {
    [$code, $json, $raw] = supabase_request('GET', 'user', [
        'user_id' => 'eq.' . $userId,
        'select' => 'user_id,username,email,department,profile'
    ]);

    if ($code >= 200 && $code < 300 && is_array($json) && !empty($json)) {
        $u = $json[0];
        echo json_encode([
            'success' => true,
            'profile' => [
                'user_id' => $u['user_id'] ?? null,
                'username' => $u['username'] ?? '',
                'email' => $u['email'] ?? '',
                'department' => $u['department'] ?? '',
                'avatar_url' => $u['profile'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Profile not found', 'status' => $code]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
