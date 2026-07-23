<?php
require_once 'config/config.php';

echo "<h2>Create Test User</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (username, password, full_name, role) 
                  VALUES (:username, :password, :full_name, :role)";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password,
            ':full_name' => $full_name,
            ':role' => $role
        ]);
        
        if ($result) {
            echo "<p style='color:green'>User created successfully!</p>";
            echo "<p>Username: " . $username . "<br>";
            echo "Password: " . $password . "<br>";
            echo "Hash: " . $hashed_password . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create User</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        form { max-width: 400px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Create New User</h2>
    <form method="POST">
        <div>
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Password:</label>
            <input type="text" name="password" required>
        </div>
        <div>
            <label>Full Name:</label>
            <input type="text" name="full_name" required>
        </div>
        <div>
            <label>Role:</label>
            <select name="role">
                <option value="admin">Admin</option>
                <option value="waiter">Waiter</option>
                <option value="kitchen">Kitchen</option>
            </select>
        </div>
        <button type="submit">Create User</button>
    </form>
</body>
</html>