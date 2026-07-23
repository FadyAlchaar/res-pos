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
    echo json_encode(['success' => false, 'message' => 'Item name is required']);
    exit;
}

if (empty($data['category_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a category']);
    exit;
}

if (empty($data['price']) || $data['price'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid price is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if description column exists
    $checkDesc = $db->query("SHOW COLUMNS FROM menu_items LIKE 'description'");
    $hasDescription = $checkDesc->rowCount() > 0;
    
    if (!empty($data['id'])) {
        // Update existing item
        if ($hasDescription) {
            $query = "UPDATE menu_items 
                      SET name = :name, 
                          category_id = :category_id,
                          price = :price,
                          description = :description,
                          preparation_time = :preparation_time,
                          sort_order = :sort_order
                      WHERE id = :id";
        } else {
            $query = "UPDATE menu_items 
                      SET name = :name, 
                          category_id = :category_id,
                          price = :price,
                          preparation_time = :preparation_time,
                          sort_order = :sort_order
                      WHERE id = :id";
        }
    } else {
        // Insert new item
        if ($hasDescription) {
            $query = "INSERT INTO menu_items 
                      (name, category_id, price, description, preparation_time, sort_order, is_available) 
                      VALUES 
                      (:name, :category_id, :price, :description, :preparation_time, :sort_order, 1)";
        } else {
            $query = "INSERT INTO menu_items 
                      (name, category_id, price, preparation_time, sort_order, is_available) 
                      VALUES 
                      (:name, :category_id, :price, :preparation_time, :sort_order, 1)";
        }
    }
    
    $stmt = $db->prepare($query);
    
    $params = [
        ':name' => $data['name'],
        ':category_id' => $data['category_id'],
        ':price' => $data['price'],
        ':preparation_time' => !empty($data['preparation_time']) ? $data['preparation_time'] : 10,
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
        echo json_encode(['success' => true, 'message' => 'Menu item saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save menu item']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>