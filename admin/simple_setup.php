<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Simple Table Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Disable foreign key checks temporarily
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // First, let's check if tables exist
    echo "<p>Checking database structure...</p>";
    
    // Create restaurant_settings table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS restaurant_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✓ restaurant_settings table ready</p>";
    
    // Clear settings table
    $db->exec("DELETE FROM restaurant_settings");
    echo "<p>✓ Cleared settings</p>";
    
    // Insert settings directly with simple INSERT
    $insert = $db->prepare("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES (?, ?)");
    $insert->execute(['total_tables', '60']);
    $insert->execute(['table_prefix', 'Table ']);
    $insert->execute(['restaurant_name', 'My Restaurant']);
    echo "<p>✓ Settings inserted</p>";
    
    // Now handle tables
    // First, mark all existing tables as inactive
    $db->exec("UPDATE restaurant_tables SET is_active = 0");
    echo "<p>✓ Marked existing tables inactive</p>";
    
    // Get existing table numbers
    $existing = [];
    $result = $db->query("SELECT table_number FROM restaurant_tables");
    while ($row = $result->fetch()) {
        $existing[] = $row['table_number'];
    }
    
    // Generate 60 tables
    $inserted = 0;
    $updated = 0;
    
    for ($i = 1; $i <= 60; $i++) {
        $table_name = "Table " . $i;
        
        if (in_array($i, $existing)) {
            // Update existing
            $stmt = $db->prepare("UPDATE restaurant_tables SET is_active = 1, table_name = ?, status = 'available' WHERE table_number = ?");
            $stmt->execute([$table_name, $i]);
            $updated++;
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO restaurant_tables (table_number, table_name, capacity, status, sort_order, is_active) VALUES (?, ?, 4, 'available', ?, 1)");
            $stmt->execute([$i, $table_name, $i]);
            $inserted++;
        }
    }
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<p>✓ Tables generated: $inserted new, $updated reactivated</p>";
    
    // Show results
    $total = $db->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active = 1")->fetchColumn();
    echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✅ Success! $total active tables ready.</p>";
    
    // Preview
    echo "<h3>First 10 tables:</h3>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 5px;'>";
    $preview = $db->query("SELECT table_name FROM restaurant_tables WHERE is_active = 1 ORDER BY table_number LIMIT 10");
    while ($row = $preview->fetch()) {
        echo "<span style='background: #3498db; color: white; padding: 5px 10px; border-radius: 15px;'>" . $row['table_name'] . "</span>";
    }
    echo "</div>";
    
    echo "<p><a href='settings.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;'>Go to Settings</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " on line " . $e->getLine() . "</p>";
    
    // Re-enable foreign key checks even if there's an error
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ex) {
        // Ignore
    }
}
?>