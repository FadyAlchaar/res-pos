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
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

if (empty($data['kitchen_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a kitchen']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if description column exists
    $checkDesc = $db->query("SHOW COLUMNS FROM categories LIKE 'description'");
    $hasDescription = $checkDesc->rowCount() > 0;
    
    if (!empty($data['id'])) {
        // Update existing category
        if ($hasDescription) {
            $query = "UPDATE categories 
                      SET name = :name, 
                          kitchen_id = :kitchen_id,
                          sort_order = :sort_order,
                          description = :description
                      WHERE id = :id";
        } else {
            $query = "UPDATE categories 
                      SET name = :name, 
                          kitchen_id = :kitchen_id,
                          sort_order = :sort_order
                      WHERE id = :id";
        }
    } else {
        // Insert new category
        if ($hasDescription) {
            $query = "INSERT INTO categories 
                      (name, kitchen_id, sort_order, description, is_active) 
                      VALUES 
                      (:name, :kitchen_id, :sort_order, :description, 1)";
        } else {
            $query = "INSERT INTO categories 
                      (name, kitchen_id, sort_order, is_active) 
                      VALUES 
                      (:name, :kitchen_id, :sort_order, 1)";
        }
    }
    
    $stmt = $db->prepare($query);
    
    $params = [
        ':name' => $data['name'],
        ':kitchen_id' => $data['kitchen_id'],
        ':sort_order' => !empty($data['sort_order']) ? $data['sort_order'] : 0
    ];
    
    if ($hasDescription) {
        $params[':description'] = !empty($data['description']) ? $data['description'] : null;
    }
    
    if (!empty($data['id'])) {
        $params[':id'] = $data['id'];
    }
    
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Category saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save category']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>