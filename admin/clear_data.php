<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

echo "<h1>Clear Transactional Data</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Show counts before
    echo "<h2>Before Clear:</h2>";
    $orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $order_items = $db->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
    $print_jobs = $db->query("SELECT COUNT(*) FROM print_jobs")->fetchColumn();
    $printer_logs = $db->query("SELECT COUNT(*) FROM printer_logs")->fetchColumn();
    
    echo "<ul>";
    echo "<li>Orders: $orders</li>";
    echo "<li>Order Items: $order_items</li>";
    echo "<li>Print Jobs: $print_jobs</li>";
    echo "<li>Printer Logs: $printer_logs</li>";
    echo "</ul>";
    
    // Start transaction
    $db->beginTransaction();
    
    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Delete data
    $db->exec("DELETE FROM print_jobs");
    $db->exec("DELETE FROM order_items");
    $db->exec("DELETE FROM orders");
    $db->exec("DELETE FROM printer_logs");
    
    // Reset table status
    $db->exec("UPDATE restaurant_tables SET status = 'available'");
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Commit transaction
    $db->commit();
    
    // Show counts after
    echo "<h2>After Clear:</h2>";
    $orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $order_items = $db->query("SELECT COUNT(*) FROM order_items")->fetchColumn();
    $print_jobs = $db->query("SELECT COUNT(*) FROM print_jobs")->fetchColumn();
    $printer_logs = $db->query("SELECT COUNT(*) FROM printer_logs")->fetchColumn();
    
    echo "<ul>";
    echo "<li>Orders: $orders</li>";
    echo "<li>Order Items: $order_items</li>";
    echo "<li>Print Jobs: $print_jobs</li>";
    echo "<li>Printer Logs: $printer_logs</li>";
    echo "</ul>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ All transactional data cleared successfully!</p>";
    echo "<p><a href='settings.php'>← Back to Settings</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    if (isset($db)) {
        $db->rollBack();
    }
}
?>