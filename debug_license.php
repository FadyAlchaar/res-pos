<?php
require_once 'config/config.php';  // This includes database config
require_once 'config/hardware_id.php';

echo "<h1>🔍 License Debugger</h1>";

$hardware = HardwareID::generateHardwareId();
$hardware_id = $hardware['hardware_id'];

echo "<h2>1. Current Hardware Information:</h2>";
echo "<pre>";
echo "CPU Serial: " . $hardware['components']['cpu'] . "\n";
echo "Motherboard: " . $hardware['components']['motherboard'] . "\n";
echo "Hardware ID: " . $hardware_id . "\n";
echo "</pre>";

echo "<h2>2. Generated License Key for THIS Hardware:</h2>";
$expected_key = HardwareID::generateLicenseKey();
echo "<div style='background:#2c3e50; color:#ecf0f1; padding:15px; border-radius:8px; font-family:monospace; word-break:break-all;'>";
echo $expected_key;
echo "</div>";

echo "<h2>3. Test Your License Key:</h2>";
echo "<form method='POST'>";
echo "<input type='text' name='test_key' style='width:100%; padding:10px; margin:10px 0; font-family:monospace;' placeholder='Paste your license key here'>";
echo "<button type='submit'>Test License Key</button>";
echo "</form>";

if (isset($_POST['test_key'])) {
    $test_key = trim($_POST['test_key']);
    echo "<h3>Test Result:</h3>";
    
    if ($test_key === $expected_key) {
        echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:8px;'>✅ VALID! This license key matches this hardware.</div>";
    } else {
        echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>❌ INVALID!<br><br>";
        echo "<strong>Your key:</strong> " . $test_key . "<br>";
        echo "<strong>Expected key:</strong> " . $expected_key . "<br><br>";
        echo "They don't match. Make sure you copied the complete key correctly.</div>";
    }
}

echo "<h2>4. Check Stored License (if any):</h2>";
try {
    // Now Database class should be available from config.php
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT setting_value FROM restaurant_settings WHERE setting_key = 'hardware_id'";
    $stmt = $db->query($query);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<div style='background:#f8f9fa; padding:15px; border-radius:8px;'>";
        echo "<strong>Stored Hardware ID:</strong><br>";
        echo "<code style='word-break:break-all;'>" . $result['setting_value'] . "</code><br><br>";
        
        $is_valid = HardwareID::verify($result['setting_value']);
        if ($is_valid) {
            echo "<span style='color:green;'>✅ Stored ID matches current hardware</span>";
        } else {
            echo "<span style='color:red;'>❌ Stored ID does NOT match current hardware</span>";
        }
        echo "</div>";
    } else {
        echo "<div>No license stored yet.</div>";
    }
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>";
    echo "Database error: " . $e->getMessage() . "<br>";
    echo "This is normal if the database tables don't exist yet.";
    echo "</div>";
}
?>