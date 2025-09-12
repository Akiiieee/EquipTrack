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

$adminDepartment = $_SESSION['department'] ?? '';
if (empty($adminDepartment)) {
    echo json_encode(['success' => false, 'message' => 'Admin department not found']);
    exit;
}

$equipmentName = trim($_POST['equipment_name'] ?? '');
$quantity = intval($_POST['quantity'] ?? 1);
// Always set status to available for new equipment
$status = 'available';

if (empty($equipmentName)) {
    echo json_encode(['success' => false, 'message' => 'Equipment name is required']);
    exit;
}

// Force department to be the admin's department
$department = $adminDepartment;

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit;
}

try {
    // Handle image upload
    $equipmentImg = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/equipment/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $equipmentImg = 'uploads/equipment/' . $fileName;
        }
    }
    
    // Insert new equipment
    $equipmentData = [
        'equipment_name' => $equipmentName,
        'quantity' => $quantity,
        'department' => $department,
        'status' => $status,
        'equipment_img' => $equipmentImg,
        'date_added' => date('Y-m-d H:i:s')
    ];
    
    [$code, $json, $raw] = supabase_request('POST', 'equipments', [], $equipmentData, ['Prefer: return=representation']);
    
    if ($code >= 200 && $code < 300) {
        echo json_encode(['success' => true, 'message' => 'Equipment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add equipment', 'status' => $code]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
