<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $order_query = "SELECT o.*, u.full_name as waiter_name
                    FROM orders o
                    LEFT JOIN users u ON o.waiter_id = u.id
                    WHERE o.id = :id";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->execute([':id' => $order_id]);
    $order = $order_stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    $items_query = "SELECT oi.*, mi.name as item_name
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                    WHERE oi.order_id = :order_id";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([':order_id' => $order_id]);
    $items = $items_stmt->fetchAll();
    
    // Store order data in session for receipt page
    $_SESSION['receipt_order'] = $order;
    $_SESSION['receipt_items'] = $items;
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>