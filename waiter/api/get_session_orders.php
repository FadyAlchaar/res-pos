<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
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
    
    // Get session info
    $sessionStmt = $db->prepare("SELECT * FROM table_sessions WHERE id = ?");
    $sessionStmt->execute([$session_id]);
    $session = $sessionStmt->fetch();
    if (!$session) {
        echo json_encode(['error' => 'Session not found']);
        exit;
    }
    
    // Get all orders in this session
    $ordersStmt = $db->prepare("SELECT id, order_number, created_at, total_amount FROM orders WHERE session_id = ?");
    $ordersStmt->execute([$session_id]);
    $orders = $ordersStmt->fetchAll();
    
    $ordersData = [];
    $sessionTotal = 0;
    foreach ($orders as $order) {
        // Get items for this order including cancelled flag
        $itemsStmt = $db->prepare("
            SELECT oi.id, oi.quantity, oi.subtotal, oi.notes, oi.cancelled,
                   mi.name as item_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll();
        
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
            'items' => $items
        ];
    }
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'session_number' => $session['session_number'],
        'table_number' => $session['table_id'],
        'orders' => $ordersData,
        'session_total' => $sessionTotal
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>