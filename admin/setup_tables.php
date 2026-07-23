<?php
require_once '../config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Quick Table Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Default settings
    $total_tables = 60;
    $table_prefix = 'Table ';
    
    echo "<p>Setting up $total_tables tables with prefix '$table_prefix'...</p>";
    
    // Start transaction
    $db->beginTransaction();
    
    // First, check if restaurant_settings table exists, if not create it
    $db->exec("
        CREATE TABLE IF NOT EXISTS restaurant_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✓ Settings table checked/created</p>";
    
    // Mark all existing tables as inactive (but don't delete them)
    $db->exec("UPDATE restaurant_tables SET is_active = 0");
    echo "<p>✓ Marked existing tables as inactive</p>";
    
    // Get existing table numbers
    $existingStmt = $db->query("SELECT table_number FROM restaurant_tables");
    $existingTables = [];
    while ($row = $existingStmt->fetch()) {
        $existingTables[] = $row['table_number'];
    }
    
    // Generate new tables
    $inserted = 0;
    $updated = 0;
    
    for ($i = 1; $i <= $total_tables; $i++) {
        $table_name = $table_prefix . $i;
        
        // Check if table with this number already exists
        if (in_array($i, $existingTables)) {
            // Update existing table
            $stmt = $db->prepare("UPDATE restaurant_tables SET is_active = 1, table_name = ?, status = 'available' WHERE table_number = ?");
            $stmt->execute([$table_name, $i]);
            $updated++;
        } else {
            // Insert new table
            $stmt = $db->prepare("INSERT INTO restaurant_tables (table_number, table_name, capacity, status, sort_order, is_active) VALUES (?, ?, 4, 'available', ?, 1)");
            $stmt->execute([$i, $table_name, $i]);
            $inserted++;
        }
    }
    
    echo "<p>✓ Reactivated: $updated tables</p>";
    echo "<p>✓ Inserted: $inserted new tables</p>";
    
    // Save settings using simple INSERT with ON DUPLICATE KEY UPDATE
    $settings = [
        ['total_tables', $total_tables],
        ['table_prefix', $table_prefix],
        ['restaurant_name', 'My Restaurant']
    ];
    
    foreach ($settings as $setting) {
        $key = $setting[0];
        $value = $setting[1];
        
        // Check if setting exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM restaurant_settings WHERE setting_key = ?");
        $checkStmt->execute([$key]);
        $exists = $checkStmt->fetchColumn() > 0;
        
        if ($exists) {
            $stmt = $db->prepare("UPDATE restaurant_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $db->prepare("INSERT INTO restaurant_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }
    
    echo "<p>✓ Settings saved!</p>";
    
    // Commit transaction
    $db->commit();
    
    // Get counts
    $activeCount = $db->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active = 1")->fetchColumn();
    $inactiveCount = $db->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active = 0")->fetchColumn();
    $orderCount = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    
    echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>✓ Setup completed successfully!</p>";
    echo "<p>Active tables now: <strong>$activeCount</strong></p>";
    
    // Preview
    echo "<h3>Preview of first 10 tables:</h3>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0;'>";
    
    $previewStmt = $db->query("SELECT table_name FROM restaurant_tables WHERE is_active = 1 ORDER BY table_number LIMIT 10");
    while ($table = $previewStmt->fetch()) {
        echo "<span style='background: #3498db; color: white; padding: 8px 15px; border-radius: 25px;'>" . htmlspecialchars($table['table_name']) . "</span>";
    }
    
    if ($activeCount > 10) {
        echo "<span style='background: #95a5a6; color: white; padding: 8px 15px; border-radius: 25px;'>... and " . ($activeCount - 10) . " more</span>";
    }
    echo "</div>";
    
    // Statistics
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>📊 Statistics:</h4>";
    echo "<p>📝 Total orders in history: <strong>$orderCount</strong> (preserved)</p>";
    echo "<p>🗄️ Inactive tables (with order history): <strong>$inactiveCount</strong></p>";
    echo "<p>✅ Active tables (ready for use): <strong>$activeCount</strong></p>";
    echo "</div>";
    
    // Navigation buttons
    echo "<div style='margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap;'>";
    echo "<a href='settings.php' style='background: #27ae60; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px;'>⚙️ Go to Settings</a>";
    echo "<a href='../waiter/orders_responsive.php' target='_blank' style='background: #3498db; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px;'>👨‍🍳 Preview Waiter View</a>";
    echo "<a href='dashboard.php' style='background: #95a5a6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px;'>📊 Back to Dashboard</a>";
    echo "</div>";
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2 style='color: #721c24;'>❌ Error Occurred</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
    echo "<p><a href='settings.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Settings Page Instead</a></p>";
}
?>