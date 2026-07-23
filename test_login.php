<?php
require_once 'config/config.php';

echo "<h1>Test Login System</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p>User found: {$user['username']}</p>";
            echo "<p>Stored hash: {$user['password']}</p>";
            
            if (password_verify($password, $user['password'])) {
                echo "<p style='color: green;'>✅ Password verified! Login successful!</p>";
                // You can set session here if needed
            } else {
                echo "<p style='color: red;'>❌ Password does not match</p>";
                
                // Generate a new hash for testing
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                echo "<p>New hash for '{$password}' would be: <code>{$new_hash}</code></p>";
                echo "<p>Use this SQL to update:</p>";
                echo "<code>UPDATE users SET password = '{$new_hash}' WHERE username = '{$username}';</code>";
            }
        } else {
            echo "<p style='color: red;'>❌ User not found: {$username}</p>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Test Login</button>
</form>