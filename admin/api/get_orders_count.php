<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$date_filter = $_GET['date'] ?? 'today';
$status_filter = $_GET['status'] ?? 'all';
$waiter_filter = $_GET['waiter'] ?? 'all';
$table_filter = $_GET['table'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
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
    
    $query = "SELECT COUNT(*) as total FROM orders o $where_clause";
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'total_orders' => (int)$result['total']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>