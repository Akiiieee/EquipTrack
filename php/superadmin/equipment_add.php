<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

// Check if super admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../../config/db.php';

try {
    $equipmentName = trim($_POST['equipment_name'] ?? '');
    $quantity = max(1, intval($_POST['quantity'] ?? 1)); // Ensure quantity is at least 1
    $department = trim($_POST['department'] ?? '');
    $status = trim($_POST['status'] ?? 'available');
    
    // Validation
    if (empty($equipmentName)) {
        echo json_encode(['success' => false, 'message' => 'Equipment name is required']);
        exit;
    }
    
    if (empty($department)) {
        echo json_encode(['success' => false, 'message' => 'Department is required']);
        exit;
    }
    
    // Check if equipment name already exists
    [$codeCheck, $rowsCheck] = supabase_select('equipments', ['equipment_name' => 'eq.' . $equipmentName], 'equipment_id', ['limit' => 1]);
    if (is_array($rowsCheck) && count($rowsCheck) > 0) {
        echo json_encode(['success' => false, 'message' => 'Equipment name already exists']);
        exit;
    }
    
    // Handle image upload
    $imageUrl = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/equipment/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $imageUrl = 'uploads/equipment/' . $fileName;
        }
    }
    
    // Insert equipment
    $equipmentData = [
        'equipment_name' => $equipmentName,
        'quantity' => $quantity,
        'department' => $department,
        'status' => $status,
        'equipment_img' => $imageUrl
    ];
    
    [$code, $json, $raw] = supabase_insert('equipments', $equipmentData);
    
    if ($code >= 200 && $code < 300) {
        log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'add_equipment', [
            'equipment_name' => $equipmentName,
            'department' => $department,
            'status' => $status
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Equipment added successfully']);
    } else {
        // Provide detailed error information
        $errorMessage = 'Failed to add equipment';
        if (is_array($json) && isset($json['message'])) {
            $errorMessage = $json['message'];
        } elseif (is_string($raw)) {
            $errorMessage = $raw;
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $errorMessage,
            'status_code' => $code,
            'response' => $json,
            'raw_response' => $raw
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
