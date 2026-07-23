<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

echo "<h1>💀 COMPLETE DATABASE RESET</h1>";
echo "<p style='color: red;'><strong>WARNING:</strong> This will delete EVERYTHING! All data will be lost.</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'DELETE_EVERYTHING') {
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        echo "<h2>Resetting database...</h2>";
        
        // Disable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<ul>";
        foreach ($tables as $table) {
            $db->exec("TRUNCATE TABLE $table");
            echo "<li>✅ Cleared: $table</li>";
        }
        echo "</ul>";
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "<p style='color: green; font-weight: bold;'>✅ COMPLETE RESET FINISHED! All tables are empty.</p>";
        echo "<p><a href='../setup_first_admin.php'>Run First Time Setup →</a></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    ?>
    <div style="background: #fee; padding: 20px; border: 1px solid red; border-radius: 5px; margin: 20px 0;">
        <h3 style="color: red;">⚠️ DANGER ZONE ⚠️</h3>
        <p>This will delete ALL data from ALL tables:</p>
        <ul>
            <li>Kitchens</li>
            <li>Categories</li>
            <li>Menu Items</li>
            <li>Orders</li>
            <li>Order Items</li>
            <li>Print Jobs</li>
            <li>Restaurant Tables</li>
            <li>Users</li>
            <li>Settings</li>
            <li>Printer Logs</li>
        </ul>
        <p><strong>Type "DELETE_EVERYTHING" to confirm:</strong></p>
        <form method="POST">
            <input type="text" name="confirm" placeholder="DELETE_EVERYTHING" style="padding: 10px; width: 300px;">
            <button type="submit" style="background: red; color: white; padding: 10px 20px; margin-left: 10px;">💀 DELETE EVERYTHING</button>
        </form>
    </div>
    <?php
}
?>