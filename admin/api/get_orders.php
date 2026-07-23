<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$date_filter = $_GET['date'] ?? 'today';
$status_filter = $_GET['status'] ?? 'all';
$waiter_filter = $_GET['waiter'] ?? 'all';
$table_filter = $_GET['table'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Build WHERE clause
    $where = [];
    $params = [];
    
    // Date filter
    switch ($date_filter) {
        case 'today':
            $where[] = "DATE(o.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $where[] = "DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $where[] = "YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $where[] = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            break;
        default:
            // all - no filter
            break;
    }
    
    // Status filter
    if ($status_filter !== 'all') {
        $where[] = "o.status = :status";
        $params[':status'] = $status_filter;
    }
    
    // Waiter filter
    if ($waiter_filter !== 'all') {
        $where[] = "o.waiter_id = :waiter_id";
        $params[':waiter_id'] = $waiter_filter;
    }
    
    // Table filter
    if (!empty($table_filter)) {
        $where[] = "o.table_number LIKE :table_number";
        $params[':table_number'] = "%$table_filter%";
    }
    
    $where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM orders o $where_clause";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_orders = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_orders / $per_page);
    
    // Get orders
    $query = "SELECT o.*, 
                     u.full_name as waiter_name,
                     (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
              FROM orders o
              LEFT JOIN users u ON o.waiter_id = u.id
              $where_clause
              ORDER BY o.created_at DESC
              LIMIT $offset, $per_page";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    echo json_encode([
        'orders' => $orders,
        'total_orders' => $total_orders,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'per_page' => $per_page,
        'from' => $offset + 1,
        'to' => min($offset + $per_page, $total_orders)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>