<?php
require_once '../../config/config.php';
require_once '../../config/language.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
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
              WHERE c.is_active = 1
              ORDER BY c.sort_order, c.name";
    $stmt = $db->query($query);
    $categories = $stmt->fetchAll();
    
    echo json_encode($categories);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>