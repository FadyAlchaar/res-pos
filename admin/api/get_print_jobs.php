<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT pj.*, 
                     k.name as kitchen_name,
                     o.order_number,
                     mi.name as item_name,
                     oi.quantity
              FROM print_jobs pj
              LEFT JOIN kitchens k ON pj.kitchen_id = k.id
              LEFT JOIN order_items oi ON pj.order_item_id = oi.id
              LEFT JOIN orders o ON oi.order_id = o.id
              LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
              ORDER BY pj.created_at DESC
              LIMIT 50";
    $stmt = $db->query($query);
    $jobs = $stmt->fetchAll();
    
    echo json_encode($jobs);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>