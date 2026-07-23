<?php
// Quick check to verify admin exists
require_once 'config/config.php';

echo "<h1>Admin Check</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, full_name, role FROM users WHERE role = 'admin'";
    $stmt = $db->query($query);
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        echo "<p style='color: green;'>✅ Admin users found:</p>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin['id']} - Username: {$admin['username']} - Name: {$admin['full_name']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ No admin user found! Please run setup_first_admin.php</p>";
        echo "<p><a href='setup_first_admin.php'>Run Setup →</a></p>";
    }
    
    echo "<p><a href='login.php'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>