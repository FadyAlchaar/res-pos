<?php
require_once 'config/config.php';
require_once 'config/hardware_id.php';

echo "<h1>License Status Check</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT setting_value FROM restaurant_settings WHERE setting_key = 'hardware_id'";
    $stmt = $db->query($query);
    $result = $stmt->fetch();
    
    if (!$result) {
        echo "<p style='color: orange;'>⚠️ No license installed</p>";
        echo "<p><a href='activate.php'>Go to Activation Page →</a></p>";
    } else {
        $is_valid = HardwareID::verify($result['setting_value']);
        if ($is_valid) {
            echo "<p style='color: green;'>✅ License is VALID</p>";
            echo "<p><a href='login.php'>Go to Login →</a></p>";
        } else {
            echo "<p style='color: red;'>❌ License is INVALID (hardware mismatch)</p>";
            echo "<p><a href='activate.php'>Reactivate License →</a></p>";
        }
    }
    
    $hardware = HardwareID::generateHardwareId();
    echo "<hr>";
    echo "<p><strong>Current Hardware ID:</strong> <code>" . $hardware['hardware_id'] . "</code></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='activate.php'>Go to Activation Page →</a></p>";
}
?>