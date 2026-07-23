<?php
require_once '../../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$action = isset($_GET['action']) ? $_GET['action'] : 'summary';

try {
    $db = (new Database())->getConnection();
    
    // Build date filter based on orders.created_at
    if ($period === 'today') {
        $date_filter = "DATE(o.created_at) = CURDATE()";
    } elseif ($period === 'yesterday') {
        $date_filter = "DATE(o.created_at) = CURDATE() - INTERVAL 1 DAY";
    } elseif ($period === 'week') {
        $date_filter = "YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
    } elseif ($period === 'month') {
        $date_filter = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
    } else {
        $date_filter = "1=1";
    }
    
    // If action is 'orders' – return detailed orders list
    if ($action === 'orders') {
        $stmt = $db->prepare("
            SELECT o.id, o.order_number, o.table_number, o.total_amount, o.created_at,
                   u.full_name as waiter_name
            FROM orders o
            LEFT JOIN users u ON o.waiter_id = u.id
            WHERE $date_filter
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $orders]);
        exit;
    }
    
    // If action is 'cancelled' – return list of cancelled items
    if ($action === 'cancelled') {
        $stmt = $db->prepare("
            SELECT oi.id, oi.quantity, oi.subtotal, oi.notes,
                   mi.name as item_name, o.order_number, o.table_number,
                   o.created_at, u.full_name as waiter_name
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            LEFT JOIN users u ON o.waiter_id = u.id
            WHERE oi.cancelled = 1 AND $date_filter
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $items]);
        exit;
    }
    // If action is 'waiter' – return orders grouped by waiter
    if ($action === 'waiter') {
        $stmt = $db->prepare("
            SELECT u.id, u.full_name as waiter_name, 
                COUNT(o.id) as order_count, 
                COALESCE(SUM(o.total_amount), 0) as total_revenue
            FROM orders o
            JOIN users u ON o.waiter_id = u.id
            WHERE $date_filter
            GROUP BY o.waiter_id
            ORDER BY order_count DESC
        ");
        $stmt->execute();
        $waiters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $waiters]);
        exit;
    }
    
    // Default: summary data
    $stmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(o.total_amount), 0) as revenue FROM orders o WHERE $date_filter");
    $stmt->execute();
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT COUNT(*) as cancelled FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.cancelled = 1 AND $date_filter");
    $stmt->execute();
    $cancelled = $stmt->fetchColumn();

    // Get total orders by waiter count (for summary card)
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT o.waiter_id) as waiter_count 
        FROM orders o 
        WHERE $date_filter AND o.waiter_id IS NOT NULL
    ");
    $stmt->execute();
    $waiter_count = (int)$stmt->fetchColumn();
    
    echo json_encode([
        'total_orders' => (int)$totals['total'],
        'cancelled_items' => (int)$cancelled,
        'total_revenue' => (float)$totals['revenue'],
        'orders_by_waiter' => $waiter_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>