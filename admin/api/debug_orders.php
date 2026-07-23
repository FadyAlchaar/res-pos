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
    
    // Simple query without filters
    $query = "SELECT o.*, u.full_name as waiter_name,
                     (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
              FROM orders o
              LEFT JOIN users u ON o.waiter_id = u.id
              ORDER BY o.created_at DESC
              LIMIT 10";
    
    $stmt = $db->query($query);
    $orders = $stmt->fetchAll();
    
    echo json_encode([
        'orders' => $orders,
        'total_orders' => count($orders),
        'current_page' => 1,
        'total_pages' => 1,
        'debug' => 'This is a debug endpoint'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>