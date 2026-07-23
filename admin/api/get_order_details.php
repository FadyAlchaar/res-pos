<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get order details
    $query = "SELECT o.*, u.full_name as waiter_name
              FROM orders o
              LEFT JOIN users u ON o.waiter_id = u.id
              WHERE o.id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Get order items with kitchen information
    $items_query = "SELECT oi.*, mi.name, k.id as kitchen_id, k.name as kitchen_name
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                    LEFT JOIN categories c ON mi.category_id = c.id
                    LEFT JOIN kitchens k ON c.kitchen_id = k.id
                    WHERE oi.order_id = :order_id
                    ORDER BY oi.id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([':order_id' => $order_id]);
    $items = $items_stmt->fetchAll();
    
    // Make sure each item has kitchen info, fallback to default if missing
    foreach ($items as &$item) {
        if (empty($item['kitchen_id'])) {
            $item['kitchen_id'] = 1;
            $item['kitchen_name'] = 'Main Kitchen';
        }
    }
    
    $order['items'] = $items;
    
    echo json_encode($order);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>