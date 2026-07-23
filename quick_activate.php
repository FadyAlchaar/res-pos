<?php
// QUICK ACTIVATION - Use this only for testing
// Remove this file after activation!

// Load only what we need
require_once 'config/hardware_id.php';

echo "<h1>🔑 Quick Activation Helper</h1>";

$hardware = HardwareID::generateHardwareId();
$expected_key = HardwareID::generateLicenseKey();

echo "<div style='background:#f8f9fa; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>Hardware Information:</h3>";
echo "<p><strong>CPU:</strong> " . $hardware['components']['cpu'] . "</p>";
echo "<p><strong>Motherboard:</strong> " . $hardware['components']['motherboard'] . "</p>";
echo "<p><strong>Hardware ID:</strong> <code style='word-break:break-all;'>" . $hardware['hardware_id'] . "</code></p>";
echo "</div>";

echo "<div style='background:#2c3e50; color:#ecf0f1; padding:20px; border-radius:10px; margin:20px 0;'>";
echo "<h3>🔐 Your License Key:</h3>";
echo "<code style='word-break:break-all; font-size:14px;'>" . $expected_key . "</code>";
echo "</div>";

if (isset($_POST['activate'])) {
    try {
        // Now load database
        require_once 'config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if table exists, if not create settings table
        $db->exec("CREATE TABLE IF NOT EXISTS restaurant_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $stored_id = $hardware['hardware_id'];
        
        // First check if record exists
        $check = $db->prepare("SELECT COUNT(*) FROM restaurant_settings WHERE setting_key = 'hardware_id'");
        $check->execute();
        $exists = $check->fetchColumn() > 0;
        
        if ($exists) {
            // Update existing
            $query = "UPDATE restaurant_settings SET setting_value = :value WHERE setting_key = 'hardware_id'";
            $stmt = $db->prepare($query);
            $stmt->execute([':value' => $stored_id]);
        } else {
            // Insert new
            $query = "INSERT INTO restaurant_settings (setting_key, setting_value) VALUES ('hardware_id', :value)";
            $stmt = $db->prepare($query);
            $stmt->execute([':value' => $stored_id]);
        }
        
        echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-top:20px;'>";
        echo "✅ License activated successfully!<br>";
        echo "<a href='login.php' style='color:#155724; font-weight:bold;'>Click here to login →</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:10px; margin-top:20px;'>";
        echo "❌ Error: " . $e->getMessage();
        echo "</div>";
    }
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='activate' style='background:#27ae60; color:white; padding:12px 30px; border:none; border-radius:5px; cursor:pointer; font-size:16px;'>🔑 Activate License</button>";
    echo "</form>";
}
?>