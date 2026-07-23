<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Kitchen name is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Always treat as windows printer, ignore any network remnants
    $printerType = 'windows';
    
    if (!empty($data['id'])) {
        // Update existing kitchen
        $query = "UPDATE kitchens SET 
                  name = :name, 
                  printer_ip = :printer_ip,
                  printer_port = :printer_port,
                  printer_name = :printer_name,
                  printer_type = :printer_type,
                  notes = :notes,
                  fallback_printer_name = :fallback_printer_name
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':printer_ip' => $data['printer_ip'] ?? null,
            ':printer_port' => $data['printer_port'] ?? 9100,
            ':printer_name' => $data['printer_name'] ?? null,
            ':printer_type' => $printerType,
            ':notes' => $data['notes'] ?? null,
            ':fallback_printer_name' => $data['fallback_printer_name'] ?? null,
            ':id' => $data['id']
        ]);
    } else {
        // Insert new kitchen
        $query = "INSERT INTO kitchens 
                  (name, printer_ip, printer_port, printer_name, printer_type, notes, fallback_printer_name, is_active, status) 
                  VALUES 
                  (:name, :printer_ip, :printer_port, :printer_name, :printer_type, :notes, :fallback_printer_name, 1, 'offline')";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':printer_ip' => $data['printer_ip'] ?? null,
            ':printer_port' => $data['printer_port'] ?? 9100,
            ':printer_name' => $data['printer_name'] ?? null,
            ':printer_type' => $printerType,
            ':notes' => $data['notes'] ?? null,
            ':fallback_printer_name' => $data['fallback_printer_name'] ?? null
        ]);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Kitchen saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save kitchen']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>