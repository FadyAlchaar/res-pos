<?php
require_once 'config/config.php';

echo "<h1>Simple Admin Creator</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create admin directly
    $username = 'admin';
    $password = 'admin123';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if exists
    $check = $db->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    
    if ($check > 0) {
        // Update existing
        $db->exec("UPDATE users SET password = '$hashed', full_name = 'System Administrator', role = 'admin', is_active = 1 WHERE username = 'admin'");
        echo "<p style='color: green;'>✅ Admin user UPDATED!</p>";
    } else {
        // Create new
        $db->exec("INSERT INTO users (username, password, full_name, role, is_active) 
                   VALUES ('admin', '$hashed', 'System Administrator', 'admin', 1)");
        echo "<p style='color: green;'>✅ Admin user CREATED!</p>";
    }
    
    echo "<p>Username: admin</p>";
    echo "<p>Password: admin123</p>";
    echo "<p><a href='login.php'>Go to Login →</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>