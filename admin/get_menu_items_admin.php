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
    
    $query = "SELECT mi.*, c.name as category_name, c.kitchen_id,
                     k.name as kitchen_name
              FROM menu_items mi
              LEFT JOIN categories c ON mi.category_id = c.id
              LEFT JOIN kitchens k ON c.kitchen_id = k.id
              ORDER BY c.name, mi.sort_order, mi.name";
    $stmt = $db->query($query);
    $items = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($items);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>