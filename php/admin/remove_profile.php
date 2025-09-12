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
    // Fetch current profile URL to delete the file if local
    [$gcode, $gjson, $graw] = supabase_request('GET', 'user', [ 'user_id' => 'eq.' . $userId, 'select' => 'profile' ]);
    if ($gcode >= 200 && $gcode < 300 && is_array($gjson) && !empty($gjson)) {
        $current = $gjson[0]['profile'] ?? '';
        if ($current && strpos($current, 'uploads/avatars/') === 0) {
            $local = '../../' . $current;
            if (file_exists($local)) { @unlink($local); }
        }
    }

    [$ucode, $ujson, $uraw] = supabase_request('PATCH', 'user', [ 'user_id' => 'eq.' . $userId ], ['profile' => ''], ['Prefer: return=representation']);
    if ($ucode >= 200 && $ucode < 300) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove profile', 'status' => $ucode]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
