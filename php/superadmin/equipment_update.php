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
    $equipmentId = intval($_POST['equipment_id'] ?? 0);
    $equipmentName = trim($_POST['equipment_name'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $department = trim($_POST['department'] ?? '');
    $status = trim($_POST['status'] ?? 'available');
    
    // Validation
    if ($equipmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
        exit;
    }
    
    if (empty($equipmentName)) {
        echo json_encode(['success' => false, 'message' => 'Equipment name is required']);
        exit;
    }
    
    if (empty($department)) {
        echo json_encode(['success' => false, 'message' => 'Department is required']);
        exit;
    }
    
    // Check if equipment exists
    [$codeCheck, $rowsCheck] = supabase_select('equipments', ['equipment_id' => 'eq.' . $equipmentId], 'equipment_id,equipment_name,equipment_img', ['limit' => 1]);
    if (!is_array($rowsCheck) || count($rowsCheck) === 0) {
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit;
    }
    
    $existingEquipment = $rowsCheck[0];
    
    // Check if equipment name already exists (excluding current equipment)
    [$codeNameCheck, $rowsNameCheck] = supabase_select('equipments', 
        ['equipment_name' => 'eq.' . $equipmentName, 'equipment_id' => 'neq.' . $equipmentId], 
        'equipment_id', ['limit' => 1]);
    if (is_array($rowsNameCheck) && count($rowsNameCheck) > 0) {
        echo json_encode(['success' => false, 'message' => 'Equipment name already exists']);
        exit;
    }
    
    // Handle image upload
    $imageUrl = $existingEquipment['equipment_img'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if (!empty($imageUrl) && file_exists('../../' . $imageUrl)) {
            unlink('../../' . $imageUrl);
        }
        
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
    
    // Update equipment
    $updateData = [
        'equipment_name' => $equipmentName,
        'quantity' => $quantity,
        'department' => $department,
        'status' => $status,
        'equipment_img' => $imageUrl
    ];
    
    [$code, $json, $raw] = supabase_update('equipments', ['equipment_id' => 'eq.' . $equipmentId], $updateData);
    
    if ($code >= 200 && $code < 300) {
        log_activity('super_admin', $_SESSION['super_admin_id'] ?? 0, 'update_equipment', [
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipmentName,
            'department' => $department,
            'status' => $status
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Equipment updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update equipment']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
