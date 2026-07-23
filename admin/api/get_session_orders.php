<?php
require_once '../../config/config.php';
require_once '../../config/language.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id) {
    echo json_encode(['error' => 'Session ID required']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Get all orders in this session
    $ordersStmt = $db->prepare("
        SELECT o.id, o.order_number, o.created_at, o.total_amount, o.status, 
               u.full_name as waiter_name
        FROM orders o
        LEFT JOIN users u ON o.waiter_id = u.id
        WHERE o.session_id = ?
        ORDER BY o.created_at
    ");
    $ordersStmt->execute([$session_id]);
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ordersData = [];
    $sessionTotal = 0;
    
    foreach ($orders as $order) {
        // Get items for this order
        $itemsStmt = $db->prepare("
            SELECT oi.id, oi.quantity, oi.subtotal, oi.notes, oi.cancelled,
                   mi.name as item_name, k.name as kitchen_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            LEFT JOIN kitchens k ON oi.kitchen_id = k.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $orderTotal = 0;
        foreach ($items as $item) {
            $orderTotal += $item['subtotal'];
        }
        $sessionTotal += $orderTotal;
        
        $ordersData[] = [
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'created_at' => $order['created_at'],
            'total_amount' => $order['total_amount'],
            'status' => $order['status'],
            'waiter_name' => $order['waiter_name'],
            'item_count' => count($items),
            'items' => $items
        ];
    }
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'orders' => $ordersData,
        'session_total' => $sessionTotal
    ]);
    
} catch (Exception $e) {
    error_log("get_session_orders.php error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>