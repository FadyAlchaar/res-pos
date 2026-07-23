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
    
    // mi.* includes print_on_controller automatically
    $query = "SELECT mi.*, c.name as category_name, k.name as kitchen_name
              FROM menu_items mi
              LEFT JOIN categories c ON mi.category_id = c.id
              LEFT JOIN kitchens k ON c.kitchen_id = k.id
              WHERE mi.is_available = 1
              ORDER BY c.name, mi.sort_order, mi.name";
    $stmt = $db->query($query);
    $items = $stmt->fetchAll();
    
    echo json_encode($items);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>