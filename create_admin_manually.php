<?php
// EMERGENCY: Direct admin creation (use if everything else fails)
require_once 'config/config.php';

echo "<h1>🚨 Emergency Admin Creator</h1>";
echo "<p style='color: red;'><strong>WARNING:</strong> This script creates an admin user directly.</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    
    if (empty($username) || empty($password)) {
        echo "<p style='color: red;'>Username and password required!</p>";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if exists
            $check = $db->prepare("SELECT id FROM users WHERE username = :username");
            $check->execute([':username' => $username]);
            
            if ($check->fetch()) {
                // Update existing
                $query = "UPDATE users SET password = :password, full_name = :full_name, role = 'admin', is_active = 1 WHERE username = :username";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':password' => $hashed,
                    ':full_name' => $full_name,
                    ':username' => $username
                ]);
                echo "<p style='color: green;'>✅ User UPDATED as admin!</p>";
            } else {
                // Create new
                $query = "INSERT INTO users (username, password, full_name, role, is_active) VALUES (:username, :password, :full_name, 'admin', 1)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hashed,
                    ':full_name' => $full_name
                ]);
                echo "<p style='color: green;'>✅ Admin user CREATED!</p>";
            }
            
            echo "<p>Username: <strong>$username</strong></p>";
            echo "<p>Password: <strong>$password</strong></p>";
            echo "<p><a href='login.php'>Go to Login →</a></p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<form method="POST">
    <div>
        <label>Username:</label>
        <input type="text" name="username" value="admin" required>
    </div>
    <div>
        <label>Password:</label>
        <input type="text" name="password" value="admin123" required>
    </div>
    <div>
        <label>Full Name:</label>
        <input type="text" name="full_name" value="System Administrator">
    </div>
    <button type="submit">Create/Update Admin</button>
</form>