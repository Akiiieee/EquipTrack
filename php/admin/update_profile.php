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

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_type'] ?? '') !== 'user' || ($_SESSION['role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { echo json_encode(['success' => false, 'message' => 'Session missing user_id']); exit; }

try {
    $avatarUrl = '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/avatars/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'admin_' . $userId . '_' . uniqid() . '.' . $ext;
        $path = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
            exit;
        }
        $avatarUrl = 'uploads/avatars/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    [$ucode, $ujson, $uraw] = supabase_request('PATCH', 'user', [ 'user_id' => 'eq.' . $userId ], ['profile' => $avatarUrl], ['Prefer: return=representation']);
    if ($ucode >= 200 && $ucode < 300) {
        echo json_encode(['success' => true, 'avatar_url' => $avatarUrl]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save profile', 'status' => $ucode]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
