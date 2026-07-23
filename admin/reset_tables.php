<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

echo "<h1>🔄 Complete Reset</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop and recreate restaurant_settings table
    $db->exec("DROP TABLE IF EXISTS restaurant_settings");
    $db->exec("
        CREATE TABLE restaurant_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ Recreated restaurant_settings table</p>";
    
    // Insert settings
    $db->exec("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES 
        ('total_tables', '60'),
        ('table_prefix', 'Table '),
        ('restaurant_name', 'My Restaurant')");
    echo "<p>✓ Inserted settings</p>";
    
    // Clear restaurant_tables but keep structure
    $db->exec("DELETE FROM restaurant_tables");
    echo "<p>✓ Cleared restaurant_tables</p>";
    
    // Insert new tables
    for ($i = 1; $i <= 60; $i++) {
        $db->exec("INSERT INTO restaurant_tables (table_number, table_name, capacity, status, sort_order, is_active) 
                   VALUES ($i, 'Table $i', 4, 'available', $i, 1)");
    }
    echo "<p>✓ Inserted 60 new tables</p>";
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ Reset complete! 60 tables created.</p>";
    
    echo "<p><a href='settings.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;'>Go to Settings</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    
    // Re-enable foreign key checks
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ex) {
        // Ignore
    }
}
?>