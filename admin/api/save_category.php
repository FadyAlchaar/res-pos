<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!empty($data['id'])) {
        // Update existing category
        $query = "UPDATE categories SET 
                  name = :name, 
                  kitchen_id = :kitchen_id, 
                  sort_order = :sort_order, 
                  description = :description, 
                  icon = :icon, 
                  is_active = :is_active 
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':kitchen_id' => $data['kitchen_id'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ':id' => $data['id']
        ]);
    } else {
        // Insert new category
        $query = "INSERT INTO categories 
                  (name, kitchen_id, sort_order, description, icon, is_active) 
                  VALUES 
                  (:name, :kitchen_id, :sort_order, :description, :icon, :is_active)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':kitchen_id' => $data['kitchen_id'] ?? null,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Category saved successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>