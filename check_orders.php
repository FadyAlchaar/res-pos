<?php
require_once 'config/config.php';

echo "<h1>Order Check Debug</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check total orders
    $total = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "<p>Total orders in database: <strong>$total</strong></p>";
    
    // Show recent orders
    $orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
    $ordersList = $orders->fetchAll();
    
    if (count($ordersList) > 0) {
        echo "<h2>Recent Orders:</h2>";
        echo "<table border='1' cellpadding='8'>";
        echo "运转<th>ID</th><th>Order #</th><th>Table</th><th>Status</th><th>Created At</th><th>Total</th></tr>";
        foreach ($ordersList as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['table_number']}</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "<td>\${$order['total_amount']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>No orders found in database!</p>";
    }
    
    // Check order items
    $items = $db->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
    echo "<p>Order items: <strong>$items</strong></p>";
    
    // Check print jobs
    $jobs = $db->query("SELECT COUNT(*) FROM print_jobs")->fetchColumn();
    echo "<p>Print jobs: <strong>$jobs</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>