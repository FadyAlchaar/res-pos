<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$kitchen_id = isset($_GET['kitchen']) ? $_GET['kitchen'] : 'all';
$kitchen_id = $kitchen_id === 'all' ? 0 : (int)$kitchen_id;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Build query based on kitchen filter
    $where = "oi.status IN ('pending', 'preparing', 'ready')";
    if ($kitchen_id > 0) {
        $where .= " AND oi.kitchen_id = $kitchen_id";
    }
    
    $query = "SELECT 
                o.id as order_id,
                o.order_number,
                o.table_number,
                o.created_at,
                oi.id as order_item_id,
                oi.menu_item_id,
                oi.quantity,
                oi.notes,
                oi.status,
                mi.name as item_name,
                TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as waiting_minutes
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN menu_items mi ON oi.menu_item_id = mi.id
              WHERE $where
              ORDER BY 
                FIELD(oi.status, 'pending', 'preparing', 'ready'),
                o.created_at ASC";
    
    $stmt = $db->query($query);
    $rows = $stmt->fetchAll();
    
    // Group by order
    $orders = [];
    $stats = ['pending' => 0, 'preparing' => 0, 'ready' => 0];
    
    foreach ($rows as $row) {
        $orderId = $row['order_id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $row['order_id'],
                'order_number' => $row['order_number'],
                'table_number' => $row['table_number'],
                'created_at' => $row['created_at'],
                'waiting_time' => $row['waiting_minutes'] . ' min',
                'items' => [],
                'is_urgent' => $row['waiting_minutes'] > 15
            ];
        }
        
        $orders[$orderId]['items'][] = [
            'name' => $row['item_name'],
            'quantity' => $row['quantity'],
            'notes' => $row['notes'],
            'status' => $row['status']
        ];
        
        // Update stats
        if (isset($stats[$row['status']])) {
            $stats[$row['status']]++;
        }
        
        // Time ago
        $created = new DateTime($row['created_at']);
        $now = new DateTime();
        $diff = $created->diff($now);
        if ($diff->i < 1) {
            $orders[$orderId]['time_ago'] = 'Just now';
        } elseif ($diff->i < 60) {
            $orders[$orderId]['time_ago'] = $diff->i . ' min ago';
        } else {
            $orders[$orderId]['time_ago'] = $diff->h . 'h ' . $diff->i . 'm ago';
        }
    }
    
    echo json_encode([
        'success' => true,
        'orders' => array_values($orders),
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>