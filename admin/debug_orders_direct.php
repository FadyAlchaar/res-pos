<?php
// TEMPORARY DEBUG SCRIPT - REMOVE AFTER TESTING
require_once '../config/config.php';

// Manually set session for debugging
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h1>Direct Order Debug</h1>";
    
    // Check total orders
    $total = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "<p>Total orders in database: <strong>$total</strong></p>";
    
    // Get all orders
    $orders = $db->query("SELECT o.*, u.full_name as waiter_name,
                          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                          FROM orders o
                          LEFT JOIN users u ON o.waiter_id = u.id
                          ORDER BY o.created_at DESC
                          LIMIT 20");
    
    $ordersList = $orders->fetchAll();
    
    if (count($ordersList) > 0) {
        echo "<h2>Recent Orders:</h2>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Order #</th><th>Table</th><th>Waiter</th><th>Status</th><th>Items</th><th>Total</th><th>Created</th></tr>";
        foreach ($ordersList as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['table_number']}</td>";
            echo "<td>{$order['waiter_name']}</td>";
            echo "<td>{$order['status']}</td>";
            echo "<td>{$order['item_count']}</td>";
            echo "<td>\${$order['total_amount']}</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>⚠️ No orders found in database!</p>";
        echo "<p>Try placing a test order from the waiter page first.</p>";
    }
    
    // Check if there are any orders at all
    $any_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    if ($any_orders == 0) {
        echo "<h3>No orders found. Let's create a test order...</h3>";
        
        // Insert a test order
        $db->exec("INSERT INTO orders (order_number, waiter_id, table_number, status, total_amount, created_at) 
                   VALUES ('TEST-001', 1, 'Test Table', 'pending', 25.99, NOW())");
        $order_id = $db->lastInsertId();
        
        $db->exec("INSERT INTO order_items (order_id, menu_item_id, kitchen_id, quantity, unit_price, subtotal) 
                   VALUES ($order_id, 1, 1, 1, 25.99, 25.99)");
        
        echo "<p style='color:green;'>✅ Test order created! Order ID: $order_id</p>";
        echo "<p><a href='orders.php'>Go to Orders Page →</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>