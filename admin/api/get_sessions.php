<?php
require_once '../../config/config.php';
require_once '../../config/language.php';
header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    $status = $_GET['status'] ?? 'all';
    $date = $_GET['date'] ?? 'today';
    $table = $_GET['table'] ?? '';
    
    $where = [];
    $params = [];
    
    if ($status === 'open') {
        $where[] = "ts.status = 'open'";
    } elseif ($status === 'closed') {
        $where[] = "ts.status = 'closed'";
    }
    
    if (!empty($table)) {
        $where[] = "(t.table_number LIKE :table OR t.table_name LIKE :table)";
        $params[':table'] = "%$table%";
    }
    
    if ($date === 'today') {
        $where[] = "DATE(ts.opened_at) = CURDATE()";
    } elseif ($date === 'yesterday') {
        $where[] = "DATE(ts.opened_at) = CURDATE() - INTERVAL 1 DAY";
    } elseif ($date === 'week') {
        $where[] = "YEARWEEK(ts.opened_at) = YEARWEEK(CURDATE())";
    } elseif ($date === 'month') {
        $where[] = "MONTH(ts.opened_at) = MONTH(CURDATE()) AND YEAR(ts.opened_at) = YEAR(CURDATE())";
    }
    
    $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
    
    // Query without opened_by join – get waiter name from orders? Not needed for now.
    $query = "
        SELECT ts.*, 
               t.table_number, 
               t.table_name,
               (SELECT SUM(total_amount) FROM orders WHERE session_id = ts.id) as total_amount
        FROM table_sessions ts
        LEFT JOIN restaurant_tables t ON ts.table_id = t.id
        $whereClause
        ORDER BY ts.opened_at DESC
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format session data
    foreach ($sessions as &$session) {
        // If session_number missing, generate one
        if (empty($session['session_number'])) {
            $session['session_number'] = 'S' . str_pad($session['id'], 4, '0', STR_PAD_LEFT);
        }
        $session['customer_count'] = $session['customer_count'] ?? 1;
        $session['waiter_name'] = 'N/A'; // or fetch from first order's waiter? Optional
        $session['total_amount'] = $session['total_amount'] ?? 0;
    }
    
    echo json_encode(['sessions' => $sessions]);
    
} catch (Exception $e) {
    error_log("get_sessions.php error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>