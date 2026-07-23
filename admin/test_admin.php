<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

echo "<h1>✅ Admin System Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test 1: Check if all required files exist
    echo "<h2>1. Checking API Files:</h2>";
    $api_files = [
        'get_kitchens.php',
        'get_kitchen.php',
        'save_kitchen.php',
        'delete_kitchen.php',
        'get_categories.php',
        'get_category.php',
        'save_category.php',
        'delete_category.php',
        'get_menu_items_admin.php',
        'get_menu_item.php',
        'save_menu_item.php',
        'delete_menu_item.php',
        'get_printer_status.php',
        'test_printer.php',
        'test_all_printers.php'
    ];
    
    echo "<ul>";
    foreach ($api_files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo "<li style='color: green;'>✅ $file - OK</li>";
        } else {
            echo "<li style='color: red;'>❌ $file - MISSING</li>";
        }
    }
    echo "</ul>";
    
    // Test 2: Check database tables
    echo "<h2>2. Checking Database Tables:</h2>";
    $tables = ['kitchens', 'categories', 'menu_items', 'orders', 'order_items', 'print_jobs', 'users'];
    
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color: green;'>✅ $table - EXISTS</li>";
        } else {
            echo "<li style='color: red;'>❌ $table - MISSING</li>";
        }
    }
    echo "</ul>";
    
    // Test 3: Count records
    echo "<h2>3. Record Counts:</h2>";
    echo "<ul>";
    $counts = [
        'kitchens' => 'Kitchens',
        'categories' => 'Categories',
        'menu_items' => 'Menu Items',
        'users' => 'Users'
    ];
    
    foreach ($counts as $table => $label) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<li>$label: <strong>$count</strong></li>";
    }
    echo "</ul>";
    
    echo "<h2 style='color: green; margin-top: 30px;'>✅ Test Complete!</h2>";
    echo "<p><a href='dashboard.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
}
?>