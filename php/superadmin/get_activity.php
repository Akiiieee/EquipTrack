<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/db.php';

// Recent 30 activities
[$codeA, $rowsA] = supabase_select('activity_log', [], 'actor_type,actor_id,action,details,created_at', ['order' => 'created_at.desc', 'limit' => 30]);

// Active user count in last 15 minutes based on latest login/heartbeat and ignoring later logout
$activeAdmins = 0;
$activeStaff = 0;
$activeUsers = [];
if (is_array($rowsA)) {
    $cutoff = strtotime('-15 minutes');
    $lastSeen = [];
    foreach ($rowsA as $r) {
        $ts = isset($r['created_at']) ? strtotime($r['created_at']) : 0;
        if ($r['actor_type'] !== 'user') { continue; }
        $id = (string)$r['actor_id'];
        // initialize if not set
        if (!isset($lastSeen[$id])) { $lastSeen[$id] = null; }
        // Only consider events within cutoff for activity
        if ($ts >= $cutoff) {
            // We prefer heartbeat/login over logout for activity state
            if (in_array($r['action'], ['heartbeat','login'])) {
                $lastSeen[$id] = $r;
            } elseif ($r['action'] === 'logout' && $lastSeen[$id] === null) {
                // mark explicitly inactive only if we haven't seen heartbeat/login in window
                $lastSeen[$id] = 'inactive';
            }
        }
    }
    foreach ($lastSeen as $r) {
        if ($r === 'inactive' || $r === null) { continue; }
        $details = json_decode($r['details'] ?? '', true);
        $role = is_array($details) && isset($details['role']) ? strtolower($details['role']) : '';
        $id = $r['actor_id'] ?? null;
        $username = is_array($details) && isset($details['username']) ? $details['username'] : '';
        if ($role === 'admin') { $activeAdmins++; }
        elseif ($role === 'staff') { $activeStaff++; }
        $activeUsers[] = [ 'user_id' => $id, 'username' => $username, 'role' => $role ];
    }
}

echo json_encode([
    'success' => true,
    'recent' => $rowsA ?: [],
    'active' => [ 'admins' => $activeAdmins, 'staff' => $activeStaff, 'total' => $activeAdmins + $activeStaff, 'users' => $activeUsers ]
]);
?>


