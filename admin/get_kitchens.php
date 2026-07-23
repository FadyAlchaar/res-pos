<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT k.*, 
                     (SELECT COUNT(*) FROM categories WHERE kitchen_id = k.id) as category_count,
                     (SELECT COUNT(*) FROM menu_items mi 
                      JOIN categories c ON mi.category_id = c.id 
                      WHERE c.kitchen_id = k.id) as item_count
              FROM kitchens k 
              ORDER BY k.name";
    $stmt = $db->query($query);
    $kitchens = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($kitchens);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>