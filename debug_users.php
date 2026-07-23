<?php
require_once 'config/config.php';

echo "<h2>Database User Debug</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if users table exists and has records
    $query = "SHOW TABLES LIKE 'users'";
    $stmt = $db->query($query);
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<b>Users table exists:</b> " . ($tableExists ? 'Yes' : 'No') . "<br><br>";
    
    if ($tableExists) {
        // Get all users
        $query = "SELECT id, username, full_name, role, is_active, created_at FROM users";
        $stmt = $db->query($query);
        $users = $stmt->fetchAll();
        
        echo "<b>Users in database:</b><br>";
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th><th>Created</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['username'] . "</td>";
                echo "<td>" . $user['full_name'] . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $user['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:red'>No users found in the database!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>