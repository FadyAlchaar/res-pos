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

if (empty($data['table_number'])) {
    echo json_encode(['success' => false, 'message' => 'Table number is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!empty($data['id'])) {
        // Update existing table
        $query = "UPDATE restaurant_tables 
                  SET table_number = :table_number,
                      table_name = :table_name,
                      capacity = :capacity,
                      status = :status,
                      section = :section,
                      sort_order = :sort_order
                  WHERE id = :id";
    } else {
        // Insert new table
        $query = "INSERT INTO restaurant_tables 
                  (table_number, table_name, capacity, status, section, sort_order, is_active) 
                  VALUES 
                  (:table_number, :table_name, :capacity, :status, :section, :sort_order, 1)";
    }
    
    $stmt = $db->prepare($query);
    
    $params = [
        ':table_number' => $data['table_number'],
        ':table_name' => $data['table_name'] ?? null,
        ':capacity' => $data['capacity'] ?? 4,
        ':status' => $data['status'] ?? 'available',
        ':section' => $data['section'] ?? null,
        ':sort_order' => $data['sort_order'] ?? 0
    ];
    
    if (!empty($data['id'])) {
        $params[':id'] = $data['id'];
    }
    
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Table saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save table']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>