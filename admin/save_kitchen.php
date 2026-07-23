<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Kitchen name is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!empty($data['id'])) {
        // Update existing kitchen
        $query = "UPDATE kitchens 
                  SET name = :name, 
                      printer_ip = :printer_ip,
                      printer_port = :printer_port,
                      printer_model = :printer_model,
                      paper_size = :paper_size,
                      notes = :notes
                  WHERE id = :id";
    } else {
        // Insert new kitchen
        $query = "INSERT INTO kitchens 
                  (name, printer_ip, printer_port, printer_model, paper_size, notes, is_active, status) 
                  VALUES 
                  (:name, :printer_ip, :printer_port, :printer_model, :paper_size, :notes, 1, 'offline')";
    }
    
    $stmt = $db->prepare($query);
    
    $params = [
        ':name' => $data['name'],
        ':printer_ip' => !empty($data['printer_ip']) ? $data['printer_ip'] : null,
        ':printer_port' => !empty($data['printer_port']) ? $data['printer_port'] : 9100,
        ':printer_model' => !empty($data['printer_model']) ? $data['printer_model'] : 'epson',
        ':paper_size' => !empty($data['paper_size']) ? $data['paper_size'] : '80mm',
        ':notes' => !empty($data['notes']) ? $data['notes'] : null
    ];
    
    if (!empty($data['id'])) {
        $params[':id'] = $data['id'];
    }
    
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Kitchen saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save kitchen']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>