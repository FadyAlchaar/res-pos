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
    
    $query = "SELECT c.*, k.name as kitchen_name, 
                     (SELECT COUNT(*) FROM menu_items WHERE category_id = c.id) as item_count
              FROM categories c
              LEFT JOIN kitchens k ON c.kitchen_id = k.id
              ORDER BY c.sort_order, c.name";
    $stmt = $db->query($query);
    $categories = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($categories);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>