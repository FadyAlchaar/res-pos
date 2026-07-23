<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['name']) || empty($data['category_id'])) {
    echo json_encode(['success' => false, 'message' => 'Name and category are required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get kitchen_id from the selected category
    $kitchen_id = null;
    $catStmt = $db->prepare("SELECT kitchen_id FROM categories WHERE id = ?");
    $catStmt->execute([$data['category_id']]);
    $kitchen_id = $catStmt->fetchColumn();
    
    if (!empty($data['id'])) {
        // Update existing item
        $query = "UPDATE menu_items SET 
                name = :name,
                price = :price,
                category_id = :category_id,
                kitchen_id = :kitchen_id,
                description = :description,
                preparation_time = :preparation_time,
                print_on_controller = :print_on_controller,
                is_available = :is_available
                WHERE id = :id";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':category_id' => $data['category_id'],
            ':price' => !empty($data['price']) ? $data['price'] : 0,
            ':kitchen_id' => $kitchen_id,
            ':description' => $data['description'] ?? null,
            ':preparation_time' => $data['preparation_time'] ?? 10,
            ':print_on_controller' => isset($data['print_on_controller']) ? (int)$data['print_on_controller'] : 1,
            ':is_available' => isset($data['is_available']) ? (int)$data['is_available'] : 1,
            ':id' => $data['id']
        ]);
    } else {
        // Insert new item
        $query = "INSERT INTO menu_items 
                  (name, category_id, kitchen_id, price, description, preparation_time, is_available, print_on_controller) 
                  VALUES 
                  (:name, :category_id, :kitchen_id, :price, :description, :preparation_time, :is_available, :print_on_controller)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':category_id' => $data['category_id'],
            ':kitchen_id' => $kitchen_id,
            ':price' => !empty($data['price']) ? $data['price'] : 0,
            ':description' => $data['description'] ?? null,
            ':preparation_time' => $data['preparation_time'] ?? 10,
            ':is_available' => isset($data['is_available']) ? (int)$data['is_available'] : 1,
            ':print_on_controller' => isset($data['print_on_controller']) ? (int)$data['print_on_controller'] : 1
        ]);
    }
    
    if ($result) {
        // Clear the categories cache to reflect availability changes immediately
        $cacheFile = dirname(__DIR__, 2) . '/cache/categories.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        echo json_encode(['success' => true, 'message' => 'Menu item saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save menu item']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>