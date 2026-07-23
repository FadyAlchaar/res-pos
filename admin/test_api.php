<?php
require_once '../config/config.php';

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}

echo "<h1>Testing Orders API</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test simple query
    $query = "SELECT o.*, u.full_name as waiter_name
              FROM orders o
              LEFT JOIN users u ON o.waiter_id = u.id
              ORDER BY o.created_at DESC";
    
    $stmt = $db->query($query);
    $orders = $stmt->fetchAll();
    
    echo "<h2>Database Query Results:</h2>";
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
    
    echo "<h2>JSON Response (what the API would return):</h2>";
    echo "<pre>";
    echo json_encode(['orders' => $orders, 'total_orders' => count($orders)], JSON_PRETTY_PRINT);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>